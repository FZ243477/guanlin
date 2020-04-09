<?php

class SendEmail{

    public function mailto($address,$data,$datatitle,$image1,$image2){
        //$to 表示收件人地址 $subject 表示邮件标题 $body表示邮件正文
        error_reporting(E_ALL);
        error_reporting(E_STRICT);
        date_default_timezone_set("Asia/Shanghai");//设定时区东八区
        require_once('class.phpmailer.php');
    include('class.smtp.php');
    $mail             = new PHPMailer(); //new一个PHPMailer对象出来
    $body             = eregi_replace("[\]",'',$body); //对邮件内容进行必要的过滤
    $mail->CharSet ="UTF-8";//设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->IsSMTP(); // 设定使用SMTP服务
    $mail->SMTPDebug  = 1;                     // 启用SMTP调试功能
                                           // 1 = errors and messages
                                           // 2 = messages only
    $mail->SMTPAuth   = true;                  // 启用 SMTP 验证功能
    $mail->SMTPSecure = "ssl";                 // 安全协议
    $mail->Host       = "smtp.163.com";      // SMTP 服务器
    $mail->Port       = 465;                   // SMTP服务器的端口号
    $mail->Username   = "xxxx@163.com";  // SMTP服务器用户名
    $mail->Password   = "****";            // SMTP服务器密码
    $mail->SetFrom('xxx@t163.com');
    $mail->AddReplyTo("xxx@163.com");//回复邮件到哪
    $mail->Subject    = $datatitle;//标题
    $mail->AltBody    = "资料审核邮件 "; // optional, comment out and test
    $mail->MsgHTML($data);//邮件内容
    $address = $address;//收件人地址
    $mail->AddAddress($address, "xxx");//收件人地址和收件人名称
    $mail->AddAttachment($image1);      // attachment这里是你要添加的附件
    $mail->AddAttachment($image2); // attachment
    if(!$mail->Send()) {
        echo  "Mailer Error: " . $mail->ErrorInfo;
    } else {
        echo  "Message sent!恭喜，邮件发送成功！";
    }
	}
}

$mailto = new Sentmail();
