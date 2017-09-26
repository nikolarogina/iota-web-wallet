<?php
require('config.php');
session_start();
$_SESSION[$_POST['key']] = $_POST['value'];
?>