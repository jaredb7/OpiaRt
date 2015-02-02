<?php

Router::connect('/:plugin/feed/*', array('controller' => 'opia_rt_api', 'action' => 'feed'));


Router::connect('/:plugin/object/ids/*', array('controller' => 'opia_rt_api', 'action' => 'objects_in_quadrant'));
Router::connect('/:plugin/object/datas/*', array('controller' => 'opia_rt_api', 'action' => 'object_data_in_quadrant'));
Router::connect('/:plugin/object/poss/*', array('controller' => 'opia_rt_api', 'action' => 'object_positions_in_quadrant'));
Router::connect('/:plugin/object/pos/*', array('controller' => 'opia_rt_api', 'action' => 'object_position'));
