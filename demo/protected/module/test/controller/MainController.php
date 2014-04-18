<?php

class MainController extends TeaController {

    public function index($name) {
        echo "Hello, {$name}";
        $req = new TeaRequest();
        var_dump($req->getPathInfo());
        var_dump($req->getRequestUri());
        var_dump($req->getScriptName());
        var_dump($req->getPhpSelf());
    }

}