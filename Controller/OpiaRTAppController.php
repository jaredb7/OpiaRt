<?php

App::uses('AppController', 'Controller');

class OpiaRtAppController extends AppController {

    public function beforeRender()
    {
        parent::beforeFilter();
        $this->viewClass = 'Json';
    }

}