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
class TeaRouter extends TeaCommon {

    /**
     * Class config.
     * @var array
     */
    public static $config = array(
        'caseInsensitive' => true,
        'routeMode' => 'auto',  // 'path', 'get' or 'auto'
        'routeModeGetName' => 'r',  // only available when route mode is 'get'
        'urlSuffix' => '',
        'openRouteRules' => false,
        'routeRules' => array() // regexp => route array
    );

    /**
     * Current controller instance.
     * @var object
     */
    private $_controller;

    /**
     * Current url module name.
     * @var string
     */
    private $_urlModuleName;

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
     * Current url action name.
     * @var string
     */
    private $_urlActionName;

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
     * Get current url module name.
     * @return string
     */
    public function getUrlModuleName() {
        return $this->_urlModuleName;
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
     * Get current url action name.
     * @return string
     */
    public function getUrlActionName() {
        return $this->_urlActionName;
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
     * Set module name, controller name, action name and action params.
     */
    protected function setRouteInfo() {
        if (!empty($this->_routeArgs)) {
            $this->setRoutePathinfo(implode('/', $this->_routeArgs));
            return null;
        }
        $request = $this->loadLib('TeaRequest');
        $queryPathinfo = $request->getQuery(self::$config['routeModeGetName']);
        $pathinfo = $request->getPathinfo();
        switch (self::$config['routeMode']) {
            case 'auto':
                if (!empty($queryPathinfo)) {
                    $this->setRoutePathinfo($queryPathinfo);
                    $this->setRouteRules($queryPathinfo);
                } else {
                    $this->setRoutePathinfo($pathinfo);
                    $this->setRouteRules($pathinfo);
                }
                break;
            case 'path':
                $this->setRoutePathinfo($pathinfo);
                $this->setRouteRules($pathinfo);
                break;
            case 'get':
                $this->setRoutePathinfo($queryPathinfo);
                $this->setRouteRules($queryPathinfo);
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
        $trimedPathinfo = preg_replace('/' . preg_quote(self::$config['urlSuffix']) . '$/', '', $pathinfo);
        $trimedPathinfo = ltrim(rtrim($trimedPathinfo, '/'), '/');
        $pathSegments = !empty($trimedPathinfo) ? explode('/', $trimedPathinfo) : array();
        if (isset($pathSegments[0]) && $this->isModule($pathSegments[0])) {
            $this->_urlModuleName = $pathSegments[0];
            $this->_moduleName = $this->getModuleNameBySegment($pathSegments[0]);
            array_shift($pathSegments);
        }
        $this->_urlControllerName = isset($pathSegments[0]) ? $pathSegments[0] : TeaController::$config['defaultController'];
        $this->_controllerName = $this->getControllerNameBySegment($this->_urlControllerName);
        // after having determined module and controller, then import proper controller.
        $this->importController();
        // set action name and action parameters.
        $this->_urlActionName = isset($pathSegments[1]) ? $pathSegments[1] : TeaController::$config['defaultAction'];
        $this->_actionName = $this->getActionNameBySegment($this->_controllerName, $this->_urlActionName);
        $this->_actionParams = array_diff($pathSegments, array($this->_urlControllerName, $this->_urlActionName));
    }

    /**
     * Set route rules.
     * This method must call after TeaRouter::setRoutePathinfo().
     * @param string $pathinfo pathinfo style string.
     */
    private function setRouteRules($pathinfo) {
        if (self::$config['openRouteRules']) {
            $routeRules = self::$config['routeRules'];
            foreach ($routeRules as $rule => $route) {
                $route = rtrim(ltrim($route, '/'), '/');
                if (!empty($route) && preg_match($rule, $pathinfo)) {
                    $pathinfo = preg_replace($rule, $route, $pathinfo);
                    $this->setRoutePathinfo($pathinfo);
                }
            }
        }
    }

    /**
     * Import proper controller.
     */
    private function importController() {
        $moduleName = $this->getModuleName();
        $controllerName = $this->getControllerName();
        if (empty($moduleName)) {
            Tea::import("protected.controller.{$controllerName}");
        } else {
            Tea::import("module.{$moduleName}.controller.{$controllerName}");
        }
    }

    /**
     * Check whether segment is a module.
     * @param string $segment Segment string.
     * @return bool
     */
    private function isModule($segment) {
        $moduleMap = Tea::$moduleMap;
        if (self::$config['caseInsensitive']) {
            $segment = strtolower($segment);
            $moduleMap = $this->getLcModuleMap();
        }
        if (array_key_exists($segment, $moduleMap)) {
            return true;
        }
        return false;
    }

    /**
     * Get lowercased module map.
     * @param bool $isNameMap Whether return module name array map.
     * @param bool $moduleToLower If it is true, it will return moduleName => lowercasedModuleName array map, else return lowercasedModuleName => moduleName array map.
     * @return array
     */
    private function getLcModuleMap($isNameMap = false, $moduleToLower = false) {
        $lcModuleMap = array();
        foreach (Tea::$moduleMap as $name => $path) {
            if ($isNameMap) {
                if ($moduleToLower) {
                    $lcModuleMap[$name] = strtolower($name);
                } else {
                    $lcModuleMap[strtolower($name)] = $name;
                }
            } else {
                $lcModuleMap[strtolower($name)] = $path;
            }
        }
        return $lcModuleMap;
    }

    /**
     * Get module name by module segment.
     * @param string $segment Segment string.
     * @return string
     */
    private function getModuleNameBySegment($segment) {
        $moduleName = $segment;
        if (self::$config['caseInsensitive']) {
            $lcModuleNameMap = $this->getLcModuleMap(true);
            if (isset($lcModuleNameMap[strtolower($segment)])) {
                $moduleName = $lcModuleNameMap[strtolower($segment)];
            }
        }
        return $moduleName;
    }

    /**
     * Get controller name by controller segment.
     * @param string $segment Segment string.
     * @return string
     */
    private function getControllerNameBySegment($segment) {
        $controllerName = $segment;
        if (self::$config['caseInsensitive']) {
            $controllerName = ucfirst(strtolower($segment)) . 'Controller';
        }
        return $controllerName;
    }

    /**
     * Get action name by action segment.
     * @param mixed $controllerName Controller name or controller instance.
     * @param string $segment Segment string.
     * @return string
     */
    private function getActionNameBySegment($controllerName, $segment) {
        $actionName = $segment;
        $methods = get_class_methods($controllerName);
        if (self::$config['caseInsensitive']) {
            $lcMethodNameMap = array();
            if (is_array($methods) && !empty($methods)) {
                foreach ($methods as $method) {
                    $lcMethodNameMap[strtolower($method)] = $method;
                }
            }
            if (isset($lcMethodNameMap[strtolower($segment)])) {
                $actionName = $lcMethodNameMap[strtolower($segment)];
            }
        }
        return $actionName;
    }

}