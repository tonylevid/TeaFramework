<?php

class TestModel extends TeaModel {

    public function all() {
        return $this->findAll();
    }

    public function one() {
        return $this->find();
    }

}