<?php
use PHPUnit\Framework\TestCase;
use tinymeng\mailer\Email;

/**
 * IntelligentParseTest
 */
class MailerTest extends TestCase
{
    public function testSend()
    {

        //******************** 配置信息 start ********************************
        $config = [
            'host'         => 'smtp.mxhichina.com',// 邮箱的服务器地址
            'port'         => 465,// SMTP 端口
            'from_address' => '3@majiameng.com',// 发件人地址，一般和username一致
            'username'     => '3@majiameng.com',// 用户名
            'password'     => 'J^*****&H',// 密码
            'encryption'   => 'ssl',// 加密方式
        ];
        //******************** 配置信息 end ********************************

        $smtpemailto = '879042886@qq.com';//发送给谁
        $mailtitle = "测试邮件";//邮件主题
        $mailcontent = "<h1>吃饭了嘛</h1>";//邮件内容

        $email = Email::smtp($config);
        $email->setDebug(true);//线上注释此行
        $email->toEmail($smtpemailto);
        $mailtype = "html";
//        $cc = "xxx2886@qq.com";
//        $bcc = "xxx1234@qq.com";
//        $attachments = ['E:\git\phpMailer\example\sendmail.php'];
        $attachments = ['http://oss-joywork.oss-cn-beijing.aliyuncs.com/miningplatform/api/6bb8b72d99de37d8e7b86243b52898ec.csv'];
        $email->addAttachments($attachments);//添加附件
//        $email->ccEmail($cc);//抄送人
//        $email->bccEmail($bcc);//隐性抄送人
        $state = $email->sendmail( $mailtitle, $mailcontent, $mailtype);
        var_dump($state);
    }

}