<?php

/**
 * TeaDbException class file
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package system
 */
class TeaDbException extends TeaException {

    public $errorInfo;

    /**
     * Constructor.
     * @param string $message PDO error message.
     * @param integer $code PDO error code.
     * @param mixed $errorInfo PDO error info.
     */
    public function __construct($message, $code = 0, $errorInfo = null) {
        $this->errorInfo = $errorInfo;
        parent::__construct($message, $code);
    }

}