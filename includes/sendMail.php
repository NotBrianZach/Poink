<?php
require_once '/var/www/vendor/autoload.php';
function sendMail($to,$subject,$body){
    $from = "darklordvadermort@gmail.com";//to be replaced with a more formal email address
    $mail = new PHPMailer();
    $mail->IsSMTP(true); // SMTP
    $mail->SMTPAuth = true;  // SMTP authentication
    $mail->Mailer = "smtp";
    $mail->Host= "ssl://smtp.gmail.com"; // Amazon SES
    $mail->Port = 465;  // SMTP Port
    $mail->ssl = 1;
    $mail->debug = 1;
    $mail->html_debug = 1;
    $mail->Username = "darklordvadermort@gmail.com";  // SMTP  Username
    $mail->Password = "proverb2ialdrago1n";  // SMTP Password
    $mail->SetFrom($from, 'The Poink Team');
    $mail->AddReplyTo($from,'The Poink Team');
    $mail->Subject = $subject;
    $mail->MsgHTML($body);
    $address = $to;
    $mail->AddAddress($address, $to);
    if(!$mail->Send()){
        echo "not sent" . $mail->ErrorInfo;
        return false;
    }
    else{
        echo "sent";
        return true;
    }
}
?>
