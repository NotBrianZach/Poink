<?php
require '/var/script/openZdatabase.php';
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
$openAuctionQuery = $database->prepare('
    SELECT
	A.ITEM_CAPTION,
	A.RESERVE,
	BID.AMOUNT
        FROM AUCTION A
	    LEFT JOIN BID ON BID.AUCTION = A.AUCTION_ID
	    AND BID.AMOUNT = (SELECT(MAX(BID.AMOUNT)) FROM BID WHERE BID.AUCTION = A.AUCTION_ID)
	WHERE AUCTION_ID=:id;
    ');
$openAuctionQuery->bindValue(':id',$_GET['id'],PDO::PARAM_STR);
$openAuctionQuery->execute();
$currAuction = $openAuctionQuery->fetch();
$openAuctionQuery->closeCursor();
//only need one auction here
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
    <title>Payment</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="mystyle.css"/>
</head>
    <body> 
        <div class="sitename">
            <h1>
            BuySellBuyBuy! Time to pay up!
            </h1>
        </div>
        <div class="navbar">
            <a href="index.php">[Log out]</a>
            <a href="Buyer.php">[Buyin' Bucket]</a>
            <a href="Seller.php">[Sellin' Shack]</a>
        </div>
        <div id="browse" class="displayform">
	    <h3><?=htmlspecialchars($currAuction['ITEM_CAPTION'])?></h3>
            <img src="cigarette.jpg" alt="cigarette" height="100" width="100"/>
		<p>Minimum bid: <?=htmlspecialchars($currAuction['RESERVE'])?></p>  
		<p>Your winning bid: <?=htmlspecialchars($currAuction['AMOUNT'])?></p>
                <form method="post" action="updatePayment.php?id=<?=htmlspecialchars($_GET['id'])?>">
		<p>Credit Card:</p>
		<input type="text" name="creditcard" value="1234567891234567"/>
		<p>Enter Password:</p>
                <input type="password" name="password"/>
		<br/>
		<input type="submit" value="Confirm Payment"/>
		<br/>
		</form>
        </div>
    </body>
</html>
