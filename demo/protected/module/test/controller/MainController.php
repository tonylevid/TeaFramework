<?php

class MainController extends TeaController {

    public function index($name) {
        $rst = Tea::loadModel('test')->all();
        $sqlAll = Tea::loadModel('test')->getLastSql();
        $this->assign(array(
            'data' => $rst[0],
            'pager' => $rst[1]->content(),
            'sqlAll' => $sqlAll,
            'name' => $name
        ));
        $this->render();
    }

    public function captcha() {
        $captcha = Tea::loadLib('TeaImage')->captcha(100, 30, array('bgColor' => 'FFFCCC'));
        $_SESSION['captcha'] = $captcha->getCaptchaVal();
        $captcha->output();
    }

    public function captchaVal() {
        var_dump($_SESSION['captcha']);
    }

    public function upload() {
        echo '<pre>';
        print_r(Tea::loadLib('TeaUpload')->upload()->getFileInfo());
        echo '</pre>';
    }

}