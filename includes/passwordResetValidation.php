<?php
include_once 'db_connect.php';
include_once 'functions.php';
require '../vendor/autoload.php';
sec_session_start();

if (isset($_SESSION['email'], $_POST['validation'], $_POST['p'])){
    $enteredValidationCode = filter_input(INPUT_POST, 'validation', FILTER_SANITIZE_STRING);
    $newpass = filter_input(INPUT_POST, 'p', FILTER_SANITIZE_STRING);
    $getCode = $database->prepare('
        SELECT VALIDATION_CODE, COMPANY_ID FROM COMPANIES WHERE EMAIL=:email;
    '); 
    $getCode->bindValue(':email', $_SESSION['email'], PDO::PARAM_STR);
    $getCode->execute();
    $validationCodeAndId = $getCode->fetchAll(PDO::FETCH_ASSOC);
    $getCode->closeCursor();
    unset($_SESSION['email']);
    $salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
    $password = hash('sha512', $newpass . $salt);
    if ($validationCodeAndId[0]['VALIDATION_CODE'] === $enteredValidationCode){
        $updatePassword = $database->prepare('
            UPDATE COMPANIES SET PASSWORD = :password, SALT = :salt, VALIDATED = 1
            WHERE COMPANY_ID = :companyId
            ');
        $updatePassword->bindValue(':password', $password, PDO::PARAM_STR);
        $updatePassword->bindValue(':salt', $salt, PDO::PARAM_STR);
        $updatePassword->bindValue(':companyId', $validationCodeAndId[0]['COMPANY_ID'], PDO::PARAM_INT);
        $updatePassword->execute();
        $updatePassword->closeCursor();
        header('Location: ../Login.php');
    }
    else{
        echo 'Wrong validation code;';
    }
}
?>
