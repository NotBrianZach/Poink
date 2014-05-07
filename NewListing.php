<?php
	ini_set('display_errors','On');
	error_reporting(E_ALL);
	require '/var/script/openZdatabase.php';
	session_start();
	$findsellerid = $database->prepare('
		SELECT
		    PERSON_ID
		FROM PERSON WHERE NAME=:name;
	    ');
	$findsellerid->bindValue(':name',$_SESSION['user']);
	$findsellerid->execute();
	$sellerId = $findsellerid->fetchColumn();
	$findsellerid->closeCursor();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
    <title>NewListing</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="mystyle.css"/>
    <script type="text/javascript" src="datetimepicker.js">
    </script>
</head>
    <body> 
        <div class="sitename">
            <h1>
            BuySellBuyBuy! Everything must go!
            </h1>
        </div>
        <div class="navbar">
            <a href="index.php">[Logout]</a>
            <a href="Seller.php">[Sellin' Shack]</a>
            <a href="Buyer.php">[Buyin' Bucket]</a>
        </div>
        <div id="newlistingform" class="displayform">
            <form action="insertNewListing.php?id=<?=$sellerId?>" method="post" enctype="multipart/form-data" onsubmit="return minBidValidate();">
		<p>Item Name</p>
		<input type="text" value="Item Name" name="caption"/>
		<p>Item Description</p>
			<textarea name="description" rows="8" columns="30"/>
			Describe your stuff!
			</textarea>
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
	      	<p>Item Category</p>
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
	      	<p>Auction End Date (military time, UTC -6:00)</p>
		<input id="demo1" type="text" name="enddate" value="yyyy-mm-dd hh:mm:ss" size="25"><a href="javascript:NewCal('demo1','ddmmyyyy',true,24)"><img src="cal.gif" width="16" height="16" border="0" alt="Pick a date"></a>
		<!--courtesy of http://www.javascriptkit.com/script/script2/tengcalendar.shtml-->
		<p>Minimum Bid</p>
              	<input id="minBid" type="text" value="Minimum Bid" name="reserve"/>
	 	<h3>Find a nice picture of your stuff:</h3>
              	<input type="file" name="photo" accept="image/*"/>
             	<input type="submit">
	    </form>
        </div>
    <script src="validateForm.js" type="text/javascript"></script>
    </body>
</html>
