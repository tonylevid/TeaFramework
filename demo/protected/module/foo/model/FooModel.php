<?php

class FooModel extends TeaModel {

    public function tableName() {
        return '{{test.test->A}}';
    }

    public function all() {
        $criteria = Tea::getDbCriteria()->join(array('left:test_detail->TD' => array('TD.parent_id' => 'A.id')))
                                   ->where(array('A.id:gt' => 0));
        return $this->findAll($criteria, array('A.id->a_id', 'A.name->a_name', 'A.hits->a_hits'));
    }

    public function one() {
        $criteria = array(
            'join' => array(
                'left:test_detail->TD' => array('TD.parent_id' => 'A.id')
            ),
            'where' => array('A.id:gt' => 0)
        );
        return $this->findColumn('TD.addr->td_addr', $criteria);
    }

}