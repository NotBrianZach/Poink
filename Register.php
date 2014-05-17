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
            Advertister Registration
            </h1>
        </div>
        <div id="registrationform" class="loginform">
            <form method="post" action="insertRegistration.php">
                <p>Poink Account name:</p> <input type="text" name="accountname"/>
                <p>Company name:</p> <input type="text" name="companyname"/>
                <p>E-mail:</p> <input type="text" name="email"/>
                <p>Billing Address:</p> <textarea name="billing" rows="4" columns ="20"></textarea>
                <p>Phone:</p> <input type="text" name="phone"/>
                <p>Password:</p> <input type="password" name="password"/>
                <p>Confirm Password:</p> <input type="password" name="confirmpassword"/>
		<input type="submit"/>
		<br/>
            </form>
        </div>
    </body>
</html>
