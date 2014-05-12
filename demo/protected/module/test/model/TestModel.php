<?php

class TestModel extends TeaModel {

    public function tableName() {
        return '{{test.test->A}}';
    }

    public function all() {
        $criteria = $this->criteria->join(array('left:test_detail->TD' => array('TD.parent_id' => 'A.id')))
                                   ->where(array('A.id:gt' => 0));
        $criteriaArr = array(
            'join' => array(
                'left:test_detail->TD' => array('TD.parent_id' => 'A.id')
            ),
            'where' => array('A.id:gt' => 0)
        );
        return $this->findAll($criteria);
    }

    public function one() {
        $criteria = array(
            'join' => array(
                'left:test_detail->TD' => array('TD.parent_id' => 'A.id')
            ),
            'where' => array('A.id:gt' => 0)
        );
        $this->updateByPk(array('name' => 'tonylevid'), 1);
        //return $this->findColumn('TD.addr->td_addr', $criteria);
    }

}