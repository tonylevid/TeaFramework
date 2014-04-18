<?php

class TeaRouter {

    public $config = array(
        'routeMode' => 'path',  // 'path' or 'get'
        'routeModeGetName' => 'r',  // only available when route mode is 'get'
        'controllerSuffix' => 'Controller'
    );

    private $_moduleName;

    private $_controllerName;

    private $_actionName;

    private $_actionParams = array();

    public function __construct() {
        $this->setConfig();
        $this->setRouteInfo();
    }

    public function setConfig() {
        $classConfig = Tea::getConfig(get_class($this));
        $this->config = ArrayHelper::mergeArray($this->config, $classConfig);
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
        return $this->_moduleName;
    }

    public function getControllerName() {
        return $this->_controllerName;
    }

    public function getActionName() {
        return $this->_actionName;
    }

    public function getActionParams() {
        return $this->_actionParams;
    }

    /**
     * Set module name, controller name, action name and action params.
     */
    protected function setRouteInfo() {
        $request = new TeaRequest();
        $routeInfo = array();
        switch ($this->config['routeMode']) {
            case 'path':
                $this->setRoutePathinfo($request->getPathinfo());
                break;
            case 'get':
                $this->setRoutePathinfo($request->getQuery($this->config['routeModeGetName']));
                break;
            default:
                throw new TeaException("Unable to determine route mode {$this->config['routeMode']}.");
                break;
        }
    }

    protected function setRoutePathinfo($pathinfo) {
        $pathSegments = explode('/', ltrim($pathinfo, '/'));
        $this->_moduleName = $pathSegments[0];
        $this->_controllerName = $pathSegments[1] . $this->config['controllerSuffix'];
        $this->_actionName = $pathSegments[2];
        $this->_actionParams = array_slice($pathSegments, 3);
    }

}