<?php

class MainController extends TeaController {

    public function index($name) {
        $data = $this->getModel('{{test.test->A}}')->findAll();
        var_dump($this->getModel('{{test.test->A}}')->getLastSql());
        $data = $this->loadModel('test')->all();
        var_dump($this->loadModel('test')->getLastSql());
        $this->loadModel('test')->increaseByPk(1, 'hits', 1);
        $this->loadModel('test')->increase(1, 'hits', array('where' => array(
            'id:between' => array(2, 100)
        )));
        $this->assign(array(
            'data' => $data,
            'name' => $name
        ));
        $this->render();
    }

}