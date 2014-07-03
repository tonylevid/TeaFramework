<?php

/**
 * TeaDbCriteria class file
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package db
 */
class TeaDbCriteria {
    
    const OP_DELIMITER = ':';
    const COL_DELIMITER = ',';

    /**
     * Logic operators.
     * @var array
     */
    public static $logicOps = array(
        'and' => 'AND',
        'or' => 'OR'
    );

    /**
     * Comparison and other operators.
     * @var array
     */
    public static $commonOps = array(
        'eq' => '=',
        'ne' => '<>',
        'gt' => '>',
        'lt' => '<',
        'gte' => '>=',
        'lte' => '<=',
        'is' => 'IS',
        'is-not' => 'IS NOT',
        'regexp' => 'REGEXP',
        'not-regexp' => 'NOT REGEXP',
        'iregexp' => 'REGEXP BINARY',
        'not-iregexp' => 'NOT REGEXP BINARY',
        'between' => null,
        'not-between' => null,
        'like' => null,
        'not-like' => null,
        'llike' => null,
        'not-llike' => null,
        'rlike' => null,
        'not-rlike' => null,
        'lrlike' => null,
        'not-lrlike' => null,
        'in' => null,
        'not-in' => null,
        'match' => null,
        'match-bool' => null,
        'match-ex' => null
    );
    
    /**
     * ORDER BY operators.
     * @var array
     */
    public static $orderOps = array(
        'asc' => 'ASC',
        'desc' => 'DESC'
    );
    
    /**
     * Join type map.
     * @var array
     */
    public static $joinTypeMap = array(
        'inner' => 'INNER JOIN',
        'left' => 'LEFT JOIN',
        'right' => 'RIGHT JOIN',
        'cross' => 'CROSS JOIN',
        'natural' => 'NATURAL JOIN'
    );

}