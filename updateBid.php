<?php
//new bid id 
require '/var/scripts/openZdatabase.php';
session_start();
$finduserid = $database->prepare('
	SELECT
	    PERSON_ID
	FROM PERSON WHERE NAME=:name;
    ');
$finduserid->bindValue(':name', $_SESSION['user'], PDO::PARAM_STR);
$finduserid->execute();
$userId = $finduserid->fetchColumn(0);
$finduserid->closeCursor();

$newIdQuery = $database->prepare('SELECT NEXT_SEQ_VALUE(:seqGenName);');
$newIdQuery ->bindValue(':seqGenName', 'BID', PDO::PARAM_STR);
$newIdQuery ->execute();
$newBidId = $newIdQuery ->fetchColumn(0);
$newIdQuery->closeCursor();

$updateBidQuery = $database->prepare('
        INSERT BID
		(BID_ID, BIDDER, AUCTION, BID_TIME, AMOUNT)
		VALUES (:bidid, :bidder, :auction, CURRENT_TIMESTAMP, :amount);
	');
$updateBidQuery->bindValue(':bidid', $newBidId,PDO::PARAM_INT);
$updateBidQuery->bindValue(':bidder', $userId, PDO::PARAM_INT);
$updateBidQuery->bindValue(':auction', $_GET['id'],PDO::PARAM_INT);
$updateBidQuery->bindValue(':amount', $_POST['bid'],PDO::PARAM_STR);
$updateBidQuery->execute();
$updateBidQuery->closeCursor();
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
            <p>YOLO. Bid placed.</p>
            <form>
                <a href="Buyer.php">[Back to Browsin'.]</a>
            </form>
        </div>
	<div id="thekid">
		<img src="kid.jpg" alt="Image not found"/>
		<p>Stanley took out a second mortgage to place more bids. Look how happy he is.</p>
	</div>
    </body>
</html>
