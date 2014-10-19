<?php
include_once './includes/functions.php';
sec_session_start();
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Secure Login: Registration Form</title>
        <script type="text/JavaScript" src="./js/sha512.js"></script> 
        <script type="text/JavaScript" src="./js/forms.js"></script>
        <link rel="stylesheet" href="mystyle.css" />
    </head>
    <body>
        <div id="header" class="sitename">
            <h1>
            Advertister Registration
            </h1>
        </div>
        <!-- Registration form to be output if the POST variables are not
        set or if the registration script caused an error. -->
        <?php
        if (!empty($error_msg)) {
            echo $error_msg;
        }
        ?>
    <div id="registrationform" class="loginform">
        <ul>
            <li>Usernames may contain only digits, upper and lower case letters and underscores</li>
            <li>Emails must have a valid email format</li>
            <li>Passwords must be at least 6 characters long</li>
            <li>Passwords must contain
                <ul>
                    <li>At least one upper case letter (A..Z)</li>
                    <li>At least one lower case letter (a..z)</li>
                    <li>At least one number (0..9)</li>
                </ul>
            </li>
            <li>Your password and confirmation must match exactly</li>
        </ul>
        <form action="accountConfirmationSender.php"
                method="post" 
                name="registration_form">
            Email: <br/> <input type="text" name="email" id="email" /><br/>
            Password: <br/> <input type="password"
                             name="password" 
                             id="password"/><br/>
            Confirm password: <br/> <input type="password" 
                                     name="confirmpwd" 
                                     id="confirmpwd" /><br/>
            Company name: <br/> <input type="text" name="companyname" id="companyname"/><br/>
            Billing Address: <br/> <textarea name="billing" id="billing" rows="4" cols="20"></textarea><br/>
            Phone: <br/> <input type="text" name="phone" id="phone"/> Ex. 123-456-7891<br/>
            <input type="button" 
                   value="Register" 
                   onclick="return regformhash(this.form,
                                   this.form.email,
                                   this.form.password,
				                   this.form.companyname,
				                   this.form.billing,
				                   this.form.phone,
                                   this.form.confirmpwd);" /> 
        </form>
        <p>Return to the <a href="index.php">Home page</a>.</p>
    </div>
    </body>
</html>
