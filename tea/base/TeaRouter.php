<?php

/**
 * TeaRouter类文件。
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package base
 */
class TeaRouter {

    /**
     * 类配置数组。
     * @var array
     */
    public static $config = array(
        'caseInsensitive' => true, // 是否开启大小写不敏感。
        'routeMode' => 'auto',  // 路由模式，共三种'path', 'get' 和 'auto'。
        'routeModeGetName' => 'r',  // 路由参数名，仅当路由模式为'get'时有效。
        'urlSuffix' => '', // url后缀。
        'openRouteRules' => false, // 是否开启路由规则。
        'routeRules' => array() // 路由规则数组，数组格式：array(regexp => route, ...)
    );

    /**
     * 当前控制器实例。
     * @var object
     */
    private $_controller;

    /**
     * 当前url模块名。
     * @var string
     */
    private $_urlModuleName;

    /**
     * 当前模块名。
     * @var string
     */
    private $_moduleName;

    /**
     * 当前url控制器名。
     * @var string
     */
    private $_urlControllerName;

    /**
     * 当前控制器名。
     * @var string
     */
    private $_controllerName;

    /**
     * 当前url动作名。
     * @var string
     */
    private $_urlActionName;

    /**
     * 当前动作名。
     * @var string
     */
    private $_actionName;

    /**
     * 动作接收参数。
     * @var array
     */
    private $_actionParams = array();

    /**
     * TeaRouter::route()的路由参数值。
     * @var array
     */
    private $_routeArgs = array();

    /**
     * 构造函数，加载配置。
     */
    public function __construct() {
        Tea::setClassConfig(__CLASS__);
    }

    /**
     * 路由并运行程序。
     * @param array $routeArgs 手动设置的路由参数。
     * @throws TeaException
     */
    public function route($routeArgs = array()) {
        $this->_routeArgs = $routeArgs;
        $this->setRoute();
        if (!class_exists($this->getControllerName())) {
            throw new TeaException("Controller '{$this->getControllerName()}' does not exist.");
        }
        $rfc = new ReflectionClass($this->getControllerName());
        if (!$rfc->isSubClassOf('TeaController')) {
            throw new TeaException("Controller '{$this->getControllerName()}' must extend 'TeaController'.");
        }
        $this->_controller = $rfc->newInstance();
        if (method_exists($this->_controller, $this->getActionName())) {
            $method = $rfc->getMethod($this->getActionName());
            if ($method->isPublic() && !$method->isStatic()) {
                $rfc->getMethod('onBeforeAction')->invoke($this->_controller, $this->getActionName());
                $method->invokeArgs($this->_controller, $this->getActionParams());
                $rfc->getMethod('onAfterAction')->invoke($this->_controller, $this->getActionName());
            } else {
                throw new TeaException("Action '{$this->getActionName()}' could not be accessed.");
            }
        } else {
            $rfc->getMethod('onBeforeAction')->invoke($this->_controller, $this->getActionName());
            $rfc->getMethod('onEmptyAction')->invoke($this->_controller, $this->getActionName());
            $rfc->getMethod('onAfterAction')->invoke($this->_controller, $this->getActionName());
        }
    }

    /**
     * 获取当前控制器实例。
     * @return object
     */
    public function getController() {
        return $this->_controller;
    }

    /**
     * 获取当前url模块名。
     * @return string
     */
    public function getUrlModuleName() {
        return $this->_urlModuleName;
    }

    /**
     * 获取当前模块名。
     * @return string
     */
    public function getModuleName() {
        return $this->_moduleName;
    }

    /**
     * 获取当前url控制器名。
     * @return string
     */
    public function getUrlControllerName() {
        return $this->_urlControllerName;
    }

    /**
     * 获取当前控制器名。
     * @return string
     */
    public function getControllerName() {
        return $this->_controllerName;
    }

    /**
     * 获取当前url动作名。
     * @return string
     */
    public function getUrlActionName() {
        return $this->_urlActionName;
    }

    /**
     * 获取当前动作名。
     * @return string
     */
    public function getActionName() {
        return $this->_actionName;
    }

    /**
     * 获取动作接收参数。
     * @return array
     */
    public function getActionParams() {
        return $this->_actionParams;
    }

    /**
     * 设置模块名，控制器名，动作名和动作接收参数。
     */
    protected function setRoute() {
        if (!empty($this->_routeArgs)) {
            $routeArgsPathinfo = implode('/', $this->_routeArgs);
            $this->setRouteInfo($routeArgsPathinfo);
            return null;
        }
        $request = Tea::$request;
        $queryPathinfo = $request->getQuery(Tea::getConfig('TeaRouter.routeModeGetName'));
        $pathinfo = $request->getPathinfo();
        switch (Tea::getConfig('TeaRouter.routeMode')) {
            case 'auto':
                $routePathinfo = !empty($queryPathinfo) ? $queryPathinfo : $pathinfo;
                break;
            case 'path':
                $routePathinfo = $pathinfo;
                break;
            case 'get':
                $routePathinfo = $queryPathinfo;
                break;
            default:
                throw new TeaException('Unable to determine route mode ' . Tea::getConfig('TeaRouter.routeMode') . '.');
                break;
        }
        $this->setRouteInfo($routePathinfo);
    }

    /**
     * 设置路由的pathinfo和路由规则。
     */
    private function setRouteInfo($pathinfo) {
        $this->setRoutePathinfo($pathinfo);
        $this->setRouteRules($pathinfo);
        // 在检测模块和控制器后，导入合适的控制器。
        $this->importController();
    }

    /**
     * 设置路由的pathinfo信息。
     * @param string $pathinfo pathinfo风格的字符串。
     */
    private function setRoutePathinfo($pathinfo) {
        $trimedPathinfo = preg_replace('/' . preg_quote(Tea::getConfig('TeaRouter.urlSuffix'), '/') . '$/', '', $pathinfo);
        $trimedPathinfo = ltrim(rtrim($trimedPathinfo, '/'), '/');
        $pathSegments = !empty($trimedPathinfo) ? array_filter(explode('/', $trimedPathinfo)) : array();
        if (isset($pathSegments[0]) && $this->isModule($pathSegments[0])) {
            $this->_urlModuleName = $pathSegments[0];
            $this->_moduleName = $this->getModuleNameBySegment($pathSegments[0]);
            array_shift($pathSegments);
        }
        $this->_urlControllerName = isset($pathSegments[0]) ? $pathSegments[0] : TeaController::$config['defaultController'];
        $this->_controllerName = $this->getControllerNameBySegment($this->_urlControllerName);
        // set action name and action parameters.
        $this->_urlActionName = isset($pathSegments[1]) ? $pathSegments[1] : TeaController::$config['defaultAction'];
        $this->_actionName = $this->getActionNameBySegment($this->_controllerName, $this->_urlActionName);
        $this->_actionParams = array_diff($pathSegments, array($this->_urlControllerName, $this->_urlActionName));
    }

    /**
     * 设置路由规则。
     * 此方法必须在TeaRouter::setRoutePathinfo()后调用。
     * @param string $pathinfo pathinfo风格的字符串。
     */
    private function setRouteRules($pathinfo) {
        if (Tea::getConfig('TeaRouter.openRouteRules')) {
            $routeRules = Tea::getConfig('TeaRouter.routeRules');
            foreach ($routeRules as $rule => $route) {
                if (!empty($route) && preg_match($rule, $pathinfo)) {
                    $replacedPathinfo = null;
                    if (is_string($route)) {
                        $replacedPathinfo = preg_replace($rule, $route, $pathinfo);
                    } else if (is_callable($route)) {
                        $replacedPathinfo = preg_replace_callback($rule, $route, $pathinfo);
                    }
                    if (!empty($replacedPathinfo)) {
                        $this->setRoutePathinfo($replacedPathinfo);
                    }
                }
            }
        }
    }

    /**
     * 导入合适的控制器。
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
     * 检测字符串是否为模块。
     * @param string $segment 被检测字符串。
     * @return bool
     */
    private function isModule($segment) {
        $moduleMap = Tea::$moduleMap;
        if (Tea::getConfig('TeaRouter.caseInsensitive')) {
            $segment = strtolower($segment);
            $moduleMap = $this->getLcModuleMap();
        }
        if (array_key_exists($segment, $moduleMap)) {
            return true;
        }
        return false;
    }

    /**
     * 获取小写字母化的模块映射数组。
     * @param bool $isNameMap 是否返回模块名称小写化的映射数组，true则根据$moduleToLower返回模块名映射数组，false则返回array(小写模块名 => 模块路径)映射数组。
     * @param bool $moduleToLower 如果为true，返回array(模块名 => 小写模块名)映射数组，为false返回array(小写模块名 => 模块名)映射数组。
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
     * 根据字符串获取正确的模块名。
     * @param string $segment 被检测的字符串。
     * @return string
     */
    private function getModuleNameBySegment($segment) {
        $moduleName = $segment;
        if (Tea::getConfig('TeaRouter.caseInsensitive')) {
            $lcModuleNameMap = $this->getLcModuleMap(true);
            if (isset($lcModuleNameMap[strtolower($segment)])) {
                $moduleName = $lcModuleNameMap[strtolower($segment)];
            }
        }
        return $moduleName;
    }

    /**
     * 根据字符串获取正确的控制器名。
     * @param string $segment 被检测的字符串。
     * @return string
     */
    private function getControllerNameBySegment($segment) {
        $controllerName = $segment;
        if (Tea::getConfig('TeaRouter.caseInsensitive')) {
            $controllerName = ucfirst(strtolower($segment)) . 'Controller';
        } else {
            $controllerName = $segment . 'Controller';
        }
        return $controllerName;
    }

    /**
     * 根据字符串获取正确的动作名。
     * @param mixed $controllerName 控制器名或者控制器实例。
     * @param string $segment 被检测的字符串。
     * @return string
     */
    private function getActionNameBySegment($controllerName, $segment) {
        $actionName = $segment;
        $methods = get_class_methods($controllerName);
        if (Tea::getConfig('TeaRouter.caseInsensitive')) {
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