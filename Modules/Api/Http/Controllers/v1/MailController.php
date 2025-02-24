<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\Request;
use PHPMailer\PHPMailer\PHPMailer;

class MailController extends BaseApiController
{
    public function send(Request $request)
    {
        try {
            $to_email = $request->input('email', '');
            if (!$to_email) {
                die('接收者邮箱不能为空');
            }
            $mail = new PHPMailer(true);
            // 配置 SMTP 设置
            $mail->isSMTP();
            $mail->Host = 'smtp.bestedm.net';
            $mail->SMTPAuth = true;
            $mail->Username = 'mail888@mail9988.com';
            $mail->Password = 'DV4mRxwcfgAGvZYh8jSW9y1JOp';
            // $mail->SMTPSecure = 'tls'; // 使用 'tls' 或 'ssl'
            $mail->Port = 2525; // 或 465

            // 配置邮件内容
            $mail->setFrom('mail888@mail9988.com', 'Testing');
            $mail->addAddress($to_email, 'Recipient Name');
            $mail->Subject = 'Test Email';
            $mail->Body = 'This is a test email sent via SMTP using PHPMailer.';

            // 发送邮件
            $mail->send();
            echo 'Email has been sent.';
        } catch (\Exception $e) {
            echo 'Email could not be sent. Error: ', $mail->ErrorInfo;
        }
    }

}
