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
     * Base url (with http(s)://).
     * @var string
     */
    private $_baseUrl;

    /**
     * Base uri (without http(s)://).
     * @var string
     */
    private $_baseUri;

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
     * Get REQUEST_METHOD.
     * @return string Request method, such as GET, POST, HEAD, PUT, DELETE.
     */
    public function getRequestMethod() {
        return strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET');
    }

    /**
     * Check whether request method is GET.
     * @return bool
     */
    public function isGet() {
        return $this->getRequestMethod() === 'GET' ? true : false;
    }

    /**
     * Check whether request method is POST.
     * @return bool
     */
    public function isPost() {
        return $this->getRequestMethod() === 'POST' ? true : false;
    }

    /**
     * Check whether request method is PUT.
     * @return bool
     */
    public function isPut() {
        return $this->getRequestMethod() === 'PUT' ? true : false;
    }

    /**
     * Check whether request method is DELETE.
     * @return bool
     */
    public function isDelete() {
        return $this->getRequestMethod() === 'DELETE' ? true : false;
    }

    /**
     * Get full url string.
     * @return string Full url.
     */
    public function getFullUrl() {
        return $this->getHttpHost() . $this->getRequestUri();
    }

    /**
     * Get HTTP_HOST.
     * @return string
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

    /**
     * Get SCRIPT_NAME.
     * @return string
     */
    public function getScriptName() {
        return $_SERVER['SCRIPT_NAME'];
    }

    /**
     * Get PHP_SELF.
     * @return string
     */
    public function getPhpSelf() {
        return $_SERVER['PHP_SELF'];
    }

    /**
     * Get url directory.
     * @return string Url directory.
     */
    public function getUrlDir() {
        return rtrim(dirname($this->getScriptName()), '/');
    }

    /**
     * Get REQUEST_URI.
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
     * Get PATH_INFO.
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
     * Get QUERY_STRING.
     * @return string
     */
    public function getQueryStr() {
        return isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    }

    /**
     * Get base uri (without http(s)://) string with filename if file name exists in request uri.
     * @return string Base uri.
     */
    public function getBaseUri() {
        if ($this->_baseUri === null) {
            $this->_baseUri = rtrim(str_replace($this->getHttpHost(), '', $this->getBaseUrl()), '/');
        }
        return $this->_baseUri;
    }

    /**
     * Get base url string (with http(s)://).
     * @return string Base url.
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
        return $this->_baseUrl;
    }

}