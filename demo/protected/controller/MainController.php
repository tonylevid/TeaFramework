<?php

class MainController extends TeaController {

    public function index($name) {
        echo "Hello, {$name}";
    }

}