<?php 
	ini_set('display errors','On');
	error_reporting(E_ALL);
?>
<?php
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

$openAuctionQuery = $database->prepare('
    SELECT
        A.STATUS,
        P.NAME AS SELLER,
        A.OPEN_TIME,
	A.AUCTION_ID,
        A.CLOSE_TIME,
        C.NAME AS ITEM_CATEGORY,
        A.ITEM_CAPTION,
        A.ITEM_DESCRIPTION,
	A.RESERVE,
	BID.AMOUNT,
	BIDDER
        FROM AUCTION A
            JOIN ITEM_CATEGORY C ON A.ITEM_CATEGORY = C.ITEM_CATEGORY_ID
            JOIN PERSON P ON A.SELLER = P.PERSON_ID
	    LEFT JOIN BID ON BID.AUCTION = A.AUCTION_ID
	    AND BID.AMOUNT = (SELECT(MAX(BID.AMOUNT)) FROM BID WHERE BID.AUCTION = A.AUCTION_ID);
    ');
$openAuctionQuery->execute();
$auctions = $openAuctionQuery->fetchAll();
$openAuctionQuery->closeCursor();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
  <head>
    <title>Buyer</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="mystyle.css"/>
  </head>
    <body> 
        <div class="sitename">
            <h1>
            BuySellBuyBuy! The Buyin' Bucket!
            </h1>
        </div>
        <div class="navbar">
	    Welcome, <?=htmlspecialchars($_SESSION['user'])?>
            <a href="index.php">[Log out]</a>
            <a href="Seller.php">[Sellin' Shack]</a>
        </div>
        <div id="browseoptions" class="displayform">
	    <h4>Browse the Goods</h4>
            <form> <input type="text" name="option" value="keyword"/></form>
            <form>
            <select name="sort by">
            <option value="most recent">most recent</option>
            <option value="by category">by Category</option>
            <option value="by seller">by Seller</option>
            </select>
            </form>
	    <form><button type="button">Search</button></form>
	    <?php
		foreach($auctions as $currAuction):
			if ($currAuction['STATUS'] == 1):
	    ?>
		<div id="auction<?=htmlspecialchars($currAuction['AUCTION_ID'])?>">
		  <h3><?=htmlspecialchars($currAuction['ITEM_CAPTION'])?></h3>
		  <button class="link" id="descButton<?=htmlspecialchars($currAuction['AUCTION_ID'])?>" onclick="switchDescriptsion(<?=htmlspecialchars($currAuction['AUCTION_ID'])?>);">click to expand descriptsion</button> 
		  <br/>
            	  <img src="showPhoto.php?id=<?=htmlspecialchars($currAuction['AUCTION_ID'])?>" alt="Photo failed to display" height="100" width="100"/>
		  <div class="longDescriptsion" id="longDescriptsion<?=htmlspecialchars($currAuction['AUCTION_ID'])?>">
		    <p>Item Category:<?=htmlspecialchars($currAuction['ITEM_CATEGORY'])?></p>
		    <p>Descriptsion:<?=htmlspecialchars($currAuction['ITEM_DESCRIPTION'])?></p>
		    <p>Seller:<?=htmlspecialchars($currAuction['SELLER'])?></p>
		    <p>Minimum Bid:<span id="minBid<?=htmlspecialchars($currAuction['AUCTION_ID'])?>"><?=htmlspecialchars($currAuction['RESERVE'])?></span></p>
                    <p>Current Bid:<?=htmlspecialchars($currAuction['AMOUNT'])?></p>
                    <p>Auction ends:<?=htmlspecialchars($currAuction['CLOSE_TIME'])?></p>
           	    <form method="post" onsubmit="return bidValidate(<?=htmlspecialchars($currAuction['AUCTION_ID'])?>);"
			 action="updateBid.php?id=<?=htmlspecialchars($currAuction['AUCTION_ID'])?>"> 
		    <input type="text" value="Enter a bid" name="bid" id="aBid<?=htmlspecialchars($currAuction['AUCTION_ID'])?>"/>
		    <!--had to include the auction id in the bid to differentiate between id elements for javascripts validation to work.-->
		    <input type="submit"/>
		    </form>
		  </div>
		</div>
	    <?php
			endif;
		endforeach;
	    ?>
        </div>
        <div id="pay" class="displayform">
	    <h4>Stuff you've bought</h4>
            <form> <input type="text" name="option" value="keyword"/></form>
            <form>
            <select name="sort by">
            <option value="most recent">most recent</option>
            <option value="by category">by Category</option>
            <option value="by seller">by Seller</option>
            </select>
            </form>
	    <form><button type="button">Search</button></form>
	    <?php
		foreach($auctions as $currAuction):
			if ($currAuction['STATUS'] == 3 && $userId == $currAuction['BIDDER'])://will need to grab auction id's from payments and then compare
	    ?>
		<h3><?=htmlspecialchars($currAuction['ITEM_CAPTION'])?><h3>
		<button class="link" id="boughtDescButton<?=htmlspecialchars($currAuction['AUCTION_ID'])?>" onclick="switchBoughtDescriptsion(<?=htmlspecialchars($currAuction['AUCTION_ID'])?>);">click to expand item descriptsion</button> 
		<br/>
            	<img src="showPhoto.php?id=<?=htmlspecialchars($currAuction['AUCTION_ID'])?>" alt="Photo failed to display" height="100" width="100"/>
		<div class="longDescriptsion" id="boughtDescriptsion<?=htmlspecialchars($currAuction['AUCTION_ID'])?>">
		<p>Item Category:<?=htmlspecialchars($currAuction['ITEM_CATEGORY'])?></p>
		<p>Descriptsion:<?=htmlspecialchars($currAuction['ITEM_DESCRIPTION'])?></p>
		<p>Seller:<?=htmlspecialchars($currAuction['SELLER'])?></p>
                <p>Winning Bid:<?=htmlspecialchars($currAuction['AMOUNT'])?></p>
           	<form method="post" action="Payment.php?id=<?=htmlspecialchars($currAuction['AUCTION_ID'])?>"> 
		<input type="submit" value="Pay for purchase"/>
		</form>
		<br/>
		</div>
	    <?php
			endif;
		endforeach;
	    ?>
        </div>
    <scripts src="validateForm.js" type="text/javascripts"></scripts>
    </body>
</html>
