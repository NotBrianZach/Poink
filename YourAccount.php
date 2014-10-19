<?php
    include_once './includes/db_connect.php';
    include_once './includes/functions.php';
    include_once './includes/addtoaccount.php';
    include_once './includes/removead.php';
    include_once './includes/permanentdelete.php';
    include_once './includes/removeQuestionCoord.php';
    include_once './includes/globalVariables.php';
    require_once 'vendor/autoload.php';
    sec_session_start();
    //Starting out with a global variable that propagates into the javascript.
    
    $minimumBid = .05;
    $findCompanyBudget = $database->prepare('
    	SELECT
    	    BUDGET
    	FROM COMPANIES WHERE COMPANY_ID=:id;
        ');
    $findCompanyBudget->bindValue(':id',$_SESSION['companyId'], PDO::PARAM_INT);
    $findCompanyBudget->execute();
    $companyBudget = $findCompanyBudget->fetchColumn(0);
    $findCompanyBudget->closeCursor();
//we stick questionId in session to check later on if we need to update page info
//this controls the behavior of submitting question, whether it's an update or insert 
    if (!isset($_POST['questionId'])){
        $newQuestionIdQuery = $database->prepare('
        	SELECT NEXT_SEQ_VALUE(:seqGenName);
        	');
        $newQuestionIdQuery->bindValue(':seqGenName', 'QUESTIONS', PDO::PARAM_STR);
        $newQuestionIdQuery->execute();
        $newQuestionId = $newQuestionIdQuery->fetchColumn(0);
        $newQuestionIdQuery->closeCursor();
        $_SESSION['questionId'] = $newQuestionId;
        $_SESSION['updated'] = 0;
    }
    else{
        $_SESSION['questionId'] = $_POST['questionId'];
        $_SESSION['updated'] = 1;
    }
    $openQuestionQuery = $database->prepare('
        SELECT
            Q.QUESTION_ID,
    	    Q.COMPANY_ID,
    	    Q.QUESTION,
            Q.MIN_AGE,
            Q.MAX_AGE,
    	    Q.TARGET_GENDERS,
            Q.DELETED
        FROM QUESTIONS Q
    	WHERE Q.COMPANY_ID = :companyId;
        ');
    $openQuestionQuery->bindValue(':companyId', $_SESSION['companyId'], PDO::PARAM_INT);
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
      if ($currQuestion['DELETED'] == 0){
        $counter = 0;
        $questionCoordsQuery = $database->prepare('
            SELECT
    	       QC.LAT,
    	       QC.LNG,
    	       QC.RADIUS,
    	       QC.QUESTION_ID,
    	       QC.QUESTION_COORD_ID,
    	       QC.BID,
    	       QC.BUDGET,
    	       QC.VALID,
               AU.TIMES_DISPLAYED,
               AU.TIMES_ANSWERED,
               AU.ANSWER_VIEWS,
               AU.ANSWER_CLICKTHROUGHS
    	    FROM QUESTION_COORDS AS QC
            LEFT JOIN APP_USAGE AU
            ON QC.QUESTION_ID = AU.QUESTION_ID
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
    	    QC.BID
    	FROM QUESTION_COORDS AS QC;
    	/*LEFT JOIN QUESTIONS AS Q
    	ON Q.QUESTION_ID=QC.QUESTION_ID
        WHERE Q.DELETED = 0; might break everything since how to filter out deleted?*/
    ');
    $allCoordsAndBidsQuery->execute();
    $allCoordsAndBids=$allCoordsAndBidsQuery->fetchAll(PDO::FETCH_ASSOC);//bids are not necessarily local, but we get the local highest bids from this array
    $allCoordsAndBidsQuery->closeCursor();
    //we'll use this to filter all coords and bids to only those that are relevant for the demographics targeted by the user
    if(isset($_POST['minage'], $_POST['maxage'], $_POST['gender'], $_POST['question'])){
        $minage = filter_input(INPUT_POST, 'minage', FILTER_SANITIZE_NUMBER_INT);
        $maxage = filter_input(INPUT_POST, 'maxage', FILTER_SANITIZE_NUMBER_INT);
        $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_NUMBER_INT);
        $question = filter_input(INPUT_POST, 'question', FILTER_SANITIZE_STRING);
    }
    else{
        $minage = $minAge;
        $maxage = $maxAge;
        $gender = 0;
        $question = "";
    }
    
    $othersCoordsAndBids = array();
    //FILTERING COORDS AND BIDS ACCORDING TO DEMOGRAPHICS!
    foreach ($allCoordsAndBids as $one){
        if ($one['minage'] >= $minage && $one['maxage'] <= $maxage && 
    	($one['gender'] || $gender) == 0 || $one['gender'] == $gender){
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
            $highestBids[$one['QUESTION_COORD_ID']] = $minimumBid;
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
    $checkBanned = $database->prepare('
        SELECT EMAIL FROM BANNED WHERE EMAIL=:email;
        ');
    $checkBanned->bindValue(':email',$_SESSION['email'],PDO::PARAM_STR);
    $checkBanned->execute();
    $banned = $checkBanned->fetchAll();
    $checkBanned->closeCursor();
    if (count($banned) == 0){
        $banned = 0;
    }
    else{
        $banned = 1;
    } 
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
  <head>
    <title>Your Account</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="mystyle.css"/>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <script type="text/javascript" src="https://code.jquery.com/ui/1.11.0/jquery-ui.js"></script>
    <link rel="stylesheet" type="text/css" media="all"
        href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/themes/smoothness/jquery-ui.css"/>
    <script type="text/javascript" src="./js/timePicker.js"></script>
    <script type="text/javascript" src="./js/paypalButton.js"></script>
    <script type="text/javascript"
      src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCjIbU3ukNmqzu9tJGhPLBRbQwBdzQ4ScM&libraries=geometry&sensor=false">
    </script>
    <script type="text/javascript">
    $(document).on("click",".datepicker", function(){
        //$(".datepicker").datetimepicker({ minDate: 0, timeFormat: 'hh:mm tt z'});
        $(".datepicker").datetimepicker({ minDate: 0, timeFormat: 'hh tt'});
        return true;
    });
    </script>
    <script language="javascript" type="text/javascript">
	//important "global" variables
    var endDateNullValue = <?php echo json_encode($endDateNullValue)?>;
	var othersCoordsAndBids = <?php echo json_encode($othersCoordsAndBids)?>;
	var circles = [];
	var numCircles = 0;
	var map;
    var mapCenter;//this and lastRadius used when making new circles.
    var lastRadius = 1000000;
	var highestBids = []; //the index of the bid cooresponds to the order in which the user draws the circles.
	var minimumBid = <?php echo json_encode($minimumBid)?>;
	var userMinAge = <?php echo json_encode($minage)?>;
	var userMaxAge = <?php echo json_encode($maxage)?>;
    var numCirclesList = [0,0];
	//this is used to compute whether or not a region the user is selecting overlaps with regions other advertisers
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
	    var circleOverlap = Math.sqrt(latArcLength * latArcLength + lngArcLength * lngArcLength) - (parseFloat(geoData1.RADIUS) + parseFloat(geoData2.RADIUS));
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
	    highestBids[0] = minimumBid;
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
	    $('<p id="bidForTarget' + numCircles + '"> Others Highest Bid: ' + highestBids[0] + '</p>')
	        .appendTo('#highestBidsInTarget');
        $('<p id="yourBidForTarget' + numCircles + '"> Your Bid: <input type="text" class="bidx" name="bid[]" value="' + minimumBid + '"/> </p>')
            .appendTo('#highestBidsInTarget');
	  	$('<p id="yourBudgetForTarget' + numCircles + '"> Budget: <input type="text" class="budgetx" name="questionBudget[]" value="' + minimumBid + '"/></p>')
            .appendTo('#highestBidsInTarget');
        $('<p id="yourTargetEndDate' + numCircles +
            '"> End Date (optional): <input type="text" class="datepicker" name="date[]" id="datepicker"> GMT. Leave blank if you don\'t want an end date. </p>')
            .appendTo('#highestBidsInTarget');
        $(".datepicker").trigger("click");//have to trigger both on click and the event listener.
	    var localNumCircles = numCircles;
	    google.maps.event.addListener(circles[localNumCircles], 'radius_changed', function(){
            lastRadius = circles[localNumCircles].getRadius();
	  		$('#latlngOfBid'.concat(localNumCircles)).text('Lat:' + circles[localNumCircles].getCenter().lat().toFixed(3) 
	  		        + ' Lng:' + circles[localNumCircles].getCenter().lng().toFixed(3));
            var thisCoord = {"LAT":circles[localNumCircles].getCenter().lat(),
                "LNG":circles[localNumCircles].getCenter().lng(),"RADIUS":lastRadius};
	        highestBids[localNumCircles] = .05;
	  	    $('#bidForTarget' + localNumCircles).html('Others Highest Bid: ' + highestBids[localNumCircles]);
	        for (var oneOfAll in othersCoordsAndBids){
	            if (overlapExists(othersCoordsAndBids[oneOfAll],thisCoord)){
	          		if (highestBids[localNumCircles] < othersCoordsAndBids[oneOfAll].BID){
	         	  	  highestBids[localNumCircles] = othersCoordsAndBids[oneOfAll].BID;
	  		          $('#bidForTarget'.concat(localNumCircles)).text('Others Highest Bid: ' + highestBids[localNumCircles]);
	       	    	}
	             }
	         }
	     });
	     google.maps.event.addListener(circles[localNumCircles], 'center_changed', function(){
	  	     $('#latlngOfBid' + localNumCircles).html('Lat:' + circles[localNumCircles].getCenter().lat().toFixed(3) 
	  	             + ' Lng:' + circles[localNumCircles].getCenter().lng().toFixed(3));
             var thisCoord= {"LAT":circles[localNumCircles].getCenter().lat(),
                 "LNG":circles[localNumCircles].getCenter().lng(),"RADIUS":circles[localNumCircles].getRadius()};
	         highestBids[localNumCircles] = .05;
	  	     $('#bidForTarget' + localNumCircles).html('Others Highest Bid: ' + highestBids[localNumCircles]);
	         for (var oneOfAll in othersCoordsAndBids){
	             if (overlapExists(othersCoordsAndBids[oneOfAll],thisCoord)){
	           		if (highestBids[localNumCircles] < othersCoordsAndBids[oneOfAll].BID){
	           	  	    highestBids[localNumCircles] = othersCoordsAndBids[oneOfAll].BID;
	  	                $('#bidForTarget' + localNumCircles).html('Others Highest Bid: ' + highestBids[localNumCircles]);
	         	    }
	             }
	         }
	     });
	    numCircles += 1;
	}
	google.maps.event.addDomListener(window, 'load', initialize);
	function createCircle() {
	    var selectionCircleOptions = {
	        //center: new google.maps.LatLng(30.25, -97.75),
	        center: map.getCenter(),
	        radius: lastRadius,
	        editable: true,
	        map: map
	    };
	    var localNumCircles1 = numCircles;
	    if (localNumCircles1 < 100){
            var circleBidArray;
	        circle = new google.maps.Circle(selectionCircleOptions);
	        circles.push(circle);
	        $('<p id="latlngOfBid' + localNumCircles1 + '" > Lat:' + map.getCenter().lat() + 'Lng:' + map.getCenter().lng() + '</p>' )
	            .appendTo('#highestBidsInTarget');
	        $('<p id="bidForTarget' + localNumCircles1 + '" > Bid:' + highestBids[0] + '</p>' )
	            .appendTo('#highestBidsInTarget');
            $('<p id="yourBidForTarget' + localNumCircles1 +'"> Your Bid: <input type="text" class="bidx" name="bid[]" value="'
                + minimumBid + '"/> </p>')
                .appendTo('#highestBidsInTarget');
            $('<p id="yourBudgetForTarget' + localNumCircles1 + '"> Budget: <input type="text" class="budgetx" name="questionBudget[]" value="'
                + minimumBid + '"/></p>')
                .appendTo('#highestBidsInTarget');
            $('<p id="yourTargetEndDate' + localNumCircles1 + '"> End Date(optional): <input type="text" class="datepicker" name="date[]" id="datepickerx'
                + localNumCircles1 + '"> GMT. Leave blank if you don\'t want an end date.  </p>')
                .appendTo('#highestBidsInTarget');
            $(".datepicker").trigger("click");//have to trigger both on click and the event listener.
	        google.maps.event.addListener(circles[localNumCircles1], 'radius_changed', function(){
                lastRadius = circles[localNumCircles1].getRadius();
                var thisCoord = {"LAT":circles[localNumCircles1].getCenter().lat(),
                    "LNG":circles[localNumCircles1].getCenter().lng(),"RADIUS":lastRadius};
	            highestBids[localNumCircles1] = minimumBid;
	            $('#bidForTarget' + localNumCircles1).html('Others Highest Bid: ' + highestBids[localNumCircles1]);
	            $('#latlngOfBid' + localNumCircles1).html('Lat:' + circles[localNumCircles1].getCenter().lat().toFixed(3) 
	                + ' Lng:' + circles[localNumCircles1].getCenter().lng().toFixed(3));
	            for (var oneOfAll in othersCoordsAndBids){
	                if (overlapExists(othersCoordsAndBids[oneOfAll], thisCoord)){
	                	 if (highestBids[localNumCircles1] < othersCoordsAndBids[oneOfAll].BID){
	                  	     highestBids[localNumCircles1] = othersCoordsAndBids[oneOfAll].BID;
	                         $('#bidForTarget' + localNumCircles1).html('Others Highest Bid: ' + highestBids[localNumCircles1]);
	                    }
	                }
	            }
	   	    });
	        google.maps.event.addListener(circles[localNumCircles1], 'center_changed', function(){
                var thisCoord= {"LAT":circles[localNumCircles1].getCenter().lat(),
                    "LNG":circles[localNumCircles1].getCenter().lng(), "RADIUS":circles[localNumCircles1].getRadius()};
	              highestBids[localNumCircles1] = minimumBid;
	              $('#bidForTarget' + localNumCircles1).html('Others Highest Bid: ' + highestBids[localNumCircles1]);
	  	    	  $('#latlngOfBid' + localNumCircles1).html('Lat:' + circles[localNumCircles1].getCenter().lat().toFixed(3)
	  	    	     + ' Lng:' + circles[localNumCircles1].getCenter().lng().toFixed(3));
	    	      for (var oneOfAll in othersCoordsAndBids){
	                   if (overlapExists(othersCoordsAndBids[oneOfAll],thisCoord)){
	             	    	 if (highestBids[localNumCircles1] < othersCoordsAndBids[oneOfAll].BID){
	             	  	         highestBids[localNumCircles1] = othersCoordsAndBids[oneOfAll].BID;
	  	    	                 $('#bidForTarget' + localNumCircles1).html('Others Highest Bid: ' + highestBids[localNumCircles1]);
	    	      	         }
	    	             }
	    	      }
            });
	    }
	    numCircles += 1;
	}
	function deleteCircle() {
		if (numCircles >= 0){//need global list that updates with local num circles.. to remove these at the appropriate time.
		    deletedCircle = circles.pop();
	            $('#latlngOfBid' + numCircles).remove(); 
	            $('#bidForTarget' + numCircles).remove(); 
	            $('#yourBidForTarget' + numCircles).remove(); 
	            $('#yourBudgetForTarget' + numCircles).remove(); 
	            $('#yourTargetEndDate' + numCircles).remove(); 
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
	        map1[i] = new google.maps.Map(document.getElementById("map-canvas-currentAd" + qids[i]),
	        	    mapOptions);
	        while(true) {
	            if(whichCoord == (i + 2)){//each question does not nececessarily have more circles...but whichcoord is not reset...
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
  <?php if (loginCheck($mysqli) == true): ?>
      <ul id="nav">
      <li>Welcome, <?=htmlspecialchars($_SESSION['email'])?></li>
          <li><a href="./includes/logout.php">[Log out]</a></li>
          <li><a href="Obfuscate1.php">[About]</a></li>
          <li><a href="Obfuscate2.php">[Obfuscate2]</a></li>
      </ul>
      <div class="displayform">
      <h4>Explanation</h4>
      <p>
        First you allocate a budget through paypal or credit card. Then you submit target demographic info along with an ad. After you submit that,
        you select target regions. Then your ad is checked by a live person who then either validates or rejects it 
        as inappropriate. If invalidated, you will receive an email with an explanation. If accepted your ad will appear in the Current Ads section.
        There, you can delete target regions, or add them by clicking Update this ad and then inserting new target regions in the select target regions form.
        When adding, your ad will be re-reviewed for geographic suitability.  After your ad campaign is over or you delete the ad, your ad will be 
        moved to the Past Ads section where you will no longer see target regions on a map but will have access to all the information provided in the Current Ads section.
      </p>
      <h4>Total Budget</h4>
      <p> Your current total unallocated Budget for Ads:</p>
      <p> $<?=htmlspecialchars($companyBudget)?></p>
      <form id="paypal_form" class="paypal" action="payments.php" method="post">
  	    <input type="text" name="itemprice" id="addAccount"/>
        <input name="cmd" type="hidden" value="_xclick" />
        <input name="no_note" type="hidden" value="1" />
        <input name="lc" type="hidden" value="US" />
        <input name="currency_code" type="hidden" value="USD" />
        <input name="bn" type="hidden" value="PP-BuyNowBF:btn_buynow_LG.gif:NonHostedGuest" />
        <input name="first_name" type="hidden" value="Customer's First Name" />
        <input name="last_name" type="hidden" value="Customer's Last Name" />
  	    <input type="hidden" id="companyBudgetTest0" name="companyBudget" value="<?=$companyBudget?>"/>
        <input name="payer_email" type="hidden" value="customer@example.com" />
        <input name="item_number" type="hidden" value="0" />
        <input type="button" value="Add money to account" 
        onclick="return modifyAccountBalance(this.form, document.getElementById('itemprice').value, document.getElementById('companyBudgetTest0').value);"/>
      </form>

      <form method="post" action="" enctype="multipart/form-data">
  	    <input type="hidden" name="companyId" value="<?php echo htmlspecialchars($_SESSION['companyId'])?>"/>
  	    <input type="text" name="itemprice" id="addAccount"/>
  	    <input type="hidden" id="companyBudgetTest0" name="companyBudget" value="<?=$companyBudget?>"/>
        <input type="hidden" name="itemname" value="adCurrency" /> 
        <input type="hidden" name="itemnumber" value="0" /> 
        <input type="hidden" name="itemdesc" value="Currency used to bid on ads inside the poink ecosystem." /> 
        <input type="button" value="Add money to account" 
        onclick="return modifyAccountBalance(this.form, document.getElementById('addAccount').value, document.getElementById('companyBudgetTest0').value);"/>
        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
        <input type="hidden" name="cmd" value="_s-xclick">
        <input type="hidden" name="hosted_button_id" value="9BD9QRJ5LR68G">
        <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
        <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
        </form>

      <p>To add an amount to your account, first type a value into the empty form field, then click Modify account balance by amount.
         Finally after the page is done loading, click the Buy Now button. The minimum payment amount is 20 dollars, in part to offset Paypal's fees.</p>
      </form>
      </div>
    <?php if( $banned === 0 ):?> 
      <div class="displayform" >
      <h4>Submit an Ad with Target Demographics</h4>
      <p>Advert:</p>
      <form id="submitquestion"  action="insertNewQuestion.php?identifier=Advert%20and%20Demographic%20Info" method="post" enctype="multipart/form-data">
  	    <textarea id="questionx" name="question" rows="8" columns="30"/><?=$question?></textarea>
    	<p>Age Range</p> 
    	<input type="text" id="minagex" name="minage" value=<?=$minage?> />
    	to
    	<input type="text" id="maxagex" name="maxage" value=<?=$maxage?> />
        <p>Targeted Gender(s)</p>
    	<select id="genderx" name="gender"> 
    	  <option value="0">All</option>
    	  <option value="1">Male</option>
    	  <option value="2">Female</option>
    	</select>
        <script type="text/javascript"> document.getElementById("genderx").selectedIndex=<?=$gender?>;</script>
  	<input type="hidden" name="companyBudget" value="<?=$companyBudget?>"/>
  	<input type="hidden" name="questionId" value="<?=htmlspecialchars($_SESSION['questionId'])?>"/>
  	<input type="hidden" name="companyId" value="<?=htmlspecialchars($_SESSION['companyId'])?>"/>
  	</br>
  	<p>Target Demographic info will be used when selecting target regions to display competing bids</p>
    <input type="button" value="Submit Demographic Info" onclick="return validateQuestionInsertOrUpdate(this.form,
       document.getElementById('questionx').value, document.getElementById('minagex').value, document.getElementById('maxagex').value);"/>
    </form>
    </br>
    <h4>Select Target Regions</h4>
      <input type="button" onclick="createCircle()" value="Add new target region"/>
      <input type="button" onclick="deleteCircle()" value="Delete most recent region"/>
      <form id="submitquestioncoords"  action="insertNewQuestion.php?identifier=Target%20Regions" method="post" enctype="multipart/form-data">
          <div id="map-canvas" style="height:30em; width:30em;"></div>
  	      <p>Bid on Target Regions for Your Selected Demographics</p>
  	      <div id="highestBidsInTarget"></div>
  	      <input type="hidden" name="questionId" value="<?=htmlspecialchars($_SESSION['questionId'])?>"/>
  	      <input type="hidden" name="companyBudget" value="<?=htmlspecialchars($companyBudget)?>"/>
          <input type="button" value="Submit Full Ad to Database" onclick="return appendCoords(event, this.form, 
            document.getElementsByClassName('bidx'), document.getElementsByClassName('budgetx'),
            document.getElementsByClassName('datepicker'), <?=htmlspecialchars($companyBudget)?>);"/>
          <!--getElementsByClassName doesnt work with some older browsers.-->
          <p>Submit coords only if you are satisfied with everything!</p>
      </form>
      </div>
    <?php endif; ?>
      <div class="displayform" id="alreadylisted">
    <h4>Current Ads</h4>
      <?php
  	    foreach($yourQuestions as $currQuestion):
  	    if ($currQuestion['DELETED'] == 0):
      ?>
  	    <p>Ad: <?=htmlspecialchars($currQuestion['QUESTION'])?></p>
  	    <p>Target Gender(s): <?php if($currQuestion['TARGET_GENDERS'] == 0){ echo "All";}
  	    elseif($currQuestion['TARGET_GENDERS'] === "1"){ echo "Male";}else{ echo "Female";}?></p>
  	    <p>Age Range: <?=htmlspecialchars($currQuestion['MIN_AGE'])?> to <?=htmlspecialchars($currQuestion['MAX_AGE'])?></p>
  	    <p>Target Regions</p>
    	<div id=<?=htmlspecialchars("map-canvas-currentAd".$currQuestion['QUESTION_ID'])?> style="height:20em; width:20em;"></div>
  	    <?php  
  		    $id=0;
  		    foreach($coords[$currQuestion['QUESTION_ID']] as $currCoords):
                if ($currCoords['VALID'] == 1):
  	    ?>
            <p>Lat, Lng: <?=htmlspecialchars($currCoords['LAT'])?>, <?=htmlspecialchars($currCoords['LNG'])?></p>
                   <!--Highest Other Bid: <?=htmlspecialchars($highestBids[$currCoords['QUESTION_COORD_ID']])?>don't need this info..-->
  	        <p>Your Bid: <?=htmlspecialchars($currCoords['BID'])?></p>
  	        <p>Current Budget: <?=htmlspecialchars($currCoords['BUDGET'])?></p>
            <p>Engagement Info</p>
            <p>Times Displayed:<?=htmlspecialchars($currCoords['TIMES_DISPLAYED'])?> Times answered:<?=htmlspecialchars($currCoords['TIMES_ANSWERED'])?></p>
            <p>Answer viewed:<?=htmlspecialchars($currCoords['ANSWER_VIEWS'])?> Answer clickthroughs:<?=htmlspecialchars($currCoords['ANSWER_CLICKTHROUGHS'])?></p>
            <!--This info is for rendering maps-->
  	        <input type="hidden" id="<?=htmlspecialchars("lat".$currQuestion['QUESTION_ID'].$id)?>"
  		        value="<?=htmlspecialchars($coords[$currQuestion['QUESTION_ID']][$id]['LAT'])?>"/>
    	        <input type="hidden" id="<?=htmlspecialchars("lng".$currQuestion['QUESTION_ID'].$id)?>"
  		        value="<?=htmlspecialchars($coords[$currQuestion['QUESTION_ID']][$id]['LNG'])?>"/>
    	        <input type="hidden" id="<?=htmlspecialchars("radius".$currQuestion['QUESTION_ID'].$id)?>"
                value="<?=htmlspecialchars($coords[$currQuestion['QUESTION_ID']][$id]['RADIUS'])?>"/>
  	        <form method="post" action="<?php echo esc_url($_SERVER['PHP_SELF']);?>" enctype="multipart/form-data">
  	        <input type="hidden" value=<?=$currCoords['QUESTION_COORD_ID']?> name="removeQuestionCoordId"/>
  	        <input type="hidden" value=<?=$currCoords['BUDGET']?> name="budget"/>
            <input type="submit" value="Delete this target region."/>
            </form>
          <?php       
                $id+=1;
                endif;
  		    endforeach;
  	    ?> 
    
  	<form method="post" action="<?php echo esc_url($_SERVER['PHP_SELF']);?>" enctype="multipart/form-data">
  	    <input type="hidden" value=<?=$currQuestion['QUESTION_ID']?> name="questionId"/>
  	    <input type="hidden" value=<?=htmlspecialchars($currQuestion['QUESTION'])?> name="question"/>
  	    <input type="hidden" value=<?=$currQuestion['TARGET_GENDERS']?> name="gender"/>
  	    <input type="hidden" value=<?=$currQuestion['MAX_AGE']?> name="maxage"/>
  	    <input type="hidden" value=<?=$currQuestion['MIN_AGE']?> name="minage"/>
  	    </br>
  	    <input type="submit" value="Update this ad"/>
  	</form>
  	<form method="post" action="<?php echo esc_url($_SERVER['PHP_SELF']);?>" enctype="multipart/form-data">
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
  	        if ($currQuestion['DELETED'] > 0)://ord converts from string to ascii value.
      ?>
  	<form method="post" action="<?php echo esc_url($_SERVER['PHP_SELF']);?>" enctype="multipart/form-data">
  	    <p>Ad: <?=htmlspecialchars($currQuestion['QUESTION'])?></p>
  	    <p>Target Gender(s): <?=htmlspecialchars($currQuestion['TARGET_GENDERS'])?></p>
  	    <p>Age Range: <?=htmlspecialchars($currQuestion['MIN_AGE'])?> to <?=htmlspecialchars($currQuestion['MAX_AGE'])?></p>
          <?php
  		    foreach($coords[$currQuestion['QUESTION_ID']] as $currCoords):
          ?>
              <p>Lat, Lng, Radius (meters): <?=htmlspecialchars($currCoords['LAT'])?>, <?=htmlspecialchars($currCoords['LNG'])?>, <?=htmlspecialchars($currCoords['RADIUS'])?>
              <p>Engagement Info</p>
              <p>Times Displayed:<?=htmlspecialchars($currCoords['TIMES_DISPLAYED'])?> Times answered:<?=htmlspecialchars($currCoords['TIMES_ANSWERED'])?></p>
              <p>Answer viewed:<?=htmlspecialchars($currCoords['ANSWER_VIEWS'])?> Answer clickthroughs:<?=htmlspecialchars($currCoords['ANSWER_CLICKTHROUGHS'])?></p>
          <?php       
  		    endforeach;
  	    ?> 
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
