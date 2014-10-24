<?php
include_once "db_connect.php";
if (isset($_POST)):
$insertUser = $database->prepare('
    INSERT INTO markers
    (lat, lng, user_id) VALUES
    (:lat, :lng, :id);
');
$insertUser->bindValue(':lat',$_POST['lat'],PDO::PARAM_INT);
$insertUser->bindValue(':lng',$_POST['lng'],PDO::PARAM_STR);
$insertUser->bindValue(':id',$_POST['user_id'],PDO::PARAM_STR);
$insertUser->execute();
$insertUser->closeCursor();
endif;

/*
echo $_POST['lat'];
$userLocations[] = $_POST['lat'];
echo $_POST['lng'];
endforeach;
foreach ($userLocations as $meow):
Lat:<?=$_POST['lat']?> 
Lng:<?=$_POST['lng']?> 
user_id:<?=$_POST['user_id']?> 
*/
?>
