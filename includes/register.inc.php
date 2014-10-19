<?php
include_once 'db_connect.php';
include_once 'psl-config.php'; 
$error_msg = "";
 
if (isset($_POST['email'], $_POST['p'], $_POST['companyname'], $_POST['billing'], $_POST['phone'])) {
    $companyname = filter_input(INPUT_POST, 'companyname', FILTER_SANITIZE_STRING);
    $billing = filter_input(INPUT_POST, 'billing', FILTER_SANITIZE_STRING);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_NUMBER_INT);
    // Sanitize and validate the data passed in
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Not a valid email
        $error_msg .= '<p class="error">The email address you entered is not valid</p>';
    }
 
    $password = filter_input(INPUT_POST, 'p', FILTER_SANITIZE_STRING);
    if (strlen($password) != 128) {
        // The hashed pwd should be 128 characters long.
        // If it's not, something really odd has happened
        $error_msg .= '<p class="error">Invalid password configuration.</p>';
    }
 
    // Username validity and password validity have been checked client side.
    // This should should be adequate as nobody gains any advantage from
    // breaking these rules.
    $prep_stmt = "SELECT COMPANY_ID FROM COMPANIES WHERE EMAIL = ? LIMIT 1";
    $stmt = $mysqli->prepare($prep_stmt);
 
   // check existing email  
    if ($stmt) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
 
        if ($stmt->num_rows == 1) {
            // A user with this email address already exists
            $error_msg .= '<p class="error">A user with this email address already exists.</p>';
            $stmt->close();
        }else{
            $stmt->close();
        }
    } else {
        $error_msg .= '<p class="error">Database error Line 39</p>';
    }
 
    if (empty($error_msg)) {
        // Create a random salt
        //$random_salt = hash('sha512', uniqid(openssl_random_pseudo_bytes(16), TRUE)); // Did not work
        $random_salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
 
        // Create salted password 
        $password = hash('sha512', $password . $random_salt);
        //Create the activation code thing to append to the url to make the activation url user has to click on.
        $validationCode = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
 
        // Insert the new user into the database 
	if ($insert_stmt = $mysqli->prepare("INSERT INTO COMPANIES 
		(EMAIL, PASSWORD, SALT, BILLING_ADDRESS, PHONE, COMPANY_NAME, VALIDATION_CODE) 
		VALUES ( ?, ?, ?, ?, ?, ?, ?)")) {
            $insert_stmt->bind_param('ssssiss', $email, $password, 
		        $random_salt, $billing, $phone, $companyname, $validationCode);
            // Execute the prepared query.
            if (!$insert_stmt->execute()) {
		        $insert_stmt->close();
                header('Location: ../error.php?err=Registration failure: INSERT');
            }
	        $insert_stmt->close();
        }
    }
    $to = $email;
    if(isset($_POST['email'])){ 
        if (!preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/",$to)){
              echo "Invalid email format"; 
              break;
        }
        $validationURL = "https://72.182.49.84:8080/accountConfirmationReceiver.php?validationCode="; #to be replaced with www.poink.org or something similar.
        $subject = "Poink Account Confirmation";
        $body = "Click on this link to confirm you are not a robot and activate your account: " . $validationURL . $validationCode;
        sendMail($to, $subject, $body);
    }
}
?>
