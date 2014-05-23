<?php
include_once 'psl-config.php';   
try {
    $mysqli = new mysqli(HOST, USER, PASSWORD, DATABASE);
    $database = new PDO('mysql:='.HOST.';dbname='.DATABASE.';charset=utf8', USER, PASSWORD, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => true
    ));
} catch(PDOException $e) {
    error_log("{$e->getFile()}:{$e->getLine()}: PDO open failed: {$e->getCode()}: {$e->getMessage()}");
    header("HTTP/1.1 500 Internal Server Error");
    echo '<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
  <head>
    <title>Internal Server Error</title>
    <meta charset="utf-8" />
  </head>
  <body>
    <h1>Internal Server Error</h1>
    <p>Sorry, this Web site has encountered an unexpected condition, and is currently unable to respond to your request.</p>
    <p>Please retry later.</p>
    <p><small></small></p>
  </body>
</html>
';
    exit(1);
}
?>
