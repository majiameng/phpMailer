<?php
/**
 * Class Smtp
 * @author Tinymeng <666@majiameng.com>
 * @date: 2019/9/25 18:51
 */
namespace tinymeng\mailer\Gateways;
use tinymeng\mailer\Connector\Gateway;

class Smtp extends Gateway{

    /**
     * @var
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 16:21
     */
    private $sock;

    /**
     * Function Name: 发送email
     * @param string $subject 邮件主题
     * @param string $body 邮件内容
     * @param string $mailtype 邮件格式（HTML/TXT）,TXT为文本邮件
     * @param string $cc
     * @param string $bcc
     * @param string $additional_headers
     * @return bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:33
     */
    public function sendmail( $subject = "", $body = "", $mailtype='TXT', $cc = "", $bcc = "", $additional_headers = ""){
        $mail_from = $this->get_address($this->strip_comment($this->from_address));
        $body = preg_replace("/(^|(\r\n))(\.)/", "\1.\3", $body);
        $header = "MIME-Version:1.0\r\n";
        if($mailtype=="HTML"){
            $header .= "Content-Type:text/html\r\n";
        }
        $header .= "To: ".$this->to_adress."\r\n";
        if ($cc != "") {
            $header .= "Cc: ".$cc."\r\n";
        }
        $header .= "From: $this->from_address<".$this->from_address.">\r\n";
        $header .= "Subject: ".$subject."\r\n";
        $header .= $additional_headers;
        $header .= "Date: ".date("r")."\r\n";
        $header .= "X-Mailer:By Redhat (PHP/".phpversion().")\r\n";
        list($msec, $sec) = explode(" ", microtime());
        $header .= "Message-ID: <".date("YmdHis", $sec).".".($msec*1000000).".".$mail_from.">\r\n";
        $TO = explode(",", $this->strip_comment($this->to_adress));
        if ($cc != "") {
            $TO = array_merge($TO, explode(",", $this->strip_comment($cc)));
        }
        if ($bcc != "") {
            $TO = array_merge($TO, explode(",", $this->strip_comment($bcc)));
        }
        $sent = TRUE;
        foreach ($TO as $rcpt_to) {
            $rcpt_to = $this->get_address($rcpt_to);
            if (!$this->smtp_sockopen($rcpt_to)) {
                $this->log_write("Error: Cannot send email to ".$rcpt_to."\n");
                $sent = FALSE;
                continue;
            }
            if ($this->smtp_send($this->host_name, $mail_from, $rcpt_to, $header, $body)) {
                $this->log_write("E-mail has been sent to <".$rcpt_to.">\n");
            } else {
                $this->log_write("Error: Cannot send email to <".$rcpt_to.">\n");
                $sent = FALSE;
            }
            fclose($this->sock);
            $this->log_write("Disconnected from remote host\n");
        }
        return $sent;
    }

    /**
     * Function Name: smtp_send
     * @param $helo
     * @param $from
     * @param $to
     * @param $header
     * @param string $body
     * @return bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:30
     */
    private function smtp_send($helo, $from, $to, $header, $body = "")
    {
        if (!$this->smtp_putcmd("HELO", $helo)) {
            return $this->smtp_error("sending HELO command");
        }
        //auth
        if($this->auth){
            if (!$this->smtp_putcmd("AUTH LOGIN", base64_encode($this->username))) {
                return $this->smtp_error("sending HELO command");
            }
            if (!$this->smtp_putcmd("", base64_encode($this->password))) {
                return $this->smtp_error("sending HELO command");
            }
        }
        if (!$this->smtp_putcmd("MAIL", "FROM:<".$from.">")) {
            return $this->smtp_error("sending MAIL FROM command");
        }
        if (!$this->smtp_putcmd("RCPT", "TO:<".$to.">")) {
            return $this->smtp_error("sending RCPT TO command");
        }
        if (!$this->smtp_putcmd("DATA")) {
            return $this->smtp_error("sending DATA command");
        }
        if (!$this->smtp_message($header, $body)) {
            return $this->smtp_error("sending message");
        }
        if (!$this->smtp_eom()) {
            return $this->smtp_error("sending <CR><LF>.<CR><LF> [EOM]");
        }
        if (!$this->smtp_putcmd("QUIT")) {
            return $this->smtp_error("sending QUIT command");
        }
        return TRUE;
    }

    /**
     * Function Name: smtp_sockopen
     * @param $address
     * @return bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:30
     */
    private function smtp_sockopen($address)
    {
        if ($this->host == "") {
            return $this->smtp_sockopen_mx($address);
        } else {
            return $this->smtp_sockopen_relay();
        }
    }

    /**
     * Function Name: smtp_sockopen_relay
     * @return bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:31
     */
    private function smtp_sockopen_relay()
    {
        $this->log_write("Trying to ".$this->host.":".$this->port."\n");
        $this->sock = @fsockopen($this->host, $this->port, $errno, $errstr, $this->time_out);
        if (!($this->sock && $this->smtp_ok())) {
            $this->log_write("Error: Cannot connenct to relay host ".$this->host."\n");
            $this->log_write("Error: ".$errstr." (".$errno.")\n");
            return FALSE;
        }
        $this->log_write("Connected to relay host ".$this->host."\n");
        return TRUE;
    }

    /**
     * Function Name: smtp_sockopen_mx
     * @param $address
     * @return bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:31
     */
    private function smtp_sockopen_mx($address)
    {
        $domain = preg_replace("/^.+@([^@]+)$/", "\1", $address);
        if (!@getmxrr($domain, $MXHOSTS)) {
            $this->log_write("Error: Cannot resolve MX \"".$domain."\"\n");
            return FALSE;
        }
        foreach ($MXHOSTS as $host) {
            $this->log_write("Trying to ".$host.":".$this->port."\n");
            $this->sock = @fsockopen($host, $this->port, $errno, $errstr, $this->time_out);
            if (!($this->sock && $this->smtp_ok())) {
                $this->log_write("Warning: Cannot connect to mx host ".$host."\n");
                $this->log_write("Error: ".$errstr." (".$errno.")\n");
                continue;
            }
            $this->log_write("Connected to mx host ".$host."\n");
            return TRUE;
        }
        $this->log_write("Error: Cannot connect to any mx hosts (".implode(", ", $MXHOSTS).")\n");
        return FALSE;
    }

    /**
     * Function Name: smtp_message
     * @param $header
     * @param $body
     * @return bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:31
     */
    private function smtp_message($header, $body)
    {
        fputs($this->sock, $header."\r\n".$body);
        $this->debug("> ".str_replace("\r\n", "\n"."> ", $header."\n> ".$body."\n> "));
        return TRUE;
    }

    /**
     * Function Name: smtp_eom
     * @return bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:31
     */
    private function smtp_eom()
    {
        fputs($this->sock, "\r\n.\r\n");
        $this->debug(". [EOM]\n");
        return $this->smtp_ok();
    }

    /**
     * Function Name: smtp_ok
     * @return bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:31
     */
    private function smtp_ok()
    {
        $response = str_replace("\r\n", "", fgets($this->sock, 512));
        $this->debug($response."\n");
        if (!preg_match("/^[23]/", $response)) {
            fputs($this->sock, "QUIT\r\n");
            fgets($this->sock, 512);
            $this->log_write("Error: Remote host returned \"".$response."\"\n");
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Function Name: smtp_putcmd
     * @param $cmd
     * @param string $arg
     * @return bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:31
     */
    private function smtp_putcmd($cmd, $arg = "")
    {
        if ($arg != "") {
            if($cmd=="") $cmd = $arg;
            else $cmd = $cmd." ".$arg;
        }
        fputs($this->sock, $cmd."\r\n");
        $this->debug("> ".$cmd."\n");
        return $this->smtp_ok();
    }

    /**
     * Function Name: smtp_error
     * @param $string
     * @return bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:31
     */
    private function smtp_error($string)
    {
        $this->log_write("Error: Error occurred while ".$string.".\n");
        return FALSE;
    }

    /**
     * Function Name: log_write
     * @param $message
     * @return bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:26
     */
    private function log_write($message)
    {
        $this->debug($message);
        if ($this->log_file == "") {
            return TRUE;
        }
        $message = date("M d H:i:s ").get_current_user()."[".getmypid()."]: ".$message;
        if (!@file_exists($this->log_file) && !($fp = @fopen($this->log_file, "a"))) {
            $this->debug("Warning: Cannot open log file \"".$this->log_file."\"\n");
            return FALSE;
        }
        flock($fp, LOCK_EX);
        fputs($fp, $message);
        fclose($fp);
        return TRUE;
    }

    /**
     * Function Name: strip_comment
     * @param $address
     * @return string|string[]|null
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:26
     */
    private function strip_comment($address)
    {
        $comment = "/\([^()]*\)/";
        while (preg_match($comment, $address)) {
            $address = preg_replace($comment, "", $address);
        }
        return $address;
    }

    /**
     * Function Name: get_address
     * @param $address
     * @return string|string[]|null
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:26
     */
    private function get_address($address)
    {
        $address = preg_replace("/([ \t\r\n])+/", "", $address);
        $address = preg_replace("/^.*<(.+)>.*$/", "\1", $address);
        return $address;
    }

    /**
     * Function Name: debug
     * @param $message
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:27
     */
    private function debug($message)
    {
        if ($this->debug) {
            echo $message."<hr/>";
        }
    }
}