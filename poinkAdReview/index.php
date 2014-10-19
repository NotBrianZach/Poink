<?php
require_once '../vendor/autoload.php';
include_once '../includes/sendMail.php';
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';
session_start();

//We don't have to worry about security on this page since in the official set up it will only be visible over lan or to certain ip addresses.
if (isset($_POST['switch'])){
    if ($_POST['switch'] == 1 && ($_SESSION['currentQuestion'] < count($_SESSION['questionList']))){
        $_SESSION['currentQuestion'] += 1; 
    }
    elseif ($_SESSION['currentQuestion'] > 0){
        $_SESSION['currentQuestion'] -= 1;
    }
}
elseif (isset($_POST['validate'])){
    if ($_POST['validate'] == 1){
        $updateQuestionValidation = $database->prepare('
            UPDATE QUESTION_COORDS SET VALID = 1 WHERE QUESTION_COORD_ID = :questionId;
        '); 
        $updateQuestionValidation->bindValue(':questionId', $_SESSION['questionList'][$_SESSION['currentQuestion']]['QUESTION_COORD_ID'], PDO::PARAM_INT);
        $updateQuestionValidation->execute();
        $updateQuestionValidation->closeCursor();
        $_SESSION['questionList'][$_SESSION['currentQuestion']]['VALID'] = 1;
        $_SESSION['questionList'][$_SESSION['currentQuestion']]['DELETED'] = 0;
    }
    else{//If the question wasn't valid, send them an email telling them why.
        $database->beginTransaction();
        $updateQuestionValidation = $database->prepare('
            UPDATE QUESTION_COORDS SET VALID = 0 WHERE QUESTION_COORD_ID = :questionId;
        '); 
        $updateQuestionValidation->bindValue(':questionCoordId', $_SESSION['questionList'][$_SESSION['currentQuestion']]['QUESTION_COORD_ID'], PDO::PARAM_INT);
        $updateQuestionValidation->execute();
        $updateQuestionValidation->closeCursor();
        $findBudget = $database->prepare('
            SELECT BUDGET FROM COMPANIES WHERE COMPANY_ID = :id
            ');
        $findBudget->bindValue(':id', $_SESSION['questionList'][$_SESSION['currentQuestion']]['COMPANY_ID'] ,PDO::PARAM_INT);
        $findBudget->execute();
        $newBudget = $findBudget->fetchColumn(0);
        $findBudget->closeCursor();
        $newBudget += $_SESSION['questionList'][$_SESSION['currentQuestion']]['BUDGET'];
        $updateBudget = $database->prepare('
            UPDATE COMPANIES SET BUDGET=:newbudget WHERE COMPANY_ID=:id
            ');
        $updateBudget->bindValue(':newbudget', $newBudget, PDO::PARAM_INT);
        $updateBudget->bindValue(':id',$_SESSION['questionList'][$_SESSION['currentQuestion']]['COMPANY_ID'], PDO::PARAM_INT);
        $updateBudget->execute();
        $updateBudget->closeCursor();

        $_SESSION['questionList'][$_SESSION['currentQuestion']]['DELETED'] = 1;

        $body = htmlspecialchars($_POST['invalidReason']);
        $to = $_SESSION['questionList'][$_SESSION['currentQuestion']]['EMAIL'];
        $subject = 'Poink - Submitted Question Invalid';
        sendMail($to,$subject,$body);
    }
}
elseif (isset($_POST['ban'])){
    if ($_POST['ban'] == 1){
        //LEGAL OBLIGATION TO FULFILL CURRENT AD CONTRACTS, so we don't delete if they have questions already present.
        //does this even make sense? since if they have invalid questions they will be marked as deleted but still in the table.
        //THE ANSWER IS NO.
        /*$deleteUser = $database->prepare('
                IF NOT EXISTS (SELECT * FROM QUESTIONS WHERE COMPANY_ID = :companyId)
                THEN 
                DELETE FROM COMPANIES WHERE COMPANY_ID = :companyId
                END IF;
            ');
        $deleteUser->bindValue(':companyId', $_SESSION['questionList'][$_SESSION['currentQuestion']]['COMPANY_ID'], PDO::PARAM_INT);
        $deleteUser->execute();
        $deleteUser->closeCursor();
         If we want to permanently delete users from the banned emails, we'll just run a script to clean it up*/
        //What kind of access do banned users have?
        //answer: don't show them the question submission form, only their current ads and past ads.
        $addToBanned = $database->prepare('
            INSERT INTO BANNED
            (EMAIL) VALUES (:email);
            ');
        $addToBanned->bindValue(':email', $_SESSION['questionList'][$_SESSION['currentQuestion']]['EMAIL'], PDO::PARAM_STR);
        $addToBanned->execute();
        $addToBanned->closeCursor();
        //THERE WILL BE A PROBLEM IF THEY HAVE SUBMITTED MULTIPLE QUESTIONS?WON'T INVALIDATE THEM?
        $_SESSION['questionList'][$_SESSION['currentQuestion']]['DELETED'] = 1;
    }
    else {
        $unDeleteUser = $database->prepare('
            DELETE * FROM BANNED WHERE EMAIL = :email;
           '); 
        $unDeleteUser->bindValue(':email', $_SESSION['questionList'][$_SESSION['currentQuestion']]['EMAIL'], PDO::PARAM_STR);
        $unDeleteUser->execute();
        $unDeleteUser->closeCursor();
    }
}
else{
    $grabQuestionsToValidate = $database->prepare('
        SELECT Q.*, C.EMAIL, QC.QUESTION_COORD_ID, QC.VALID, QC.LAT, QC.LNG, QC.BUDGET FROM QUESTIONS AS Q
        LEFT JOIN COMPANIES AS C ON Q.COMPANY_ID = C.COMPANY_ID
        LEFT JOIN QUESTION_COORDS AS QC ON QC.QUESTION_ID = Q.QUESTION_ID
        WHERE QC.VALID = 0 AND Q.DELETED = 0 AND QC.LAT IS NOT NULL;
    ');
    $grabQuestionsToValidate->execute();
    $_SESSION['questionList'] = $grabQuestionsToValidate->fetchAll(PDO::FETCH_ASSOC);
    $grabQuestionsToValidate->closeCursor();
    $_SESSION['currentQuestion'] = 0;
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
    <title>Question Review</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="../mystyle.css" />
</head>
    <body> 
        <div id="header" class="sitename">
            <h1>
            Question Review
            </h1>
        </div>
        <div class="displayform">
        <p>Account Name/Email: <?=$_SESSION['questionList'][$_SESSION['currentQuestion']]['EMAIL']?></p>
        <p>Question: <?=$_SESSION['questionList'][$_SESSION['currentQuestion']]['QUESTION']?></p>
        <p>Gender(s) Targeted:<?=$_SESSION['questionList'][$_SESSION['currentQuestion']]['TARGET_GENDERS']?></p>
        <p>Age Range Targeted:<?=$_SESSION['questionList'][$_SESSION['currentQuestion']]['MIN_AGE']?> to
            <?=$_SESSION['questionList'][$_SESSION['currentQuestion']]['MAX_AGE']?></p>
        <p>Latitude and Longitude:<?=$_SESSION['questionList'][$_SESSION['currentQuestion']]['LAT'] . ',' . $_SESSION['questionList'][$_SESSION['currentQuestion']]['LNG']?></p>
        <p>Validity: <?=$_SESSION['questionList'][$_SESSION['currentQuestion']]['VALID']?></p>
        <p>
        <form method="post" enctype="mulitpart/form-data" action="<?php echo esc_url($_SERVER['PHP_SELF']);?>">
        <input type="hidden" name="validate" value=1 />
        <input type="submit" value="Validate"/>
        </form>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url($_SERVER['PHP_SELF']);?>">
        <input type="hidden" name="validate" value=0 />
        <p>Reason for invalidation if applicable </p>
        <textarea rows="8" columns="30" name="invalidReason"/> </textarea>
        <input type="submit" value="Invalidate"/>
        </form>
        </p>
        <p>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url($_SERVER['PHP_SELF']);?>">
        <input type="hidden" name="switch" value=0 />
        <input type="submit" value="Previous"/>
        </form>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url($_SERVER['PHP_SELF']);?>">
        <input type="hidden" name="switch" value=1 />
        <input type="submit" value="Next"/>
        </form>
        </p>
        </br>
        </br>
        <p>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url($_SERVER['PHP_SELF']);?>">
        <input type="hidden" name="ban" value=1 />
        <input type="submit" value="Ban Account"/>
        </form>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url($_SERVER['PHP_SELF']);?>">
        <input type="hidden" name="ban" value=0 />
        <input type="submit" value="Un-Ban"/>
        </form>
        </p>
        </div>
    </body>
</html>
