<?php

class MainController extends TeaController {

    public function index($name) {
        $rst = $this->loadModel('test')->all();
        $sqlAll = $this->loadModel('test')->getLastSql();
        $this->assign(array(
            'data' => $rst[0],
            'pager' => $rst[1]->content(),
            'sqlAll' => $sqlAll,
            'name' => $name
        ));
        $this->render();
    }

}