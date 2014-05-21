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
class TeaController extends TeaCommon {

    public static $config = array(
        'defaultController' => 'Main',
        'defaultAction' => 'index'
    );

    /**
     * Assigned values for template.
     * @var array
     */
    private $_assignedVals = array();

    public function __construct() {
        $this->setClassConfig(__CLASS__);
    }

    /**
     * Hook method, invoking before action.
     */
    public function beforeAction() {

    }

    /**
     * Hook method, invoking after action.
     */
    public function afterAction() {

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
     * Render template.
     * @param string $tpl Template to be rendered. If empty, it will detect automatically.
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
        $router = $this->getRouter();
        $moduleName = $router->getModuleName();
        $tplBasePathAlias = empty($moduleName) ? 'protected.view' : "module.{$moduleName}.view";
        $tplName = empty($tpl) ? $router->getActionName() : $tpl;
        $tplFile = $this->aliasToPath($tplBasePathAlias) . DIRECTORY_SEPARATOR . $tplName . '.php';
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

}