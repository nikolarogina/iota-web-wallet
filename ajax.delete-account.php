<?php
require('config.php');
session_start();
$mysql = new mysqli($dbhost,$dbuser,$dbpass,$dbname);
$mysql->query("SET NAMES 'utf8'");

$userID = $_SESSION['id'];
if ($_POST['confirm']) {
	$query = $mysql->query("DELETE FROM users WHERE id='$userID'");
}

if ($query) {echo 1;} else {echo 0;}

$mysql->close();
?>