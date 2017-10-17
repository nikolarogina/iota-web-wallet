<?php
require('config.php');
session_start();
$mysql = new mysqli($dbhost,$dbuser,$dbpass,$dbname);
$mysql->query("SET NAMES 'utf8'");

function elapsedTime($time1,$precision=1) {
	$time2 = time();
	if (!is_int($time1)) {
		$time1 = strtotime($time1);
	}
	if (!is_int($time2)) {
		$time2 = strtotime($time2);
	}
	if ($time1 > $time2) {
		$ttime = $time1;
		$time1 = $time2;
		$time2 = $ttime;
	}
	if ($time1 == $time2) {
		return 'Right now';
	}
	$intervals = array('year','month','day','hour','minute','second');
	$diffs = array();
	foreach ($intervals as $interval) {
		$ttime = strtotime('+1 '.$interval,$time1);
		$add = 1;
		$looped = 0;
		while ($time2 >= $ttime) {
			$add++;
			$ttime = strtotime("+".$add." ".$interval,$time1);
			$looped++;
		}
		$time1 = strtotime("+".$looped." ".$interval,$time1);
		$diffs[$interval] = $looped;
	}
	$count = 0;
	$times = array();
	foreach ($diffs as $interval => $value) {
		if ($count >= $precision) {
			break;
		}
		if ($value > 0) {
			if ($value != 1) {
			  $interval .= "s";
			}
			$times[] = $value." ".$interval;
			$count++;
		}
	}
	return implode(", ", $times)." ago";
}

$ticketID = addslashes($_POST['id']);
if (!empty($_POST['id'])) {
	$query = $mysql->query("SELECT id,sender,title,message,created,status FROM tickets WHERE id LIKE '$ticketID%' ORDER BY created ASC");
	if ($query->num_rows > 0) {
		$ticket = array();
		$i = 1;
		while ($row = $query->fetch_assoc()) {
			$ticket['chat'][$i]['id'] = $row['id'];
			$ticket['chat'][$i]['sender'] = $row['sender'];
			$ticket['chat'][$i]['message'] = $row['message'];
			$ticket['chat'][$i]['time'] = elapsedTime($row['created']);
			$ticket['chat'][$i]['active'] = $row['status'];
			$ticket['title'] = $row['title'];
			$ticket['status'] = $row['status'];
			$i++;
		}
		$ticket['error'] = false;
	}
	else {
		$ticket['error'] = 'Ticket not found!';
	}
	echo json_encode($ticket);
}
$mysql->close();
?>