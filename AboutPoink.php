<?php
include_once './includes/db_connect.php';
include_once './includes/functions.php';
sec_session_start();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
  <head>
    <title>About Poink</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="mystyle.css"/>
  </head>
        <div id="header" class="sitename">
	    <h1>
		About Poink
            </h1>
        </div>
<?php if( loginCheck($mysqli) == true): ?>
    <ul id="nav">
        <li>Welcome, <?=htmlspecialchars($_SESSION['username'])?></li>
        <li><a href="index.php">[Log out]</a></li>
        <li><a href="HowAdsWork.php">[How Ads Work]</a></li>
        <li><a href="YourAccount.php">[Your Account]</a></li>
    </ul>
<?php else: ?>
    <ul id="nav">
        <li><a href="index.php">[Welcome Page]</a></li>
        <li><a href="Login.php">[Login]</a></li>
        <li><a href="register.php">[Register]</a></li>
        <li><a href="HowAdsWork.php">[How Ads Work]</a></li>
    </ul>
<?php endif;?>

    Poink youtube video animation thing goes here showing how it works

