<?php
require('config.php');
session_start();
$mysql = new mysqli($dbhost,$dbuser,$dbpass,$dbname);
$mysql->query("SET NAMES 'utf8'");

$email = trim(addslashes($_POST['email']));
$query = $mysql->query("SELECT id FROM users WHERE email='$email'");

if ($query->num_rows < 1 && filter_var($email,FILTER_VALIDATE_EMAIL)) {echo 1;} else {echo 0;}

$mysql->close();
?>