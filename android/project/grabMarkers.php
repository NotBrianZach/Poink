<?php
include_once "db_connect.php";
if ($_GET['id'] == 1):
//if (isset($_GET)):
    $selectCoords = $database->prepare('
    SELECT * FROM markers;
    ');
    $selectCoords->execute();
    $coords = $selectCoords->fetchAll();
    $selectCoords->closeCursor();
    echo json_encode($coords);
endif;
?>
