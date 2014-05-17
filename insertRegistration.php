<?php
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== "on") {
    header('HTTP/1.1 403 Forbidden: TLS Required');
    echo "why you no use https? is you a dummy?";
    exit(1);
}
require '/var/script/openZdatabase.php';
require 'password.php';
$newIdQuery = $database->prepare('
	SELECT NEXT_SEQ_VALUE(:seqGenName);
	');
$newIdQuery->bindValue(':seqGenName', 'COMPANIES', PDO::PARAM_STR);
$newIdQuery->execute();
$newId = $newIdQuery ->fetchColumn(0);
$newIdQuery->closeCursor();

$insertRegistrationStmt = $database->prepare('
        INSERT INTO COMPANIES
		(ACCOUNT_NAME, COMPANY_ID, BILLING_ADDRESS, EMAIL, PHONE, COMPANY_NAME, PASSWORD)
		VALUES (:accountname, :companyid, :billing, :email, :phone, :companyname, :password);
	');
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
if ($password == FALSE){
	echo "No password entered.";
	exit(1);
}
$insertRegistrationStmt->bindValue(':accountname',$_POST['accountname'], PDO::PARAM_STR);
$insertRegistrationStmt->bindValue(':billing',$_POST['billing'], PDO::PARAM_STR);
$insertRegistrationStmt->bindValue(':companyname',$_POST['companyname'], PDO::PARAM_STR);
$insertRegistrationStmt->bindValue(':email',$_POST['email'], PDO::PARAM_STR);
$insertRegistrationStmt->bindValue(':phone',$_POST['phone'], PDO::PARAM_STR);
$insertRegistrationStmt->bindValue(':password',$password, PDO::PARAM_STR);
$insertRegistrationStmt->bindValue(':companyid',$newId, PDO::PARAM_INT);
$insertRegistrationStmt->execute();
$insertRegistrationStmt->closeCursor();
session_start();
session_regenerate_id(true);
$_SESSION['user']=$_POST['accountname'];
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
    <title>Confirmation</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="mystyle.css"/>
</head>
    <body> 
        <div class="sitename">
            <h1>
		Registration Complete.
            </h1>
        </div>
        <div id="confirmationform">
            <p>Registration Complete.</p>
            <form method="post" action="YourAccount.php">
		<input type="submit" value="Proceed to Poink Advertisers"/>
            </form>
        </div>
    </body>
</html>
