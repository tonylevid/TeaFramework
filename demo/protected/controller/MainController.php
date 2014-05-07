<?php

class MainController extends TeaController {

    public function index($name) {
        $this->assign('name', $name);
        $this->render();
    }

}