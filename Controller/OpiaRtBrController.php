<?php
App::uses('OpiaRtController', 'OpiaRt.Controller');
App::uses('GtfsRtLoader', 'OpiaRt.Model');
App::uses('GtfsHistory', 'OpiaRt.Model/Datasource');


class OpiaRtBrController extends OpiaRtAppController
{

    /**
     * @var $gtfsrt_api_client GtfsRtLoader
     */
    private $gtfsrt_api_client;

    public function beforeFilter()
    {
        parent::beforeFilter();

        $this->viewPath = 'api_response';
        $this->view = "base_api_response";

        $this->Auth->allow();
    }

    public function objects_in_quadrant()
    {

        if (!empty($this->passedArgs)) {
            $region_chk = $this->passedArgs[0];
            if (is_string($region_chk)) {
                $bearing = $region_chk;
            }
        }

        $gtfs_hist = new GtfsHistory();
        $res = $gtfs_hist->get_object_ids_in_quadrant($bearing);

        $this->set('response', json_encode($res));
    }

    public function object_data_in_quadrant()
    {
        if (!empty($this->passedArgs)) {
            $region_chk = $this->passedArgs[0];
            if (is_string($region_chk)) {
                $bearing = $region_chk;
            }
        }

        $gtfs_hist = new GtfsHistory();
        $res = $gtfs_hist->get_object_data_in_quadrant($bearing);

        $this->set('response', json_encode($res));
    }


    public function object_positions_in_quadrant()
    {
        if (!empty($this->passedArgs)) {
            $region_chk = $this->passedArgs[0];
            if (is_string($region_chk)) {
                $bearing = $region_chk;
            }
        }

        $gtfs_hist = new GtfsHistory();
        $res = $gtfs_hist->get_object_positions_in_quadrant($bearing);

        $this->set('response', json_encode($res));
    }

    public function object_position()
    {
        if (!empty($this->passedArgs)) {
            $region_chk = $this->passedArgs[0];
            if (is_string($region_chk)) {
                $object_id = $region_chk;
            }
        }

        $gtfs_hist = new GtfsHistory();
        $res = $gtfs_hist->get_object_position($object_id);

        $this->set('response', json_encode($res));
    }

}
