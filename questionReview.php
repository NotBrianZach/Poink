<?php
require 'vendor/autoload.php';
include_once './includes/db_connect.php';

function sendMail($to,$subject,$body){
    $from = "darklordvadermort@gmail.com";//to be replaced with a more formal email address
    $mail = new PHPMailer();
    $mail->IsSMTP(true); // SMTP
    $mail->SMTPAuth = true;  // SMTP authentication
    $mail->Mailer = "smtp";
    $mail->Host= "ssl://smtp.gmail.com"; // Amazon SES
    $mail->Port = 465;  // SMTP Port
    $mail->ssl = 1;
    $mail->debug = 1;
    $mail->html_debug = 1;
    $mail->Username = "darklordvadermort@gmail.com";  // SMTP  Username
    $mail->Password = "proverb2ialdrago1n";  // SMTP Password
    $mail->SetFrom($from, 'The Poink Team');
    $mail->AddReplyTo($from,'The Poink Team');
    $mail->Subject = $subject;
    $mail->MsgHTML($body);
    $address = $to;
    $mail->AddAddress($address, $to);
    if(!$mail->Send()){
        echo "not sent" . $mail->ErrorInfo;
        return false;
    }
    else{
        echo "sent";
        return true;
    }
}
function esc_url($url) {
//sanitize the output from the PHP_SELF server variable to prevent session hijacking 
    if ('' == $url) {
        return $url;
    }
 
    $url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $url);
 
    $strip = array('%0d', '%0a', '%0D', '%0A');
    $url = (string) $url;
 
    $count = 1;
    while ($count) {
        $url = str_replace($strip, '', $url, $count);
    }
 
    $url = str_replace(';//', '://', $url);
 
    $url = htmlentities($url);
 
    $url = str_replace('&amp;', '&#038;', $url);
    $url = str_replace("'", '&#039;', $url);
 
    if ($url[0] !== '/') {
        // We're only interested in relative links from $_SERVER['PHP_SELF']
        return '';
    } else {
        return $url;
    }
}
$questionList = array();
$currentQuestion = 0;

if (isset($_POST['switch'])){//what to say when there is no more next?
    if ($_POST['switch'] === 1 && $currQuestion < count($questionList[0])){//hopefully [0] is right
        $currQuestion += 1; 
    }
    elseif ($currQuestion > 0){
        $currQuestion -= 1;
    }
}
elseif (isset($_POST['validate'], $_POST['questionId'])){
    $questionId = filter_input(INPUT_POST,'questionId',FILTER_SANITIZE_NUMBER_INT);
    if ($_POST['validate'] === 1){
        $updateQuestionValidation = $database->prepare('
            UPDATE QUESTIONS SET VALID = 1 WHERE QUESTION_ID = :questionId;
        '); 
        $updateQuestionValidation->bindValue(':questionId', $questionId, PDO::PARAM_INT);
        $updateQuestionValidation->execute();
        $updateQuestionValidation->closeCursor();
    }
    else{//If the question wasn't valid, send them an email telling them why.
        //delete the question first though
        $deleteInvalid = $databse->prepare('
            DELETE FROM QUESTION_COORDS AS QC WHERE QC.QUESTION_ID = :questionId;
            DELETE FROM QUESTIONS AS Q WHERE Q.QUESTION_ID = :questionId;
            ');
        $deleteInvalid->bindValue(':questionId', $questionId, PDO::PARAM_INT);
        $deleteInvalid->execute();
        $deleteInvalid->closeCursor();
        $body = htmlspecialchars($_POST['invalidReason']);
        $to = htmlspecialchars($questionList[$currQuestion]['EMAIL']);
        $subject = 'Poink - Submitted Question Invalid';
        sendMail($to,$subject,$body);
    }
}
elseif (isset($_POST['ban'])){
    if ($_POST['ban'] === 1){
        //LEGAL OBLIGATION TO FULFILL CURRENT AD CONTRACTS,so we don't delete if they have questions already present.
        /*$deleteUser = $database->prepare('
                IF NOT EXISTS (SELECT * FROM QUESTIONS WHERE COMPANY_ID = :companyId)
                THEN 
                DELETE FROM COMPANIES WHERE COMPANY_ID = :companyId
                END IF;
            ');
        $deleteUser->bindValue('', , PDO::PARAM_INT);
        $deleteUser->execute();
        $deleteUser->closeCursor();*/
        //throwing this out in favor of being able to un-ban accounts, slightly more storage space but whatever, who cares.

        //What kind of access do banned users have?
        //answer: don't show them the question submission form, only their current ads and past ads.
        $addToBanned = $database->prepare('
            INSERT INTO BANNED
            (EMAIL) VALUES (:email);
            ');
        $addToBanned->bindValue(':email',$questionList[$currQuestion]['EMAIL'],PDO::PARAM_STR);
        $addToBanned->execute();
        $addToBanned->closeCursor();
    }
}
else{//this would be when we first start the page
    //need to include database_connect I guess..
    //also, want to grab coordinates and company name here...
    $grabQuestionsToValidate = $database->prepare('
        SELECT
        Q.QUESTION_ID,
        Q.QUESTION,
        Q.VALID,
        Q.TARGET_GENDERS,
        C.EMAIL,
        QC.LAT,
        QC.LNG
        FROM QUESTIONS Q 
        JOIN COMPANIES C ON C.COMPANY_ID = Q.COMPANY_ID
        JOIN QUESTION_COORDS QC ON QC.QUESTION_ID = Q.QUESTION_ID
        WHERE Q.VALID = 0;
    ');
    $grabQuestionsToValidate->execute();
    $questionList = $grabQuestionsToValidate->fetchAll(PDO::FETCH_ASSOC);
    $grabQuestionsToValidate->closeCursor();
}
d($questionList);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
    <title>Question Review</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="mystyle.css" />
</head>
    <body> 
        <div id="header" class="sitename">
            <h1>
            Question Review
            </h1>
        </div>
        <div class="displayform">
        <p>Account Name/Email <?=$questionList[$currentQuestion]['EMAIL']?></p>
        <p>Question: <?=$questionList[$currentQuestion]['QUESTION']?></p>
        <p>Gender(s) Targeted:<?=$questionList[$currentQuestion]['GENDER']?></p>
        <p>Age Range Targeted:<?=$questionList[$currentQuestion]['QUESTION']?></p>
        <p>Latitude and Longitude:<?=$questionList[$currentQuestion]['LAT'] . ',' . $questionList[$currentQuestion]['LNG']?></p>
        <p>Validity: <?=$questionList[$currentQuestion]['VALID']?></p>
        <p>
        <form method="post" enctype="mulitpart/form-data" action="<?php echo esc_url($_SERVER['PHP_SELF']);?>">
        <input type="hidden" name="validate" value="1"/>
        <input type="hidden" name="questionId" value=<?php echo htmlspecialchars($questionList[$currentQuestion]['QUESTION_ID']);?>/>
        <input type="submit" value="Validate"/>
        </form>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url($_SERVER['PHP_SELF']);?>">
        <input type="hidden" name="validate" value="0"/>
        <input type="hidden" name="questionId" value=<?php echo htmlspecialchars($questionList[$currentQuestion]['QUESTION_ID']);?>/>
        <p>Reason for invalidation if applicable </p>
  	    <textarea id="questionx" name="invalidReason" rows="8" columns="30"/><?=htmlspecialchars($question)?></textarea>
        <input type="submit" value="Invalidate"/>
        </form>
        </p>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url($_SERVER['PHP_SELF']);?>">
        <input type="hidden" name="switch" value="0"/>
        <input type="submit" value="Previous"/>
        </form>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url($_SERVER['PHP_SELF']);?>">
        <input type="hidden" name="switch" value="1"/>
        <input type="submit" value="Next"/>
        </form>
        </p>
        </br>
        </br>
        <p>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url($_SERVER['PHP_SELF']);?>">
        <input type="hidden" name="ban" value="1"/>
        <input type="hidden" name="email" value="<?php echo $questionList[$currentQuestion]['EMAIL'];?>"/>
        <input type="submit" value="Ban Account"/>
        </form>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url($_SERVER['PHP_SELF']);?>">
        <input type="hidden" name="ban" value="0"/>
        <input type="hidden" name="email" value="<?php echo $questionList[$currentQuestion]['EMAIL'];?>"/>
        <input type="submit" value="Un-Ban"/>
        </form>
        </p>
        </div>
    </body>
</html>
