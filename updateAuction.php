<?php
require '/var/script/openZdatabase.php';
if(!isset($_FILES['photo'])){
  $updateAuctionStmt = $database->prepare('
          UPDATE AUCTION
  		SET CLOSE_TIME = :close,
  		 ITEM_CATEGORY = :category,
  		 ITEM_CAPTION = :caption,
  		 ITEM_DESCRIPTION = :description,
  		 RESERVE = :reserve
  	WHERE AUCTION_ID = :auctionid;
  	');
  $updateAuctionStmt->bindValue(':close',$_POST['enddate'], PDO::PARAM_STR);
  $updateAuctionStmt->bindValue(':category',$_POST['category'], PDO::PARAM_INT);
  $updateAuctionStmt->bindValue(':caption',$_POST['caption'], PDO::PARAM_STR);
  $updateAuctionStmt->bindValue(':description',$_POST['description'], PDO::PARAM_STR);
  $updateAuctionStmt->bindValue(':reserve',$_POST['reserve'], PDO::PARAM_STR);
  $updateAuctionStmt->bindValue(':auctionid',$_GET['id'], PDO::PARAM_INT);
  $updateAuctionStmt->execute();
  $updateAuctionStmt->closeCursor();
}
else{
  $updateAuctionStmt = $database->prepare('
          UPDATE AUCTION
  		SET CLOSE_TIME = :close,
  		 ITEM_PHOTO = :photo,
  		 ITEM_CATEGORY = :category,
  		 ITEM_CAPTION = :caption,
  		 ITEM_DESCRIPTION = :description,
  		 RESERVE = :reserve
  	WHERE AUCTION_ID = :auctionid;
  	');
  if(isset($_FILES['photo']) && ($_FILES['photo']['error'] === 0)){
  	$photoFile = fopen($_FILES['photo']['tmp_name'], 'rb');
  }
  else{
    echo 'Error uploading photo';
    exit(1);
  }
  $updateAuctionStmt->bindValue(':photo',$photoFile, PDO::PARAM_LOB);
  $updateAuctionStmt->bindValue(':close',$_POST['enddate'], PDO::PARAM_STR);
  $updateAuctionStmt->bindValue(':category',$_POST['category'], PDO::PARAM_INT);
  $updateAuctionStmt->bindValue(':caption',$_POST['caption'], PDO::PARAM_STR);
  $updateAuctionStmt->bindValue(':description',$_POST['description'], PDO::PARAM_STR);
  $updateAuctionStmt->bindValue(':reserve',$_POST['reserve'], PDO::PARAM_STR);
  $updateAuctionStmt->bindValue(':auctionid',$_GET['id'], PDO::PARAM_INT);
  $updateAuctionStmt->execute();
  $updateAuctionStmt->closeCursor();
}

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
            <p>Updated.</p>
            <form>
                <a href="YourAccount.php">[Back to Sellin' stuff.]</a>
            </form>
        </div>
	<div id="thekid">
		<img src="kid.jpg" alt="Image not found"/>
		<p>Stanley updated his stuff. Look how happy he is.</p>
	</div>
    </body>
</html>
