<?php

class TestModel extends TeaModel {

    public function tableName() {
        return '{{test.test->A}}';
    }

    public function all() {
        return $this->findAll();
    }

    public function one() {
        return $this->find();
    }

}