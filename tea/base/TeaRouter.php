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

    /**
     * Class config.
     * @var array
     */
    public static $config = array(
        'caseInsensitive' => true,
        'routeMode' => 'path',  // 'path' or 'get'
        'routeModeGetName' => 'r',  // only available when route mode is 'get'
        'urlSuffix' => ''
    );

    /**
     * Current controller instance.
     * @var object
     */
    private $_controller;

    /**
     * Current module name.
     * @var string
     */
    private $_moduleName;

    /**
     * Current url controller name.
     * @var string
     */
    private $_urlControllerName;

    /**
     * Current controller class name.
     * @var string
     */
    private $_controllerName;

    /**
     * Current action name.
     * @var string
     */
    private $_actionName;

    /**
     * Action parameters.
     * @var array
     */
    private $_actionParams = array();

    /**
     * Arguments of method TeaRouter::route().
     * @var array
     */
    private $_routeArgs = array();

    /**
     * Constructor, set class config.
     */
    public function __construct() {
        Tea::setClassConfig(__CLASS__);
    }

    /**
     * Route and run.
     * @param array $routeArgs Used for setting route information.
     * @throws TeaException
     */
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
        $this->_controller = $rfc->newInstance();
        $method = $rfc->getMethod($this->getActionName());
        if (method_exists($this->_controller, $this->getActionName()) && $method->isPublic() && !$method->isStatic()) {
            $rfc->getMethod('onBeforeAction')->invoke($this->_controller, $this->getActionName());
            $method->invokeArgs($this->_controller, $this->getActionParams());
            $rfc->getMethod('onAfterAction')->invoke($this->_controller, $this->getActionName());
        } else {
            throw new TeaException("Action '{$this->getActionName()}' could not be accessed.");
        }
    }

    /**
     * Get current controller instance.
     * @return object
     */
    public function getController() {
        return $this->_controller;
    }

    /**
     * Get current module name.
     * @return string
     */
    public function getModuleName() {
        return $this->_moduleName;
    }

    /**
     * Get current url controller name.
     * @return string
     */
    public function getUrlControllerName() {
        return $this->_urlControllerName;
    }

    /**
     * Get current controller name.
     * @return string
     */
    public function getControllerName() {
        return $this->_controllerName;
    }

    /**
     * Get current action name.
     * @return string
     */
    public function getActionName() {
        return $this->_actionName;
    }

    /**
     * Get current action parameters.
     * @return array
     */
    public function getActionParams() {
        return $this->_actionParams;
    }

    /**
     * Import proper controller.
     */
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

    /**
     * Set route pathinfo information.
     * @param string $pathinfo pathinfo style string.
     */
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