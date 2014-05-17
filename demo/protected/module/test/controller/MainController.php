<?php

class MainController extends TeaController {

    public function index($name) {
        var_dump($this->getRouter()->getModuleName());
        var_dump($this->getRouter()->getUrlControllerName());
        var_dump($this->getRouter()->getControllerName());
        var_dump($this->getRouter()->getActionName());
        $data = $this->loadModel('test')->all();
        $sqlAll = $this->loadModel('test')->getLastSql();
        $this->loadModel('test')->incByCondition(array('id:between' => array(1, 100)), 'hits');
        $sqlInc = $this->loadModel('test')->getLastSql();
        var_dump($this->loadModel('test')->getColumnNames());
        $this->assign(array(
            'data' => $data,
            'sqlAll' => $sqlAll,
            'sqlInc' => $sqlInc,
            'name' => $name
        ));
        $this->render();
    }

}