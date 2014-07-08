<?php

class TestModel extends TeaModel {

    public function tableName() {
        return '{{test->T}}';
    }

    public function criterias() {
        return array(
            'test_detail_where' => array(
                'where' => array('{{table}}.id:gte' => 5)
            )
        );
    }

    public function joins() {
        return array(
            'left_test_detail' => array(
                'left:test_detail->TD' => array('{{joinTable}}.parent_id' => '{{table}}.id', ':condition' => array(
                    '{{table}}.id:gt' => 10
                ))
            ),
            'inner_test_detail' => array(
                'test_detail' => array('{{joinTable}}.parent_id' => '{{table}}.id')
            )
        );
    }

    public function all() {
        $count = $this->withJoin('left_test_detail')->withCriteria('test_detail_where')->count();
        $limitCriteria = $this->loadLib('TeaPager', array($count))->getLimitCriteria();
        $rst = $this->withJoin('left_test_detail')->withCriteria('test_detail_where')->findAll($limitCriteria);
        return $rst;
    }

}