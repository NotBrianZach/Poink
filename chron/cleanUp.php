<?php
/*Run once every day at a low usage time (start at 00:00)
#1 A select statement that grabs all question coord end dates
#2 a loop that checks if the end day is today
#3 if the end date is today, add it to a queue
#4 sort the queue
#5 every hour check the first item in the queue, if it matches, start setting budgets to 0 and removing from queue until one doesn't match
#6 done.*/
$findEndDateQuestionCoords = $database->prepare('
        SELECT * FROM QUESTION_COORDS WHERE END_DATE != '0000000000000000';//END_DATE IS 16 CHARS
');
$findEndDateQuestionCoords->execute();
$findEndDateQuestionCoords->fetchAll();
$findEndDateQuestionCoords->closeCursor();
$findCompanyBudget = $database->prepare('
	SELECT
	    BUDGET
	FROM COMPANIES WHERE COMPANY_ID=:id;
    ');
$findCompanyBudget->bindValue(':id',$_SESSION['companyId'], PDO::PARAM_INT);
$findCompanyBudget->execute();
$companyBudget = $findCompanyBudget->fetchColumn(0);
$findCompanyBudget->closeCursor();
$hour = 0;
while ($hour < 24){
    foreach ($findEndDateQuestionCoords as $question){
        try{
            if (substr($question['END_DATE'],0,10) === gmdate("m/d/y")){
                //first check days equal then check if hour is less than hour.
                $questionCoordId = $question['QUESTION_COORD_ID'];
            }
            $database->beginTransaction();
            $closeBid = $database->prepare('
                UPDATE QUESTION_COORDS
                SET BUDGET = 0
                WHERE QUESTION_COORD_ID = :questionCoordId;
            ');
            $closeBid->bindValue(':questionCoordId', $questionCoordId ,PDO::PARAM_INT); 
            $closeBid->execute();
            $closeBid->closeCursor();
            //Need to update the total budget as well
            $companyBudget += $question['BUDGET'];
            $updateBudget = $database->prepare('UPDATE COMPANIES SET BUDGET=:newbudget WHERE COMPANY_ID=:id');
            $updateBudget->bindValue(':newbudget',$companyBudget, PDO::PARAM_STR);
            $updateBudget->bindValue(':id',$companyId, PDO::PARAM_STR);
            $updateBudget->execute();
            $updateBudget->closeCursor();
        }
        catch(PDOexception $e){
            if ($e->getCode() == '23000') 
                echo "You need to submit demographic information/the question before submitting the whole bid to the database: ".$e->getMessage(); 
            echo "Error " . $e;
            $database->rollback();
        }
        $database->commit();
    }
    $hour += 1;
    sleep(3600);
}

//Clear out the login attempts table once a day
$cleanLoginAttempts = $database->prepare('
    DELETE * FROM LOGIN_ATTEMPTS;
');
$cleanLoginAttempts->execute();
$cleanLoginAttempts->closeCursor();
