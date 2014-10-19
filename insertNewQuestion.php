<?php
include_once './includes/db_connect.php';
include_once './includes/functions.php';
include_once './includes/globalVariables.php';
require_once 'vendor/autoload.php';
sec_session_start();
if (isset($_POST['questionId'],$_POST['companyBudget']))
{
    $questionId = filter_input(INPUT_POST, 'questionId', FILTER_SANITIZE_NUMBER_INT);
    $companyId = $_SESSION['companyId'];
    $companyBudget = filter_input(INPUT_POST, 'companyBudget', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    if (!isset($_POST['lat']) && isset($_POST['minage'], $_POST['maxage'], $_POST['gender'],
        $_POST['question'], $_POST['companyBudget'], $_POST['questionId'], $_POST['companyId'])) {
        $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_NUMBER_INT);
        $minage = filter_input(INPUT_POST, 'minage', FILTER_SANITIZE_NUMBER_INT);
        $maxage = filter_input(INPUT_POST, 'maxage', FILTER_SANITIZE_NUMBER_INT);
        $question = filter_input(INPUT_POST, 'question', FILTER_SANITIZE_STRING);
        if ($minage <= $maxage){
            if ($_SESSION['updated'] === 0){
                try {
                    $insertQuestionStmt = $database->prepare('
                            INSERT INTO QUESTIONS (QUESTION, MIN_AGE, MAX_AGE, TARGET_GENDERS, QUESTION_ID, COMPANY_ID)
                    	VALUES (:question, :minage, :maxage, :gender, :questionid, :companyid);
                    	');
                    $insertQuestionStmt->bindValue(':question', $question, PDO::PARAM_STR);
                    $insertQuestionStmt->bindValue(':minage', $minage, PDO::PARAM_INT);
                    $insertQuestionStmt->bindValue(':maxage', $maxage, PDO::PARAM_INT);
                    $insertQuestionStmt->bindValue(':gender', $gender, PDO::PARAM_INT);
                    $insertQuestionStmt->bindValue(':questionid', $questionId, PDO::PARAM_INT);
                    $insertQuestionStmt->bindValue(':companyid', $companyId, PDO::PARAM_INT);
                    $insertQuestionStmt->execute();
                    $insertQuestionStmt->closeCursor();
                }
                catch(Exception $e) {
                    echo 'Exception inserting question -> ';
                    var_dump($e->getMessage());
                }
            }
            else {//$_SESSION['updated'] == 1
               try{
                   $updateQuestionStmt = $database->prepare('
                        UPDATE QUESTIONS
                            SET
                            QUESTION=:question, MIN_AGE=:minage, MAX_AGE=:maxage, TARGET_GENDERS=:gender
                            WHERE QUESTION_ID = :questionid;
                        ');
                   $updateQuestionStmt->bindValue(':question', $question, PDO::PARAM_STR);
                   $updateQuestionStmt->bindValue(':minage', $minage, PDO::PARAM_INT);
                   $updateQuestionStmt->bindValue(':maxage', $maxage, PDO::PARAM_INT);
                   $updateQuestionStmt->bindValue(':gender', $gender, PDO::PARAM_INT);
                   $updateQuestionStmt->bindValue(':questionid', $questionId, PDO::PARAM_INT);
                   $updateQuestionStmt->execute();
                   $updateQuestionStmt->closeCursor();
               }
               catch(Exception $e) {
                   echo 'Exception updating question -> ';
                   var_dump($e->getMessage());
               }
            }
        }
    }
    
    function filterFloat($thingToFilter){
        return filter_var($thingToFilter, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    function filterDate($thingToFilter){
        if (!is_null($thingToFilter))
            return filter_var($thingToFilter, FILTER_SANITIZE_STRING);
        //string must be this format. 07/19/2014 11 pm or blank. checked for this in javascript,
        //so if people override it, it's their own fault this breaks.
        else
            return $endDateNullValue;
    }

    //THIS IS WHERE QUESTION COORDS INSERTIONS START, AND WHERE WE UPDATE THE TOTAL BUDGET.
    if(isset($_POST['lat'], $_POST['lng'], $_POST['radius'], $_POST['questionBudget'], $_POST['date'])){
        $questionBudget = array_map("filterFloat",$_POST['questionBudget']);
        $bid = array_map("filterFloat",$_POST['bid']);
        $lat = array_map("filterFloat",$_POST['lat']);
        $lng = array_map("filterFloat",$_POST['lng']);
        $radius = array_map("filterFloat",$_POST['radius']);
        $date = array_map("filterDate",$_POST['date']);//This depends on numCircles in the js on YourAccount.php 
        $database->beginTransaction();
        for ($i=0; $i < sizeof($lat); $i++){
            try{
                $grabQuestionCoordId = $database->prepare('
                        SELECT NEXT_SEQ_VALUE(:seqGenName);
                    ');
                $grabQuestionCoordId->bindValue(':seqGenName', 'QUESTION_COORDS', PDO::PARAM_STR);
                $grabQuestionCoordId->execute();
                $questionCoordId = $grabQuestionCoordId->fetchColumn(0);
                $grabQuestionCoordId->closeCursor();
                $insertQuestionCoordsStmt = $database->prepare('
                        INSERT INTO QUESTION_COORDS 
                        (LAT, LNG, RADIUS, BID, BUDGET, QUESTION_ID, QUESTION_COORD_ID, END_DATE, VALID)
                		VALUES (:lat, :lng, :radius, :bid, :questionBudget, :questionId, :questionCoordId, :date, 1);
                	');
                $insertQuestionCoordsStmt->bindValue(':lat', $lat[$i], PDO::PARAM_STR);
                $insertQuestionCoordsStmt->bindValue(':lng', $lng[$i], PDO::PARAM_STR);
                $insertQuestionCoordsStmt->bindValue(':radius', $radius[$i], PDO::PARAM_STR);
                $insertQuestionCoordsStmt->bindValue(':bid', $bid[$i], PDO::PARAM_STR);
                $insertQuestionCoordsStmt->bindValue(':questionBudget', $questionBudget[$i], PDO::PARAM_STR);
                $insertQuestionCoordsStmt->bindValue(':questionId', $questionId, PDO::PARAM_INT);
                $insertQuestionCoordsStmt->bindValue(':questionCoordId', $questionCoordId, PDO::PARAM_INT);
                $insertQuestionCoordsStmt->bindValue(':date', $date[$i], PDO::PARAM_STR); //This depends on numCircles in the js on YourAccount.php
                $insertQuestionCoordsStmt->execute();
                $insertQuestionCoordsStmt->closeCursor();
                $companyBudget -= $questionBudget[$i];
                if ($companyBudget < 0){
                    $database->rollback();
                    $companyBudget += $questionBudget[$i];
                    echo "Error, the total budget for your questions exceeds your overall budget"; 
                    break;
                }
            }
            catch(PDOexception $e){
                if ($e->getCode() == '23000') 
                    echo "You need to submit demographic information/the question before submitting the whole bid to the database: ".$e->getMessage(); 
                else
                    echo "Error " . $e;
                $database->rollback();
            }
        }
        $database->commit();
        $updateBudget = $database->prepare('UPDATE COMPANIES SET BUDGET=:newbudget WHERE COMPANY_ID=:id');
        $updateBudget->bindValue(':newbudget',$companyBudget, PDO::PARAM_STR);
        $updateBudget->bindValue(':id',$companyId, PDO::PARAM_STR);
        $updateBudget->execute();
        $updateBudget->closeCursor();
    }
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
    <title>Confirmation</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="mystyle.css"/>
</head>
        <div class="sitename">
            <h1>
                <?=$_GET['identifier']?> uploaded to database
            </h1>
        </div>
    <?php if( loginCheck($mysqli) == true): ?>
        <div id="confirmationform">
            <p><?=$_GET['identifier']?> Uploaded.</p>
        <?php if($_GET['identifier'] === "Advert and Demographic Info"): ?>
	        <form method='post' action='YourAccount.php' enctype='multipart/form-data'>
	        <input type='hidden' value='<?=$question?>' name='question'/>
	        <input type='hidden' value='<?=$bid?>' name='bid'/>
	        <input type='hidden' value='<?=$minage?>' name='minage'/>
	        <input type='hidden' value='<?=$maxage?>' name='maxage'/>
	        <input type='hidden' value='<?=$gender?>' name='gender'/>
	        <input type='hidden' value='<?=$questionBudget?>' name='questionBudget'/>
	        <input type='hidden' value='<?=$companyBudget?>' name='companyBudget'/>
	        <input type='hidden' value='<?=$questionId?>' name='questionId'/>
	        <input type='submit' value='Back to Your Account'/>
	        </form>
<!--this is for when submitting the coordinates-->
        <?php else: ?>
	        <form method='post' action='YourAccount.php' enctype='multipart/form-data'>
            <!--we don't set questionId here and that tells the youraccount page to start a new question-->
            <!--wait I can't tell what this is actually used for...-->
	        <input type='submit' value='Back to Your Account'/>
	        </form>
        <?php endif; ?>
    <?php else: ?>
	<p>
		<span class="error">You are not authorized to access this page</span>
	</p>
    <?php endif; ?>
        </div>
    </body>
</html>
