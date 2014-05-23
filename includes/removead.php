<?php
include_once 'db_connect.php';
include_once 'psl-config.php';

if (isset($_POST['removedquestionid'])) {
	$get_balance = $database->prepare("SELECT BUDGET FROM COMPANIES WHERE COMPANY_ID=:id");
	$get_balance->bindValue(':id',$_POST['companyid'],PDO::PARAM_INT);
	$get_balance->execute();
	$ifbalancenull = $get_balance->fetchColumn(0);
	if (!is_null($ifbalancenull)){
		$unallocatedbudget = floatval($ifbalancenull);
	}
	else{
		$unallocatedbudget = 0;
	}
	$get_balance->closeCursor();

	$unallocatedbudgetmodify = floatval($_POST['budget']);
	$unallocatedbudget += $unallocatedbudgetmodify;

	$update_budget = $database->prepare("UPDATE COMPANIES SET BUDGET=:newbudget WHERE COMPANY_ID=:id");
	$update_budget->bindValue(':id',$_POST['companyid'],PDO::PARAM_INT);
	$update_budget->bindValue(':newbudget', $unallocatedbudget, PDO::PARAM_STR);
	$update_budget->execute();
	$update_budget->closeCursor();
	
	$remove_question = $database->prepare("UPDATE QUESTIONS SET DELETED=1 WHERE QUESTION_ID=:id");
	$remove_question->bindValue(':id',$_POST['removedquestionid'],PDO::PARAM_INT);
	$remove_question->execute();
	$remove_question->closeCursor();
}
?>
