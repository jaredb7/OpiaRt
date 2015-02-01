<?php
define('CAKE_OPIA_RT_VER','0.0.5');

/**
 * OPIA API ACCESS CREDENTIALS
 */
Configure::write('OpiaRt.opiaLogin', ""); //Your login as supplied by Translink
Configure::write('OpiaRt.opiaPassword', ""); //Your password as supplied by Translink
Configure::write('OpiaRt.api_key', "special_key"); //Maybe for some future OPIA API implementation, this is sent in the header by default

Configure::write('OpiaRt.build_history', false); //Save GTFS-RT feed entries to the database to build a archive

//Configuration for the OpiaRt feed path plugin
Configure::write('OpiaRt.storage_path', App::pluginPath('OpiaRt') . "tmp");

/**
 * Configures logging options for the OpiaRt
 */
CakeLog::config('opia_rt', array(
    'engine' => 'FileLog',
    'types' => array('info', 'error', 'warning'),
    'scopes' => array('opia_rt'),
    'file' => 'opia_rt.log',
));