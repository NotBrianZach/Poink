<?php
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== "on") {
    header('Location: https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
    exit(1);
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
    <title>Login</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="mystyle.css"/>
</head>
    <body> 
        <div id="header" class="sitename">
            <h1>
		Login
            </h1>
        </div>
        <div class="loginform">
            <form method="post" action="confirmLogin.php">
                <p>Account Name:</p> <input type="text" name="user"/>
                <p>Password:</p> <input type="password" name="password"/>
		<p></p>
		<input type="submit"/>	
		<p></p>
            </form>
        </div>
    </body>
</html>
