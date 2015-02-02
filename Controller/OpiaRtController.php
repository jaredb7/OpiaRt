<?php
App::uses('OpiaRtAppController', 'OpiaRt.Controller');
App::uses('GtfsRtLoader', 'OpiaRt.Model');
App::uses('GtfsHistory', 'OpiaRt.Model/Datasource');

class OpiaRtController extends OpiaRtAppController
{

    public function beforeFilter()
    {
        parent::beforeFilter();

//        $this->Auth->allow('');

        if ($this->Auth->user('role') == 'admin') {
            $this->Auth->allow('admin_index');
        }
    }

    public function beforeRender()
    {
        $this->viewClass = 'View';
    }

    public function admin_index()
    {

        $count = 0;
        if (Configure::read('OpiaRt.build_history') == true) {
            App::uses('GtfsHistory', 'OpiaRt.Model/Datasource');

            $gtfs_hist = new GtfsHistory();
            $count = $gtfs_hist->find('count');
        }


        //Build info about the cache
        $rt_records = array(
            'rt_record_count' => $count,
            'rt_save_to_db' => Configure::read('OpiaRt.build_history'));


        //Now check APIs - this is crude but it works
        //Disable saving of these records
        Configure::write('OpiaRt.build_history', false);
        //init everything to false
        $api_status = array('gtfs_rt' => false);

        //Network
        $rt_loader = new GtfsRtLoader("https://gtfsrt.api.translink.com.au/feed/", 'json');
        $gtfs_rt_result = $rt_loader->load();


        //Check em all
        if (!empty($gtfs_rt_result) || $gtfs_rt_result != null) {
            $api_status['gtfs_rt'] = true;
        }

        $log = HelpfulUtils::tail(LOGS . "opia_rt.log", 400);
        //Turn the log into an array so we can iterate over the lines
        $log_arr = array_reverse(explode("\n", $log));

        $this->set('api_status', $api_status);
        $this->set('rt_records', $rt_records);
        $this->set('log_tail', $log_arr);

    }

}