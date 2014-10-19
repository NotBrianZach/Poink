<?php
include_once "db_connect.php";
?>
<?php
if (isset($_POST)):
var_dump($_POST);
$userLocations[] = $_POST['USER_LAT'];
echo $_POST['USER_LAT'];
echo $_POST['USER_LNG'];
foreach ($userLocations as $meow):
?>
Lat:<?=$_POST['USER_LAT']?> 
Lng:<?=$_POST['USER_LNG']?> 
USER_ID:<?=$_POST['USER_ID']?> 
<?php
endforeach;
endif;
$lat = 
$lng = 
$insertUser = $database->prepare('
    INSERT INTO APP_USERS
    (USER_LAT, USER_LNG, USER_ID) VALUES
    (:lat, :lng, :id);
');
$insertUser->bindValue(':lat',$_POST['USER_LAT'],PDO::PARAM_INT);
$insertUser->bindValue(':lng',$_POST['USER_LNG'],PDO::PARAM_STR);
$insertUser->bindValue(':id',$_POST['USER_ID'],PDO::PARAM_STR);
$insertUser->execute();
$insertUser->closeCursor();
?>
