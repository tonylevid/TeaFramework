<?php

/**
 * TeaRequest class file
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package lib
 */
class TeaRequest {
    
    /**
     * 类配置数组。
     * @var array
     */
    public static $config = array(
        'requestOrder' => 'CGP', // $_COOKIE，$_GET，$_POST的注入到$this->REQUEST_DATA顺序，从左到右，右边的将覆盖左边键名相同的值。
        'globalFilters' => array('htmlspecialchars', 'trim') // 全局过滤函数，支持回调函数。
    );
    
    public static $requestOrderMap = array(
        'C' => 'COOKIE_DATA',
        'G' => 'GET_DATA',
        'P' => 'POST_DATA'
    );
    
    /**
     * HTTP Request变量，不受影响php.ini配置项request_order的影响。
     * 数组包含了$_COOKIE，$_GET 和 $_POST 的数组。
     * @var array 
     */
    public $REQUEST_DATA;
    
    /**
     * 通过 HTTP Cookies 方式传递给当前脚本的变量的数组。
     * @var array 
     */
    public $COOKIE_DATA;

    /**
     * 通过 URL 参数传递给当前脚本的变量的数组。
     * @var array 
     */
    public $GET_DATA;

    /**
     * 通过 HTTP POST 方法传递给当前脚本的变量的数组。
     * @var array 
     */
    public $POST_DATA;

    /**
     * 通过 HTTP PUT 方法传递给当前脚本的变量的数组。
     * @var array
     */
    public $PUT_DATA;

    /**
     * 通过 HTTP DELETE 方法传递给当前脚本的变量的数组。
     * @var array
     */
    public $DELETE_DATA;
    
    /**
     * 全局过滤函数配置项值。
     * @var array 
     */
    private $_globalFilters;

    /**
     * 当前请求头中 Host: 项的内容，如果存在的话。
     * @var string
     */
    private $_host;

    /**
     * URI用来指定要访问的页面。例如 “/index.html”。
     * @var string
     */
    private $_requestUri;

    /**
     * 包含由客户端提供的、跟在真实脚本名称之后并且在查询语句（query string）之前的路径信息，如果存在的话。
     * @var string
     */
    private $_pathinfo;
    
    /**
     * 获取引导URL（包含http(s)://）。
     * @var string
     */
    private $_baseUrl;

    /**
     * 获取引导URI（不包含http(s)://）。
     * @var string
     */
    private $_baseUri;
    
    /**
     * 构造函数，加载配置并初始化。
     * @param type $filters
     */
    public function __construct() {
        Tea::setClassConfig(__CLASS__);
        $this->init();
    }
    
    /**
     * 初始化，设置相应值。
     */
    public function init() {
        if ($this->COOKIE_DATA === null) {
            $this->COOKIE_DATA = $_COOKIE;
        }
        if ($this->GET_DATA === null) {
            $this->GET_DATA = $_GET;
        }
        if ($this->POST_DATA === null) {
            $this->POST_DATA = $_POST;
        }
        if ($this->PUT_DATA === null) {
            $this->PUT_DATA = $this->getRequestMethod() === 'PUT' ? $this->getRestParams() : array();
        }
        if ($this->DELETE_DATA === null) {
            $this->DELETE_DATA = $this->getRequestMethod() === 'DELETE' ? $this->getRestParams() : array();
        }
        if ($this->REQUEST_DATA === null) {
            $this->REQUEST_DATA = array();
            $requestOrders = str_split(Tea::getConfig('TeaRequest.requestOrder'));
            foreach ($requestOrders as $str) {
                $upperStr = strtoupper($str);
                if (array_key_exists($upperStr, self::$requestOrderMap)) {
                    $mergeVarName = self::$requestOrderMap[$upperStr];
                    $this->REQUEST_DATA = array_merge($this->REQUEST_DATA, $this->{$mergeVarName});
                }
            }
        }
        $this->_globalFilters = Tea::getConfig('TeaRequest.globalFilters');
    }
    
    /**
     * 根据过滤函数过滤值。
     * @param mixed $val 需要过滤的值。
     * @param array $filters 数组包含回调函数字符串或者回调函数。
     * @return mixed 返回过滤后的值。
     */
    public function filterVal($val, $filters = array()) {
        if (is_array($filters) && !empty($filters)) {
            foreach ($filters as $filter) {
                $val = MiscHelper::filterVal($val, $filter);
            }
        }
        return $val;
    }

    /**
     * 通过键名获取$this->REQUEST_DATA（通常为$_REQUEST）的键值。
     * @param string $name $this->REQUEST_DATA的键名。
     * @param mixed $default 如果$this->REQUEST_DATA键名未设置的默认值。
     * @param mixed $filters 如果为null，则默认为配置TeaRequest.globalFilters的值。如果为数组，则数组需要包含回调函数字符串或者回调函数。需要获取原始值，可以指定为array()。
     * @return mixed
     */
    public function getRequest($name = null, $default = null, $filters = null) {
        if ($filters === null) {
            $filters = $this->_globalFilters;
        }
        if ($name === null) {
            return $this->filterVal($this->REQUEST_DATA, $filters);
        }
        return isset($this->REQUEST_DATA[$name]) ? $this->filterVal($this->REQUEST_DATA[$name], $filters) : $default;
    }
    
    /**
     * 通过键名获取$_COOKIE的键值。
     * @param string $name $_COOKIE的键名。
     * @param mixed $default 如果$_COOKIE键名未设置的默认值。
     * @param mixed $filters 如果为null，则默认为配置TeaRequest.globalFilters的值。如果为数组，则数组需要包含回调函数字符串或者回调函数。需要获取原始值，可以指定为array()。
     * @return mixed
     */
    public function getCookie($name = null, $default = null, $filters = null) {
        if ($filters === null) {
            $filters = $this->_globalFilters;
        }
        if ($name === null) {
            return $this->filterVal($this->COOKIE_DATA, $filters);
        }
        return isset($this->COOKIE_DATA[$name]) ? $this->filterVal($this->COOKIE_DATA[$name], $filters) : $default;
    }

    /**
     * 通过键名获取$_GET的键值。
     * @param string $name $_GET的键名。
     * @param mixed $default 如果$_GET键名未设置的默认值。
     * @param mixed $filters 如果为null，则默认为配置TeaRequest.globalFilters的值。如果为数组，则数组需要包含回调函数字符串或者回调函数。需要获取原始值，可以指定为array()。
     * @return mixed
     */
    public function getQuery($name = null, $default = null, $filters = null) {
        if ($filters === null) {
            $filters = $this->_globalFilters;
        }
        if ($name === null) {
            return $this->filterVal($this->GET_DATA, $filters);
        }
        return isset($this->GET_DATA[$name]) ? $this->filterVal($this->GET_DATA[$name], $filters) : $default;
    }

    /**
     * 通过键名获取$_POST的键值。
     * @param string $name $_POST的键名。
     * @param mixed $default 如果$_POST键名未设置的默认值。
     * @param mixed $filters 如果为null，则默认为配置TeaRequest.globalFilters的值。如果为数组，则数组需要包含回调函数字符串或者回调函数。需要获取原始值，可以指定为array()。
     * @return mixed
     */
    public function getPost($name = null, $default = null, $filters = null) {
        if ($filters === null) {
            $filters = $this->_globalFilters;
        }
        if ($name === null) {
            return $this->filterVal($this->POST_DATA, $filters);
        }
        return isset($this->POST_DATA[$name]) ? $this->filterVal($this->POST_DATA[$name], $filters) : $default;
    }

    /**
     * 通过键名获取$this->PUT_DATA（即HTTP PUT传递的数组）的键值。
     * @param string $name $this->PUT_DATA的键名。
     * @param mixed $default 如果$this->PUT_DATA键名未设置的默认值。
     * @param mixed $filters 如果为null，则默认为配置TeaRequest.globalFilters的值。如果为数组，则数组需要包含回调函数字符串或者回调函数。需要获取原始值，可以指定为array()。
     * @return mixed
     */
    public function getPut($name = null, $default = null, $filters = null) {
        if ($filters === null) {
            $filters = $this->_globalFilters;
        }
        if ($name === null) {
            return $this->filterVal($this->PUT_DATA, $filters);
        }
        return isset($this->PUT_DATA[$name]) ? $this->filterVal($this->PUT_DATA[$name], $filters) : $default;
    }

    /**
     * 通过键名获取$this->DELETE_DATA（即HTTP DELETE传递的数组）的键值。
     * @param string $name $this->DELETE_DATA的键名。
     * @param mixed $default 如果$this->DELETE_DATA键名未设置的默认值。
     * @param mixed $filters 如果为null，则默认为配置TeaRequest.globalFilters的值。如果为数组，则数组需要包含回调函数字符串或者回调函数。需要获取原始值，可以指定为array()。
     * @return mixed
     */
    public function getDelete($name = null, $default = null, $filters = null) {
        if ($filters === null) {
            $filters = $this->_globalFilters;
        }
        if ($name === null) {
            return $this->filterVal($this->DELETE_DATA, $filters);
        }
        return isset($this->DELETE_DATA[$name]) ? $this->filterVal($this->DELETE_DATA[$name], $filters) : $default;
    }

    /**
     * 获取HTTP PUT或者HTTP DELETE传递的数组
     * @return array
     */
    public function getRestParams() {
        $params = array();
        if (function_exists('mb_parse_str')) {
            mb_parse_str(file_get_contents('php://input'), $params);
        } else {
            parse_str(file_get_contents('php://input'), $params);
        }
        return $params;
    }

    /**
     * 获取请求类型。
     * @return string 请求类型，如GET，POST，HEAD，PUT，DELETE。
     */
    public function getRequestMethod() {
        return strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET');
    }

    /**
     * 判断请求类型是否为GET。
     * @return bool
     */
    public function isGet() {
        return $this->getRequestMethod() === 'GET' ? true : false;
    }

    /**
     * 判断请求类型是否为POST。
     * @return bool
     */
    public function isPost() {
        return $this->getRequestMethod() === 'POST' ? true : false;
    }

    /**
     * 判断请求类型是否为PUT。
     * @return bool
     */
    public function isPut() {
        return $this->getRequestMethod() === 'PUT' ? true : false;
    }

    /**
     * 判断请求类型是否为DELETE。
     * @return bool
     */
    public function isDelete() {
        return $this->getRequestMethod() === 'DELETE' ? true : false;
    }
    
    /**
     * 是否为ajax请求。
     * 注意：此方法并不完全可靠，如果使用的是jQuery或者其他主流javascript库，此方法会如期运行。
     * 如果你自定义header或不清楚的情况下可以显式的设置header的X-Requested-With为XMLHttpRequest。
     * @return bool
     */
    public function isAjax() {
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }
        return false;
    }

    /**
     * 获取完整URL链接。
     * @return string
     */
    public function getFullUrl() {
        return $this->getHttpHost() . $this->getRequestUri();
    }

    /**
     * 获取包含http(s)://的主机名。
     * @return string
     */
    public function getHttpHost() {
        if ($this->_host === null) {
            if (isset($_SERVER['HTTP_HOST'])) {
                $this->_host = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
                $this->_host .= '://' . $_SERVER['HTTP_HOST'];
            } else {
                $this->_host = 'http://localhost';
            }
        }
        return $this->_host;
    }

    /**
     * 获取执行脚本的绝对路径。
     * @return string
     */
    public function getScriptName() {
        return $_SERVER['SCRIPT_NAME'];
    }

    /**
     * 获取执行脚本的文件名，与 document root 有关。
     * @return string
     */
    public function getPhpSelf() {
        return $_SERVER['PHP_SELF'];
    }

    /**
     * 获取URL链接父级目录。
     * @return string Url directory.
     */
    public function getUrlDir() {
        return rtrim(dirname($this->getScriptName()), '/');
    }

    /**
     * 获取访问页面的URI。
     * @return string
     */
    public function getRequestUri() {
        if ($this->_requestUri === null) {
            if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // IIS
                $this->_requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
            } else if (isset($_SERVER['REQUEST_URI'])) {
                $this->_requestUri = $_SERVER['REQUEST_URI'];
                if (!empty($_SERVER['HTTP_HOST'])) {
                    if (strpos($this->_requestUri, $_SERVER['HTTP_HOST']) !== false) {
                        $this->_requestUri = preg_replace('/^\w+:\/\/[^\/]+/', '', $this->_requestUri);
                    }
                } else {
                    $this->_requestUri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $this->_requestUri);
                }
            } else if (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0 CGI
                $this->_requestUri = $_SERVER['ORIG_PATH_INFO'];
                if (!empty($_SERVER['QUERY_STRING'])) {
                    $this->_requestUri .= '?' . $_SERVER['QUERY_STRING'];
                }
            } else {
                trigger_error('Unable to determine the request URI.');
            }
        }
        return $this->_requestUri;
    }

    /**
     * 获取由客户端提供的、跟在真实脚本名称之后并且在查询语句（query string）之前的路径信息。
     * 注意pathinfo的前面是带有'/'的，如：'/main/foo/bar'。
     * @return string
     */
    public function getPathinfo() {
        if ($this->_pathinfo === null) {
            if (isset($_SERVER['PATH_INFO'])) {
                $this->_pathinfo = $_SERVER['PATH_INFO'];
            } else {
                $this->_pathinfo = str_replace($this->getScriptName(), '', $this->getPhpSelf());
            }
        }
        return $this->_pathinfo;
    }
    
    /**
     * 获取查询字符串之前的URL。
     * @return string
     */
    public function getBasePathUrl() {
        return $this->getBaseUrl() . $this->getPathinfo();
    }

    /**
     * 获取查询字符串。
     * @return string
     */
    public function getQueryStr() {
        return isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    }

    /**
     * 获取引导URI（不包含http(s)://），如果有文件名，引导URI也将包含文件名。
     * 如请求URL为http://localhost/index.php/foo/bar，引导URI的内容将为/index.php。
     * @return string 引导URI。
     */
    public function getBaseUri() {
        if ($this->_baseUri === null) {
            $this->_baseUri = rtrim(str_replace($this->getHttpHost(), '', $this->getBaseUrl()), '/');
        }
        return $this->_baseUri;
    }

    /**
     * 获取引导URL（包含http(s)://），如果有文件名，引导URL也将包含文件名。
     * 如请求URL为http://localhost/index.php/foo/bar，引导URL的内容将为http://localhost/index.php。
     * @return string 引导URL。
     */
    public function getBaseUrl() {
        if ($this->_baseUrl === null) {
            $file = basename($this->getScriptName());
            if (preg_match('/^' . preg_quote($this->getScriptName(), '/') . '/', $this->getRequestUri())) {
                $this->_baseUrl = $this->getHttpHost() . $this->getScriptName();
            } else {
                $this->_baseUrl = $this->getHttpHost() . str_replace($file, '', $this->getScriptName());
            }
        }
        return rtrim($this->_baseUrl, '/');
    }

}