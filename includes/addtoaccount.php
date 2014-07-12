<?php
include_once 'db_connect.php';
include_once 'psl-config.php';

if (isset($_POST['addaccount'])) {
    $addaccount = filter_input(INPUT_POST, 'addaccount', FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
   	
    if (!filter_var($addaccount, FILTER_VALIDATE_FLOAT)){
	$error_msg = '<p class="error"> Invalid amount to add to account</p>';
    } 
    if (empty($error_msg)) {
	//get old account balance
	$get_balance = $database->prepare("SELECT BUDGET FROM COMPANIES WHERE COMPANY_ID=:id");
	$get_balance->bindValue(':id',$_POST['companyId'],PDO::PARAM_INT);
	$get_balance->execute();
	$ifbalancenull = $get_balance->fetchColumn(0);
	if (!is_null($ifbalancenull)){
		$addaccount += floatval($ifbalancenull);
	}
	$get_balance->closeCursor();
        // Insert the new account balance into the database 
	if ($update_balance = $database->prepare("UPDATE COMPANIES 
	        SET BUDGET = :addaccount
		WHERE COMPANY_ID=:id")) {
            $update_balance->bindValue(':addaccount', $addaccount, PDO::PARAM_STR);
	    $update_balance->bindValue(':id',$_POST['companyId'], PDO::PARAM_INT);
            // Execute the prepared query.
            if (! $update_balance->execute()) {
		$update_balance->closeCursor();
                header('Location: ../error.php?err=Database failure: INSERT');
            }
        }
	$update_balance->closeCursor();
    }
}
?>
