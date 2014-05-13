<?php

/**
 * TeaRouter class file.
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package base
 */
class TeaRouter {

    public static $config = array(
        'routeMode' => 'path',  // 'path' or 'get'
        'routeModeGetName' => 'r',  // only available when route mode is 'get'
        'urlSuffix' => ''
    );

    private $_moduleName;

    private $_urlControllerName;

    private $_controllerName;

    private $_actionName;

    private $_actionParams = array();

    /**
     * Arguments of method TeaRouter::route().
     * @var array
     */
    private $_routeArgs = array();

    public function __construct() {
        Tea::setClassConfig(__CLASS__);
    }

    public function route($routeArgs = array()) {
        $this->_routeArgs = $routeArgs;
        $this->setRouteInfo();
        $this->importController();
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

    public function getUrlControllerName() {
        return $this->_urlControllerName;
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

    protected function importController() {
        $moduleName = $this->getModuleName();
        $controllerName = $this->getControllerName();
        if (empty($moduleName)) {
            Tea::import("protected.controller.{$controllerName}");
        } else {
            Tea::import("module.{$moduleName}.controller.{$controllerName}");
        }
    }

    /**
     * Set module name, controller name, action name and action params.
     */
    protected function setRouteInfo() {
        if (!empty($this->_routeArgs)) {
            $this->setRoutePathinfo(implode('/', $this->_routeArgs));
            return null;
        }
        $request = new TeaRequest();
        switch (self::$config['routeMode']) {
            case 'path':
                $this->setRoutePathinfo($request->getPathinfo());
                break;
            case 'get':
                $this->setRoutePathinfo($request->getQuery(self::$config['routeModeGetName']));
                break;
            default:
                throw new TeaException('Unable to determine route mode {' . self::$config['routeMode'] . '}.');
                break;
        }
    }

    private function setRoutePathinfo($pathinfo) {
        $trimedPathinfo = preg_replace('/' . preg_quote(self::$config['urlSuffix']) . '$/', '', ltrim($pathinfo, '/'));
        $pathSegments = !empty($trimedPathinfo) ? explode('/', $trimedPathinfo) : array();
        if (isset($pathSegments[0]) && array_key_exists($pathSegments[0], Tea::$moduleMap)) {
            $this->_moduleName = $pathSegments[0];
            $this->_urlControllerName = isset($pathSegments[1]) ? $pathSegments[1] : TeaController::$config['defaultController'];
            $this->_controllerName = $this->_urlControllerName . 'Controller';
            $this->_actionName = isset($pathSegments[2]) ? $pathSegments[2] : TeaController::$config['defaultAction'];
            $this->_actionParams = array_diff($pathSegments, array($this->_moduleName, $this->_urlControllerName, $this->_actionName));
        } else {
            $this->_urlControllerName = isset($pathSegments[0]) ? $pathSegments[0] : TeaController::$config['defaultController'];
            $this->_controllerName = $this->_urlControllerName . 'Controller';
            $this->_actionName = isset($pathSegments[1]) ? $pathSegments[1] : TeaController::$config['defaultAction'];
            $this->_actionParams = array_diff($pathSegments, array($this->_urlControllerName, $this->_actionName));
        }
    }

}