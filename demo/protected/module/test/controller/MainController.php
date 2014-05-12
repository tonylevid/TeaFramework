<?php

class MainController extends TeaController {

    public function index($name) {
        $data = $this->loadModel('test')->one();
        print_r($this->getModel('{{test.test->A}}')->getLastSql());
        $this->loadModel('test')->increase(1, 'hits', array('where' => array(
            'id:between' => array(1, 100)
        )));
        $this->assign(array(
            'data' => $data,
            'name' => $name
        ));
        $this->render();
    }

}