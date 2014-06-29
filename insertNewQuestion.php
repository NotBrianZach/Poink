<?php
include_once './includes/db_connect.php';
include_once './includes/functions.php';
sec_session_start();
$questionId = filter_input(INPUT_POST, 'questionId', FILTER_SANITIZE_NUMBER_INT);
$gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_NUMBER_INT);
if (isset($_POST['bid'], $_POST['questionBudget'], $_POST['minage'], $_POST['maxage'], $_POST['gender'], $_POST['question'],
	$_POST['companyBudget'], $_POST['questionId'], $_POST['companyId'])) {
    $bid = filter_input(INPUT_POST, 'bid', FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
    $minage = filter_input(INPUT_POST, 'minage', FILTER_SANITIZE_NUMBER_INT);
    $maxage = filter_input(INPUT_POST, 'maxage', FILTER_SANITIZE_NUMBER_INT);
    $question = filter_input(INPUT_POST, 'question', FILTER_SANITIZE_STRING);
    $companyId = filter_input(INPUT_POST,'companyId', FILTER_SANITIZE_NUMBER_INT);
    $companyBudget = filter_input(INPUT_POST, 'companyBudget', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $questionBudget = filter_input(INPUT_POST, 'questionBudget', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        
if ($_SESSION['questionId'] != $questionId){
    try {
        $companyBudget -= $questionBudget;//have to prevent this from messing with users account balance if they come back to this page.
    //how would I know if they are coming back? if id already exists. need previous sql query then.
    //better plan, store it in a session variable.which you now see 2 lines above.
        $updateBudget = $database->prepare('UPDATE COMPANIES SET BUDGET=:newbudget WHERE COMPANY_ID=:id');
        $updateBudget->bindValue(':newbudget',$companyBudget, PDO::PARAM_STR);
        $updateBudget->bindValue(':id',$companyId, PDO::PARAM_STR);
        $updateBudget->execute();
        $updateBudget->closeCursor();
        
        $insertQuestionStmt = $database->prepare('
                INSERT INTO QUESTIONS (BID, BUDGET, QUESTION, MIN_AGE, MAX_AGE, TARGET_GENDERS, QUESTION_ID, COMPANY_ID)
        	VALUES (:bid, :questionBudget, :question, :minage, :maxage, :gender, :questionid, :companyid);
        	');
        $insertQuestionStmt->bindValue(':bid', $bid, PDO::PARAM_STR);
        $insertQuestionStmt->bindValue(':questionBudget', $questionBudget, PDO::PARAM_STR);
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
}
 
//THIS IS WHERE QUESTION COORDS INSERTIONS START
//problem is $thing to filter is not the name of one of the post inputs.... it is a post input, it's the associated value!
function filterFloat($thingToFilter){
    return filter_var($thingToFilter, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}
function filterInt($thingToFilter){
    return filter_var($thingToFilter, FILTER_SANITIZE_NUMBER_INT);
}

if(isset($_POST['lat'],$_POST['lng'],$_POST['radius'])){
    $lat = array_map("filterFloat",$_POST['lat']);
    $lng = array_map("filterFloat",$_POST['lng']);
    $radius = array_map("filterFloat",$_POST['radius']);
    var_dump($lat);
    var_dump($lng);
    var_dump($radius);
    for ($i=0; $i < sizeof($lat); $i++){
//IDK CAN WE IMPLEMENT INPUT FILTERS ON AN ARRAY? THIS IS PROBABLY MESSED UP RIGHT NOW..
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
        $insertQuestionCoordsStmt->bindValue(':lat',$lat[$i], PDO::PARAM_STR);
        $insertQuestionCoordsStmt->bindValue(':lng',$lng[$i], PDO::PARAM_STR);
        $insertQuestionCoordsStmt->bindValue(':radius',$radius[$i], PDO::PARAM_STR);
        $insertQuestionCoordsStmt->bindValue(':questionid',$_POST['questionId'], PDO::PARAM_INT);
        $insertQuestionCoordsStmt->bindValue(':questioncoordid',$newQuestionCoordId, PDO::PARAM_INT);
        $insertQuestionCoordsStmt->execute();
        $insertQuestionCoordsStmt->closeCursor();
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
    <body> 
        <div class="sitename">
            <h1>
            <?=$_GET['identifier']?> uploaded to database
            </h1>
        </div>
    <?php if( login_check($mysqli) == true): ?>
        <div id="confirmationform">
            <p><?=$_GET['identifier']?> Uploaded.</p>
        <?php if( $_GET['identifier'] === "Advert%20and%20Demographic%20Info"): ?>
	        <form method='post' action='YourAccount.php' enctype='multipart/form-data'>
	        <input type='hidden' value='<?=$question?>' name='question'/>
	        <input type='hidden' value='<?=$bid?>' name='bid'/>
	        <input type='hidden' value='<?=$minage?>' name='minage'/>
	        <input type='hidden' value='<?=$maxage?>' name='maxage'/>
	        <input type='hidden' value='<?=$gender?>' name='gender'/>
	        <input type='hidden' value='<?=$questionBudget?>' name='questionBudget'/>
	        <input type='hidden' value='<?=$companyBudget?>' name='companyBudget'/><!--not clear where stuff below this is used.-->
	        <input type='hidden' value='<?=$questionId?>' name='questionId'/>
	        <input type='hidden' value='<?=$companyId?>' name='companyId'/>
	        <input type='submit' value='Back to Your Account'/>
	        </form>
<!--this is for when submitting the coordinates-->
        <?php else: ?>
	        <form method='post' action='YourAccount.php' enctype='multipart/form-data'>
            <!--we don't set questionId here and that tells the youraccount page to start a new questiohn-->
	        <input type='hidden' value='<?=$question?>' name='question'/>
	        <input type='hidden' value='<?=$bid?>' name='bid'/>
	        <input type='hidden' value='<?=$minage?>' name='minage'/>
	        <input type='hidden' value='<?=$maxage?>' name='maxage'/>
	        <input type='hidden' value='<?=$gender?>' name='gender'/>
	        <input type='hidden' value='<?=$questionBudget?>' name='questionBudget'/>
	        <input type='hidden' value='<?=$companyBudget?>' name='companyBudget'/>
	        <input type='hidden' value='<?=$companyId?>' name='companyId'/>
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
