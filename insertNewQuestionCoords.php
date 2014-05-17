<?php
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== "on") {
    header('HTTP/1.1 403 Forbidden: TLS Required');
    echo "why you no use https? is you a dummy?";
    exit(1);
}
require '/var/script/openZdatabase.php';

$newQuestionCoordIdQuery = $database->prepare('
	SELECT NEXT_SEQ_VALUE(:seqGenName);
	');
$newQuestionCoordIdQuery->bindValue(':seqGenName', 'QUESTION_COORDS', PDO::PARAM_STR);
$newQuestionCoordIdQuery->execute();
$newQuestionCoordId = $newQuestionCoordIdQuery ->fetchColumn(0);
$newQuestionCoordIdQuery->closeCursor();

try{
    $insertQuestionCoordsStmt = $database->prepare('
            INSERT INTO QUESTION_COORDS
    		(LAT, LNG, RADIUS, QUESTION_ID, QUESTION_COORD_ID)
    		VALUES (:lat, :lng, :radius, :questionid, :questioncoordid);
    	');
    $insertQuestionCoordsStmt->bindValue(':lat',$_POST['lat'], PDO::PARAM_STR);
    $insertQuestionCoordsStmt->bindValue(':lng',$_POST['lng'], PDO::PARAM_STR);
    $insertQuestionCoordsStmt->bindValue(':radius',$_POST['radius'], PDO::PARAM_INT);
    $insertQuestionCoordsStmt->bindValue(':questionid',$_POST['question_Id'], PDO::PARAM_INT);
    $insertQuestionCoordsStmt->bindValue(':questioncoordid',$newQuestionCoordId, PDO::PARAM_INT);
    $insertQuestionCoordsStmt->execute();
    $insertQuestionCoordsStmt->closeCursor();
}
catch(Exception $e) {
    echo 'Exception -> ';
    var_dump($e->getMessage());
}
?>
