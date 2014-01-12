<?php
App::uses('OpiaRtAppController', 'OpiaRt.Controller');
App::uses('GtfsRtLoader', 'OpiaRt.Model');

class OpiaRtController extends OpiaRtAppController
{

    /**
     * @var $gtfsrt_api_client GtfsRtLoader
     */
    private $gtfsrt_api_client;

    public function beforeFilter()
    {
        $this->viewPath = 'api_response';
        $this->view = "base_api_response";
    }

    /**
     * RT feed processing
     */
    public function feed()
    {
        $region = "";
        //Support region scoping
        if (!empty($this->passedArgs)) {
            $region_chk = $this->passedArgs[0];
            if (is_string($region_chk)) {
                $region = $region_chk;
            }
        }

        //Load and process the GTFS RT feed and output in json format
        $this->gtfsrt_api_client = new GtfsRtLoader("http://gtfsrt.api.translink.com.au/feed/" . $region, 'json');
        $obj = $this->gtfsrt_api_client->load();

        $this->set('response', $obj);
    }

}
