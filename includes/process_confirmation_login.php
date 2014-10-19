<?php
include_once 'db_connect.php';
include_once 'functions.php';
require '../vendor/autoload.php';
 
sec_session_start(); // Our custom secure way of starting a PHP session.
if (isset($_GET['validationCode'])){
    if (isset($_POST['email'], $_POST['p'])) {
        $email = filter_input(INPUT_POST,'email',FILTER_SANITIZE_EMAIL);
        $getCode = $database->prepare('
            SELECT VALIDATION_CODE FROM COMPANIES WHERE EMAIL=:email;
        '); 
        $getCode->bindValue(':email', $email, PDO::PARAM_STR);
        $getCode->execute();
        $validationCode = $getCode->fetchColumn(0);
        $getCode->closeCursor();
        if ($_GET['validationCode'] === $validationCode){
            $password = $_POST['p']; // The hashed password.
            if (login($email, $password, $mysqli) == true) {
                $setValidation = $database->prepare('
                    UPDATE COMPANIES
                    SET VALIDATED = 1
                    WHERE COMPANY_ID = :companyId;
                '); 
                $setValidation->bindValue(':companyId',$_SESSION['companyId'],PDO::PARAM_INT);
                $setValidation->execute();
                $setValidation->closeCursor();
                header('Location: ../YourAccount.php');
            }
            else{
                // Login failed 
                header('Location: ../accountConfirmationReceiver.php?error=1');
            }
        } else {
            // The correct POST variables were not sent to this page. 
            echo 'Incorrect Validation Code';
            d($_GET['validationCode']);
            d($validationCode);
        }
    }
    else{
        echo "Email and password fields not filled out, no POST form fields detected.";
    }
}
else{
    echo "No Validation Code, how are we suposed to validate you?";
}
?>
