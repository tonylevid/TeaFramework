<?php

class MainController extends TeaController {

    public function index($name, $say) {
        $this->render(null, array(
            'name' => $name,
            'say' => $say
        ));
    }

    public function getName() {
        $datas = array(
            'china' => 'America'
        );
        $this->render('Main.getName', $datas);
    }

}