<?php

class MainController extends TeaController {

    public function index($name) {
        $model = new TestModel();
        print_r($model->test());
        echo "Hello, {$name}";
    }

}