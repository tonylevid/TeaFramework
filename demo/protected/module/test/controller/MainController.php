<?php

class MainController extends TeaController {

    public function beforeAction($name) {
        var_dump("Action name: {$name}");
    }

    public function index($name) {
        $data = $this->loadModel('test')->all();
        $sqlAll = $this->loadModel('test')->getLastSql();
        $this->loadModel('test')->incByCondition(array('id:between' => array(1, 100)), 'hits', 1, false);
        $sqlInc = $this->loadModel('test')->getLastSql();
        $this->assign(array(
            'data' => $data,
            'sqlAll' => $sqlAll,
            'sqlInc' => $sqlInc,
            'name' => $name
        ));
        $this->render();
    }

}