<?php

class TestModel extends TeaModel {

    public function tableName() {
        return '{{test->A}}';
    }

    public function criterias() {
        return array(
            'test_detail' => array(
                'join' => array('left:test_detail->TD' => array('TD.parent_id' => 'A.id')),
                'where' => array('A.id:lte' => 5)
            )
        );
    }

    public function relations() {

    }

    public function all() {
        $rst = $this->withCriteria('test_detail')->findByCondition(array('A.id:gt' => 0));
        return $rst;
    }

}