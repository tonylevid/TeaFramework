<?php

class TestModel extends TeaModel {

    public function test() {
        return $this->findAll();
    }

}