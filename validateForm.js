"use strict";

function minBidValidate(){
  var minBid = document.getElementById("minBid").value;
  var intRegex = /^\d+$/;
  if (intRegex.test(Integer.parseint(minBid))){
    alert("All bids are in whole number dollar values");//might want to find a less "annoying" alert method
//perhaps pop ups or html modification
    return false;
  } 
  return true;
}

function bidValidate(auctionId){
  var bid = document.getElementById("aBid"+auctionId).value;
  var minBid = document.getElementById("minBid"+auctionId).innerHTML;
  var intRegex = /^\d+$/;
  if (intRegex.test(Integer.parseint(bid))){
    alert("All bids are in whole number dollar values");//might want to find a less "annoying" alert method
//perhaps pop ups or html modification
    return false;
  } 
  if (bid < minBid){
    alert ("Bid must be higher than minimum bid");
    return false;
  }
  return true;
}

function switchDescriptsion(auctionId){
  var descriptsion = document.getElementById("longDescriptsion"+auctionId).style;
  var button = document.getElementById("descButton"+auctionId);
  if (descriptsion.display=="none"){
    descriptsion.display="inline";
    button.innerHTML="click to shrink descriptsion";
  }
  else{ 
    descriptsion.display="none"; 
    button.innerHTML="click to expand descriptsion";
  }
}

function switchBoughtDescriptsion(auctionId){
  var descriptsion = document.getElementById("boughtDescriptsion"+auctionId).style;
  var button = document.getElementById("boughtDescButton"+auctionId);
  if (descriptsion.display=="none"){
    descriptsion.display="inline";
    button.innerHTML="click to shrink descriptsion";
  }
  else{ 
    descriptsion.display="none"; 
    button.innerHTML="click to expand descriptsion";
  }
}
