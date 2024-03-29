<?php
if (isset($_POST['deletequestionid'])) {
    $deleteQuestionId = filter_input(INPUT_POST, 'deletequestionid', FILTER_SANITIZE_NUMBER_INT);

    $deleteData = $database->prepare('DELETE FROM APP_USAGE WHERE QUESTION_ID=:id');
	$deleteData->bindValue(':id',$deleteQuestionId,PDO::PARAM_INT);
	$deleteData->execute();
	$deleteData->closeCursor();

	$deleteCoords = $database->prepare('DELETE FROM QUESTION_COORDS WHERE QUESTION_ID=:id');
	$deleteCoords->bindValue(':id',$deleteQuestionId,PDO::PARAM_INT);
	$deleteCoords->execute();
	$deleteCoords->closeCursor();
	
	$delete_question = $database->prepare('DELETE FROM QUESTIONS WHERE QUESTION_ID=:id');
	$delete_question->bindValue(':id',$deleteQuestionId,PDO::PARAM_INT);
	$delete_question->execute();
	$delete_question->closeCursor();
}
?>
