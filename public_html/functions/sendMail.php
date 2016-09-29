<?php

require_once '../config.php';
require_once './helpers.php';

require_once './mail/class.phpmailer.php';
require_once './mail/class.smtp.php';

foreach ($_POST as $key => $value) {
    if (ini_get('magic_quotes_gpc')) {
        $_POST[ $key ] = stripslashes($_POST[ $key ]);
    }

    $_POST[$key] = htmlspecialchars(strip_tags($_POST[ $key ]));
}
$lang = $_POST['lang'];

$name = $_POST['name'];
$email = $_POST['email'];
$subject = $_POST['subject'];
$message = $_POST['message'];
$subject = "[Contato]: $subject";

$mail = new PHPMailer();

$mail->IsSMTP();
$mail->SMTPDebug = 0;
$mail->SMTPAuth = true;
$mail->Host = 'smtp.zoho.com';
$mail->Port = 465;
$mail->Username = 'christian@kaisermann.me';
$mail->Password = '.@lksa.5647.powq@.';
$mail->Priority = 1;
$mail->SMTPSecure = 'ssl';
$mail->CharSet = 'UTF-8';
$mail->IsHTML(true);

$mail->SetFrom('christian@kaisermann.me', $name);
$mail->AddReplyTo($email, $name);
$mail->Subject = $subject;

$mail->MsgHTML(nl2br($message."\n\nIP:".get_client_ip()));
$mail->AddAddress('christian@kaisermann.me', 'Christian Kaisermann');
$mail->AddCC('chris.kaisermann@outlook.com', 'Christian Kaisermann');

// Arquivo de backup
$fname = date('Y-m-d H:i:s');
$fname = $fname.' - '.$email.' '.substr(md5($fname), 0, 6).'.txt';
$fcont = $name.PHP_EOL.$email.PHP_EOL.$subject.PHP_EOL.$message;

file_put_contents('../data/mail/'.$fname, $fcont);

// Arquivo de backup

if (!$mail->Send()) {
    echo json_encode(array('state' => -1, 'msg' => lang('form_error', false)));
} else {
    echo json_encode(array('state' => 1, 'msg' => lang('form_ok', false)));
}

// Function to get the client IP address
function get_client_ip()
{
    $ipaddress = '';
    if ($_SERVER['HTTP_CLIENT_IP']) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } elseif ($_SERVER['HTTP_X_FORWARDED_FOR']) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif ($_SERVER['HTTP_X_FORWARDED']) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } elseif ($_SERVER['HTTP_FORWARDED_FOR']) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif ($_SERVER['HTTP_FORWARDED']) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } elseif ($_SERVER['REMOTE_ADDR']) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    } else {
        $ipaddress = 'UNKNOWN';
    }

    return $ipaddress;
}
