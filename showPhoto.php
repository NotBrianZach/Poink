<?php
//need to add error checking
//might want to check for zero length $photoContents, show placeholder image in that case
//need to add php database query
require '/u/zach1/openZdatabase.php';
$openPhotoQuery = $database->prepare('
	SELECT
		A.ITEM_PHOTO
		FROM AUCTION A WHERE :id = A.AUCTION_ID;
	');
$openPhotoQuery->bindValue(':id',$_GET['id'],PDO::PARAM_INT);
$openPhotoQuery->execute();
$photoFile = $openPhotoQuery->fetchAll();
$openPhotoQuery->closeCursor();

//$photoFile = fopen($photoContents, 'rb');
//$photoFile = fopen($photoContents['photo'], 'rb');
//var_dump($photoFile);

//error checking and default values go here
/*if ($auctionPhotoResult === false) {
	echo "Error with photo";	
	exit(1);
}*/

header('Content-Type: image/jpeg');
header('Content-Length: '.strlen($photoFile[0]["ITEM_PHOTO"]));
echo $photoFile[0]["ITEM_PHOTO"];
