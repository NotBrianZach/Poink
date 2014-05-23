<?php
if (isset($_POST['deletequestionid'])) {
	$deleteCoords = $database->prepare('DELETE FROM QUESTION_COORDS WHERE QUESTION_ID=:id');
	$deleteCoords->bindValue(':id',$_POST['deletequestionid'],PDO::PARAM_INT);
	$deleteCoords->execute();
	$deleteCoords->closeCursor();
	
	$delete_question = $database->prepare('DELETE FROM QUESTIONS WHERE QUESTION_ID=:id');
	$delete_question->bindValue(':id',$_POST['deletequestionid'],PDO::PARAM_INT);
	$delete_question->execute();
	$delete_question->closeCursor();
}
?>
