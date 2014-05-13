<?php

class MainController extends TeaController {

    public function index($name) {
        var_dump($this->getRouter()->getUrlControllerName());
        var_dump($this->getRouter()->getControllerName());
        $data = $this->loadModel('test')->all();
        print_r($this->loadModel('module.foo.foo')->all());
        print_r($this->getModel('{{test.test->A}}')->getLastSql());
        $this->loadModel('test')->increase(-1, 'hits', array('where' => array(
            'id:between' => array(1, 100)
        )));
        $this->assign(array(
            'data' => $data,
            'name' => $name
        ));
        $this->render();
    }

}