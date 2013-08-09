<?php

class Account extends Controller {

    private $util;
    private $valid;
    public static $rules = array(
        'username' => array(
            'type' => 'string',
            'required' => true,
            'min' => 6,
            'max' => 32,
            'exists' => 'users',
            'trim' => true
        ),
        'password' => array(
            'type' => 'string',
            'required' => true,
            'min' => 6,
            'max' => 32,
            'trim' => true
        ),
        'confirm' => array(
            'type' => 'equals',
            'required' => true,
            'key' => 'password'
        ),
        'email' => array(
            'type' => 'email',
            'required' => true,
            'min' => 10,
            'max' => 150,
            'exists' => 'users',
            'trim' => true
        ),
        'fullname' => array(
            'type' => 'string',
            'required' => true,
            'min' => 10,
            'max' => 50,
            'trim' => true
        ),
        'mobile' => array(
            'type' => 'string',
            'required' => true,
            'min' => 10,
            'max' => 11,
            'trim' => true
        ),
        'address' => array(
            'type' => 'string',
            'required' => true,
            'min' => 10,
            'max' => 255,
            'trim' => true
        )
    );
    private $change_label = array(
        'username' => 'Tên tài khoản',
        'password' => 'Mật khẩu',
        'confirm' => 'Hai mật khẩu',
        'email' => 'Địa chỉ email',
        'fullname' => 'Họ tên',
        'mobile' => 'Số điện thoại',
        'address' => 'Địa chỉ',
    );

    function __construct() {
        parent::__construct();
        $this->view->layout = 'home';
        $this->util = new Util();
        $this->valid = new Validation();
    }

    function index() {
        Util::redirectTo('index');
    }

    public function register() {
        $this->view->title = 'Đăng ký tài khoản';
        if (Request::isPost()) {
            $this->valid->addRules(self::$rules);
            $this->valid->addSource($_POST);
            $this->valid->run();
            $this->valid->changeLabel($this->change_label);
            if ($this->valid->isValid()) {
                $key = Hash::create('md5', uniqid(rand(), true), HASH_GENERAL_KEY);
                $flag = true;
                $exists = new CheckExists();
                if (Request::post('subscribe') == 1 && !$exists->check(Request::post('email'), 'email', 'subscribes')) {
                    $data = array('email' => Request::post('email'));
                    $flag = $this->model->subscribe($data);
                }
                $_POST['key'] = $key;
                $_POST['active'] = 0;
                $_POST['password'] = Hash::create('sha1', Request::post('password'), HASH_PASSWORD_KEY);
                unset($_POST['subscribe']);
                unset($_POST['confirm']);
                if ($this->model->register($_POST) && $flag) {
                    $this->view->title = 'Đăng ký tài khoản thành công';
                    $message = "Chúc mừng bạn đã đăng ký tài khoản thành công tại MobileStore.Com\n. Để hoành thành việc đăng ký bạn vui lòng vào đường link sau: ".URL."account/active/{$key}/kich-hoat-tai-khoan.html";
                    $mail = new Mail();
                    if($mail->Send(Request::post('email'), Request::post('fullname'), 'Đăng ký tài khoản thành công', $message)){
                        $this->view->message = $this->util->alertMessage('Bạn đã đăng ký tài khoản thành công. Vui lòng kiểm tra email để kích hoạt', 'Thành công', 'success');
                    }else{
                        $this->view->message = $this->util->alertMessage('Chúng tôi không thể gửi được email kích hoạt cho bạn. Bạn vui lòng sử dụng chức năng gửi lại mã kích hoạt. Xin cảm ơn', 'Có lỗi', 'error');
                    }
                    
                    
                    $_POST = array();
                } else {
                    $this->view->message = $this->util->alertMessage('Có lỗi xảy ra. Bạn vui lòng thử lại', 'Có lỗi', 'error');
                }
            } else {
                if (isset($this->valid->error['diff_key'])) {
                    $message = 'Dữ liệu đầu vào không hợp lệ';
                } else {
                    $message = 'Bạn vui lòng kiểm tra lại các trường dữ liệu.';
                }
                $this->view->message = $this->util->alertMessage($message, 'Có lỗi', 'error');
                $this->util->errors = $this->valid->errors;
                $this->view->account = $_POST;
            }
        }
        
        $this->view->util = $this->util;
        $this->view->render('account/register');
    }

    public function login() {
        if (Request::isPost()) {
            $data = array(
                'username' => $_POST['username'],
                'password' => HASH::create('sha1', "{$_POST['password']}", HASH_PASSWORD_KEY)
            );
            if ($this->model->checkLogin($data) == true) {
                Util::redirectTo('index');
            } else {
                $this->view->message = Util::alertMessage('Tên tài khoản hoặc mật khẩu sai', 'Có lỗi');
            }
        }
        $this->view->title = 'Đăng nhập tài khoản';
        $this->view->render('account/login');
    }

}

?>