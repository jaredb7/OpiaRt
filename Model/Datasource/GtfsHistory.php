<?php
App::uses('AppModel', 'Model');

/**
 * Calendar Model
 *
 * @property Timetable $Timetable
 */
class GtfsHistory extends AppModel
{
    /**
     * Use table
     *
     * @var mixed False or table name
     */
    public $useTable = 'gtfsrt_history';
    public $primaryKey = 'trip_id';

    //Restrict records to the last x seconds
    public $restrict_timestamp = false;

    /**
     * Saves the feed to the database
     */
    public function save_feed($feed_json)
    {
        $feed_json = json_decode($feed_json, true);
        $timestamp = $feed_json['header']['timestamp'];

        foreach ($feed_json['entity'] as $id => $object) {
            $this->create();
            $object_id = $object['id'];
            $trip_id = 0;
            //do some filtering to get the trip_id
            if (stripos($object_id, 'TU') !== false) {
                //TripUpdate
                $trip_id = $object['trip_update']['trip']['trip_id'];
            } else if (stripos($object_id, 'VU') !== false) {
                //Vehicle Update
                $trip_id = $object['vehicle']['trip']['trip_id'];
            }

            $object_json = json_encode($object);

            $this->save(array(
                    'timestamp' => $timestamp,
                    'trip_id' => $trip_id,
                    'object_id' => $object_id,
                    'object_data' => $object_json,
                    'object_data_hash' => md5($object_json)
                )
            );
        }

        return;
    }

    public function get_object_ids_in_quadrant($quadrant)
    {
        $quadrant = strtoupper($quadrant);
        $result = $this->find('all', array('conditions' => array('Trip.bearing' => $quadrant, 'GtfsHistory.object_id LIKE' => 'TU-%'), 'fields' => 'DISTINCT GtfsHistory.trip_id'));
        return $result;

    }

    public function get_object_data_in_quadrant($quadrant)
    {
        $quadrant = strtoupper($quadrant);

        $result = $this->find('all', array('conditions' => array('Trip.bearing' => $quadrant, 'GtfsHistory.object_id LIKE' => 'TU-%'), 'fields' => 'DISTINCT GtfsHistory.trip_id, GtfsHistory.object_data'));
        return $result;
    }


    public function get_object_positions_in_quadrant($quadrant)
    {
        $quadrant = strtoupper($quadrant);

        $result = $this->find('all', array('conditions' => array('Trip.bearing' => $quadrant, 'GtfsHistory.object_id LIKE' => 'TU-%'), 'fields' => ' DISTINCT GtfsHistory.trip_id, GtfsHistory.object_id, GtfsHistory.object_data'));
        $veh_result = $this->find('all', array('conditions' => array('GtfsHistory.object_id LIKE' => 'VU-%'), 'fields' => 'DISTINCT GtfsHistory.trip_id, GtfsHistory.object_id, GtfsHistory.object_data'));

        $result_return = array();
        $position = array();

        foreach ($result as $id => &$data) {
            $object_id = $data['GtfsHistory']['object_id'];
            $object_data = json_decode($data['GtfsHistory']['object_data'], true);

            $vehicle_id = null;
            $position = array();

//            if (stripos($object_id, 'TU') !== false) {
            //Get the vehicle ID
            if (isset($object_data['trip_update']['vehicle'])) {

                $vehicle_id = $object_data['trip_update']['vehicle']['id'];
                //find the vehicle in the results
                foreach ($veh_result as $vid => $vdata) {
                    $vobject_id = $vdata['GtfsHistory']['object_id'];
                    $vobject_data = json_decode($vdata['GtfsHistory']['object_data'], true);

                    if ($vobject_id == 'VU-' . $vehicle_id) {
//                        debug($vobject_id);
//                        debug($vobject_data);

                        //This is the matching vehicle, get the position
                        $position = $vobject_data['vehicle']['position'];
                        break;
                    }
                }
            }

            $data['GtfsHistory']['vehicle_id'] = $vehicle_id;
            $data['GtfsHistory']['position'] = ($position);

            unset($data['GtfsHistory']['object_data']);

//            }
        }

//        debug($result);

        return $result;
    }


    public function get_object_position($object_or_trip_id)
    {
        if (stripos($object_or_trip_id, 'TU') !== false) {
            $result = $this->find('all', array('conditions' => array('GtfsHistory.object_id' => $object_or_trip_id, 'GtfsHistory.object_id LIKE' => 'TU-%'), 'fields' => 'GtfsHistory.trip_id, GtfsHistory.object_data'));

        } else {
            $result = $this->find('all', array('conditions' => array('GtfsHistory.trip_id' => $object_or_trip_id, 'GtfsHistory.object_id LIKE' => 'TU-%'), 'fields' => 'GtfsHistory.trip_id, GtfsHistory.object_data'));

        }

        //        $veh_result = $this->find('all', array('conditions' => array('GtfsHistory.object_id LIKE' => 'VU-%', 'GtfsHistory.timestamp' => (time() - 30)), 'fields' => 'DISTINCT GtfsHistory.trip_id, GtfsHistory.object_id, GtfsHistory.object_data'));
        $veh_result = $this->find('all', array('conditions' => array('GtfsHistory.object_id LIKE' => 'VU-%'), 'fields' => 'GtfsHistory.object_id, GtfsHistory.object_data'));

        $result_return = array();
        $position = array();

        //Extract the position
        foreach ($result as $id => &$data) {
            $object_id = $data['GtfsHistory']['object_id'];
            $object_data = json_decode($data['GtfsHistory']['object_data'], true);

            $vehicle_id = null;
            $position = array();

            if (isset($object_data['trip_update']['vehicle'])) {

                $vehicle_id = $object_data['trip_update']['vehicle']['id'];
                //find the vehicle in the results
                foreach ($veh_result as $vid => $vdata) {
                    $vobject_id = $vdata['GtfsHistory']['object_id'];
                    $vobject_data = json_decode($vdata['GtfsHistory']['object_data'], true);

                    if ($vobject_id == 'VU-' . $vehicle_id) {

                        //This is the matching vehicle, get the position
                        $position = $vobject_data['vehicle']['position'];
                        break;
                    }
                }
            }

            $data['GtfsHistory']['vehicle_id'] = $vehicle_id;
            $data['GtfsHistory']['position'] = ($position);

            unset($data['GtfsHistory']['object_data']);
        }

        return $result;
    }

    public function  beforeFind($queryData)
    {
        if ($this->restrict_timestamp !== 0 && $this->restrict_timestamp !== false) {
            $queryData['conditions']['GtfsHistory.timestamp'] = (time() - 30);
        }

        parent::beforeFind($queryData);

    }

    /**
     * Validation rules
     *
     * @var array
     */
    public $validate = array(
        'timestamp' => array(
            'notempty' => array(
                'rule' => array('notempty'),
                'message' => 'Timestamp should be supplied',
                'allowEmpty' => false,
                'required' => true,
                //'last' => false, // Stop validation after this rule
                //'on' => 'create', // Limit validation to 'create' or 'update' operations
            ),
        ),
        'object_id' => array(
            'notempty' => array(
                'rule' => array('notempty'),
                'message' => 'Object ID should be supplied',
                'allowEmpty' => false,
                'required' => true,
                //'last' => false, // Stop validation after this rule
                //'on' => 'create', // Limit validation to 'create' or 'update' operations
            ),
            'unique_on_timestamp_objectid' => array(
                'rule' => array('unique_on_timestamp_objectid'),
                'message' => "timestamp and object_id combo must be unique.",
                'on' => 'create', // Limit validation to 'create' or 'update' operations
            )
        ),
        'object_data' => array(
            'unique_on_object_id_object_data_hash' => array(
                'rule' => array('unique_on_object_id_object_data_hash'),
                'message' => "object_id and object data hash combo must be unique.",
                'on' => 'create', // Limit validation to 'create' or 'update' operations
            )
        ),
    );

    public $belongsTo = array(
        'Trip' => array(
            'className' => 'Trip',
            'foreignKey' => 'trip_id',
            'conditions' => '',
            'fields' => '',
            'order' => ''
        ),
    );


    //Checks to see if the combo of timestamp and objectid are unique in the database
    //helps avoid duplicate data, there is also a uniqueness constraint set on the database table
    public function unique_on_timestamp_objectid($check)
    {
        // $check will have value: array('geolong' => 'some-value')
        $check['timestamp'] = $this->data['GtfsHistory']['timestamp'];

        $existing_count = $this->find('count', array(
            'conditions' => $check,
            'recursive' => -1
        ));

        //If there are no existing rows for the supplied field combo
        //then return true to pass the validation or false to fail it
        if ($existing_count == 0) {
            return true;
        } else {
            return false;
        }
    }


    //Checks to see if the combo of object_id and object_data is unique
    //helps avoid duplicate data, there is also a uniqueness constraint set on the database table
    public function unique_on_object_id_object_data_hash($check)
    {
        // $check will have value: array('geolong' => 'some-value')
        $check['object_id'] = $this->data['GtfsHistory']['object_id'];
        $check['object_data_hash'] = md5($this->data['GtfsHistory']['object_data']);

        unset($check['object_data']);

        $existing_count = $this->find('count', array(
            'conditions' => $check,
            'recursive' => -1
        ));

        //If there are no existing rows for the supplied field combo
        //then return true to pass the validation or false to fail it
        if ($existing_count == 0) {
            return true;
        } else {
            return false;
        }
    }

}