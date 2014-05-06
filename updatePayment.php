<?php
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== "on") {
    header('HTTP/1.1 403 Forbidden: TLS Required');
    echo "why you no use https? is you a dummy?";
    exit(1);
}
require '/var/scripts/openZdatabase.php';
require 'password.php';
session_start();

$passwordQuery = $database->prepare('
    SELECT 
        PASSWORD
    FROM PERSON 
    WHERE PERSON.NAME = :name;
');
$passwordQuery->bindValue(':name', $_SESSION['user'], PDO::PARAM_STR);  
$passwordQuery->execute();
$passwordQueryRow = $passwordQuery->fetch();
$passwordHash = $passwordQueryRow['PASSWORD'];
$passwordQuery->closeCursor();
if (!password_verify($_POST['password'],$passwordHash))
{
    echo 'Password does not match.';
    exit(1);
}
$findAmountBid = $database->prepare('
    SELECT
	PERSON_ID
    FROM PERSON
    WHERE PERSON.NAME=:name;
');
$findAmountBid->bindValue(':name', $_SESSION['user'], PDO::PARAM_STR);
$findAmountBid->execute();
$personId = $findAmountBid->fetchColumn(0);
$findAmountBid->closeCursor();

$findAmountBid1 = $database->prepare('
    SELECT
        BID_ID,
	AMOUNT
    FROM BID
    WHERE BID_ID=:bidid;
');
$findAmountBid1->bindValue(':bidid', $personId, PDO::PARAM_STR);
$findAmountBid1->execute();
$findAmountBidRow = $findAmountBid1->fetch();
$bidId = $findAmountBidRow['BID_ID'];
$bidAmount = $findAmountBidRow['AMOUNT'];
$findAmountBid1->closeCursor();

//also need to insert into
$updateAuctionStmt = $database->prepare('
    UPDATE AUCTION
	SET CLOSE_TIME = CURRENT_TIMESTAMP,
	STATUS = 0
    WHERE AUCTION_ID = :auctionid;  # will have to get this auction id from somewhere
    INSERT INTO PAYMENTS
        (CREDIT_CARD, AMOUNT, BID)
	VALUES (:cardno,:amount,:bid); 
	');
$updateAuctionStmt->bindValue(':bid',$bidId, PDO::PARAM_INT);
$updateAuctionStmt->bindValue(':cardno',$_POST['creditcard'], PDO::PARAM_INT);
$updateAuctionStmt->bindValue(':amount',$bidAmount, PDO::PARAM_INT);
$updateAuctionStmt->bindValue(':auctionid',$_GET['id'], PDO::PARAM_INT);
$updateAuctionStmt->execute();
$updateAuctionStmt->closeCursor();
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
            <p>Purchase Complete.</p>
            <form method="post" action="Buyer.php">
		<input type="submit" value="Back to Buyin'"/>
            </form>
        </div>
	<div id="thekid">
		<img src="kid.jpg" alt="Image not found"/>
		<p>Stanley paid for his bid. Look how happy he is.</p>
	</div>
    </body>
</html>
