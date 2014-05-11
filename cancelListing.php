<?php
require '/var/script/openZdatabase.php';
$cancelQuery = $database->prepare('
	DELETE 
		FROM AUCTION
		WHERE AUCTION_ID = :auctionid;
	DELETE 
		FROM BID
		WHERE AUCTION = :auctionid;
	');
$cancelQuery->bindValue(':auctionid',$_GET['id'],PDO::PARAM_INT);
$cancelQuery->execute();
$cancelQuery->closeCursor();
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
            <p>Confirmed: Listing Canceled.</p>
            <a href="YourAccount.php">[Back To Sellin']</a>
        </div>
	<div id="thekid">
		<img src="kid.jpg" alt="Image not found"/>
		<p>Stanley deleted an item. He's probably happy because he posted 4 more.</p>
	</div>
    </body>
</html>
