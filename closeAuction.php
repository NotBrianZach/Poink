<?php
require '/u/zach1/openZdatabase.php';
$updateAuctionStmt = $database->prepare('
    UPDATE AUCTION 
        SET STATUS = 4
	WHERE CLOSE_TIME < CURRENT_TIMESTAMP
        AND (SELECT MAX(BID.AMOUNT) FROM BID WHERE AUCTION = AUCTION.AUCTION_ID) < AUCTION.RESERVE;
    UPDATE AUCTION 
        SET STATUS = 3
	WHERE CLOSE_TIME < CURRENT_TIMESTAMP
        AND (SELECT MAX(BID.AMOUNT) FROM BID WHERE AUCTION = AUCTION.AUCTION_ID) > AUCTION.RESERVE;
    ');
$updateAuctionStmt->execute();
$updateAuctionStmt->closeCursor();

$findAuction = $database->prepare('
    SELECT
        AUCTION_ID
    FROM AUCTION
    WHERE CLOSE_TIME < CURRENT_TIMESTAMP
    AND (SELECT MAX(BID.AMOUNT) FROM BID WHERE BID.AUCTION = AUCTION.AUCTION_ID) > AUCTION.RESERVE;
    ');
$findAuction->execute();
$auctionIds = $findAuction->fetchAll();
$findAuction->closeCursor();

foreach($currId as $auctionIds):
  $findBidder = $database->prepare('
      SELECT
          BIDDER
      FROM BID 
      WHERE BID.AUCTION=:auctionId;
  ');
  $findBidder->bindValue(':auctionId',$currId['AUCTION_ID'],PDO::PARAM_INT);
  $findBidder->execute();
  $bidderId = $findBidder->fetchColumn(0);
  $findBidder->closeCursor();
  
  $findEmail = $database->prepare('
      SELECT
          EMAIL_ADDRESS
      FROM PERSON
      WHERE PERSON.PERSON_ID=:bidderId;
      ');
  $findEmail->bindValue(':bidderId',$bidderId,PDO::PARAM_INT);
  $findEmail->execute();
  $bidderEmail = $findEmail->fetchColumn(0);
  $findEmail->closeCursor();
  mail($bidderEmail,'You won an auction, time to pay up!','');
endforeach;
//mailto();
//
?>
