<?php
include_once './includes/db_connect.php';
include_once './includes/functions.php';
sec_session_start();
 
if (loginCheck($mysqli) == true) {
    $logged = 'in';
} else {
    $logged = 'out';
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head> 
    <title>Login</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="mystyle.css"/>
    <script type="text/JavaScript" src="js/sha512.js"></script> 
    <script type="text/JavaScript" src="js/forms.js"></script> 
</head>
    <body> 
        <div id="header" class="sitename">
            <h1>
         		Login
            </h1>
        </div>
        <?php
        if (isset($_GET['error'])) {
            echo '<p class="error">Error logging in. Check your email and password.</p>';
        }
        ?> 
        <div class="loginform">
            <form method="post" action="./includes/process_login.php" name="login_form">
                <p>Email:</p> <input type="text" name="email"/>
                <p>Password:</p> <input type="password" name="password" id="password"/>
		<br/>
                <input type="button" 
                   value="Login" 
                   onclick="formhash(this.form, this.form.password);" /> 
		<br/>
            </form>
        <p>If you don't have a login, please <a href="register.php">register</a></p>
		<br/>
        <p>Can't remember your password, or didn't get an account confirmation email? 
            <form method="post" action="./includes/passwordResetEmail.php" name="password_reset_form">
                 Enter your Email: <input type="text" name="email"/>
                <input type="submit" value="Reset Password"/> 
            </form></p>
        <p>You are currently logged <?php echo $logged ?>.</p>
        </div>
    </body>
</html>
