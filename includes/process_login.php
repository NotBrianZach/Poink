<?php
include_once 'db_connect.php';
include_once 'functions.php';
require_once '../vendor/autoload.php';
 
sec_session_start(); // Our custom secure way of starting a PHP session.
 
if (isset($_POST['email'], $_POST['p'])) {
    $email = $_POST['email'];
    $password = $_POST['p']; // The hashed password.
    $checkValidated = $database->prepare('
        SELECT VALIDATED FROM COMPANIES WHERE EMAIL=:email;
        ');
    $checkValidated->bindValue(':email',$email,PDO::PARAM_STR);
    $checkValidated->execute();
    $validationCheck = $checkValidated->fetchColumn(0);
    $checkValidated->closeCursor();
    if ($validationCheck){
        if (login($email, $password, $mysqli) == true) {
            // Login success 
            header('Location: ../YourAccount.php');
        } else {
            // Login failed 
            header('Location: ../Login.php?error=1');
        }
    }
    else{
        echo 'Account not validated.';
    d($validationCheck);
    }
}
else {
   // The correct POST variables were not sent to this page. 
   echo 'Invalid Request';
    d($validationCheck);
}
?>
