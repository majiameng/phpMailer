<h1 align="center">tinymeng/mailer</h1>
<p align="center">
<a href="https://scrutinizer-ci.com/g/majiameng/phpMailer/?branch=master"><img src="https://scrutinizer-ci.com/g/majiameng/phpMailer/badges/quality-score.png?b=master" alt="Scrutinizer Code Quality"></a>
<a href="https://scrutinizer-ci.com/g/majiameng/phpMailer/build-status/master"><img src="https://scrutinizer-ci.com/g/majiameng/phpMailer/badges/build.png?b=master" alt="Build Status"></a>
<a href="https://packagist.org/packages/tinymeng/mailer"><img src="https://poser.pugx.org/tinymeng/mailer/v/stable" alt="Latest Stable Version"></a>
<a href="https://github.com/majiameng/phpMailer/tags"><img src="https://poser.pugx.org/tinymeng/mailer/downloads" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/tinymeng/mailer"><img src="https://poser.pugx.org/tinymeng/mailer/v/unstable" alt="Latest Unstable Version"></a>
<a href="https://github.com/majiameng/phpMailer/blob/master/LICENSE"><img src="https://poser.pugx.org/tinymeng/mailer/license" alt="License"></a>
</p>

PHP发送邮件要么使用起来太难理解，要么文件结构太杂乱，只有自己撸起袖子干了。最少逻辑使用最简单的Email发送!!!

* 给多人发送Email
* cc抄送人（用于将电子邮件的副本发送给其他收件人）
* bcc隐性抄送人(用于将电子邮件的隐秘副本发送给其他收件人)
* 添加附件
* 发送Html带样式的邮件

### Install

```
composer require tinymeng/mailer ^2.0 -vvv
```

### 使用

> 类库使用的命名空间为`\\tinymeng\\mailer`

 ```php
use tinymeng\mailer\Email;

//******************** 配置信息 start ********************************
$config = [
    'host'  => 'smtp.qq.com',
    'port'    => '465',
    'encryption'=> 'ssl',
    'username'=> '********@qq.com',
    'password'=> '********',
    'from_address'=> '*******@qq.com',
    'from_name'=> 'TinyMeng管理员',
];
//******************** 配置信息 end ********************************


$email = Email::smtp($config);
$email->setDebug(true);//线上注释此行
$email->toEmail("879042886@qq.com");
// $email->toEmail("879042886@qq.com,879042775@qq.com");//多账号已逗号隔开
$mailtitle = "饭后等你,测试邮件发送";
$mailcontent = "饭后等你,测试邮件发送";
$mailtype = "html";
//$cc = "xxx2886@qq.com";
//$bcc = "xxx1234@qq.com";
//$attachments = ["F:\git\admin_management_api\app\command\Hello.php"];
//$email->addAttachments($attachments);//添加附件
//$email->ccEmail($cc);//抄送人
//$email->bccEmail($bcc);//隐性抄送人
$state = $email->sendmail( $mailtitle, $mailcontent, $mailtype);
if($state==""){
    exit("发送失败");
}
exit("发送成功");

```


> 在config中可配置的参数
```php
$host;        //发送email Host
$port;        //端口 25 or 456
$encryption;  //加密 ssl
$username;    //邮箱用户名(发送人email)
$password;    //邮箱密码(如果是第三方请去设置里获取)
$from_address;//发送人email
$from_name;   //发送人名称
$to_address;   //接收人email
$log_file = false;//记录日志
$host_name = "localhost"; //is used in HELO command
$time_out = 30;//is used in fsockopen()
```


> 如报错
```
/**
 *  'Trying to smtp.163.com:25 Error: Cannot connenct to relay host smtp.163.com
 * Error: Connection timed out (110) Error: Cannot send email to 879042886@qq.com'
 *
 * port: 一般我们配置的smtp服务器端口都是25，不过有的服务器或空间提供商把25端口给禁用了，比如阿里云就给禁用了，
 * 这个可以找相应的提供商确认一下。如果真是禁用了25端口，可以采用465端口，
 * 这个端口很多主流的邮件服务商像网易邮箱、QQ邮箱、阿里云邮箱也都支持，采用了465端口，
 * 注意，部分smtp服务器地址前面一定要加上ssl://，否则还是不可用。
 */
 ```
 
### 实例使用
composer require tinymeng/mailer dev-master -vvv
 
将`example`文件下的 `index.html`和`sendmail.php`文件放在与vendor平级目录

修改`sendmail.php`对应的配置

> 注: 邮箱不能自己给自己发送
