<?php
date_default_timezone_set('PRC');
require_once "Smtp.class.php";
//******************** 配置信息 ********************************
$smtpserver = "ssl://smtp.mxhichina.com";//SMTP服务器
$smtpserverport =465;//SMTP服务器端口
/**
 * 如果报错 'Trying to smtp.163.com:25 Error: Cannot connenct to relay host smtp.163.com Error: Connection timed out (110) Error: Cannot send email to 879042886@qq.com'
 * 一般我们配置的smtp服务器端口都是25，不过有的服务器或空间提供商把25端口给禁用了，比如阿里云就给禁用了，这个可以找相应的提供商确认一下。如果真是禁用了25端口，可以采用465端口，这个端口很多主流的邮件服务商像网易邮箱、QQ邮箱、阿里云邮箱也都支持，采用了465端口，织梦后台需要如下这么配置，注意，smtp服务器地址前面一定要加上ssl://，否则还是不可用。
 */
$smtpusermail = "send@majiameng.com";//SMTP服务器的用户邮箱
$smtpemailto = $_POST['toemail'];//发送给谁
$smtpuser = "send@majiameng.com";//SMTP服务器的用户帐号，注：部分邮箱只需@前面的用户名
$smtppass = "Ma13146662737";//SMTP服务器的用户密码
$mailtitle = $_POST['title'];//邮件主题
$mailcontent = "<h1>".$_POST['content']."</h1>";//邮件内容
$mailtype = "HTML";//邮件格式（HTML/TXT）,TXT为文本邮件
//************************ 配置信息 ****************************
$smtp = new Smtp($smtpserver,$smtpserverport,true,$smtpuser,$smtppass);//这里面的一个true是表示使用身份验证,否则不使用身份验证.
$smtp->debug = true;//是否显示发送的调试信息
$state = $smtp->sendmail($smtpemailto, $smtpusermail, $mailtitle, $mailcontent, $mailtype);
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