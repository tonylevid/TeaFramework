<?php

class TestModel extends TeaModel {

    public function tableName() {
        return '{{test->A}}';
    }

    public function all() {
        $m = $this->getModel('test');
        $m->id = 1;
        $m->name = 'tonylevi';
        $m->hits = 200;
        $m->save();
        $criteria = $this->getCriteria()->join(array('left:test_detail->TD' => array('TD.parent_id' => 'A.id')))->where(array('A.id:gt' => 0));
        return $this->findAll($criteria, array(new TeaDbExpr('A.id + ? AS a_id_plus_two', array(2)), 'A.id->a_id', 'A.name->a_name', 'A.hits->a_hits', 'TD.addr', 'TD.qq'));
    }

}