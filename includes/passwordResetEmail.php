<?php
include_once 'db_connect.php';
include_once 'functions.php';
include_once 'psl-config.php';
include_once 'sendMail.php';
 
sec_session_start();
$error_msg = "";
 
if (isset($_POST['email'])) {
    // Sanitize and validate the data passed in
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Not a valid email
        $error_msg .= '<p class="error">The email address you entered is not valid</p>';
    }
    if (empty($error_msg)) {
        $validationCode = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
        $updateValidation = $database->prepare('
            UPDATE COMPANIES SET VALIDATION_CODE = :validation WHERE EMAIL = :email;
            ');
        $updateValidation->bindValue(':email', $email, PDO::PARAM_STR);
        $updateValidation->bindValue(':validation', $validationCode, PDO::PARAM_STR);
        $updateValidation->execute();
        $updateValidation->closeCursor();
        $to = $email;
        if (!preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/", $to)){
              echo "Invalid email format"; 
              break;
        }
        $validationURL = "https://72.182.40.185/passwordReset.php"; #to be replaced with www.poink.org or something similar.
        $subject = "Poink Account Confirmation";
        $body = "Copy and paste this character sequence to change your password: " . $validationCode;
        sendMail($to, $subject, $body);
        $_SESSION['email'] = $to;//IF EMAIL TAKES A LONG TIME TO DELIVER, THE USER WON"T BE IN THE SAME SESSION. THEN WE IN DOO DOO.
        header('Location: ../passwordReset.php');
    }
    else{
        echo $error_msg;
    }
}
?>
