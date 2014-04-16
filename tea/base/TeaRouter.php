<?php

class TeaRouter {

    public $moduleName;

    public $controllerName;

    public $actionName;

    public $actionParams = array();

    private static $_instance;

    private function __construct() {

    }

    private function __clone() {

    }

    public static function getInstance($url) {
        $this->setRouteByUrl($url);
        if (!self::$_instance instanceof self) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function route() {
        if (!class_exists($this->getControllerName())) {
            throw new TException("Controller '{$this->getController()}' does not exist.");
        }
        $rfc = new ReflectionClass($this->getControllerName());
        if (!$rfc->isSubClassOf('TeaController')) {
            throw new TException("Controller '{$this->getController()}' must extend 'TController'.");
        }
        if (!$rfc->hasMethod($this->getActionName())) {
            throw new TException("Action '{$this->getAction()}' does not exist.");
        }
        $instance = $rfc->newInstance();
        $method = $rfc->getMethod($this->getActionName());
        if (method_exists($instance, $this->getActionName()) && $method->isPublic() && !$method->isStatic()) {
            $method->invokeArgs($instance, $this->getActionParams());
        } else {
            throw new TException("Action '{$this->getAction()}' could not be accessed.");
        }
    }

    public function getModuleName() {
        return $this->moduleName;
    }

    public function getControllerName() {
        return $this->controllerName;
    }

    public function getActionName() {
        return $this->actionName;
    }

    public function getActionParams() {
        return $this->actionParams;
    }

    /**
     * Set module name, controller name, action name and action params.
     */
    public function setRouteByUrl() {

    }

}