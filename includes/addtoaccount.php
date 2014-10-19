<?php
include_once 'db_connect.php';
include_once 'psl-config.php';

if (isset($_POST['addaccount'],$_POST['companyId'],$_POST['companyBudget'])) {
    $addaccount = filter_input(INPUT_POST, 'addaccount', FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
	//get old account balance
	$get_balance = $database->prepare("SELECT BUDGET FROM COMPANIES WHERE COMPANY_ID=:id");
	$get_balance->bindValue(':id',$_POST['companyId'],PDO::PARAM_INT);
	$get_balance->execute();
	$balance = $get_balance->fetchColumn(0);//THIS CRAP DOESN"T WORK WHEN THE BALANCE IS NULL, FYI
	$get_balance->closeCursor();
	if (!is_null($balance)){
	    $addaccount += floatval($balance);
	}
    else{
        $addaccount = 0;
    }
    if ($addaccount >= 0){
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
	        $update_balance->closeCursor();
        }
    }
}
?>
