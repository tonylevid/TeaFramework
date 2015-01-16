<?php

/**
 * 简单CURL封装类。
 *
 * 请参考https://github.com/shuber/curl/blob/master/README.markdown获取简单使用方法，详细信息请查看类注释文档。
 * 请参考http://php.net/curl获取libcurl扩展的详细信息。
 *
 * @package curl
 * @author Sean Huber <shuber@huberry.com>
 **/
class Curl {

    /**
     * 用于请求读写cookies的文件。
     * @var string
     **/
    public $cookie_file;

    /**
     * 请求是否遵循重定向，默认为true。
     * @var boolean
     **/
    public $follow_redirects = true;

    /**
     * 用于发送请求header的关联数组。
     * @var array
     **/
    public $headers = array();

    /**
     * 用于发送请求CURLOPT配置的关联数组。
     * @var array
     **/
    public $options = array();

    /**
     * 用于发送请求的referer。
     * @var string
     **/
    public $referer;

    /**
     * 用于发送请求的user-agent。
     * @var string
     **/
    public $user_agent;

    /**
     * 如果有错误，最后一次请求的错误信息。
     * @var string
     **/
    protected $error = '';

    /**
     * 当前请求CURL的资源。
     * @var resource
     **/
    protected $request;

    /**
     * 构造函数。
     * 设置$this->cookie_file为'protected.cache.cookie'目录下的'curl_cookie.txt'文件。
     * 如果$_SERVER['HTTP_USER_AGENT']存在，则设置$this->user_agent为$_SERVER['HTTP_USER_AGENT']，否则设置成'Curl/PHP '.PHP_VERSION.' (http://github.com/shuber/curl)'。
     **/
    function __construct() {
        $cookie_dir = Tea::aliasToPath('protected.cache.cookie');
        DirectoryHelper::mkdirs($cookie_dir);
        $this->cookie_file = $cookie_dir . DIRECTORY_SEPARATOR . 'curl_cookie.txt';
        $this->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Curl/PHP ' . PHP_VERSION . ' (http://github.com/shuber/curl)';
    }

    /**
     * 发起一个HTTP DELETE请求。
     * 如果成功，则返回CurlResponse类实例，否则返回false。
     *
     * @param string $url 请求url。
     * @param array|string $vars 请求数据。
     * @return CurlResponse object
     **/
    function delete($url, $vars = array()) {
        return $this->request('DELETE', $url, $vars);
    }

    /**
     * Returns the error string of the current request if one occurred
     *
     * @return string
     **/
    function error() {
        return $this->error;
    }

    /**
     * Makes an HTTP GET request to the specified $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $vars 
     * @return CurlResponse
     **/
    function get($url, $vars = array()) {
        if (!empty($vars)) {
            $url .= (stripos($url, '?') !== false) ? '&' : '?';
            $url .= (is_string($vars)) ? $vars : http_build_query($vars, '', '&');
        }
        return $this->request('GET', $url);
    }

    /**
     * Makes an HTTP HEAD request to the specified $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $vars
     * @return CurlResponse
     **/
    function head($url, $vars = array()) {
        return $this->request('HEAD', $url, $vars);
    }

    /**
     * Makes an HTTP POST request to the specified $url with an optional array or string of $vars
     *
     * @param string $url
     * @param array|string $vars 
     * @return CurlResponse|boolean
     **/
    function post($url, $vars = array()) {
        return $this->request('POST', $url, $vars);
    }

    /**
     * Makes an HTTP PUT request to the specified $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $vars 
     * @return CurlResponse|boolean
     **/
    function put($url, $vars = array()) {
        return $this->request('PUT', $url, $vars);
    }

    /**
     * Makes an HTTP request of the specified $method to a $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful, false otherwise
     *
     * @param string $method
     * @param string $url
     * @param array|string $vars
     * @return CurlResponse|boolean
     **/
    function request($method, $url, $vars = array()) {
        $this->error = '';
        $this->request = curl_init();
        if (is_array($vars))
            $vars = http_build_query($vars, '', '&');

        $this->set_request_method($method);
        $this->set_request_options($url, $vars);
        $this->set_request_headers();

        $response = curl_exec($this->request);

        if ($response) {
            $response = new CurlResponse($response);
        } else {
            $this->error = curl_errno($this->request) . ' - ' . curl_error($this->request);
        }

        curl_close($this->request);

        return $response;
    }

    /**
     * Formats and adds custom headers to the current request
     *
     * @return void
     * @access protected
     **/
    protected function set_request_headers() {
        $headers = array();
        foreach ($this->headers as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }
        curl_setopt($this->request, CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * Set the associated CURL options for a request method
     *
     * @param string $method
     * @return void
     * @access protected
     **/
    protected function set_request_method($method) {
        switch (strtoupper($method)) {
            case 'HEAD':
                curl_setopt($this->request, CURLOPT_NOBODY, true);
                break;
            case 'GET':
                curl_setopt($this->request, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($this->request, CURLOPT_POST, true);
                break;
            default:
                curl_setopt($this->request, CURLOPT_CUSTOMREQUEST, $method);
        }
    }

    /**
     * Sets the CURLOPT options for the current request
     *
     * @param string $url
     * @param string $vars
     * @return void
     * @access protected
     **/
    protected function set_request_options($url, $vars) {
        curl_setopt($this->request, CURLOPT_URL, $url);
        if (!empty($vars))
            curl_setopt($this->request, CURLOPT_POSTFIELDS, $vars);

        # Set some default CURL options
        curl_setopt($this->request, CURLOPT_HEADER, true);
        curl_setopt($this->request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->request, CURLOPT_USERAGENT, $this->user_agent);
        if ($this->cookie_file) {
            curl_setopt($this->request, CURLOPT_COOKIEFILE, $this->cookie_file);
            curl_setopt($this->request, CURLOPT_COOKIEJAR, $this->cookie_file);
        }
        if ($this->follow_redirects)
            curl_setopt($this->request, CURLOPT_FOLLOWLOCATION, true);
        if ($this->referer)
            curl_setopt($this->request, CURLOPT_REFERER, $this->referer);

        # Set any custom CURL options
        foreach ($this->options as $option => $value) {
            curl_setopt($this->request, constant('CURLOPT_' . str_replace('CURLOPT_', '', strtoupper($option))), $value);
        }
    }

}
