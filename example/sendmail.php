<?php
// Autoload vendor自动载入
require './vendor/autoload.php';
use tinymeng\mailer\Email;
date_default_timezone_set('PRC');

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

$smtpemailto = $_POST['toemail'];//发送给谁
$mailtitle = $_POST['title'];//邮件主题
$mailcontent = "<h1>".$_POST['content']."</h1>";//邮件内容

$email = Email::smtp($config);
$email->setDebug(true);//线上注释此行
$email->toEmail($smtpemailto);
$mailtype = "html";
$state = $email->sendmail( $mailtitle, $mailcontent, $mailtype);
echo "<div style='width:300px; margin:36px auto;'>";
if($state==""){
	echo "对不起，邮件发送失败！请检查邮箱填写是否有误。";
	echo "<a href='index.html'>点此返回</a>";
	exit();
}
echo "恭喜！邮件发送成功！！";
echo "<a href='index.html'>点此返回</a>";
echo "</div>";
?>