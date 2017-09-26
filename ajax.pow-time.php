<?php
require('config.php');
$mysql = new mysqli($dbhost,$dbuser,$dbpass,$dbname);
$mysql->query("SET NAMES 'utf8'");

function calcAvgPOWtime() {
	global $mysql;
	$result = array();
	$d = $dt = $m = $mt = 0;
	$query = $mysql->query("SELECT time,device FROM pow_time");
	while ($row = $query->fetch_assoc()) {
		if ($row['device'] == 'desktop') {
			$dt += $row['time'];
			$d++;
		}
		if ($row['device'] == 'mobile') {
			$mt += $row['time'];
			$m++;
		}
	}
	$result = 'desktop:'.round($dt / $d).'|mobile:'.round($mt / $m);
	return $result;
}

$time = $_POST['time'];
$device = $_POST['device'];

$query = $mysql->query("INSERT INTO pow_time (time,device) VALUES ('$time','$device')");
if ($query) {
	$avgPOW = calcAvgPOWtime();
	$query = $mysql->query("UPDATE system SET avg_pow_time='$avgPOW' WHERE id='1'");
}
?>