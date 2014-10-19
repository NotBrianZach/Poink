<?php
require_once "db_connect.php"
include_once "paypalFunctions.php";

// PayPal settings
$paypal_email = 'brianzacabel@utexas.edu';
$return_url = 'https://72.182.49.84:8080/YourAccount.php?payment=success';
$cancel_url = 'https://72.182.49.84:8080/YourAccount.php?payment=failure';
$notify_url = 'https://72.182.49.84:8080/paypal/payments.php';

$item_name = 'adCurrency';
$item_amount = 5.00;


//Database Connection
//$link = mysql_connect($host, $user, $pass);
//mysql_select_db($db_name);

// Check if paypal request or response
if (!isset($_POST["txn_id"]) && !isset($_POST["txn_type"])){

    // Firstly Append paypal account to querystring
    $querystring .= "?business=".urlencode($paypal_email)."&";  
    
    // Append amount & currency (USD) to quersytring so it cannot be edited in html
    
    //The item name and amount can be brought in dynamically by querying the $_POST['item_number'] variable.
    $querystring .= "item_name=".urlencode($item_name)."&";
    $querystring .= "amount=".urlencode($item_amount)."&";
    
    //loop for posted values and append to querystring
    foreach($_POST as $key => $value){
        $value = urlencode(stripslashes($value));
        $querystring .= "$key=$value&";
    }
    
    // Append paypal return addresses
    $querystring .= "return=".urlencode(stripslashes($return_url))."&";
    $querystring .= "cancel_return=".urlencode(stripslashes($cancel_url))."&";
    $querystring .= "notify_url=".urlencode($notify_url);
    
    // Append querystring with custom field
    //$querystring .= "&custom=".USERID;
    
    // Redirect to paypal IPN
    header('location:https://www.sandbox.paypal.com/cgi-bin/webscr'.$querystring);
    exit();

}else{
    // Response from Paypal

    // read the post from PayPal system and add 'cmd'
    $req = 'cmd=_notify-validate';
    foreach ($_POST as $key => $value) {
        $value = urlencode(stripslashes($value));
        $value = preg_replace('/(.*[^%^0^D])(%0A)(.*)/i','${1}%0D%0A${3}',$value);// IPN fix
        $req .= "&$key=$value";
    }
    
    // assign posted variables to local variables
    $data['item_name']          = $_POST['item_name'];
    $data['item_number']        = $_POST['item_number'];
    $data['payment_status']     = $_POST['payment_status'];
    $data['payment_amount']     = $_POST['mc_gross'];
    $data['payment_currency']   = $_POST['mc_currency'];
    $data['txn_id']             = $_POST['txn_id'];
    $data['receiver_email']     = $_POST['receiver_email'];
    $data['payer_email']        = $_POST['payer_email'];
    $data['custom']             = $_POST['custom'];
        
    // post back to PayPal system to validate
    $header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
    $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
    $header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
    
    $fp = fsockopen ('ssl://www.sandbox.paypal.com', 443, $errno, $errstr, 30); 
    
    if (!$fp) {
        // HTTP ERROR
        echo "HTTP ERROR";
    } else {    
        fputs ($fp, $header . $req);
        while (!feof($fp)) {
            $res = fgets ($fp, 1024);
            if (strcmp($res, "VERIFIED") == 0) {
                // Used for debugging
                //@mail("you@youremail.com", "PAYPAL DEBUGGING", "Verified Response<br />data = <pre>".print_r($post, true)."</pre>");
                // Validate payment (Check unique txnid & correct price)
                $valid_txnid = check_txnid($data['txn_id']);
                $valid_price = check_price($data['payment_amount']);
                // PAYMENT VALIDATED & VERIFIED!
                if($valid_txnid && $valid_price){               
                    $orderid = updatePayments($data);       
                    if($orderid){                   
                        $addaccount = $data['payment_amount'];
                        if ($addaccount >= 0){
                            // Insert the new account balance into the database 
	                        if ($update_balance = $database->prepare("UPDATE COMPANIES 
	                                SET BUDGET = BUDGET + :addaccount
	                        	    WHERE COMPANY_ID=:id")) {
                                $update_balance->bindValue(':addaccount', $addaccount, PDO::PARAM_STR);
	                            $update_balance->bindValue(':id',$_POST['companyId'], PDO::PARAM_INT);
                                // Execute the prepared query.
                                if (! $update_balance->execute()) {
	                        	    $update_balance->closeCursor();
                                        header('Location: ../error.php?err=Database failure: INSERT');
                                }
	                            $update_balance->closeCursor();
                            }
                        }
                    }else{                              
                        // Error inserting into DB
                        // E-mail admin or alert user
                    }
                }else{                  
                    // Payment made but data has been changed
                    // E-mail admin or alert user
                }                       
            
            }else if (strcmp ($res, "INVALID") == 0) {
            
                // PAYMENT INVALID & INVESTIGATE MANUALY! 
                // E-mail admin or alert user
                
                // Used for debugging
                //@mail("you@youremail.com", "PAYPAL DEBUGGING", "Invalid Response<br />data = <pre>".print_r($post, true)."</pre>");
            }       
        }       
    fclose ($fp);
    }   
}
