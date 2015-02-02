<?php
App::uses('OpiaRtAppModel', 'OpiaRt.Model');
//App::uses('HttpSocket', 'Network/Http');
//App::uses('HttpResponse', 'Network/Http');
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
    public static $POST = "POST";
    public static $GET = "GET";
    public static $PUT = "PUT";
    public static $DELETE = "DELETE";

    //Info about the zip file
    protected $gtfs_rt_zip_folder_name = null; //name of the folder the zip will be extracted to
    protected $gtfs_rt_feed_local_path = null; //full path to the zip file

    //Name of the feed
    protected $feed_name = "feed";

    //URL to where the Translink RT feed sits
    protected $gtfs_rt_base_url = "https://gtfsrt.api.translink.com.au/";
    //URL of the zipfile we're processing
    protected $gtfs_rt_feed_url = null;

    protected $output_type = null;

    //HttpSocket
    protected $http_socket;
    protected $http_result;

    /**
     * Some default options for curl
     */
    public static $DEFAULT_CURL_OPTS = array(
        CURLOPT_SSLVERSION => 1,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 10, // maximum number of seconds to allow cURL functions to execute
        CURLOPT_USERAGENT => 'CakePHP OPIA RT Proxy',
//        CURLOPT_HTTPHEADER => array("Content-Type: application/json; charset=utf-8", "Accept:application/json, text/javascript, */*; q=0.01"),
        CURLOPT_HTTPHEADER => array(),
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_CIPHER_LIST => 'TLSv1',
    );
    const HEADER_SEPARATOR = ';';


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
//        $this->http_socket = new HttpSocket();
//        $this->http_result = new HttpResponse();
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
            $timer_start = microtime(true);

            //Do the request
            $request = $this->prepare_request();
            //Build the localpath
            $this->gtfs_rt_feed_local_path = Configure::read("OpiaRt.storage_path") . DS . $this->feed_name;

            CakeLog::info('get_feed :: -- Request: ' . json_encode($request), 'opia_rt');
            CakeLog::info('get_feed :: -- Local path: ' . $this->gtfs_rt_feed_local_path, 'opia_rt');


            //Perform cURL request
            $d = $this->do_request($this->gtfs_rt_feed_url, self::$GET, '', array(), array());


//            //Set the content resource for httpsocket
//            $this->http_socket->setContentResource($f);
//            //And do the request
//            $this->http_socket->request($request);

            // File handle
            $f = fopen($this->gtfs_rt_feed_local_path, 'w');
            //Write feed to file
            fwrite($f, $d);

//            fflush($f);
            //close file
            fclose($f);
            $timer_end = microtime(true);
        }
        $filesize = filesize($this->gtfs_rt_feed_local_path);

        CakeLog::info('get_feed :: -- Finished downloading :: Took:' . round($timer_end - $timer_start, 3) . ' ms. Downloaded [' . round(($filesize / 1024), 3) . '] kb of data', 'opia_rt');

        //Reset the http_socket
//        $this->http_socket->reset(true);
    }

    /**
     * Peforms the cURL request
     *
     * @param $resourcePath
     * @param $method
     * @param $queryParams
     * @param $postData
     * @param $headerParams
     * @return mixed|null
     * @throws Exception
     */
    private function do_request($resourcePath, $method, $queryParams, $postData, $headerParams)
    {
        $headers = array();
        $request = array();

        //Final url is the base path + resource path(location|network|travel|version)
        $url = $resourcePath;

        # Allow API key from $headerParams to override default
//        $added_api_key = False;
//        if ($headerParams != null) {
//            foreach ($headerParams as $key => $val) {
//                $headers[] = "$key: $val";
//                if ($key == 'api_key') {
//                    $added_api_key = True;
//                }
//            }
//        }
//        if (!$added_api_key) {
//            $headers[] = "api_key: " . $this->_apiKey;
//        }

//        if (is_object($postData) or is_array($postData)) {
//            $postData = json_encode(self::sanitizeForSerialization($postData));
//        }


        //Init curl
        $curl = curl_init();
//        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        // return the result on success, rather than just TRUE
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        //merge new headers
        if (!empty($headers)) {
            self::$DEFAULT_CURL_OPTS[CURLOPT_HTTPHEADER] = array_merge($headers, self::$DEFAULT_CURL_OPTS[CURLOPT_HTTPHEADER]);
        }

        //Set curl options
        foreach (self::$DEFAULT_CURL_OPTS as $opt => $opt_data) {
            curl_setopt($curl, $opt, $opt_data);
        }

        //Set HTTP Basic authentication
        if ((Configure::read('OpiaRt.opiaLogin') != "") && (Configure::read('OpiaRt.opiaPassword') != "")) {
            curl_setopt($curl, CURLOPT_USERPWD, Configure::read('OpiaRt.opiaLogin') . ":" . Configure::read('OpiaRt.opiaPassword')); //Your credentials goes here
        }

        if (!empty($queryParams)) {
            $url = ($url . '?' . http_build_query($queryParams));
        }

        if ($method == self::$POST) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        } else if ($method == self::$PUT) {
            $json_data = json_encode($postData);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        } else if ($method == self::$DELETE) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        } else if ($method != self::$GET) {
            throw new Exception('Method ' . $method . ' is not recognized.');
        }

        curl_setopt($curl, CURLOPT_URL, $url);

        //Collect request info
        $request['headers'] = self::$DEFAULT_CURL_OPTS[CURLOPT_HTTPHEADER];
        $request['url'] = $url;
        $url_parse = parse_url($url);

        //Built response varibles
        $response = null;
        $response_info = array('http_code' => 999);

        // Make the request
        $response = curl_exec($curl);
        $response_info = curl_getinfo($curl);

        //handle response
        if ($response_info['http_code'] == 0) {
            CakeLog::write('warning', "TIMEOUT: API call to " . $url . " took more than 1s to return", 'opia_rt');
            throw new Exception("TIMEOUT: API call to " . $url . " took more than 1s to return");
        } else if ($response_info['http_code'] == 200) {
            $data = ($response);
        } else if ($response_info['http_code'] == 400) {
            CakeLog::write('warning', ($response) . " " . $url . " : response code: " . $response_info['http_code'], 'opia_rt');
            throw new Exception(($response) . " " . $url . " : response code: " . $response_info['http_code']);
        } else if ($response_info['http_code'] == 401) {
            CakeLog::write('warning', "Unauthorized API request to " . $url . " : Invalid Login Credentials", 'opia_rt');
            throw new Exception("Unauthorized API request to " . $url . " : Invalid Login Credentials");
        } else if ($response_info['http_code'] == 403) {
            CakeLog::write('warning', "Quota exceeded for this method, or a security error prevented completion of your (successfully authorized) request : " . $url, 'opia_rt');
            throw new Exception("Quota exceeded for this method, or a security error prevented completion of your (successfully authorized) request : " . $url);
        } else if ($response_info['http_code'] == 404) {
            $data = null;
        } else if ($response_info['http_code'] == 500) {
            CakeLog::write('warning', "Internal server error, response code: " . $response_info['http_code'], 'opia_rt');
            throw new Exception("Internal server error, response code: " . $response_info['http_code']);
        } else {
            CakeLog::write('warning', "Can't connect to the api: " . $url . " : response code: " . $response_info['http_code'], 'opia_rt');
            throw new Exception("Can't connect to the api: " . $url . " : response code: " . $response_info['http_code']);
        }

        return $data;
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
                $codec = null;

                if ($this->output_type == null || $this->output_type == "phparray") {
                    include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'D rSlump' . DS . 'Protobuf' . DS . 'Codec' . DS . 'PhpArray.php');

                    $codec = new DrSlump\Protobuf\Codec\PhpArray();

                } elseif ($this->output_type == "json") {
                    //We requre PhpArray to do the heavy lifting and then Json just serializes the data
                    include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'DrSlump' . DS . 'Protobuf' . DS . 'Codec' . DS . 'PhpArray.php');
                    include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'DrSlump' . DS . 'Protobuf' . DS . 'Codec' . DS . 'Json.php');

                    $codec = new DrSlump\Protobuf\Codec\Json();

                } elseif ($this->output_type == "json_indexed") {
                    //We requre PhpArray to do the heavy lifting and then Json just serializes the data
                    include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'DrSlump' . DS . 'Protobuf' . DS . 'Codec' . DS . 'PhpArray.php');
                    include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'DrSlump' . DS . 'Protobuf' . DS . 'Codec' . DS . 'Json.php');
                    include_once(App::pluginPath('OpiaRt') . 'Lib' . DS . 'DrSlump' . DS . 'Protobuf' . DS . 'Codec' . DS . 'JsonIndexed.php');

                    $codec = new DrSlump\Protobuf\Codec\JsonIndexed();

                }

                $feed_data = $codec->encode($fm);

                //Save feed to the datavase
                if (Configure::read('OpiaRt.build_history') == true) {
                    App::uses('GtfsHistory', 'OpiaRt.Model/Datasource');

//                    TODO, this should probably get offloaded as a CakeResque job
                    $gtfs_hist = new GtfsHistory();
                    $gtfs_hist->save_feed($feed_data);
                }

                return ($feed_data);
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
//            'header' => array(
////                'Host' => $url_parse['host'],
////                'User-Agent' => self::USER_AGENT,
////                'Referer' => 'http://translink.com.au/news-and-updates/open-data',
//            ),
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