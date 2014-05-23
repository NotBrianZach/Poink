<?php
include_once './includes/db_connect.php';
include_once './includes/functions.php';
sec_session_start();
try {
$getBudget = $database->prepare('SELECT BUDGET FROM COMPANIES WHERE COMPANY_ID=:id');
$getBudget->bindValue(':id',$_POST['companyId'], PDO::PARAM_INT);
$getBudget->execute();
$budget = $getBudget->fetchcolumn(0);
$getBudget->closeCursor();

$budget -= $_POST['budget'];

$updateBudget = $database->prepare('UPDATE COMPANIES SET BUDGET=:newbudget WHERE COMPANY_ID=:id');
$updateBudget->bindValue(':newbudget',$budget, PDO::PARAM_STR);
$updateBudget->bindValue(':id',$_POST['companyId'], PDO::PARAM_STR);
$updateBudget->execute();
$updateBudget->closeCursor();

$insertQuestionStmt = $database->prepare('
        INSERT INTO QUESTIONS (BID, BUDGET, QUESTION, MIN_AGE, MAX_AGE, TARGET_GENDERS, QUESTION_ID, COMPANY_ID)
	VALUES (:bid, :budget, :question, :minage, :maxage, :gender, :questionid, :companyid);
	');
$insertQuestionStmt->bindValue(':bid',$_POST['bid'], PDO::PARAM_STR);
$insertQuestionStmt->bindValue(':budget',$_POST['budget'], PDO::PARAM_STR);
$insertQuestionStmt->bindValue(':question',$_POST['question'], PDO::PARAM_STR);
$insertQuestionStmt->bindValue(':minage',$_POST['minage'], PDO::PARAM_INT);
$insertQuestionStmt->bindValue(':maxage',$_POST['maxage'], PDO::PARAM_INT);
$insertQuestionStmt->bindValue(':gender',$_POST['gender'], PDO::PARAM_INT);
$insertQuestionStmt->bindValue(':questionid',$_POST['questionId'], PDO::PARAM_INT);
$insertQuestionStmt->bindValue(':companyid',$_POST['companyId'], PDO::PARAM_INT);
$insertQuestionStmt->execute();
$insertQuestionStmt->closeCursor();
}
catch(Exception $e) {
    echo 'Exception inserting question -> ';
    var_dump($e->getMessage());
}
$var = $_POST['lat'];
//THIS IS WHERE QUESTION COORDS INSERTIONS START
for ($i=0; $i < sizeof($_POST['lat']); $i++){
    $newQuestionCoordIdQuery = $database->prepare('
    	SELECT NEXT_SEQ_VALUE(:seqGenName);
    	');
    $newQuestionCoordIdQuery->bindValue(':seqGenName', 'QUESTION_COORDS', PDO::PARAM_STR);
    $newQuestionCoordIdQuery->execute();
    $newQuestionCoordId = $newQuestionCoordIdQuery ->fetchColumn(0);
    $newQuestionCoordIdQuery->closeCursor();

    $insertQuestionCoordsStmt = $database->prepare('
            INSERT INTO QUESTION_COORDS
    		(LAT, LNG, RADIUS, QUESTION_ID, QUESTION_COORD_ID)
    		VALUES (:lat, :lng, :radius, :questionid, :questioncoordid);
    	');
    $insertQuestionCoordsStmt->bindValue(':lat',$_POST['lat'][$i], PDO::PARAM_STR);
    $insertQuestionCoordsStmt->bindValue(':lng',$_POST['lng'][$i], PDO::PARAM_STR);
    $insertQuestionCoordsStmt->bindValue(':radius',$_POST['radius'][$i], PDO::PARAM_STR);
    $insertQuestionCoordsStmt->bindValue(':questionid',$_POST['questionId'], PDO::PARAM_INT);
    $insertQuestionCoordsStmt->bindValue(':questioncoordid',$newQuestionCoordId, PDO::PARAM_INT);
    $insertQuestionCoordsStmt->execute();
    $insertQuestionCoordsStmt->closeCursor();
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
    <title>Confirmation</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="mystyle.css"/>
</head>
    <body> 
        <div class="sitename">
            <h1>
            Question uploaded to database
            </h1>
        </div>
        <div id="confirmationform">
            <p>New Listing Uploaded.</p>
            <form>
                <a href="YourAccount.php">[Back to Your Account]</a>
            </form>
        </div>
    </body>
</html>
