<?php

class TestModel extends TeaModel {

    public function tableName() {
        return '{{test->A}}';
    }

    public function criterias() {
        return array(
            'test_detail' => array(
                'join' => array('left:test_detail->TD' => array('TD.parent_id' => 'A.id')),
                'where' => array('A.id:lt' => 5)
            )
        );
    }

    public function all() {
        return $this->withCriteria('test_detail')->findAllByCondition(array('A.id:gt' => 0), array(
            new TeaDbExpr('A.id + ? AS a_id_plus_two', array(2)),
            'A.id->a_id',
            'A.name->a_name',
            'A.hits->a_hits',
            'TD.addr',
            'TD.qq'
        ));
    }

}