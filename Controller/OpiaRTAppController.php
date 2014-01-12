<?php

App::uses('AppController', 'Controller');

class OpiaRtAppController extends AppController {

    public function beforeRender()
    {
        $this->viewClass = 'Json';
    }


}
