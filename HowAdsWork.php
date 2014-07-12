<?php
include_once './includes/db_connect.php';
include_once './includes/functions.php';
sec_session_start();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
  <head>
    <title>How Ads Work</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="mystyle.css"/>
  </head>
        <div id="header" class="sitename">
            <h1>
		How Ads Work
            </h1>
        </div>

<?php if( loginCheck($mysqli) == true): ?>
    <ul id="nav">
        <li>Welcome, <?=htmlspecialchars($_SESSION['username'])?></li>
        <li><a href="index.php">[Log out]</a></li>
        <li><a href="AboutPoink.php">[About Poink]</a></li>
        <li><a href="YourAccount.php">[Your Account]</a></li>
    </ul>
<?php else: ?>
    <ul id="nav">
        <li><a href="index.php">[Welcome Page]</a></li>
        <li><a href="Login.php">[Login]</a></li>
        <li><a href="register.php">[Register]</a></li>
        <li><a href="AboutPoink.php">[About Poink]</a></li>
    </ul>
<?php endif;?>
    <div class="displayform">
    <p>Here's how the system works:</p>
    <p>First you register</p>
    <p>You tell us how much you've budgeted to spend on Poink advertising, and hook us up with whatever bank/credit card/paypal account you choose to use</p>
    <p>Then you submit a question/text ad to be evaluated by one of our team members for appropriateness</p>
    <p>While you're doing that, you tell us how much and for which demographic groups you're willing to pay</p>
    <p>Our database maintains a queue, and if you're willing to pay more than other people for an ad in a given area, you're moved to the front of the line</p>
    <p>There's a minimum price of $.05 per ad </p>
    
    <p>Users of the Poink app see your questions whenever they're exposed to new questions. They'll only see any given ad/question once, however. If they like your product they might answer the question and talk about it with friends or even random strangers. We log that data and provide you with the engagement rates for any particular question. That is, the percentage of users who choose to answer it, as well as the number of times the question/answer pair was shared with other users.</p>
    <!--Consider showing them a sample of the answers to the questions-->
    
    <p> FAQ </p>
    <p>What happens if my check bounces?</p>
    <p>Our system stops serving your ads. Also, your account will be disabled for 2 months. Might need better fraud protection than this...</p>
    <p>When am I charged exactly?</p>
    <p>It depends. For payment system that take a percentage fee, you are charged every time a question is submitted to a user. For flat rate payment systems, our servers check every 24 hours to see how many ads we've served for you and at what rate, then match that against a minimum amount necessary for a given fee and charge you if the condition holds. Any fees are included/counted toward the overall amount of credit you've allocated for Poink by our system.</p>
    <p>What happens after my budget for advertsing runs out?</p>
    <p>Our system stops serving your ads.</p>
    <p>Are your servers secure? </p>
    <p>Security is one of our top priorities. No server hooked up to the internet is perfectly secure. Hypothetically one day we'll be licensed. Passwords and sensitive numbers are encrypted, and we try to work through third party payment systems like paypal as much as possible to maximize security.</p>
    </div>
