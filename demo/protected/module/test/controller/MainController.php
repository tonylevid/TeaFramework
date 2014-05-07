<?php

class MainController extends TeaController {

    public function index($name) {
        $model = $this->loadModel('test');
        $data = $model->all();
        $this->assign(array(
            'data' => $data,
            'name' => $name
        ));
        $this->render();
    }

}