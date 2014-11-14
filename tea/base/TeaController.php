<?php

/**
 * TeaController类文件。
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
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
            $this->_assignedVals = $args[0];
        } else if ($argNum === 2) {
            $this->_assignedVals[$args[0]] = $args[1];
        } else {
            throw new TeaException('Unknown assign error.');
        }
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
    }

    /**
     * 输出json数据。
     * @param mixed $data 输出的数据，对应键名为'data'。
     * @param mixed $msg 输出的消息提示，对应键名为'msg'。
     * @param mixed $code 输出的状态代码，对应键名为'code'。
     */
    public function ajaxReturn($data, $msg = null, $code = 0) {
        header('Content-type: application/json');
        $arr = array(
            'data' => $data,
            'msg' => $msg,
            'code' => $code
        );
        echo json_encode($arr);
        exit();
    }

    /**
     * 渲染模板。
     * @param string $tpl 需要被渲染的模板，圆点标记路径。如果为空，则将根据当前路由自动检测。
     * @param array $vals 推送到模板的变量映射数组。
     * @param string $theme 主题文件夹名。
     * @param bool $output 是否输出，默认为true。
     * @return mixed 如果不输出，将返回被渲染后的模板字符串，否则输出被渲染后的模板。
     * @throws TeaException
     */
    public function render($tpl = null, $vals = array(), $theme = null, $output = true) {       
        $tplSuffix = self::$config['tplSuffix'];
        $tplFile = Tea::aliasToPath($tpl) . $tplSuffix;
        $tplParts = explode('.', $tpl);
        $tplName = array_pop($tplParts);
        $router = Tea::getRouter();
        $moduleName = $router->getModuleName();
        $tplBasePathAlias = empty($moduleName) ? 'protected.view' : "module.{$moduleName}.view";
        $theme = empty($theme) ? self::$config['theme'] : $theme;
        if (!empty($theme)) {
            $tplBasePathAlias = $tplBasePathAlias . '.' . $theme;
        }
        // 尝试自动查找模板
        // 第一种：url控制器名_url动作名
        if (!is_file($tplFile)) {
            $tplName = empty($tpl) ? $router->getUrlControllerName() . '_' . $router->getUrlActionName() : $tpl;
            $tplFile = Tea::aliasToPath($tplBasePathAlias) . DIRECTORY_SEPARATOR . $tplName . $tplSuffix;
        }
        // 第二种：小写url控制器名_小写url动作名
        if (!is_file($tplFile)) {
            $tplName = empty($tpl) ? strtolower($router->getUrlControllerName()) . '_' . strtolower($router->getUrlActionName()) : $tpl;
            $tplFile = Tea::aliasToPath($tplBasePathAlias) . DIRECTORY_SEPARATOR . $tplName . $tplSuffix;
        }
        // 第三种：url控制器名/url动作名
        if (!is_file($tplFile)) {
            $tplName = empty($tpl) ? $router->getUrlControllerName() . DIRECTORY_SEPARATOR . $router->getUrlActionName() : $tpl;
            $tplFile = Tea::aliasToPath($tplBasePathAlias) . DIRECTORY_SEPARATOR . $tplName . $tplSuffix;
        }
        // 第四种：小写url控制器名/url动作名
        if (!is_file($tplFile)) {
            $tplName = empty($tpl) ? strtolower($router->getUrlControllerName()) . DIRECTORY_SEPARATOR . strtolower($router->getUrlActionName()) : $tpl;
            $tplFile = Tea::aliasToPath($tplBasePathAlias) . DIRECTORY_SEPARATOR . $tplName . $tplSuffix;
        }
        // 支持 自定义路径/自定义模板名 和 自定义路径.自定义模板名 两种写法来指定渲染模板
        if (!is_file($tplFile) && !empty($tpl)) {
            $tplFile = Tea::aliasToPath($tplBasePathAlias . '.' . $tpl) . $tplSuffix;
        }       
        if (is_file($tplFile)) {
            // 防止释放变量覆盖参数$tplFile, $output
            $varUniqTplFile_1qas43dg6vb = $tplFile;
            $varUniqOutput_3f8gk08sj7v = $output;
            ob_start();
            // 释放变量
            if (is_array($vals)) {
                $vals = array_merge($this->_assignedVals, $vals);
                extract($vals);
            }
            include $varUniqTplFile_1qas43dg6vb;
            if ($varUniqOutput_3f8gk08sj7v) {
                echo ob_get_clean();
            } else {
                return ob_get_clean();
            }
        } else {
            $tplFileFolder = dirname($tplFile);
            throw new TeaException("Unable to render, '{$tplName}' is not a valid template. File folder path: '{$tplFileFolder}'.");
        }
    }

    /**
     * $this->render()的便捷方法。
     * @param string $tpl 需要被渲染的模板，圆点标记路径。如果为空，则将根据当前路由自动检测。
     * @param array $vals 推送到模板的变量映射数组。
     * @param string $theme 主题文件夹。
     * @return string 返回被渲染后的模板字符串。
     */
    public function getRenderContent($tpl = null, $vals = array(), $theme = null) {
        return $this->render($tpl, $vals, $theme, false);
    }

}