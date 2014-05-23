<?php
include_once './includes/db_connect.php';
include_once './includes/functions.php';
 
sec_session_start();
 
if (login_check($mysqli) == true) {
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
            echo '<p class="error">Error Logging In!</p>';
        }
        ?> 
        <div class="loginform">
            <form method="post" action="./includes/process_login.php" name="login_form">
                <p>Account Name:</p> <input type="text" name="user"/>
                <p>Password:</p> <input type="password" name="password" id="password"/>
		<br/>
                <input type="button" 
                   value="Login" 
                   onclick="formhash(this.form, this.form.password);" /> 
		<br/>
            </form>
        </form>
        <p>If you don't have a login, please <a href="register.php">register</a></p>
        <p>If you are done, please <a href="includes/logout.php">log out</a>.</p>
        <p>You are currently logged <?php echo $logged ?>.</p>
        </div>
    </body>
</html>
