<?php
include_once "db_connect.php";

function check_txnid($txnid){
    //get result set
    $checkUniqueTransaction = $database->prepare('
        SELECT * FROM PAYMENTS WHERE txnid = :txnId
        ');
    $checkUniqueTransaction->bindValue(':txnId',$txnid,PDO::PARAM_INT);
    $checkUniqueTransaction->execute(); 
    $uniqueOrNot = $checkUniqueTransaction->fetchColumn(0);
    $checkUniqueTransaction->closeCursor(); 

    if(!empty($uniqueOrNot)) {//if there is an array retrieved by the sql query
        $valid_txnid = false;
    }
    return $valid_txnid;
}

function check_price($price, $id){
    $valid_price = false;
    //make sure they pay the minimum fee of 20 dollars. Will probably have to modify to accept foreign currency...
    if($price >= 20){
        $valid_price = true;
    }
    return $valid_price;
    return true;
}

function updatePayments($data){ 
    if(is_array($data)){                
        $insertPayments = $database->prepare('
            INSERT INTO PAYMENTS (txnid, payment_amount, payment_status, itemid, createdtime) VALUES (
                :txnId,
                :paymentAmount,
                :paymentStatus,
                :itemNumber,
                :date
            )
            ');
        $insertPayments->bindValue(':txnId',$data['txn_id'],PDO::PARAM_INT);
        $insertPayments->bindValue(':paymentAmount',$data['payment_amount'],PDO::PARAM_INT);
        $insertPayments->bindValue(':paymentStatus',$data['payment_status'],PDO::PARAM_INT);
        $insertPayments->bindValue(':itemNumber',$data['item_number'],PDO::PARAM_INT);
        $insertPayments->bindValue(':date',date("Y-m-d H:i:s"),PDO::PARAM_INT);
        $insertPayments->execute();
        return $insertPayments->closeCursor();
    }
}
