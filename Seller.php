<?php
require '/var/script/openZdatabase.php';
session_start();
$findsellerid = $database->prepare('
	SELECT
	    PERSON_ID
	FROM PERSON WHERE NAME=:name;
    ');
$findsellerid->bindValue(':name',$_SESSION['user']);
$findsellerid->execute();
$sellerId = $findsellerid->fetchColumn(0);
$findsellerid->closeCursor();

$openAuctionQuery = $database->prepare('
    SELECT
        A.STATUS,
	A.AUCTION_ID,
        PERSON.NAME AS SELLER,
        A.OPEN_TIME,
        A.CLOSE_TIME,
        ITEM_CATEGORY.NAME AS ITEM_CATEGORY,
        A.ITEM_CAPTION,
        A.ITEM_DESCRIPTION,
	A.RESERVE,
	BID.AMOUNT
        FROM AUCTION A
            JOIN ITEM_CATEGORY ON A.ITEM_CATEGORY = ITEM_CATEGORY.ITEM_CATEGORY_ID
            JOIN PERSON ON A.SELLER = PERSON.PERSON_ID
	    LEFT JOIN BID ON BID.AUCTION = A.AUCTION_ID 
	    AND BID.AMOUNT = (SELECT(MAX(BID.AMOUNT)) FROM BID WHERE BID.AUCTION = A.AUCTION_ID)
	WHERE A.SELLER = :sellerId;
    ');
$openAuctionQuery->bindValue(':sellerId', $sellerId, PDO::PARAM_INT);
$openAuctionQuery->execute();
$stuffSellin = $openAuctionQuery->fetchAll();
$openAuctionQuery->closeCursor();
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
  <head>
    <title>Home</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="mystyle.css"/>
    <script language="javascript" type="text/javascript" src="datetimepicker.js">
    </script>
  </head>
    <body> 
        <div id="header" class="sitename">
            <h1>
            BuySellBuyBuy! The Sellin' Shack!
            </h1>
        </div>
        <div class="navbar">
	    Welcome, <?=htmlspecialchars($_SESSION['user'])?>
            <a href="index.php">[Log out]</a>
            <a href="Buyer.php">[Buyin' Bucket]</a>
        </div>
        <div id="stuffselling" class="displayform">
            <h4>Stuff you are selling</h4>
            <form> <input type="text" name="option" value="keyword"/>keyword search</form>
            <form>
            <select name="sort by">
            <option value="most recent">most recent</option>
            <option value="by category">by Category</option>
            <option value="by seller">by Seller</option>
            </select>
            </form>
            <form><button type="button">Search</button></form> 
 	    <?php
                foreach($stuffSellin as $currAuction):
			if ($currAuction['STATUS'] == 1):
            ?>
		<form action="updateAuction.php?id=<?=htmlspecialchars($currAuction['AUCTION_ID'])?>" onsubmit="return minBidValidate();" method="post" enctype="multipart/form-data" >
                <h3><?=htmlspecialchars($currAuction['ITEM_CAPTION'])?></h3>
			<input type="text" name="caption" value="<?=htmlspecialchars($currAuction['ITEM_CAPTION'])?>"/>
		<br/>
	        <img src="showPhoto.php?id=<?=htmlspecialchars($currAuction['AUCTION_ID'])?>" alt="Photo failed to display" height="100" width="100"/>
			<input type="file" name="photo" value='<?=htmlspecialchars($currAuction['ITEM_PHOTO'])?>' accept="image/*"/> 
                <p>Item Category:<?=htmlspecialchars($currAuction['ITEM_CATEGORY'])?></p>
		<?php
	      	    require '/var/script/openZdatabase.php';
	      	    $categoriesQuery = $database->prepare('
	      	      SELECT
	      	    	ITEM_CATEGORY_ID,
	      	    	NAME
	      	    	FROM ITEM_CATEGORY;
	      	      ');
	      	    $categoriesQuery->execute();
	      	    $categories = $categoriesQuery->fetchAll();
	      	    $categoriesQuery->closeCursor();
	      	?>
	      	<select name="category" required="required">
	      	<?php
	      	    foreach ($categories as $currCat):
	      	?>
	      	    <option value="<?=htmlspecialchars($currCat['ITEM_CATEGORY_ID'])?>">
	      	    <?=htmlspecialchars($currCat['NAME'])?></option>
	      	<?php
	      	    endforeach;
	      	?>
		</select>
                <p>Description:</p>
			<textarea name="description" rows="8" columns="30"/>
			<?=htmlspecialchars($currAuction['ITEM_DESCRIPTION'])?>
			</textarea>
                <p>Minimum Bid:</p>
			<input type="text" value="<?=htmlspecialchars($currAuction['RESERVE'])?>" name="reserve" id="minBid"/>
                <p>Bidding Ends:</p>
		<input id="demo1<?=htmlspecialchars($currAuction['AUCTION_ID'])?>" type="text" size="25" name="enddate" 
		value="<?=htmlspecialchars($currAuction['CLOSE_TIME'])?>">
		<a href="javascript:NewCal('demo1<?=htmlspecialchars($currAuction['AUCTION_ID'])?>','ddmmyyyy',true,24)">
		<img src="cal.gif" width="16" height="16" border="0" alt="Pick a date"></a>
                <p>Current Bid:<?=htmlspecialchars($currAuction['AMOUNT'])?></p>
		<br/>
		<input type="submit" value="Submit Changes"/>
		</form>
	        <form action="cancelListing.php?id=<?=htmlspecialchars($currAuction['AUCTION_ID'])?>" method="post" >
		<input type="submit" value="Cancel This Listing"/></form>
		<br/>
            <?php
			endif;
                endforeach;
            ?>
            <a href="NewListing.php">Add new stuff</a>
        </div>
        <div class="displayform">
	    <h4>Stuff you've sold</h4>
            <form> <input type="text" name="option" value="keyword"/>
            <select name="sort by">
            <option value="most recent">most recent</option>
            <option value="by category">by Category</option>
            <option value="by seller">by Seller</option>
            </select>
            <button type="button">Search</button></form> 
 	    <?php
                foreach($stuffSellin as $currAuction):
			if ($currAuction['STATUS'] == 3 || $currAuction['STATUS'] == 0):
            ?>
		<h3><?=htmlspecialchars($currAuction['ITEM_CAPTION'])?></h3>
            	<img src="showPhoto.php?id=<?=htmlspecialchars($currAuction['AUCTION_ID'])?>" alt="Photo failed to display" height="100" width="100"/>
		<p>Item Category:<?=htmlspecialchars($currAuction['ITEM_CATEGORY'])?></p>
		<p>Description:<?=htmlspecialchars($currAuction['ITEM_DESCRIPTION'])?></p>
		<p>Minimum Bid:<?=htmlspecialchars($currAuction['RESERVE'])?></p>
                <p>Winning Bid:<?=htmlspecialchars($currAuction['AMOUNT'])?></p>
		<p>Sale date:<?=htmlspecialchars($currAuction['CLOSE_TIME'])?></p>
            <?php
			endif;
                endforeach;
            ?>
        </div>
    <script src="validateForm.js" type="text/javascript"></script>
    </body>
</html>
