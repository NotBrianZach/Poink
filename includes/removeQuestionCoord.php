
<?php
include_once 'db_connect.php';
include_once 'psl-config.php';

if (isset($_POST['removeQuestionCoordId'])) {
    $questionCoordId = filter_input(INPUT_POST, 'removeQuestionCoordId', FILTER_SANITIZE_NUMBER_INT);
    $budget = filter_input(INPUT_POST, 'budget', FILTER_SANITIZE_NUMBER_FLOAT);
    $companyId = $_SESSION['companyId'];

    $getBalance = $database->prepare("SELECT BUDGET FROM COMPANIES WHERE COMPANY_ID=:companyId");
	$getBalance->bindValue(':companyId',$companyId,PDO::PARAM_INT);
	$getBalance->execute();
	$ifbalancenull = $getBalance->fetchColumn(0);
	if (!is_null($ifbalancenull)){
		$unallocatedbudget = floatval($ifbalancenull);
	}
	else{
		$unallocatedbudget = 0;
	}
	$getBalance->closeCursor();

	$unallocatedbudgetmodify = floatval($budget);
	$unallocatedbudget += $unallocatedbudgetmodify;

	$updateBudget = $database->prepare("UPDATE COMPANIES SET BUDGET=:newbudget WHERE COMPANY_ID=:id");
	$updateBudget->bindValue(':id',$companyId,PDO::PARAM_INT);
	$updateBudget->bindValue(':newbudget', $unallocatedbudget, PDO::PARAM_STR);
	$updateBudget->execute();
	$updateBudget->closeCursor();
	
	$removeQuestion = $database->prepare("DELETE FROM QUESTION_COORDS WHERE QUESTION_COORD_ID=:id");
	$removeQuestion->bindValue(':id',$questionCoordId,PDO::PARAM_INT);
	$removeQuestion->execute();
	$removeQuestion->closeCursor();
}
