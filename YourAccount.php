<?php
require '/var/script/openZdatabase.php';
require 'password.php';
/*
$passwordQuery = $database->prepare('
    SELECT 
        PASSWORD
    FROM COMPANIES 
    WHERE COMPANIES.ACCOUNT_NAME = :name;
');
$passwordQuery->bindValue(':name', $_POST['user'], PDO::PARAM_STR);  
$passwordQuery->execute();
$passwordHash = $passwordQuery->fetchColumn(0);
$passwordQuery->closeCursor();
if (!password_verify($_POST['password'],$passwordHash))
{
    echo 'Password does not match.';
    exit(1);
}
*/
session_start();
session_regenerate_id(true);
$_SESSION['user']=$_POST['user'];
$findCompanyid = $database->prepare('
	SELECT
	    COMPANY_ID
	FROM COMPANIES WHERE ACCOUNT_NAME=:name;
    ');
$findCompanyid->bindValue(':name',$_SESSION['user']);
$findCompanyid->execute();
$companyId = $findCompanyid->fetchColumn(0);
$findCompanyid->closeCursor();


$newQuestionIdQuery = $database->prepare('
	SELECT NEXT_SEQ_VALUE(:seqGenName);
	');
$newQuestionIdQuery->bindValue(':seqGenName', 'QUESTIONS', PDO::PARAM_STR);
$newQuestionIdQuery->execute();
$newQuestionId = $newQuestionIdQuery ->fetchColumn(0);
$newQuestionIdQuery->closeCursor();

$openQuestionQuery = $database->prepare('
    SELECT
        Q.QUESTION_ID,
	Q.QUESTION,
        Q.MIN_AGE,
        Q.MAX_AGE,
        Q.BUDGET,
        Q.BID,
	Q.TARGET_GENDERS,
	QUESTION_COORDS.LAT,
	QUESTION_COORDS.LNG,
	QUESTION_COORDS.RADIUS
        FROM QUESTIONS Q
	    LEFT JOIN QUESTION_COORDS ON QUESTION_COORDS.QUESTION_ID = Q.QUESTION_ID 
	WHERE Q.COMPANY_ID = :companyId;
    ');
$openQuestionQuery->bindValue(':companyId', $companyId, PDO::PARAM_INT);
$openQuestionQuery->execute();
$yourQuestions = $openQuestionQuery->fetchAll();
$openQuestionQuery->closeCursor();
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
  <head>
    <title>Home</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="mystyle.css"/>
    <script language="javascript" type="text/javascript" src="datetimepicker.js">
    </script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <script type="text/javascript"
      src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCjIbU3ukNmqzu9tJGhPLBRbQwBdzQ4ScM&libraries=geometry&sensor=false">
    </script>
    <script type="text/javascript">
	var circles = [];
	var numCircles = 0;
	var map;
	function initialize() {
	  var mapOptions = {
	      center: new google.maps.LatLng(30.25, -97.75),
	      zoom: 2
	  };
	  map = new google.maps.Map(document.getElementById("map-canvas"),
	      mapOptions);
	  var selectionCircleOptions = {
	      center: new google.maps.LatLng(30.25, -97.75),
	      radius: 1000000,
	      editable: true,
	      map: map
	  };
	  circle = new google.maps.Circle(selectionCircleOptions);
	  circles.push(circle);
	  numCircles += 1;
	}
	google.maps.event.addDomListener(window, 'load', initialize);
	function createCircle() {
	  var selectionCircleOptions = {
	      center: new google.maps.LatLng(30.25, -97.75),
	      radius: 1000000,
	      editable: true,
	      map: map
	  };
	  if (numCircles < 100){
	  	circle = new google.maps.Circle(selectionCircleOptions);
	  	circles.push(circle);
	  	numCircles += 1;
	  }
	}
	function deleteCircle() {
		if (numCircles > 0){
	  	    circles.pop().setMap(null);  
		    numCircles -= 1;
		}
	}	
	    //latitude and longitude are in degrees (float) and radius is in meters.
	function sendCoords(ev) {
	    v.preventDefault();
	    for (i = 0; i < circles.length; i++){
	        $.post("insertNewQuestionCoords.php", 
	            {lat: circles[i].getCenter().lat(),
	            lng: circles[i].getCenter().lng(),
	            radius: circles[i].getRadius(),
	            question_Id: <?php echo $newQuestionId?>}
	        );
	    }
	    return false;
	}

	function test() {	
	    var circleParams = [];
	    //latitude and longitude are in degrees (float) and radius is in meters.
	    for (i = 0; i < circles.length; i++){
	        circleParams.push(circles[i]);
		}
	    var test = document.getElementById("test");
	    $.each(circles, function(i,param){
	        $('<input />').attr('type', 'button')
	            .attr('name', 'lat')
	            .attr('value', param.getCenter().lat())
	            .prependTo('#test');
	        $('<input />').attr('type', 'button')
	            .attr('name', 'lng')
	            .attr('value', param.getCenter().lng())
	            .prependTo('#test');
	        $('<input />').attr('type', 'button')
	            .attr('name', 'radius')
	            .attr('value', param.getRadius())
	            .prependTo('#test');
	    });
	    for (i = 0; i < circleParams.length; i++){
		test.innerHTML += circleParams[i].getCenter().toString();
	    }
	    };
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
	        <form>
		<input type="submit" value="Add More"/></form>
        </div>
        <div class="displayform" >
	    <h4>Submit a Question</h4>
                <p>Advert Question:</p>
	    <!--onsubmit can be used for javascript error checking-->
	    <form id="submitquestion" onsubmit="return sendCoords(event);" action="insertNewQuestion.php" method="post" enctype="multipart/form-data">
		<textarea id="questionx" name="question" rows="8" columns="30"/>
	  	</textarea>
	  	<p>Age Range</p> 
	  	<input type="text" id="minagex" name="minage" value="0"/>
	  	to
	  	<input type="text" id="maxagex" name="maxage" value="100"/>
	  	<p>Targeted Gender(s)</p>
	  	<select id="genderx" name="gender">
	  	  <option value="0">Male</option>
	  	  <option value="1">Female</option>
	  	  <option value="2">Both Male and Female</option>
	  	  <option value="3">Gender is too mainstream</option>
	  	  <option value="4">All</option>
	  	</select>
	  	<p>Bid on ad price in dollars</p>
	  	  <input type="text" id="bidx" name="bid" value=".05"/>
	  	<p>Budget for this ad, must be less than total budget minus other ad bugets</p>
	  	  <input type="text" id="budgetx" name="budget"/>
	  	<p>Target Regions</p>
	  	  <input type="button" onclick="createCircle()" value="Add new target region"/>
	  	  <input type="button" onclick="deleteCircle()" value="Delete most recent region"/>
	  	<div id="map-canvas" style="height:30em; width:30em;"></div>
		<input type="hidden" name="questionId" value="<?=$newQuestionId?>"/>
		<input type="hidden" name="companyId" value="<?=$companyId?>"/>
	  	<input type="submit"/>
	    </form>
        </div>
        <div class="displayform">
	    <h4>Current Questions</h4>
	    <?php
		foreach($yourQuestions as $currQuestion):
	    ?>
		    <p>Question:<?=htmlspecialchars($currQuestion['QUESTION'])?></p>
		    <p>Bid:<?=htmlspecialchars($currQuestion['BID'])?></p>
		    <p>Current Budget for this Question:<?=htmlspecialchars($currQuestion['BUDGET'])?></p>
		    <p>Target Gender(s):<?=htmlspecialchars($currQuestion['TARGET_GENDERS'])?></p>
		    <p>Age Range:<?=htmlspecialchars($currQuestion['MIN_AGE'])?> to <?=htmlspecialchars($currQuestion['MAX_AGE'])?></p>
		    <p>Target Regions with highest competitive bid for each region</p>
			MAP GOES HERE with highest bid info for each region.
		later on, show the current going rate as well as the user's bid. will also need to allow for canceling questions.
		should we allow for updating questions? idk may make user interface unnecessarily complex.
		definitely need a delete button though.
	    <?php
		endforeach;
	    ?>
        </div>
        <div id="test" class="displayform"><!-- id is just for testing.-->
	    <h4>TEST</h4>
		<input type="button" onclick="test()" />
        </div>
	</br>
	</br>
	</br>
	</br>
	</br>
    <script src="validateForm.js" type="text/javascript"></script>
    </body>
</html>
