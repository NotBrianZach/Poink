<?php
include_once "db_connect.php";
?>
<?php
if (isset($_POST)):
var_dump($_POST);
$userLocations[] = $_POST['lat'];
echo $_POST['lat'];
echo $_POST['lng'];
foreach ($userLocations as $meow):
?>
Lat:<?=$_POST['lat']?> 
Lng:<?=$_POST['lng']?> 
user_id:<?=$_POST['USER_ID']?> 
<?php
endforeach;
endif;
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
?>
