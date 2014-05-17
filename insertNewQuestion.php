<?php
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== "on") {

	header('HTTP/1.1 403 Forbidden: TLS Required');
    echo "why you no use https? is you a dummy?";
    exit(1);
}
require '/var/script/openZdatabase.php';
session_start();
session_regenerate_id(true);

try {
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
    echo 'Exception -> ';
    var_dump($e->getMessage());
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
