<?php
namespace tinymeng\mailer\Connector;

use tinymeng\mailer\Gateways\Smtp;
use tinymeng\tools\Strings;

/**
 * 所有第三方登录必须继承的抽象类
 */
abstract class Gateway implements GatewayInterface
{
    /**
     * 配置参数
     * @var array
     */
    protected $config;

    /**
     * 在config中可配置的参数
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:21
     */
    protected $host;        //发送email Host
    protected $port;        //端口 25 or 456
    protected $encryption;  //加密 ssl
    protected $username;    //邮箱用户名(发送人email)
    protected $password;    //邮箱密码(如果是第三方请去设置里获取)
    protected $from_address;//发送人email
    protected $from_name;   //发送人名称
    protected $to_adress;   //接收人email
    protected $log_file = false;//记录日志
    protected $host_name = "localhost"; //is used in HELO command
    protected $time_out = 30;//is used in fsockopen()

    /**
     * 是否开启debug
     * @var bool
     */
    protected $debug = false;
    
    /**
     * 是否需要登陆
     * @var bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:30 
     */
    protected $auth = false;

    /**
     * Gateway constructor.
     * @param null $config
     * @throws \Exception
     */
    public function __construct($config = null)
    {
        if (!$config) {
            throw new \Exception('传入的配置不能为空');
        }
        //默认参数
        $_config = [
            'host'  => 'smtp.qq.com',
            'port'    => '25',
            'encryption'=> false,
            'username'=> false,
            'password'=> false,
            'from_address'=> '',
            'from_name'=> '管理员',
            'to_adress'=> '',
        ];
        $this->config = array_replace_recursive($_config,$config);
        if(empty($this->config['from_address'])){
            $this->config['from_address'] = $this->config['username'];
        }
        if(!empty($this->config['encryption'])){
            $this->config['host'] = $this->config['encryption'] . '://' . $this->config['host'];
        }

        foreach ($this->config as $key => $value) {
            if (property_exists($this,$key)) {
                $this->$key = $value;
            }
        }
        if(!empty($this->username) && !empty($this->password)){
           $this->auth = true;
        }
    }

    /**
     * Function Name: 开启debug
     * @param boolean $debug
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 10:44
     */
    public function setDebug($debug){
        $this->debug = $debug;
    }

    /**
     * Function Name: 发送给to email
     * @return $this
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:20
     */
    public function toEmail($email){
        $this->to_adress = $email;
        return $this;
    }

}
