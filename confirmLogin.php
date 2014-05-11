<?php
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== "on") 
{
    header('HTTP/1.1 403 Forbidden: TLS Required');
    echo "why you no use https? is you a dummy?";
    exit(1);
}
require '/var/script/openZdatabase.php';
require 'password.php';

$passwordQuery = $database->prepare('
    SELECT 
        PASSWORD
    FROM PERSON 
    WHERE PERSON.NAME = :name;
');
$passwordQuery->bindValue(':name', $_POST['user'], PDO::PARAM_STR);  
$passwordQuery->execute();
$passwordHash = $passwordQuery->fetchColumn(0);
$passwordQuery->closeCursor();
if (!password_verify($_POST['password'],$passwordHash))
{
    echo 'Password does not match.';
    exit(1);
}
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
            <p>Login Complete.</p>
            <form method="post" action="YourAccount.php">
		<input type="submit" value="Start Sellin"/>
            </form>
        </div>
	<div id="thekid">
		<img src="kid.jpg" alt="Image not found"/>
		<p>Stanley logged in. Look how happy he is.</p>
	</div>
    </body>
</html>
