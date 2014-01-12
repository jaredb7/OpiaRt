<?php
App::uses('OpiaRtAppModel', 'OpiaRt.Model');
App::uses('HttpSocket', 'Network/Http');
App::uses('HttpResponse', 'Network/Http');
App::uses('Folder', 'Utility');

//include_once("lib/DrSlump/Protobuf.php");
//include_once("library/DrSlump/Protobuf/Message.php");
//include_once("library/DrSlump/Protobuf/Registry.php");
//include_once("library/DrSlump/Protobuf/Descriptor.php");
//include_once("library/DrSlump/Protobuf/Field.php");
include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'DrSlump' . DS . 'Protobuf.php');
include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'DrSlump' . DS . 'Protobuf' . DS . 'Message.php');
include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'DrSlump' . DS . 'Protobuf' . DS . 'Registry.php');
include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'DrSlump' . DS . 'Protobuf' . DS . 'Descriptor.php');
include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'DrSlump' . DS . 'Protobuf' . DS . 'Field.php');
include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'DrSlump' . DS . 'Protobuf' . DS . 'Unknown.php');

//include_once("gtfs-realtime.php");
include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'gtfs-realtime.php');

//include_once("library/DrSlump/Protobuf/CodecInterface.php");
//include_once("library/DrSlump/Protobuf/Codec/PhpArray.php");
//Codecs for data conversion
include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'DrSlump' . DS . 'Protobuf' . DS . 'CodecInterface.php');

//include_once("library/DrSlump/Protobuf/Codec/Binary.php");
//include_once("library/DrSlump/Protobuf/Codec/Binary/Reader.php");
include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'DrSlump' . DS . 'Protobuf' . DS . 'Codec' . DS . 'Binary.php');
include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'DrSlump' . DS . 'Protobuf' . DS . 'Codec' . DS . 'Binary' . DS . 'Reader.php');
include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'DrSlump' . DS . 'Protobuf' . DS . 'Codec' . DS . 'Binary' . DS . 'Unknown.php');

/**
 *
 * @property HttpSocket $http_socket
 * @property HttpResponse $http_result
 */
class GtfsRtLoader extends OpiaRtAppModel
{
    const USER_AGENT = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:13.0) Gecko/20100101 Firefox/13.0.1";

    //Info about the zip file

    protected $gtfs_rt_zip_folder_name = null; //name of the folder the zip will be extracted to
    protected $gtfs_rt_feed_local_path = null; //full path to the zip file

    protected $feed_name = "feed";

    //URL to where the Translink RT feed sits
    protected $gtfs_zip_base_url = "http://gtfsrt.api.translink.com.au/";
    //URL of the zipfile we're processing
    protected $gtfs_rt_feed_url = null;

    protected $output_type = null;

    //HttpSocket
    protected $http_socket;
    protected $http_result;

    /**
     * Class constructor
     * @param string $url
     * @param null|string $output_type The type of output we want, json or phparray. Defaults to phparray
     */
    function __construct($url, $output_type)
    {
        $this->gtfs_rt_feed_url = $url;
        $this->output_type = $output_type;

        CakeLog::info('STARTUP :: -- URL is: ' . $this->gtfs_rt_feed_url, 'opia_rt');

        //Init our HttpSocket and Resposne objects
        $this->http_socket = new HttpSocket();
        $this->http_result = new HttpResponse();
    }

    /**
     * Loads and processes the feed file
     */
    function load()
    {
        //Retrieve the feed
        $this->get_feed();

        //process each of the csv files
        return $this->process_feed_file();
    }

    /**
     * Downloads the GTFS RT feed to a local file.
     */
    function get_feed()
    {
        $timer_start = $timer_end = 0;;

        if ($this->gtfs_rt_feed_url != null) {
            //Do the request
            $request = $this->prepare_request();
            //Build the localpath
            $this->gtfs_rt_feed_local_path = Configure::read("OpiaRt.storage_path") . DS . $this->feed_name;

            CakeLog::info('get_feed :: -- Request: ' . json_encode($request), 'opia_rt');
            CakeLog::info('get_feed :: -- Local path: ' . $this->gtfs_rt_feed_local_path, 'opia_rt');

            //File handle
            $f = fopen($this->gtfs_rt_feed_local_path, 'w');

            //Set the content resource for httpsocket
            $this->http_socket->setContentResource($f);
            //And do the request
            $this->http_socket->request($request);

            //close file
            fclose($f);

            $timer_end = microtime(true);
        }

        CakeLog::info('get_feed :: -- Finished downloading :: Took:' . round($timer_end - $timer_start, 3) . ' ms.', 'opia_rt');

        //Reset the http_socket
        $this->http_socket->reset(true);
    }

    /**
     * Responsible for processing the CSV file and saving data to the database
     */
    function process_feed_file()
    {
        //If the the local feed path is set
        if ($this->gtfs_rt_feed_local_path != null) {
            //read each file
            $local_feed_path = Configure::read("OpiaRt.storage_path") . DS . $this->feed_name;
            CakeLog::info('process_feed :: -- Begin Processing :: ' . $this->feed_name, 'opia_rt');
            CakeLog::info('process_feed :: -- ' . $local_feed_path, 'opia_rt');

            if (file_exists($local_feed_path)) {
                $fm = DrSlump\Protobuf::decode('transit_realtime\FeedMessage', file_get_contents($local_feed_path));

                if ($this->output_type == null || $this->output_type == "phparray") {
                    include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'D rSlump' . DS . 'Protobuf' . DS . 'Codec' . DS . 'PhpArray.php');

                    $codec = new DrSlump\Protobuf\Codec\PhpArray();

                    return ($codec->encode($fm));
                } elseif ($this->output_type == "json") {
                    //We requre PhpArray to do the heavy lifting and then Json just serializes the data
                    include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'DrSlump' . DS . 'Protobuf' . DS . 'Codec' . DS . 'PhpArray.php');
                    include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'DrSlump' . DS . 'Protobuf' . DS . 'Codec' . DS . 'Json.php');

                    $codec = new DrSlump\Protobuf\Codec\Json();

                    return ($codec->encode($fm));
                } elseif ($this->output_type == "json_indexed") {
                    //We requre PhpArray to do the heavy lifting and then Json just serializes the data
                    include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'DrSlump' . DS . 'Protobuf' . DS . 'Codec' . DS . 'PhpArray.php');
                    include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'DrSlump' . DS . 'Protobuf' . DS . 'Codec' . DS . 'Json.php');
                    include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'DrSlump' . DS . 'Protobuf' . DS . 'Codec' . DS . 'JsonIndexed.php');

                    $codec = new DrSlump\Protobuf\Codec\JsonIndexed();

                    return ($codec->encode($fm));
                }
            }
        }
        return null;
    }

    /**
     * Used to generate the request array required for HttpSocket requests
     * extracts the host and scheme from $this->url and passes that back into the array as options
     *
     * @return array full request array in format required by HttpSocket
     */
    public function prepare_request()
    {
        //Parse the url so we can use the individual sections
        //to form the request array for HttpSocket
        $url_parse = parse_url($this->gtfs_rt_feed_url);

        //request array for HttpSocket
        $request = array(
            'method' => 'GET',
            'uri' => array(
                'schema' => $url_parse['scheme'],
                'host' => $url_parse['host'],
                'path' => $url_parse['path'],
            ),
            'header' => array(
//                'Host' => $url_parse['host'],
//                'User-Agent' => self::USER_AGENT,
//                'Referer' => 'http://translink.com.au/news-and-updates/open-data',
            ),
//            'request_info' => array(
//                'timetable' => 'base',
//                'type' => $this->t_type,
//                'request_date' => $this->crawl_date
//            ),
            'redirect' => true,
        );

        //Return the request array
        return $request;
    }
}