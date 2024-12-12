<?php
namespace tinymeng\mailer\Connector;

/**
 * 所有Email必须继承的抽象类
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

    protected $to_address;   //接收人email
    protected $cc_address;   //抄送人email
    protected $bcc_address;  //隐性抄送人email

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
    protected $auth = true;

    /**
     * 附件
     * @var array
     */
    protected $attachments = [];
    /**
     * headers
     * @var array
     */
    protected $headers = [];

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
            'to_address'=> '',
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
        $this->setPassword($this->config['password']);
    }

    /**
     * @param $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;
        $this->setAuth(!($this->password === false));
        return $this;
    }

    /**
     * @param $auth
     * @return void
     */
    public function setAuth($auth)
    {
        $this->auth = $auth;
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
     * @param string $email 多个用,分割
     * @return $this
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:20
     */
    public function toEmail($email){
        $this->to_address = $email;
        return $this;
    }

    /**
     * Function Name: 抄送 email
     * @param string $email 多个用,分割
     * @return $this
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:20
     */
    public function ccEmail($email){
        $this->cc_address = $email;
        return $this;
    }

    /**
     * Function Name: 隐性抄送 email
     * @param string $email 多个用,分割
     * @return $this
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:20
     */
    public function bccEmail($email){
        $this->bcc_address = $email;
        return $this;
    }

    /**
     * 添加附件
     * @param array|string $attachments
     * @return $this
     */
    public function addAttachments($attachments){
        if(is_array($attachments)){
            foreach ($attachments as $attachment){
                if(file_exists($attachment)) $this->attachments[] = $attachment;
            }
        }else{
            if(file_exists($attachments)) $this->attachments[] = $attachments;
        }
        return $this;
    }

    /**
     * 添加Header
     * @param array|string $headers
     * @return $this
     */
    public function addHeaders($headers){
        if(is_array($headers)){
            $this->headers = array_merge($this->headers,$headers);
        }else{
            $this->headers[] = $headers;
        }
        return $this;
    }
}
