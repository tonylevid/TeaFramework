<?php

class MainController extends TeaController {

    public function index($name, $say) {
        $this->render(null, array(
            'name' => $name,
            'say' => $say
        ));
    }

}