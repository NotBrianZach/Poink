<?php
include_once './includes/db_connect.php';
include_once './includes/functions.php';
include_once './includes/sendMail.php';
include_once './includes/register.inc.php';
sec_session_start();
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Confirmation Sent</title>
        <script type="text/JavaScript" src="./js/sha512.js"></script> 
        <script type="text/JavaScript" src="./js/forms.js"></script>
        <link rel="stylesheet" href="mystyle.css" />
    </head>
    <body>
    <div id="header" class="sitename">
        <h1>
        Account Confirmation Sent
        </h1>
    </div>
        <p>An account confirmation link has been sent to your email at <?=htmlspecialchars($_POST['email'])?></p>
    </body>
</html>
