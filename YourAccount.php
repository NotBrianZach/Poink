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
    $findCompanyBudget->bindValue(':id',$_SESSION['company_id']);
    $findCompanyBudget->execute();
    $companyBudget = $findCompanyBudget->fetchColumn(0);
    $findCompanyBudget->closeCursor();
    if (!isset($_POST['questionId'])){
        $newQuestionIdQuery = $database->prepare('
        	SELECT NEXT_SEQ_VALUE(:seqGenName);
        	');
        $newQuestionIdQuery->bindValue(':seqGenName', 'QUESTIONS', PDO::PARAM_STR);
        $newQuestionIdQuery->execute();
        $newQuestionId = $newQuestionIdQuery ->fetchColumn(0);
        $newQuestionIdQuery->closeCursor();
    }
    else{
        $newQuestionId = filter_input(INPUT_POST, 'questionId', FILTER_SANITIZE_NUMBER_INT);
        $_SESSION['questionId'] = $newQuestionId;
    }
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
    $openQuestionQuery->bindValue(':companyId', $_SESSION['company_id'], PDO::PARAM_INT);
    $openQuestionQuery->execute();
    $yourQuestions = $openQuestionQuery->fetchAll(PDO::FETCH_ASSOC);
    $openQuestionQuery->closeCursor();
    
    //defining a bunch of arrays here to uniquely specify dom elements that encode map info for current ads which we will use javascript to pick up and
    //then render the maps, so these arrays will be called when generating the page and also in the javascript... so much for seperation..
    $coords = array();
    $questionIds = array();
    $insideId = array();
    $anothercounter = 0;
    //unneccessary loopin here, probably faster to do in sql?
    //this is only for the current user/company's questions
    foreach($yourQuestions as $currQuestion){
      if (ord($currQuestion['DELETED']) == 0){
        $counter = 0;
        $questionCoordsQuery = $database->prepare('
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
    	/*WHERE Q.DELETED = 0;*/
            WHERE QC.QUESTION_ID = :questionId;
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
    //we'll use this to filter all coords and bids to only those that are relevant for the demographics targeted by the user
    if(isset($_POST['bid'], $_POST['minage'], $_POST['maxage'], $_POST['gender'], $_POST['question'], $_POST['questionBudget'])){
        $bid = filter_input(INPUT_POST, 'bid', FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
        $minage = filter_input(INPUT_POST, 'minage', FILTER_SANITIZE_NUMBER_INT);
        $maxage = filter_input(INPUT_POST, 'maxage', FILTER_SANITIZE_NUMBER_INT);
        $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_NUMBER_INT);
        $question = filter_input(INPUT_POST, 'question', FILTER_SANITIZE_STRING);
        $questionBudget = filter_input(INPUT_POST, 'questionBudget', FILTER_SANITIZE_NUMBER_FLOAT);
    }
    else{
        $bid = .05;
        $minage = 0;
        $maxage = 100;
        $gender = 0;
        $question = "";
        $questionBudget = 0;
    }
    
    $othersCoordsAndBids = array();
    foreach ($allCoordsAndBids as $one){
        if ($one['BID'] > $bid && $one['minage'] >= $minage && $one['maxage'] <= $maxage && 
    	($one['gender'] || $gender) === 0 || $one['gender'] == $gender){
    	$othersCoordsAndBids[] = $one;
        }
    }
    
    $yourCoordsAndBids = array();
    $yourCoordsAndBids = $coords;//$yourCoordsAndBids is a very nested array.organized by questionId
    
    //we are approximating the earth as a sphere.
    function overlapExists($geoData1, $geoData2){
        //6371009 meters is the radius of the earth, approx,don't think the arc length for lng are right.
        $latArcLength = (floatval($geoData1['LAT']) - floatval($geoData2['LAT'])) * pi()/180 * 6371009;
        if((floatval($geoData1['LNG']) >= 0 && floatval($geoData2['LNG']) >= 0) 
    	|| (floatval($geoData1['LNG']) < 0 && floatval($geoData2['LNG']) < 0)
    	|| (abs(floatval($geoData1['LNG'])) <= 90 && abs(floatval($geoData2['LNG'])) <= 90)){
    	    $lngArcLength = (floatval($geoData1['LNG']) - floatval($geoData2['LNG'])) * pi()/180 * 6371009; 
        }
        elseif(floatval($geoData1['LNG']) > 90){//one must be negative, one must be positive 
    	    $lngArcLength = (360 - floatval($geoData1['LNG']) + floatval($geoData2['LNG'])) * pi()/180 * 6371009; 
        }
        else{
    	    $lngArcLength = (360 - floatval($geoData2['LNG']) + floatval($geoData1['LNG'])) * pi()/180 * 6371009;
        }
        $circleOverlap = sqrt($latArcLength * $latArcLength + $lngArcLength * $lngArcLength) - ($geoData1['RADIUS'] + $geoData2['RADIUS']);
        if ($circleOverlap <= 0){
    	    return true;
        }
            return false;
    }
    $highestBids = array();
    foreach($yourCoordsAndBids as $oneOfYours){
        foreach($oneOfYours as $one){
            $highestBids[$one['QUESTION_COORD_ID']] = $one['BID'];
        }
    }

    foreach($allCoordsAndBids as $oneOfAll){
      foreach($yourCoordsAndBids as $oneOfYours){
        if (overlapExists($oneOfAll, $oneOfYours)){
    	   if ($highestBids[$oneOfYours['QUESTION_COORD_ID']] < $oneOfAll['BID'])
    	      $highestBids[$oneOfYours['QUESTION_COORD_ID']] = $oneOfAll['BID'];
        }
      }
    }
    //this might come in use? I don't know..not used anywhere right now
    //did the filtering in php
    //might make code more understandable when refactoring..
    function filterDeleted($coord){
        if (ord($coord['DELETED']) == 0)
    	return true;
        return false;
    }
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
	//important "global" variables
	var othersCoordsAndBids = <?php echo json_encode($othersCoordsAndBids)?>;
	var circles = [];
	var numCircles = 0;
	var map;
	var highestBids = []; //the index of the bid cooresponds to the order in which the user draws the circles.
	var userBid = <?php echo json_encode($bid)?>;
	var userMinAge = <?php echo json_encode($minage)?>;
	var userMaxAge = <?php echo json_encode($maxage)?>;
	var user = <?php echo json_encode($_SESSION['company_id'])?>;
	//this is used to compute whether or not a region the user is selecting overlap with regions other advertisers
	//chose and then later we find the highest bid out of those regions
	function overlapExists(geoData1, geoData2){
	    //6371009 meters is the radius of the earth, approx
	    latArcLength = (parseFloat(geoData1.LAT) - parseFloat(geoData2.LAT)) * Math.PI/180 * 6371009;
	    if((parseFloat(geoData1.LNG) >= 0 && parseFloat(geoData2.LNG) >= 0) 
		|| (parseFloat(geoData1.LNG) < 0 && parseFloat(geoData2.LNG) < 0)
		|| (Math.abs(parseFloat(geoData1.LNG)) <= 90 && Math.abs(parseFloat(geoData2.LNG)) <= 90)){
		lngArcLength = (parseFloat(geoData1.LNG) - parseFloat(geoData2.LNG)) * Math.PI/180 * 6371009; 
	    }
	    else if(parseFloat(geoData1.LNG) > 90){//one must be negative, one must be positive 
		lngArcLength = (360 - parseFloat(geoData1.LNG) + parseFloat(geoData2.LNG)) * Math.PI/180 * 6371009; 
	    }
	    else{
		lngArcLength = (360 - parseFloat(geoData2.LNG) + parseFloat(geoData1.LNG)) * Math.PI/180 * 6371009;
	    }
	    var circleOverlap = Math.sqrt(latArcLength * latArcLength + lngArcLength * lngArcLength) - (geoData1.RADIUS + geoData2.RADIUS);
	    if (circleOverlap <= 0){
		return true;
	    }
	    return false;
	}
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
	    highestBids[0] = .05;
	    //highestBids[0] will correspond to the highest competitor bid at the generating point for circles.
	    var thisCoord = {"LAT":30.25,"LNG":-97.75,"RADIUS":1000000};
	    for (var oneOfAll in othersCoordsAndBids){
	        if (overlapExists(othersCoordsAndBids[oneOfAll],thisCoord)){
	    	if (highestBids[0] < othersCoordsAndBids[oneOfAll].BID){
	    	  highestBids[0] = othersCoordsAndBids[oneOfAll].BID;
	    	}
	        }
	    }
	    $('<p id="latlngOfBid' + numCircles + '"> Lat:30.25 Lng:-97.75</p>' )
	        .appendTo('#highestBidsInTarget');
	    $('<p id="bidForTarget' + numCircles + '"> Bid:' + highestBids[0] + '</p>' )
	        .appendTo('#highestBidsInTarget');
	    var localNumCircles = numCircles;
	    google.maps.event.addListener(circles[localNumCircles], 'radius_changed', function(){
	  		$('#latlngOfBid'.concat(localNumCircles)).text('Lat:' + circles[localNumCircles].getCenter().lat().toFixed(3) 
	  		        + ' Lng:' + circles[localNumCircles].getCenter().lng().toFixed(3));
	        var thisCoord = {"LAT":circles[localNumCircles].getCenter().lat(),"LNG":circles[localNumCircles].getCenter().lng(),"RADIUS":circles[localNumCircles].getRadius()};
	        highestBids[localNumCircles] = .05;
            //othersCoordsAndBids[0].BID;//not sure if most computationally efficient method...
	        for (var oneOfAll in othersCoordsAndBids){
	            if (overlapExists(othersCoordsAndBids[oneOfAll],thisCoord)){
	          		if (highestBids[localNumCircles] < othersCoordsAndBids[oneOfAll].BID){
	         	  	  highestBids[localNumCircles] = othersCoordsAndBids[oneOfAll].BID;
	  		          $('#bidForTarget'.concat(localNumCircles)).text('Highest Bid in region:' + highestBids[localNumCircles]);
	       	    	}
	             }
	         }
	     });
	     google.maps.event.addListener(circles[localNumCircles], 'center_changed', function(){
	  	     $('#latlngOfBid' + localNumCircles).html('Lat:' + circles[localNumCircles].getCenter().lat().toFixed(3) 
	  	             + ' Lng:' + circles[localNumCircles].getCenter().lng().toFixed(3));
	         var thisCoord= {"LAT":circles[localNumCircles].getCenter().lat(),"LNG":circles[localNumCircles].getCenter().lng(),"RADIUS":circles[localNumCircles].getRadius()};
	         highestBids[localNumCircles] = .05;
	         for (var oneOfAll in othersCoordsAndBids){
	             if (overlapExists(othersCoordsAndBids[oneOfAll],thisCoord)){
	           		if (highestBids[localNumCircles] < othersCoordsAndBids[oneOfAll].BID){
	           	  	    highestBids[localNumCircles] = othersCoordsAndBids[oneOfAll].BID;
	  	                $('#bidForTarget' + localNumCircles).html('Highest Bid in region:' + highestBids[localNumCircles]);
	         	    }
	             }
	         }
	     });
	    numCircles += 1;
	}
	google.maps.event.addDomListener(window, 'load', initialize);
	console.log(othersCoordsAndBids);
	function createCircle() {
	    var selectionCircleOptions = {
	        center: new google.maps.LatLng(30.25, -97.75),
	        radius: 1000000,
	        editable: true,
	        map: map
	    };
	    var localNumCircles1 = numCircles;
	    if (localNumCircles1 < 100){
            var circleBidArray;
	        circle = new google.maps.Circle(selectionCircleOptions);
	        circles.push(circle);
	        $('<p id="latlngOfBid' + localNumCircles1 + '" > Lat:30.25 Lng:-97.75</p>' )
	            .appendTo('#highestBidsInTarget');
	        $('<p id="bidForTarget' + localNumCircles1 + '" > Bid:' + highestBids[0] + '</p>' )
	            .appendTo('#highestBidsInTarget');
	        google.maps.event.addListener(circles[localNumCircles1], 'radius_changed', function(){
	            var thisCoord = {"LAT":circles[localNumCircles1].getCenter().lat(),"LNG":circles[localNumCircles1].getCenter().lng(),"RADIUS":circles[localNumCircles1].getRadius()};
	            highestBids[localNumCircles1] = .05;
	            $('#latlngOfBid' + localNumCircles1).html('Lat:' + circles[localNumCircles1].getCenter().lat().toFixed(3) 
	                + ' Lng:' + circles[localNumCircles1].getCenter().lng().toFixed(3));
	            for (var oneOfAll in othersCoordsAndBids){
	                if (overlapExists(othersCoordsAndBids[oneOfAll], thisCoord)){
	                	 if (highestBids[localNumCircles1] < othersCoordsAndBids[oneOfAll].BID){
	                  	     highestBids[localNumCircles1] = othersCoordsAndBids[oneOfAll].BID;
	                         $('#bidForTarget' + localNumCircles1).html('Highest Bid in region:' + highestBids[localNumCircles1]);
	                    }
	                }
	            }
	   	    });
	        google.maps.event.addListener(circles[localNumCircles1], 'center_changed', function(){
	    	      var thisCoord= {"LAT":circles[localNumCircles1].getCenter().lat(),"LNG":circles[localNumCircles1].getCenter().lng(),"RADIUS":circles[localNumCircles1].getRadius()};
	              highestBids[localNumCircles1] = .05;
	  	    	  $('#latlngOfBid' + localNumCircles1).html('Lat:' + circles[localNumCircles1].getCenter().lat().toFixed(3)
	  	    	     + ' Lng:' + circles[localNumCircles1].getCenter().lng().toFixed(3));
	    	      for (var oneOfAll in othersCoordsAndBids){
	                   if (overlapExists(othersCoordsAndBids[oneOfAll],thisCoord)){
	             	    	 if (highestBids[localNumCircles1] < othersCoordsAndBids[oneOfAll].BID){
	             	  	         highestBids[localNumCircles1] = othersCoordsAndBids[oneOfAll].BID;//WHY NO WORK? CUZ OF THISCOORD?
	  	    	                 $('#bidForTarget' + localNumCircles1).html('Highest Bid in region:' + highestBids[localNumCircles1]);
	    	      	         }
	    	             }
	    	      }
            });
	    }
	    numCircles += 1;
	}
	function deleteCircle() {
		if (numCircles >= 0){
		    console.log(circles);
		    deletedCircle = circles.pop();
	            $('#latlngOfBid' + numCircles).remove(); 
	            $('#bidForTarget' + numCircles).remove(); 
	  	    deletedCircle.setMap(null);  
		    numCircles -= 1;
		}
	}	
	function showCurrentAdRegions(){
	    var map1 = [];
	    var mapOptions = {
	        center: new google.maps.LatLng(30.25, -97.75),
	        zoom: 1
	    };
	    var qids = <?php echo json_encode($questionIds)?>;
	    var coordinatesNumber = <?php echo json_encode($insideId)?>;
	    var numberCounter = 0;
	    var whichCoord = 1;
	    var circle = [];
	    for (var i=0; i < qids.length; i++){ 
	        map1[i] = new google.maps.Map(document.getElementById("map-canvas-currentAd"+qids[i]),
	        	    mapOptions);
	        while(true) {
	            if(whichCoord == i + 2){//each question does not nececessarily have more circles...but whichcoord is not reset...
	               break;
	            }
	            if(numberCounter == coordinatesNumber.length){
	      	    break;
	            }
	                var circleOptions = {
	                  center: new google.maps.LatLng(parseFloat(document.getElementById("lat"+qids[i]+coordinatesNumber[numberCounter]).getAttribute("value")), 
	            	          parseFloat(document.getElementById("lng"+qids[i]+coordinatesNumber[numberCounter]).getAttribute("value"))),
	                  radius: parseFloat(document.getElementById("radius"+qids[i]+coordinatesNumber[numberCounter]).getAttribute("value")),
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
	  <h4>Submit an Ad with Target Demographics</h4>
        <p>Advert:</p>
	    <!--onsubmit can be used for javascript error checking-->
	    <form id="submitquestion"  action="insertNewQuestion.php?identifier=Advert%20and%20Demographic%20Info" method="post" enctype="multipart/form-data">
		<textarea id="questionx" name="question" rows="8" columns="30"/><?=htmlspecialchars($question)?></textarea>
	  	<p>Age Range</p> 
	  	<input type="text" id="minagex" name="minage" value="<?=$minage?>"/>
	  	to
	  	<input type="text" id="maxagex" name="maxage" value="<?=$maxage?>"/>
	  	<p>Targeted Gender(s)</p>
	  	<select id="genderx" name="gender"> 
	  	  <option value="0">All</option>
	  	  <option value="1">Male</option>
	  	  <option value="2">Female</option>
	  	</select>
		<script type="text/javascript"> document.getElementById("genderx").selectedIndex=<?=$gender?>;</script>
	  	<p>Bid on ad price in dollars</p>
	  	  <input type="text" id="bidx" name="bid" value="<?=$bid?>"/>
	  	<p>Budget for this ad, must be less than total budget minus other ad bugets</p>
	  	  <input type="text" id="budgetx" name="questionBudget" value="<?=$questionBudget?>"/>
		<input type="hidden" name="companyBudget" value="<?=$companyBudget?>"/>
		<input type="hidden" name="questionId" value="<?=$newQuestionId?>"/>
		<input type="hidden" name="companyId" value="<?=$_SESSION['user_id']?>"/>
		</br>
		<p>Target Demographic info will be used when selecting target regions to display competing bids</p>
        <input type="button" value="Submit First Half of Ad" onclick="return validateQuestionInsertOrUpdate(this.form,
           document.getElementById('questionx').value, document.getElementById('minagex'), document.getElementById('maxagex'),
           document.getElementById('gender').value, document.getElementById('bidx').value, document.GetElementById('budgetx');"/>
	    </form>
	    <br/>
	  <h4>Select Target Regions</h4>
	  	  <input type="button" onclick="createCircle()" value="Add new target region"/>
	  	  <input type="button" onclick="deleteCircle()" value="Delete most recent region"/>
	    <form id="submitquestioncoords"  action="insertNewQuestion.php?identifier=Target%20Regions" method="post" enctype="multipart/form-data">
	  	<div id="map-canvas" style="height:30em; width:30em;"></div>
		<p>Highest Current Bid in Target Regions for Your Selected Demographics</p>
		<div id="highestBidsInTarget">
		</div>
		<input type="hidden" name="questionId" value="<?=$newQuestionId?>"/>
		<input type="hidden" name="companyId" value="<?=$_SESSION['user_id']?>"/>
        <input type="button" value="Submit Full Ad to Database" onclick="return appendCoords(event, this.form);"/><!-- somehow we have to determine if the question has been submitted.-->
        <p>Submit coords only if you are satisfied with everything!</p>
	    </form>
        </div>
        <div class="displayform" id="alreadylisted">
	  <h4>Current Ads</h4>
	    <?php
		foreach($yourQuestions as $currQuestion):
		    if (ord($currQuestion['DELETED']) == 0):
	    ?>
		    <p>Question: <?=htmlspecialchars($currQuestion['QUESTION'])?></p>
		    <p>Bid: <?=htmlspecialchars($currQuestion['BID'])?></p>
		    <p>Current Budget for this Question: <?=htmlspecialchars($currQuestion['BUDGET'])?></p>
		    <p>Target Gender(s): <?php if($currQuestion['TARGET_GENDERS'] == 0){ echo "All";}
		    else if($currQuestion['TARGET_GENDERS'] === "1"){ echo "Male";}else{ echo "Female";}?></p>
		    <p>Age Range: <?=htmlspecialchars($currQuestion['MIN_AGE'])?> to <?=htmlspecialchars($currQuestion['MAX_AGE'])?></p>
		    <p>Target Regions with highest competitive bid for each region</p>
	  	        <div id=<?=htmlspecialchars("map-canvas-currentAd".$currQuestion['QUESTION_ID'])?> style="height:20em; width:20em;"></div>
			<p>Highest Bids</p> 
		    <?php  
			 foreach($coords[$currQuestion['QUESTION_ID']] as $currCoords):
		    ?>
            <p>Lat, Lng: <?=htmlspecialchars($currCoords['LAT'])?>, <?=htmlspecialchars($currCoords['LNG'])?>
                 Highest Bid: <?=htmlspecialchars($highestBids[$currCoords['QUESTION_COORD_ID']])?></p>
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
        <div class="displayform" id="alreadylisted">
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
        <script src="./js/forms.js" type="text/javascript"></script>
    <?php else: ?>
	<p>
		<span class="error">You are not authorized to access this page</span>
	</p>
    <?php endif; ?>
    </body>
</html>
