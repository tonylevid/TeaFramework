<?php

class MainController extends TeaController {

    public function index($name = 'Tea', $says = 'hello world!') {
        $this->render(null, array(
            'name' => $name,
            'says' => $says
        ));
    }

}