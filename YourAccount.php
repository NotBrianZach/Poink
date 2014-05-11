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
            Poink Advertisers
            </h1>
        </div>
        <ul id="nav">
	    <li>Welcome, <?=htmlspecialchars($_SESSION['user'])?></li>
            <li><a href="index.php">[Log out]</a></li>
            <li><a href="AboutPoink.php">[About Poink]</a></li>
            <li><a href="HowAdsWork.php">[How Ads Work]</a></li>
            <span id="selectedpage"><li><a href="YourAccount.php">[Your Account]</a></li></span>
        </ul>
        <div class="displayform">
            <h4>App: Poink</h4>
	    <p> Your current Budget for Ads:</p>
	    <p> 80 BAJILLION! DOLLAHS!</p>
	        <form >
		<input type="submit" value="Add More"/></form>
        </div>
        <div class="displayform">
	    <h4>Submit a Question</h4>
                <p>Question:</p>
	    <!--onsubmit can be used for javascript error checking-->
	    <form action="" onsubmit="" method="post" enctype="multipart/form-data">
		<textarea name="description" rows="8" columns="30"/>
		</textarea>
		<p>Age Range</p> 
	        <input type="text" name="" value="0"/>
		to
	        <input type="text" name="" value="100"/>
		<p>Gender</p>
		<select name="">
		  <option value="male">Male</option>
		  <option value="female">Female</option>
		  <option value="toomainstream">Gender is too mainstream</option>
		</select>
		<p>Bid on ad price in dollars</p>
		  <input type="text" value=".05"/>
	        <input type="submit"/>
	    </form>
        </div>
        <!--<div class="displayform">
	    <h4>Have us create a question for you</h4>
		<p>What goes here? Brand info or what?</p>
        </div>-->
        <div class="displayform">
	    <h4>Current Questions</h4>
	    <!--later on, show the current going rate as well as the user's bid.-->
        </div>
	</br>
	</br>
	</br>
	</br>
	</br>
    <script src="validateForm.js" type="text/javascript"></script>
    </body>
</html>
