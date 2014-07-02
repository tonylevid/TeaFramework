<?php

class MainController extends TeaController {

    public function index($name) {
        $data = $this->loadModel('test')->all();
        $sqlAll = $this->loadModel('test')->getLastSql();
        $this->assign(array(
            'data' => $data,
            'sqlAll' => $sqlAll,
            'name' => $name
        ));
        $this->render();
    }

}