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
     * Url host string.
     * @var string
     */
    private $_host;

    /**
     * Request uri string.
     * @var string
     */
    private $_requestUri;

    /**
     * Pathinfo string.
     * @var string
     */
    private $_pathinfo;

    /**
     * Http PUT parameters.
     * @var array
     */
    private $_PUT;

    /**
     * Http DELETE parameters.
     * @var array
     */
    private $_DELETE;

    /**
     * Get $_GET value by name.
     * @param string $name Key in $_GET.
     * @param mixed $default Default value for $name if not set.
     * @return mixed $_GET value.
     */
    public function getQuery($name, $default = null) {
        return isset($_GET[$name]) ? $_GET[$name] : $default;
    }

    /**
     * Get $_POST value by name.
     * @param string $name Key in $_POST.
     * @param mixed $default Default value for $name if not set.
     * @return mixed $_POST value.
     */
    public function getPost($name, $default = null) {
        return isset($_POST[$name]) ? $_POST[$name] : $default;
    }

    /**
     * Get PUT value by name.
     * @param string $name Key in $this->_PUT.
     * @param mixed $default Default value for $name if not set.
     * @return mixed PUT value.
     */
    public function getPut($name, $default = null) {
        if ($this->_PUT === null) {
            $this->_PUT = $this->getRequestMethod() === 'PUT' ? $this->getRestParams() : array();
        }
        return isset($this->_PUT[$name]) ? $this->_PUT[$name] : $default;
    }

    /**
     * Get DELETE value by name.
     * @param string $name Key in $this->_DELETE.
     * @param mixed $default Default value for $name if not set.
     * @return mixed DELETE value.
     */
    public function getDelete($name, $default = null) {
        if ($this->_DELETE === null) {
            $this->_DELETE = $this->getRequestMethod() === 'DELETE' ? $this->getRestParams() : array();
        }
        return isset($this->_DELETE[$name]) ? $this->_DELETE[$name] : $default;
    }

    /**
     * Get PUT or DELETE parameters
     * @return array PUT or DELETE parameters
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
     * Get request method.
     * @return string Request method, such as GET, POST, HEAD, PUT, DELETE.
     */
    public function getRequestMethod() {
        return strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET');
    }

    /**
     * Get full url string.
     * @return string Full url.
     */
    public function getFullUrl() {
        return $this->getHttpHost() . $this->getRequestUri();
    }

    /**
     * Get url host string.
     * @return string Url host.
     */
    public function getHttpHost() {
        if ($this->_host === null) {
            if (isset($_SERVER['HTTP_HOST'])) {
                $this->_host = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
                $this->_host .= '://' . $_SERVER['HTTP_HOST'];
            } else {
                $this->_host = 'http://localhost/';
            }
        }
        return $this->_host;
    }

    public function getScriptName() {
        return $_SERVER['SCRIPT_NAME'];
    }

    public function getPhpSelf() {
        return $_SERVER['PHP_SELF'];
    }

    /**
     * Get url directory.
     * @return string Url directory.
     */
    public function getUrlDir() {
        return dirname($this->getScriptName());
    }

    /**
     * Get request uri string.
     * @return string Request uri.
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
     * Get query string.
     * @return string Uri query string.
     */
    public function getQueryStr() {
        return isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    }

}