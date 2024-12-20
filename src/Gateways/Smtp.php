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
    private $boundary;

    /**
     * Function Name: 发送email
     * @param string $subject 邮件主题
     * @param string $body 邮件内容
     * @param string $mailType 邮件格式（HTML/TXT）,TXT为文本邮件
     * @return bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:33
     */
    public function sendmail($subject, $body, $mailType = 'HTML')
    {
        $mail_from = $this->getAddress($this->stripComment($this->from_address));
        $body = preg_replace("/(^|(\r\n))(\.)/", "\1.\3", $body);
        $this->boundary = "====" . md5(uniqid()) . "====";
        $body = $this->prepareBody($body,$mailType);
        $header = $this->buildHeaders($subject, $mailType, $mail_from);

        // Prepare recipient list
        $TO = $this->getRecipientList();

        $sent = true;
        foreach ($TO as $rcpt_to) {
            $rcpt_to = $this->getAddress($rcpt_to);
            if (!$this->smtpSockopen($rcpt_to)) {
                $this->logWrite("Error: Cannot send email to " . $rcpt_to . "\n");
                $sent = false;
                continue;
            }
            if ($this->smtpSend($this->host_name, $mail_from, $rcpt_to, $header, $body)) {
                $this->logWrite("E-mail has been sent to <" . $rcpt_to . ">\n");
            } else {
                $this->logWrite("Error: Cannot send email to <" . $rcpt_to . ">\n");
                $sent = false;
            }
            fclose($this->sock);
            $this->logWrite("Disconnected from remote host\n");
        }
        return $sent;
    }

    /**
     * @param $subject
     * @param $mailType
     * @param $mail_from
     * @return string
     * @author Tinymeng <666@majiameng.com>
     */
    private function buildHeaders($subject, $mailType, $mail_from){
        // Set headers
        $header = "MIME-Version: 1.0\r\n";
        if (strtoupper($mailType) == "HTML") {
            $header .= "Content-Type: multipart/mixed; boundary=\"$this->boundary\"\r\n";
        } else {
            $header .= "Content-Type: text/plain; charset=UTF-8\r\n";
        }
        $header .= "To: " . $this->to_address . "\r\n";
        if (!empty($this->cc_address)) {
            $header .= "Cc: " . $this->cc_address . "\r\n";
        }
        $header .= "From: $this->from_address <" . $this->from_address . ">\r\n";
        $header .= "Subject: " . $subject . "\r\n";
        if(!empty($this->headers)){
            foreach ($this->headers as $head){
                $header .= $head;
            }
        }
        $header .= "Date: " . date("r") . "\r\n";
        $header .= "X-Mailer: By Redhat (PHP/" . phpversion() . ")\r\n";
        $header .= "Message-ID: <" . date("YmdHis") . "." . uniqid() . "@" . $mail_from . ">\r\n";
        return $header;
    }


    /**
     * @param $body
     * @param $mailType
     * @return string
     * @author Tinymeng <666@majiameng.com>
     */
    private function prepareBody($body, $mailType){
        if (strtoupper($mailType) == "HTML") {
            // Body with text and attachments
            $body = "--$this->boundary\r\n" .
                "Content-Type: text/html; charset=UTF-8\r\n" .
                "Content-Transfer-Encoding: 7bit\r\n\r\n" .
                $body . "\r\n";

            if(!empty($this->attachments)){
                foreach ($this->attachments as $fileName=>$attachment) {
                    $fileContent = chunk_split(base64_encode(file_get_contents($attachment)));
                    $body .= "--$this->boundary\r\n" .
                        "Content-Type: application/octet-stream; name=\"$fileName\"\r\n" .
                        "Content-Transfer-Encoding: base64\r\n" .
                        "Content-Disposition: attachment; filename=\"$fileName\"\r\n\r\n" .
                        $fileContent . "\r\n";
                }
                $this->attachments = array();
            }

            $body .= "--$this->boundary--";
        } else {
            $body .= "\r\n";
        }
        return $body;
    }

    /**
     * @return false|string[]
     * @author Tinymeng <666@majiameng.com>
     */
    private function getRecipientList()
    {
        $TO = explode(",", $this->stripComment($this->to_address));
        if (!empty($this->cc_address)) {
            $TO = array_merge($TO, explode(",", $this->stripComment($this->cc_address)));
        }
        if (!empty($this->bcc_address)) {
            $TO = array_merge($TO, explode(",", $this->stripComment($this->bcc_address)));
        }
        return $TO;
    }

    /**
     * Function Name: smtpSend
     * @param $helo
     * @param $from
     * @param $to
     * @param $header
     * @param string $body
     * @return bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:30
     */
    private function smtpSend($helo, $from, $to, $header, $body = "")
    {
        if (!$this->smtpPutCmd("HELO", $helo)) {
            return $this->smtpError("sending HELO command");
        }
        //auth
        if($this->auth){
            if (!$this->smtpPutCmd("AUTH LOGIN", base64_encode($this->username))) {
                return $this->smtpError("sending HELO command");
            }
            if (!$this->smtpPutCmd("", base64_encode($this->password))) {
                return $this->smtpError("sending HELO command");
            }
        }
        if (!$this->smtpPutCmd("MAIL", "FROM:<".$from.">")) {
            return $this->smtpError("sending MAIL FROM command");
        }
        if (!$this->smtpPutCmd("RCPT", "TO:<".$to.">")) {
            return $this->smtpError("sending RCPT TO command");
        }
        if (!$this->smtpPutCmd("DATA")) {
            return $this->smtpError("sending DATA command");
        }
        if (!$this->smtpMessage($header, $body)) {
            return $this->smtpError("sending message");
        }
        if (!$this->smtpEom()) {
            return $this->smtpError("sending <CR><LF>.<CR><LF> [EOM]");
        }
        if (!$this->smtpPutCmd("QUIT")) {
            return $this->smtpError("sending QUIT command");
        }
        return TRUE;
    }

    /**
     * Function Name: smtpSockopen
     * @param $address
     * @return bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:30
     */
    private function smtpSockopen($address)
    {
        if ($this->host == "") {
            return $this->smtpSockopenMx($address);
        } else {
            return $this->smtpSockopenRelay();
        }
    }

    /**
     * Function Name: smtpSockopenRelay
     * @return bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:31
     */
    private function smtpSockopenRelay()
    {
        $this->logWrite("Trying to ".$this->host.":".$this->port."\n");
        $this->sock = @fsockopen($this->host, $this->port, $errno, $errstr, $this->time_out);
        if (!($this->sock && $this->smtpOk())) {
            $this->logWrite("Error: Cannot connenct to relay host ".$this->host."\n");
            $this->logWrite("Error: ".$errstr." (".$errno.")\n");
            return FALSE;
        }
        $this->logWrite("Connected to relay host ".$this->host."\n");
        return TRUE;
    }

    /**
     * Function Name: smtpSockopenMx
     * @param $address
     * @return bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:31
     */
    private function smtpSockopenMx($address)
    {
        $domain = preg_replace("/^.+@([^@]+)$/", "\1", $address);
        if (!@getmxrr($domain, $MXHOSTS)) {
            $this->logWrite("Error: Cannot resolve MX \"".$domain."\"\n");
            return FALSE;
        }
        foreach ($MXHOSTS as $host) {
            $this->logWrite("Trying to ".$host.":".$this->port."\n");
            $this->sock = @fsockopen($host, $this->port, $errno, $errstr, $this->time_out);
            if (!($this->sock && $this->smtpOk())) {
                $this->logWrite("Warning: Cannot connect to mx host ".$host."\n");
                $this->logWrite("Error: ".$errstr." (".$errno.")\n");
                continue;
            }
            $this->logWrite("Connected to mx host ".$host."\n");
            return TRUE;
        }
        $this->logWrite("Error: Cannot connect to any mx hosts (".implode(", ", $MXHOSTS).")\n");
        return FALSE;
    }

    /**
     * Function Name: smtpMessage
     * @param $header
     * @param $body
     * @return bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:31
     */
    private function smtpMessage($header, $body)
    {
        fputs($this->sock, $header."\r\n".$body);
        $this->debug("> ".str_replace("\r\n", "\n"."> ", $header."\n> ".$body."\n> "));
        return TRUE;
    }

    /**
     * Function Name: smtpEom
     * @return bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:31
     */
    private function smtpEom()
    {
        fputs($this->sock, "\r\n.\r\n");
        $this->debug(". [EOM]\n");
        return $this->smtpOk();
    }

    /**
     * Function Name: smtpOk
     * @return bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:31
     */
    private function smtpOk()
    {
        $response = str_replace("\r\n", "", fgets($this->sock, 512));
        $this->debug($response."\n");
        if (!preg_match("/^[23]/", $response)) {
            fputs($this->sock, "QUIT\r\n");
            fgets($this->sock, 512);
            $this->logWrite("Error: Remote host returned \"".$response."\"\n");
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Function Name: smtpPutCmd
     * @param $cmd
     * @param string $arg
     * @return bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:31
     */
    private function smtpPutCmd($cmd, $arg = "")
    {
        if ($arg != "") {
            if($cmd=="") $cmd = $arg;
            else $cmd = $cmd." ".$arg;
        }
        fputs($this->sock, $cmd."\r\n");
        $this->debug("> ".$cmd."\n");
        return $this->smtpOk();
    }

    /**
     * Function Name: smtpError
     * @param $string
     * @return bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:31
     */
    private function smtpError($string)
    {
        $this->logWrite("Error: Error occurred while ".$string.".\n");
        return FALSE;
    }

    /**
     * Function Name: logWrite
     * @param $message
     * @return bool
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:26
     */
    private function logWrite($message)
    {
        $this->debug($message);
        if ($this->log_file == "") {
            return TRUE;
        }
        $message = date("Y-m-d H:i:s ").get_current_user()."[".getmypid()."]: ".$message;
        if (($handle = @fopen($this->log_file, "a")) === false) {
            $this->debug("Warning: Cannot open log file \"".$this->log_file."\"\n");
            return FALSE;
        }
        flock($handle, LOCK_EX);
        fputs($handle, $message);
        fclose($handle);
        return TRUE;
    }

    /**
     * Function Name: stripComment
     * @param $address
     * @return string|string[]|null
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:26
     */
    private function stripComment($address)
    {
        $comment = "/\([^()]*\)/";
        while (preg_match($comment, $address)) {
            $address = preg_replace($comment, "", $address);
        }
        return $address;
    }

    /**
     * Function Name: getAddress
     * @param $address
     * @return string|string[]|null
     * @author Tinymeng <666@majiameng.com>
     * @date: 2019/9/26 15:26
     */
    private function getAddress($address)
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