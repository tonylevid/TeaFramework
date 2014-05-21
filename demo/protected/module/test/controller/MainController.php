<?php

class MainController extends TeaController {

    public function index($name) {
        var_dump($this->getRouter()->getModuleName());
        var_dump($this->getRouter()->getUrlControllerName());
        var_dump($this->getRouter()->getControllerName());
        var_dump($this->getRouter()->getActionName());
        $m = $this->getModel('test');
        $m->id = 1;
        $m->name = 'tony and cherry';
        $m->hits = 200;
        $m->save();
        var_dump($m->getLastSql());
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