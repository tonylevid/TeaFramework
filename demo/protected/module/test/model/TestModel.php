<?php

class TestModel extends TeaModel {

    public function tableName() {
        return '{{test->A}}';
    }

    public function criterias() {
        return array(
            'test_detail_where' => array(
                'where' => array('A.id:gte' => 5)
            )
        );
    }

    public function joins() {
        return array(
            'left_test_detail' => array(
                'left:test_detail->TD' => array('TD.parent_id' => 'A.id')
            ),
            'inner_test_detail' => array(
                'test_detail->TD' => array('TD.parent_id' => 'A.id')
            )
        );
    }

    public function all() {
        $rst = $this->withJoin('left_test_detail')->withCriteria('test_detail_where')->findAll();
        return $rst;
    }

}