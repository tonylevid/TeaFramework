<?php

class MainController extends TeaController {

    public function index($name) {
        $model = $this->loadModel('test');
        var_dump($model->getTableName());
        var_dump($model->getTableAlias());
        $data = $model->all();
        echo $model->getLastSql() . '<br>';
        $this->assign(array(
            'data' => $data,
            'name' => $name
        ));
        $this->render();
    }

}