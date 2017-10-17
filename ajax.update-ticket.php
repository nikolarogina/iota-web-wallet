<?php
require('config.php');
session_start();
$mysql = new mysqli($dbhost,$dbuser,$dbpass,$dbname);
$mysql->query("SET NAMES 'utf8'");

function timestamp($zone="+1") {
	$sign = substr($zone,0,1);
	$h = substr($zone,1);
	$dst = true;
	if ($dst == true) {
		$daylight_saving = date('I');
		if ($daylight_saving) {
			if ($sign == "-") {$h = $h-1;} else {$h = $h+1;}
		}
	}
	$hm = $h * 60;
	$ms = $hm * 60;
	if ($sign == "-") {$timestamp = time() - ($ms);} else {$timestamp = time() + ($ms);}
	$result = gmdate("Y-m-d H:i:s", $timestamp);
	return $result;
}

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

function nextResponseNum($id) {
	global $mysql;
	return (int)$mysql->query("SELECT id FROM tickets WHERE id LIKE '$id%' AND status='1'")->num_rows + 1;
}

if (!empty($_POST['ticketID']) && !empty($_POST['message'])) {
	$json = array();
	$ticketID = addslashes($_POST['ticketID']);
	$message = addslashes(ucfirst(trim($_POST['message'])));
	$query = $mysql->query("SELECT id,sender,title FROM tickets WHERE id='$ticketID' AND status='1'");
	if ($query->num_rows > 0) {
		$ticket = $query->fetch_assoc();
		$responseID = $ticketID.'-'.nextResponseNum($ticketID);
		$userID = $_SESSION['id'];
		$name = $ticket['sender'];
		$title = $ticket['title'];
		$created = timestamp();
		$query = $mysql->query("INSERT INTO tickets (id,sender,title,message,user,created,status) VALUES ('$responseID','$name','$title','$message','$userID','$created','1')");
		if ($query) {
			$json['status'] = 1;
			$json['sender'] = $name;
			$json['message'] = $message;
			$json['time'] = elapsedTime($created);
			$json['error'] = false;
		}
		else {
			$json['status'] = 0;
			$json['error'] = $mysql->error;
		}
	}
	else {
		$json['status'] = 0;
		$json['error'] = 'Ticket not found!';
	}
	echo json_encode($json);
}
$mysql->close();
?>