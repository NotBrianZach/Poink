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
$newIdQuery->bindValue(':seqGenName', 'PERSON', PDO::PARAM_STR);
$newIdQuery->execute();
$newId = $newIdQuery ->fetchColumn(0);
$newIdQuery->closeCursor();

$insertRegistrationStmt = $database->prepare('
	
        INSERT INTO PERSON
		(PERSON_ID,NAME,EMAIL_ADDRESS,PASSWORD)
		VALUES (:person_id, :name, :email, :password);
	');
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
if ($password == FALSE){
	echo "No password entered.";
	exit(1);
}
$insertRegistrationStmt->bindValue(':name',$_POST['user'], PDO::PARAM_STR);
$insertRegistrationStmt->bindValue(':email',$_POST['email'], PDO::PARAM_STR);
$insertRegistrationStmt->bindValue(':password',$password, PDO::PARAM_STR);
$insertRegistrationStmt->bindValue(':person_id',$newId, PDO::PARAM_INT);
$insertRegistrationStmt->execute();
$insertRegistrationStmt->closeCursor();
session_start();
session_regenerate_id(true);
$_SESSION['user']=$_POST['user'];
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
            BuySellBuyBuy! The Confirmation Form!
            </h1>
        </div>
        <div id="confirmationform">
            <p>Registration Complete.</p>
            <form method="post" action="YourAccount.php">
		<input type="submit" value="Start Sellin"/>
            </form>
        </div>
	<div id="thekid">
		<img src="kid.jpg" alt="Image not found"/>
		<p>Stanley registered. Look how happy he is.</p>
	</div>
    </body>
</html>
