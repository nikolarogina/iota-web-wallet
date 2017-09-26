<?php
function clearCookies() {
	if (isset($_SERVER['HTTP_COOKIE'])) {
		$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
		foreach($cookies as $cookie) {
			$parts = explode('=', $cookie);
			$name = trim($parts[0]);
			setcookie($name, '', time()-1000);
			setcookie($name, '', time()-1000, '/');
		}
	}
}

session_start();
session_destroy();
clearCookies();
?>
<html>
<head>
<title>Logout...</title>
<script type="text/javascript">
	sessionStorage.clear();
	<?php
		if (isset($_REQUEST['redirect'])) {
			echo "window.location.href = '".$_REQUEST['redirect']."';";
		}
		else {
			$home = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
			echo "window.location.href = '$home';";
		}
	?>
</script>
</head>
<body>
</body>
</html>