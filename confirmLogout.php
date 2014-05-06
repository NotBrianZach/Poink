<?php
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== "on") { //not sure if I need this
    header('HTTP/1.1 403 Forbidden: TLS Required');
    echo "why you no use https? is you a dummy?";
    exit(1);
}
require '/var/scripts/openZdatabase.php';
session_destroy();
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
            <p>Loggin' ya out.</p>
            <form method="post" action="index.php">
		<input type="submit" value="Confirm logout."/>
            </form>
        </div>
	<div id="thekid">
		<img src="kid.jpg" alt="Image not found"/>
		<p>Stanley logged out. He's probably sad on the inside.</p>
	</div>
    </body>
</html>
