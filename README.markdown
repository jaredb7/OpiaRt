This project was inspired by translink-opiaproxy (https://github.com/wislon/translink-opiaproxy), who has done a awesome job of making the Translink OPIA API easy to use.

Checkout my version of his implmentionation: https://github.com/jaredb7/OpiaProxy

This is a CakePHP Plugin allowing easy access to the GTFS Real-Time feed offered by Translink
https://gtfsrt.api.translink.com.au/
Output of protobuf data is changable between JSON and PHPArray by specifing the 2nd argment ('json' or 'phparray') when initializing the GtfsRtLoader model.

I almost exclusively use the CakePHP framework when programming in PHP, so it made sense to make a plugin for it.

Hopefully this project speeds and helps projects for those developing on the Translink OPIA API :).

## INSTALLATION

__1__ - Clone the project into your apps plugins-folder (app/Plugin/)

__2__ - Enable the plugin in your app/Config/bootstrap.php file
```
      CakePlugin::load(array('OpiaRt' => array('bootstrap' => true, 'routes' => true)));
```

## CONFIGURATION

No configuration required at this stage

## USAGE
Include the GTFS RT loader 'model' with App::uses in your model/class
```
  App::uses('GtfsRtLoader', 'OpiaRt.Model');
  class MyTestAppController{
  
    private $gtfsrt_api_client;
  
  //Method to get the GTFS Real-Time feed
    public function api_rt_test(){

       //Load and process the GTFS RT feed and output in json format
       //A PHPArray can be returned by changing the 2nd argument to 'phparray'
        $this->gtfsrt_api_client = new GtfsRtLoader("http://gtfsrt.api.translink.com.au/feed/", 'json');
        $obj = $this->gtfsrt_api_client->load();
        
        //The result will be whatever data type was requested in the 2nd argument
        //eg. JSON
        //"header":{"gtfs_realtime_version":"1.0","incrementality":0,"timestamp":1389518226},"entity":[{"id":"TU-BT2013-NOV-FUL-Sunday-01-3887845","is_deleted":true,"trip_update":{"trip":{"trip_id":"BT2013-NOV-FUL-Sunday-01-3887845","route_id":"60-410",.....
      }
    }
  }
}
```
