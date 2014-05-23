<?php
include_once './includes/db_connect.php';
include_once './includes/functions.php';
include_once './includes/addtoaccount.php';
include_once './includes/removead.php';
include_once './includes/permanentdelete.php';
sec_session_start();

$findCompanyBudget = $database->prepare('
	SELECT
	    BUDGET
	FROM COMPANIES WHERE COMPANY_ID=:id;
    ');
$findCompanyBudget->bindValue(':id',$_SESSION['user_id']);
$findCompanyBudget->execute();
$companyBudget = $findCompanyBudget->fetchColumn(0);
$findCompanyBudget->closeCursor();

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
	Q.COMPANY_ID,
	Q.QUESTION,
        Q.MIN_AGE,
        Q.MAX_AGE,
        Q.BUDGET,
        Q.BID,
	Q.TARGET_GENDERS,
	Q.DELETED
        FROM QUESTIONS Q
	WHERE Q.COMPANY_ID = :companyId;
    ');
$openQuestionQuery->bindValue(':companyId', $_SESSION['user_id'], PDO::PARAM_INT);
$openQuestionQuery->execute();
$yourQuestions = $openQuestionQuery->fetchAll(PDO::FETCH_ASSOC);
$openQuestionQuery->closeCursor();

//defining a bunch of arrays here to uniquely specify dom elements that encode map info for current ads which we will use javascript to pick up and
//then render the maps, so these arrays will be called when generating the page and also in the javascript... so much for seperation..
$coords = array();
$questionIds = array();
$insideId = array();
$anothercounter = 0;
foreach($yourQuestions as $currQuestion){
  if (ord($currQuestion['DELETED']) == 0){
    $counter = 0;
    $questionCoordsQuery = $database->prepare('
        SELECT
            QUESTION_COORDS.LAT,
            QUESTION_COORDS.LNG,
            QUESTION_COORDS.RADIUS,
	    QUESTION_COORDS.QUESTION_COORD_ID
            FROM QUESTION_COORDS
            WHERE QUESTION_ID = :questionId;
    ');
    $questionCoordsQuery->bindValue(':questionId', $currQuestion['QUESTION_ID'], PDO::PARAM_INT);
    $questionCoordsQuery->execute();
    $coords[$currQuestion['QUESTION_ID']] = $questionCoordsQuery->fetchAll(PDO::FETCH_ASSOC); //this stores correlation between questin id's and coordinates
    $questionIds[$anothercounter] = $currQuestion['QUESTION_ID'];
    foreach($coords[$currQuestion['QUESTION_ID']] as $key){
        $insideId[] = $counter;
        $counter += 1;
    } 
    $questionCoordsQuery->closeCursor();
    $anothercounter += 1;
  }
}
//Need to grab location data coupled with bids from other companies
//have multiple instances of location data for every bid, how will 
//the sql statement react? maybe append bid to each coord.
//I guess that's what I want anyway.
$allCoordsAndBidsQuery = $database->prepare('
	SELECT
	    QC.LAT,
	    QC.LNG,
	    QC.RADIUS,
	    QC.QUESTION_ID,
	    QC.QUESTION_COORD_ID,
	    Q.BID
	FROM QUESTION_COORDS AS QC
	LEFT JOIN QUESTIONS AS Q
	ON Q.QUESTION_ID=QC.QUESTION_ID
	WHERE Q.DELETED = 0;
');
$allCoordsAndBidsQuery->execute();
$allCoordsAndBids=$allCoordsAndBidsQuery->fetchAll(PDO::FETCH_ASSOC);//bids are not necessarily local, but we get the local highest bids from this array
$allCoordsAndBidsQuery->closeCursor();

$yourCoordsAndBids = array();
foreach ($allCoordsAndBids as $one){
    if(in_array($one['QUESTION_ID'], $questionIds)){
	$yourCoordsAndBids[] = $one;
    }
}

function overlapExists($geoData1, $geoData2){
    //6371009 meters is the radius of the earth, approx
    $latArcLength = ((floatval($geoData1['LAT']) - floatval($geoData2['LAT'])) + 90) * pi()/180 * 6371009;
    $lngArcLength = ((floatval($geoData1['LNG']) - floatval($geoData2['LNG'])) + 180) * pi()/180 * 6371009;
    $circleOverlap = sqrt($latArcLength * $latArcLength + $lngArcLength * $lngArcLength) - ($geoData1['RADIUS'] + $geoData2['RADIUS']);
    if (circleOverlap <= 0){
	return true;
    }
    return false;
}

//still needs work...need to store highest bid for each coordinate for one thing...might be other problems...
$highestBids = array();
foreach($allCoordsAndBids as $oneOfAll){
  foreach($yourCoordsAndBids as $oneOfYours){
    if (overlapExists($oneOfAll, $oneOfYours)){
	if ($highestBids[$oneOfYours['QUESTION_COORD_ID']] < $oneOfAll['BID'])
	  $highestBids[$oneOfYours['QUESTION_COORD_ID']] = $oneOfAll['BID'];
    }
  }
}
var_dump($highestBids);
/*
function filterDeleted($coord){
    if (ord($coord['DELETED']) == 0)
	return true;
    return false;
}

$notDeletedQIds = array_filter($yourQuestions,"filterDeleted");
$relevantBids = array_filter($localBids

$highestBids = array();//not sure how many I'll show
*/
//first find question id's of your questions 
//we have two arrays then your question ids and all question coords with bids 
//then match your question id's to all question coords with bids
//resulting in yourCoordsAndBids and allCoordsAndBids
//run through each list and store all coord bids in an array of prospects
// do the distance comparison, if it checks out, then
//compare the current bid to the previous highest bid
// $questionIds  = yourQuestionIds
//$yourCoordsAndBids = $allCoordsAndBids
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
  <head>
    <title>Your Account</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="mystyle.css"/>
    <script language="javascript" type="text/javascript" src="datetimepicker.js">
    </script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <script type="text/javascript"
      src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCjIbU3ukNmqzu9tJGhPLBRbQwBdzQ4ScM&libraries=geometry&sensor=false">
    </script>
    <script language="javascript" type="text/javascript">
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
	function showCurrentAdRegions(){
	  var map1 = [];
	  var mapOptions = {
	      center: new google.maps.LatLng(30.25, -97.75),
	      zoom: 1
	  };
	  var qids = <?php echo json_encode($questionIds); ?>;
	  var coordinatesNumber = <?php echo json_encode($insideId)?>;
	  var numberCounter = 0;
	  var whichCoord = 1;
	  var circle = [];
	  for (var i=0; i < qids.length; i++){ 
	      map1[i] = new google.maps.Map(document.getElementById("map-canvas-currentAd"+qids[i]),
	      	    mapOptions);
	      while(true) {
		if(whichCoord == i + 2){
		   break;
		}
	        var circleOptions = {
	          center: new google.maps.LatLng(parseFloat(document.getElementById("lat"+qids[i]+coordinatesNumber[numberCounter]).getAttribute("value")), 
			parseFloat(document.getElementById("lng"+qids[i]+coordinatesNumber[numberCounter]).getAttribute("value"))),
	          radius: parseInt(document.getElementById("radius"+qids[i]+coordinatesNumber[numberCounter]).getAttribute("value")),
	          editable: false,
	          map: map1[i]
	        };	
	  	circle.push(new google.maps.Circle(circleOptions));
		numberCounter += 1;
		if(coordinatesNumber[numberCounter] == 0){
		   whichCoord += 1;
		}
	      }
	  }
	}
	google.maps.event.addDomListener(window, 'load', showCurrentAdRegions);
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
	function appendCoords(ev, form){
	    $.each(circles, function(i,param){
	        $('<input />').attr('type', 'hidden')
	            .attr('name', 'lat[]')
	            .attr('value' , param.getCenter().lat())
	            .prependTo('form#submitquestion');
	        $('<input />').attr('type', 'hidden')
	            .attr('name' , 'lng[]')
	            .attr('value', param.getCenter().lng())
	            .prependTo('form#submitquestion');
	        $('<input />').attr('type', 'hidden')
	            .attr('name' , 'radius[]')
	            .attr('value', param.getRadius())
	            .prependTo('form#submitquestion');
	    });
	  form.submit();
	  return true;  
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
	            .appendTo('#test');
	        $('<input />').attr('type', 'button')
	            .attr('name', 'lng')
	            .attr('value', param.getCenter().lng())
	            .appendTo('#test');
	        $('<input />').attr('type', 'button')
	            .attr('name', 'radius')
	            .attr('value', param.getRadius())
	            .appendTo('#test');
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
		Your Account
            </h1>
        </div>
    <?php if( login_check($mysqli) == true): ?>
        <ul id="nav">
	    <li>Welcome, <?=htmlspecialchars($_SESSION['username'])?></li>
            <li><a href="./includes/logout.php">[Log out]</a></li>
            <li><a href="AboutPoink.php">[About Poink]</a></li>
            <li><a href="HowAdsWork.php">[How Ads Work]</a></li>
        </ul>
        <div class="displayform">
            <h4>App: Poink</h4>
	    <p> Your current total unallocated Budget for Ads:</p>
	    <p> $<?=htmlspecialchars($companyBudget)?></p>
	    <form method="post" action="<?php echo esc_url($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
		<input type="text" name="addaccount"/>
		<input type="hidden" name="user_id" value=<?php echo $_SESSION['user_id']?>/>
		<input type="submit" value="Modify account balance by amount"/>
		<p>To subtract, include a negative sign at the front, to add, type in any number, using a period before values less than a dollar.</p>
	    </form>
        </div>
        <div class="displayform" >
	    <h4>Submit a Question</h4>
                <p>Advert Question:</p>
	    <!--onsubmit can be used for javascript error checking-->
	    <form id="submitquestion"  action="insertNewQuestion.php" method="post" enctype="multipart/form-data">
		<textarea id="questionx" name="question" rows="8" columns="30"/></textarea>
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
		<p>Highest Current Bid in Target Regions</p>
		<input type="hidden" name="questionId" value="<?=$newQuestionId?>"/>
		<input type="hidden" name="companyId" value="<?=$_SESSION['user_id']?>"/>
	  	<input type="button" value="Submit" onclick="return appendCoords(event, this.form);"/>
	    </form>
        </div>
        <div class="displayform">
	    <h4>Current Ads</h4>
	    <?php
		foreach($yourQuestions as $currQuestion):
		    if (ord($currQuestion['DELETED']) == 0):
	    ?>
		    <p>Question: <?=htmlspecialchars($currQuestion['QUESTION'])?></p>
		    <p>Bid: <?=htmlspecialchars($currQuestion['BID'])?></p>
		    <p>Current Budget for this Question: <?=htmlspecialchars($currQuestion['BUDGET'])?></p>
		    <p>Target Gender(s): <?=htmlspecialchars($currQuestion['TARGET_GENDERS'])?></p>
		    <p>Age Range: <?=htmlspecialchars($currQuestion['MIN_AGE'])?> to <?=htmlspecialchars($currQuestion['MAX_AGE'])?></p>
		    <p>Target Regions with highest competitive bid for each region</p>
	  	        <div id=<?=htmlspecialchars("map-canvas-currentAd".$currQuestion['QUESTION_ID'])?> style="height:20em; width:20em;"></div>
			<p>Highest Bids: <?=htmlspecialchars($highestBids[$currQuestion['QUESTION_ID']])?></p>
		    <?php  
			 foreach($coords[$currQuestion['QUESTION_ID']] as $currCoords)://sometin' wrong here..
					?>
			<p>Lat: <?=htmlspecialchars($currCoords['LAT'])?></p>
			<p>Lng: <?=htmlspecialchars($currCoords['LNG'])?></p>
			<p>Bid: <?=htmlspecialchars($highestBids[$currCoords['QUESTION_COORD_ID']])?></p>
		    <?php
			endforeach;
			?> 
		    <?php  
			 $id=0;
			 foreach($coords[$currQuestion['QUESTION_ID']] as $currCoords):
					?>
		        <input type="hidden" id="<?=htmlspecialchars("lat".$currQuestion['QUESTION_ID'].$id)?>"
			 value="<?=htmlspecialchars($coords[$currQuestion['QUESTION_ID']][$id]['LAT'])?>"/>
	  	        <input type="hidden" id="<?=htmlspecialchars("lng".$currQuestion['QUESTION_ID'].$id)?>"
			 value="<?=htmlspecialchars($coords[$currQuestion['QUESTION_ID']][$id]['LNG'])?>"/>
	  	        <input type="hidden" id="<?=htmlspecialchars("radius".$currQuestion['QUESTION_ID'].$id)?>"
			 value="<?=htmlspecialchars($coords[$currQuestion['QUESTION_ID']][$id]['RADIUS'])?>"/>
		    <?php       $id+=1;
			endforeach;
			?> 
		<form method="post" action="<?php echo esc_url($_SERVER['PHP_SELF']);?>" enctype="multipart/form-data">
		    <input type="hidden" value=<?=$currQuestion['COMPANY_ID']?> name="companyid"/>
		    <input type="hidden" value=<?=$currQuestion['BUDGET']?> name="budget"/>
		    <input type="hidden" value=<?=$currQuestion['QUESTION_ID']?> name="removedquestionid"/>
		    </br>
		    <input type="submit" value="Remove this ad"/>
		</form>
	    <?php
		endif;
		endforeach;
	    ?>
        </div>
        <div class="displayform">
	    <h4>Past Ads</h4>
	    <?php
		foreach($yourQuestions as $currQuestion):
		    if (ord($currQuestion['DELETED']) > 0)://ord converts from string to ascii value.
	    ?>
		<form method="post" action="<?php echo esc_url($_SERVER['PHP_SELF']);?>" enctype="multipart/form-data">
		    <p>Question: <?=htmlspecialchars($currQuestion['QUESTION'])?></p>
		    <p>Bid: <?=htmlspecialchars($currQuestion['BID'])?></p>
		    <p>Current Budget for this Question: <?=htmlspecialchars($currQuestion['BUDGET'])?></p>
		    <p>Target Gender(s): <?=htmlspecialchars($currQuestion['TARGET_GENDERS'])?></p>
		    <p>Age Range: <?=htmlspecialchars($currQuestion['MIN_AGE'])?> to <?=htmlspecialchars($currQuestion['MAX_AGE'])?></p>
		    <input type="hidden" value=<?=$currQuestion['QUESTION_ID']?> name="deletequestionid"/>
		    <input type="submit" value="Delete this from the database"/>
		</form>
	    <?php
		endif;
		endforeach;
	    ?>
        </div>
        <div id="test" class="displayform">
	    <h4>TEST</h4>
		<input type="button" onclick="test()" />
        </div>
	</br>
	</br>
	</br>
	</br>
	</br>
        <script src="validateForm.js" type="text/javascript"></script>
    <?php else: ?>
	<p>
		<span class="error">You are not authorized to access this page</span>
	</p>
    <?php endif; ?>
    </body>
</html>
