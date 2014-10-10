<?php

/**
 * TeaController class file.
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package base
 */
class TeaController {

    /**
     * Class config.
     * @var array
     */
    public static $config = array(
        'defaultController' => 'Main',
        'defaultAction' => 'index'
    );

    /**
     * Assigned values for template.
     * @var array
     */
    private $_assignedVals = array();

    /**
     * Constructor, set class config.
     */
    public function __construct() {
        Tea::setClassConfig(__CLASS__);
    }

    /**
     * Hook method, invoking before action.
     * @param string $name Action name.
     */
    public function onBeforeAction($name) {
        if (method_exists($this, 'beforeAction')) {
            $this->beforeAction($name);
        }
    }

    /**
     * Hook method, invoking after action.
     * @param string $name Action name.
     */
    public function onAfterAction($name) {
        if (method_exists($this, 'afterAction')) {
            $this->afterAction($name);
        }
    }

    /**
     * Assign value or values array for template.
     * If number of arguments is one and the argument is an array:
     * $this->assign(array(
     *     string name1 => mixed value1,
     *     string name2 => mixed value2
     * ));
     * If number of arguments is two:
     * $this->assign(string name, mixed value);
     * @param mixed $param,... Optional arguments.
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
     * Redirect url.
     * @param mixed $redirect Url string or Tea::createUrl() parameters array.
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
     * Render template.
     * @param string $tpl Template to be rendered, dot notation path. If empty, it will detect automatically with router.
     * @param array $vals An array of variable name and variable value to be assigned.
     * @param bool $output Output or not, defaults to true.
     * @return mixed If param $output is false, it will return the rendered template string, else output it.
     * @throws TeaException
     */
    public function render($tpl = null, $vals = array(), $output = true) {
        if (is_array($vals)) {
            $vals = array_merge($this->_assignedVals, $vals);
            extract($vals);
        }
        $tplSuffix = '.php';
        $tplFile = Tea::aliasToPath($tpl) . $tplSuffix;
        $tplParts = explode('.', $tpl);
        $tplName = array_pop($tplParts);
        if (!is_file($tplFile)) {
            $router = Tea::getRouter();
            $moduleName = $router->getModuleName();
            $tplBasePathAlias = empty($moduleName) ? 'protected.view' : "module.{$moduleName}.view";
            $tplName = empty($tpl) ? $router->getActionName() : $tpl;
            $tplFile = Tea::aliasToPath($tplBasePathAlias) . DIRECTORY_SEPARATOR . $tplName . $tplSuffix;
        }
        if (is_file($tplFile)) {
            ob_start();
            include $tplFile;
            if ($output) {
                echo ob_get_clean();
            } else {
                return ob_get_clean();
            }
        } else {
            throw new TeaException("Unable to render, '{$tplName}' is not a valid template.");
        }
    }

    /**
     * A handy method of $this->render().
     * @param string $tpl Template to be rendered. If empty, it will detect automatically with router.
     * @param array $vals An array of variable name and variable value to be assigned.
     * @return string The rendered template string.
     */
    public function renderContent($tpl = null, $vals = array()) {
        return $this->render($tpl, $vals, false);
    }

}