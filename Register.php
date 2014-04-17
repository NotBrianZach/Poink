<?php
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== "on") {
    header('Location: https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
    exit(1);
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
    <title>Register</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="mystyle.css"/>
</head>
    <body> 
        <div id="header" class="sitename">
            <h1>
            BuySellBuyBuy! The Registration Form!
            </h1>
        </div>
        <div id="registrationform" class="loginform">
            <form method="post" action="insertRegistration.php">
                <p>Username:</p> <input type="text" name="user"/>
                <p>E-mail:</p> <input type="text" name="email"/>
                <p>Password:</p> <input type="password" name="password"/>
		<input type="submit"/>
		<p></p>
            </form>
        </div>
    </body>
</html>
