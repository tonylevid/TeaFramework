<?php

/**
 * TeaController类文件。
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.teaframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.teaframework.com/license/
 * @package base
 */
class TeaController {

    /**
     * 类配置数组。
     * @var array
     */
    public static $config = array(
        'defaultController' => 'Main', // 默认控制器。
        'defaultAction' => 'index', // 默认动作。
        'tplSuffix' => '.php', // 模板后缀。
        'theme' => null // 主题文件夹名
    );

    /**
     * 模板已推送变量映射数组。
     * @var array
     */
    private $_assignedVals = array();

    /**
     * 构造函数，加载配置。
     */
    public function __construct() {
        Tea::setClassConfig(__CLASS__);
    }

    /**
     * 钩子函数，在action前执行，在控制器里声明beforeAction方法即可。
     * @param string $name 当前action名称。
     */
    public function onBeforeAction($name) {
        if (method_exists($this, 'beforeAction')) {
            $this->beforeAction($name);
        }
    }

    /**
     * 钩子函数，在action后执行，在控制器里声明afterAction方法即可。
     * @param string $name 当前action名称。
     */
    public function onAfterAction($name) {
        if (method_exists($this, 'afterAction')) {
            $this->afterAction($name);
        }
    }

    /**
     * 钩子函数，在action无法找到时执行，在控制器里声明emptyAction方法即可。
     * @param string $name 当前action名称。
     */
    public function onEmptyAction($name) {
        if (method_exists($this, 'emptyAction')) {
            $this->emptyAction($name);
        } else {
            throw new TeaException("Action '{$name}' does not exist.");
        }
    }

    /**
     * 为模板推送变量。
     * 如果只有一个参数，且第一个参数为数组：
     * $this->assign(array(
     *     string name1 => mixed value1,
     *     string name2 => mixed value2
     * ));
     * 如果有两个参数:
     * $this->assign(string name, mixed value);
     * @param mixed $param,... 可变长度参数。
     * @throws TException
     */
    public function assign() {
        $argNum = func_num_args();
        $args = func_get_args();
        if ($argNum === 1 && is_array($args[0])) {
            $this->_assignedVals = array_merge($this->_assignedVals, $args[0]);
        } else if ($argNum === 2) {
            $this->_assignedVals[$args[0]] = $args[1];
        } else {
            throw new TeaException('Unknown assign error.');
        }
    }
    
    /**
     * 获取已推送的变量。
     * @return array
     */
    public function getAssignedVals() {
        return $this->_assignedVals;
    }

    /**
     * 重定向url。
     * @param mixed $redirect url字符串或者Tea::createUrl()的参数数组。
     */
    public function redirect($redirect) {
        $redirectUrl = null;
        if (is_string($redirect)) {
            $redirectUrl = $redirect;
        } else if (is_array($redirect)) {
            $redirectUrl = call_user_func_array('Tea::createUrl', $redirect);
        }
        header("Location: {$redirectUrl}");
        $this->onAfterAction(Tea::getRouter()->getActionName());
        exit();
    }

    /**
     * 输出json数据。
     * @param mixed $data 如果参数个数为3，则为输出的数据，对应键名为'data'。如果参数个数为1，且为数组，则为键值对应的数组。
     * @param mixed $msg 如果参数个数为3，则为输出的消息提示，对应键名为'msg'。
     * @param mixed $code 如果参数个数为3，则为输出的状态代码，对应键名为'code'。
     */
    public function ajaxReturn() {
        header('Content-type: application/json');
        $args = func_get_args();
        if (func_num_args() === 3) {
            $arr = array(
                'data' => $args[0],
                'msg' => $args[1],
                'code' => $args[2]
            );
        } else if (func_num_args() === 1 && is_array($args[0])) {
            $arr = array(
                'data' => $args[0]['data'],
                'msg' => $args[0]['msg'],
                'code' => $args[0]['code']
            );
        } else {
            $arr = array(
                'data' => null,
                'msg' => null,
                'code' => 0
            );
        }
        echo json_encode($arr);
        $this->onAfterAction(Tea::getRouter()->getActionName());
        exit();
    }

    /**
     * 输出渲染模板。
     * @param string $tpl 需要被渲染的模板，圆点标记路径。如果为空，则将根据当前路由自动检测。
     * 注意：此方法如果在模板中用来包含公共模板，请为$tpl赋值，以防止路由自动检测渲染相同模板，而引起无限循环照成页面为空。
     * @param array $vals 推送到模板的变量映射数组。
     * @param string $theme 主题文件夹名。
     * @throws TeaException
     */
    public function render($tpl = null, $vals = array(), $theme = null) {
        $tplFile = $this->getTplFile($tpl, $theme);
        if (is_file($tplFile)) {
            // 防止释放变量覆盖参数$tplFile, $output
            $varUniqTplFile_1qas43dg6vb = $tplFile;
            ob_start();
            // 释放变量
            if (is_array($vals)) {
                $vals = array_merge($this->_assignedVals, $vals);
                extract($vals);
            }
            include $varUniqTplFile_1qas43dg6vb;
            echo ob_get_clean();
        } else {
            throw new TeaException("Unable to render, '{$tplFile}' is not a valid template.");
        }
    }

    /**
     * 获取渲染模板内容
     * @param string $tpl 需要被渲染的模板，圆点标记路径。如果为空，则将根据当前路由自动检测。
     * 注意：此方法如果在模板中用来包含公共模板，请为$tpl赋值，以防止路由自动检测渲染相同模板，而引起无限循环照成页面为空。
     * @param array $vals 推送到模板的变量映射数组。
     * @param string $theme 主题文件夹。
     * @return string 返回被渲染后的模板字符串。
     * @throws TeaException
     */
    public function getRenderContent($tpl = null, $vals = array(), $theme = null) {
        $tplFile = $this->getTplFile($tpl, $theme);
        if (is_file($tplFile)) {
            // 防止释放变量覆盖参数$tplFile
            $varUniqTplFile_1qas43dg6vb = $tplFile;
            //ob_start();
            // 释放变量
            if (is_array($vals)) {
                $vals = array_merge($this->_assignedVals, $vals);
                extract($vals);
            }
            include $varUniqTplFile_1qas43dg6vb;
            return ob_get_clean();
        } else {
            throw new TeaException("Unable to render, '{$tplFile}' is not a valid template.");
        }
    }
    
    /**
     * 获取模板真实路径。
     * @param string $tpl 需要被渲染的模板，圆点标记路径。如果为空，则将根据当前路由自动检测。
     * @param string $theme 主题文件夹。
     * @return string 模板真实路径。
     */
    protected function getTplFile($tpl = null, $theme = null) {
        $tplSuffix = Tea::getConfig('TeaController.tplSuffix');
        $tplFile = Tea::aliasToPath($tpl) . $tplSuffix;
        $router = Tea::getRouter();
        $moduleName = $router->getModuleName();
        $tplBasePathAlias = empty($moduleName) ? 'protected.view' : "module.{$moduleName}.view";
        $theme = empty($theme) ? Tea::getConfig('TeaController.theme') : $theme;
        if (!empty($theme)) {
            $tplBasePathAlias = $tplBasePathAlias . '.' . $theme;
        }
        // 尝试自动查找模板
        if (!is_file($tplFile)) {
            $tryTplNames = $this->getTryTplNames($tpl, $router);
            foreach ($tryTplNames as $tplName) {
                $tryTplFile = Tea::aliasToPath($tplBasePathAlias) . DIRECTORY_SEPARATOR . $tplName . $tplSuffix;
                if (is_file($tryTplFile)) {
                    $tplFile = $tryTplFile;
                }
            }
        }
        return $tplFile;
    }
    
    /**
     * 获取尝试模板名
     * @param string $tpl 输入的模板名
     * @param TeaRouter $router 当前运行期TeaRouter类实例。
     * @return array 尝试的模板名
     */
    protected function getTryTplNames($tpl, $router) {
        $tryTplNames = array();
        // $tpl为空时自动查找
        if (empty($tpl)) {
            // 第一种：url控制器名_url动作名
            $tryTplNames[] = $router->getUrlControllerName() . '_' . $router->getUrlActionName();
            // 第二种：小写url控制器名_小写url动作名
            $tryTplNames[] = strtolower($router->getUrlControllerName()) . '_' . strtolower($router->getUrlActionName());
            // 第三种：url控制器名/url动作名
            $tryTplNames[] = $router->getUrlControllerName() . DIRECTORY_SEPARATOR . $router->getUrlActionName();
            // 第四种：小写url控制器名/小写url动作名
            $tryTplNames[] = strtolower($router->getUrlControllerName()) . DIRECTORY_SEPARATOR . strtolower($router->getUrlActionName());
        }
        // $tpl不为空时自动查找
        if (!empty($tpl)) {
            // 第一种：$tpl为 自定义文件夹/自定义模板名 和 自定义文件夹.自定义模板名
            $tryTplNames[] = Tea::aliasToPath($tpl);
            // 第二种：url控制器名/$tpl
            $tryTplNames[] = $router->getUrlControllerName() . DIRECTORY_SEPARATOR . $tpl;
            // 第三种：小写url控制器名/$tpl
            $tryTplNames[] = strtolower($router->getUrlControllerName()) . DIRECTORY_SEPARATOR . $tpl;
        }
        return $tryTplNames;
    }

}