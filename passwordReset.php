<?php
include_once './includes/functions.php';
sec_session_start();
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
                You might want to check your email. This may take a minute.
            </h1>
        </div>
        <?php
        if (isset($_GET['error'])) {
            echo '<p class="error">Error sending password</p>';
        }
        ?> 
        <div class="loginform">
            <form method="post" action="./includes/passwordResetValidation.php" name="login_form">
                <p>Copy and paste the string from the email:</p> <input type="text" name="validation"/>
                <p>New Password:</p> <input type="password" name="password" id="password"/>
                <p>New Password Confirmation:</p> <input type="password" name="passconf" id="passconf"/>
		<br/>
                <input type="button" 
                   value="Login" 
                   onclick="resetformhash(this.form, this.form.validation, this.form.password, this.form.passconf);" />
		<br/>
            </form>
        </form>
        </div>
    </body>
</html>
