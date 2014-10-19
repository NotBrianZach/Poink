<?php
    include_once './includes/functions.php';
    sec_session_start();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
    <title>Confirmation Received</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="mystyle.css" />
    <script type="text/JavaScript" src="js/sha512.js"></script> 
    <script type="text/JavaScript" src="js/forms.js"></script> 
</head>
    <body> 
<?php if (loginCheck($mysqli) == true)://logged in?>
        <div id="header" class="sitename">
            <h1>
                You've already confirmed your account.
            </h1>
        </div>
        <a href="YourAccount.php">Home</a>
<?php else:?>
        <div id="header" class="sitename">
            <h1>
                Log in to Confirm Account Validation
            </h1>
        </div>
        <?php
        if (isset($_GET['error'])) {
            echo '<p class="error">Error logging in. Check your email and password.</p>';
        }
        ?> 
        <div class="loginform">
        <form method="post" action="./includes/process_confirmation_login.php?validationCode=<?=htmlspecialchars($_GET['validationCode'])?>" name="login_form">
            <p>Account Name/Email:</p> <input type="text" name="email"/>
            <p>Password:</p> <input type="password" name="password" id="password"/>
		    <br/>
            <input type="button" 
               value="Login" 
               onclick="formhash(this.form, this.form.password);" /> 
		    <br/>
        </form>
<?php endif;?>
    </body>
</html
