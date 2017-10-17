<?php
require('config.php');
session_start();
$_SESSION[$_POST['key']] = json_decode(stripslashes($_POST['value']),true);
?>