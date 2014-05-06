<?php
require '/var/scripts/openZdatabase.php';
session_start();
$newIdQuery = $database->prepare('SELECT NEXT_SEQ_VALUE(:seqGenName);');
$newIdQuery->bindValue(':seqGenName', 'AUCTION', PDO::PARAM_STR);
$newIdQuery->execute();
$newId = $newIdQuery ->fetchColumn(0);
$newIdQuery->closeCursor();

$insertAuctionStmt = $database->prepare('
        INSERT INTO AUCTION
		(ITEM_PHOTO,OPEN_TIME, CLOSE_TIME, ITEM_CATEGORY, ITEM_CAPTION, ITEM_DESCRIPTION, RESERVE, AUCTION_ID, SELLER, STATUS)
		VALUES (:photo, CURRENT_TIMESTAMP, :close, :category, :caption, :descriptsion, :reserve, :auction_id, :seller, 1);
	');
if (isset($_FILES['photo']) && ($_FILES['photo']['error'] === 0)){
	$photoFile = fopen($_FILES['photo']['tmp_name'], 'rb');
}
else{
	echo "Error uploading photo";
	exit(1);
}
$insertAuctionStmt->bindValue(':photo', $photoFile, PDO::PARAM_LOB);
$insertAuctionStmt->bindValue(':close',$_POST['enddate'], PDO::PARAM_STR);
$insertAuctionStmt->bindValue(':category',$_POST['category'], PDO::PARAM_INT);
$insertAuctionStmt->bindValue(':caption',$_POST['caption'], PDO::PARAM_STR);
$insertAuctionStmt->bindValue(':descriptsion',$_POST['descriptsion'], PDO::PARAM_STR);
$insertAuctionStmt->bindValue(':reserve',$_POST['reserve'], PDO::PARAM_STR);
$insertAuctionStmt->bindValue(':auction_id',$newId, PDO::PARAM_INT);
$insertAuctionStmt->bindValue(':seller',$_GET['id'], PDO::PARAM_INT);
$insertAuctionStmt->execute();
$insertAuctionStmt->closeCursor();
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
            BuySellBuyBuy! The Confirmation Form!
            </h1>
        </div>
        <div id="confirmationform">
            <p>New Listing Uploaded.</p>
            <form>
                <a href="Seller.php">[Back to Sellin']</a>
            </form>
        </div>
	<div id="thekid">
		<img src="kid.jpg" alt="Image not found"/>
		<p>Stanley uploaded a new item. Look how happy he is.</p>
	</div>
    </body>
</html>
