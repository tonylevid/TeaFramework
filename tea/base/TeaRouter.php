<?php

class TeaRouter {

    public $routeMode = 'path'; // 'path' or 'get'

    public $routeModeGetName = 'r'; // only available when route mode is 'get'

    public $moduleName;

    public $controllerName;

    public $actionName;

    public $actionParams = array();

    public function __construct($routeInfo = array()) {
        Tea::setInstProps($this, Tea::getConfig(get_class($this)));
        $this->setRouteInfo($routeInfo);
    }

    /**
     * Set moduleName, controllerName, actionName and actionParams.
     */
    public function setRouteInfo($routeInfo = array()) {
        Tea::setInstProps($this, $routeInfo);
    }

    public function route() {
        if (!class_exists($this->getControllerName())) {
            throw new TeaException("Controller '{$this->getControllerName()}' does not exist.");
        }
        $rfc = new ReflectionClass($this->getControllerName());
        if (!$rfc->isSubClassOf('TeaController')) {
            throw new TeaException("Controller '{$this->getControllerName()}' must extend 'TController'.");
        }
        if (!$rfc->hasMethod($this->getActionName())) {
            throw new TeaException("Action '{$this->getActionName()}' does not exist.");
        }
        $instance = $rfc->newInstance();
        $method = $rfc->getMethod($this->getActionName());
        if (method_exists($instance, $this->getActionName()) && $method->isPublic() && !$method->isStatic()) {
            $method->invokeArgs($instance, $this->getActionParams());
        } else {
            throw new TeaException("Action '{$this->getActionName()}' could not be accessed.");
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

}