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

function switchDescription(auctionId){
  var description = document.getElementById("longDescription"+auctionId).style;
  var button = document.getElementById("descButton"+auctionId);
  if (description.display=="none"){
    description.display="inline";
    button.innerHTML="click to shrink description";
  }
  else{ 
    description.display="none"; 
    button.innerHTML="click to expand description";
  }
}

function switchBoughtDescription(auctionId){
  var description = document.getElementById("boughtDescription"+auctionId).style;
  var button = document.getElementById("boughtDescButton"+auctionId);
  if (description.display=="none"){
    description.display="inline";
    button.innerHTML="click to shrink description";
  }
  else{ 
    description.display="none"; 
    button.innerHTML="click to expand description";
  }
}
