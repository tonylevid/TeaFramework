<?php

/**
 * TeaDbException类文件。
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.teaframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.teaframework.com/license/
 * @package system
 */
class TeaDbException extends TeaException {
    
    /**
     * 错误信息。
     * @var array
     */
    public $errorInfo;

    /**
     * 构造函数。
     * @param string $message PDO错误消息。
     * @param integer $code PDO错误代码。
     * @param mixed $errorInfo PDO错误信息。
     */
    public function __construct($message, $code = 0, $errorInfo = null) {
        $this->errorInfo = $errorInfo;
        parent::__construct($message, $code);
    }

}