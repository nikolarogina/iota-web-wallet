<?php 
require('config.php');
require_once('plugins/cryptor/cryptor.php'); // Encryption / Decryption class
require_once('plugins/curl/curl.php'); // Curl wrapper class
require_once('plugins/Mobile_Detect.php'); // PHP detect mobile devices
session_start();
$mysql = new mysqli($dbhost,$dbuser,$dbpass,$dbname);
$mysql->query("SET NAMES 'utf8'");
use \Curl\Curl;

function isHTTPS() {
  return
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || $_SERVER['SERVER_PORT'] == 443;
}

function encrypt($string) {
	/* Function to encrypt already encrypted seed (Crypto-JS) before storing it into DB */ 
	global $key;
	$result = Cryptor::Encrypt($string,$key);
	return $result;
}

function decrypt($string) {
	/* Function to decrypt DB string (AES-256) */ 
	global $key;
	$result = Cryptor::Decrypt($string,$key);
	return $result;
}

function sendMail($receiverEmail,$receiverName,$subject,$message,$attachments=null,$protocol='standard',$type='html') {
	require('plugins/PHPMailer/class.phpmailer.php');
	date_default_timezone_set('Europe/Zagreb');
	$mail = new PHPMailer();
	$mail->CharSet = 'UTF-8';
	if ($protocol == 'SMTP') {
		$mail->IsSMTP();
		$mail->SMTPDebug = 0; // 0 = off (for production use), 1 = client messages, 2 = client and server messages
		$mail->SMTPSecure = 'ssl';
		$mail->Debugoutput = 'html';
		$mail->Host	= '';
		$mail->Port	= 465;
		$mail->SMTPAuth = true;
		$mail->Username = '';
		$mail->Password = '';
	}
	$mail->SetFrom('','');
	$mail->AddReplyTo('','');
	$mail->AddAddress($receiverEmail,$receiverName);
	$mail->Subject = $subject;
	$result = array();
	if (is_array($message)) {
		$mail->MsgHTML($message['html']);
		$mail->AltBody = $message['text'];
	}
	else {
		if ($type == 'text') {
			$mail->AltBody = $message;
		}
		if ($type == 'html') {
			$mail->MsgHTML($message);
		}
	}
	if (is_array($attachments)) {
		foreach ($attachments as $attachment) {
			$mail->AddAttachment($attachment);
		}
	}
	else {
		$mail->AddAttachment($attachments);
	}
	if (!$mail->Send()) {
	  $result['status'] = false;
	  $result['errorInfo'] = $mail->ErrorInfo;
	} 
	else {
	  $result['status'] = true;
	}
	return $result;
}

function isLogged() {
	global $mysql;
	if (!empty($_SESSION['email'])) {
		$email = addslashes($_SESSION['email']);
		$password = trim($_SESSION['password']);
		$query = $mysql->query("SELECT password FROM users WHERE email='$email'");
		if ($query->num_rows > 0) {
			$data = $query->fetch_assoc();
			$hashed_password = $data['password'];
			if (password_verify($password,$hashed_password)) {
				return true;
			} 
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}
	else {
		return false;
	}
}

function getPairValue($pair,$type='ask') {
	/* Get current IOT/USD ratio */ 
	$curl = new Curl;
	$ticker = (array)$curl->get("https://api.bitfinex.com/v1/pubticker/$pair");
	return $ticker[$type];
}

function getUser($id) {
	global $mysql;
	$result = $mysql->query("SELECT id,email,hint,pin_type,seed FROM users WHERE id='$id'")->fetch_assoc();
	return $result;
}

function formatBalance($iotas,$precision=6) { 
    $units = array('iota', 'Ki', 'Mi', 'Gi', 'Ti', 'Pi'); 
    $iotas = max($iotas, 0); 
    $pow = floor(($iotas ? log($iotas) : 0) / log(1000)); 
    $pow = min($pow, count($units) - 1); 
    $iotas /= pow(1000, $pow);
    return round($iotas, $precision).' '.$units[$pow]; 
} 

function ifUserExists($email) {
	global $mysql;
	$query = $mysql->query("SELECT id FROM users WHERE email='$email'");
	if ($query->num_rows > 0) {
		return true;
	} 
	else {
		return false;
	}
}

function checkPassword($password) {
	global $mysql;
	$userID = $_SESSION['id'];
	$query = $mysql->query("SELECT password FROM users WHERE id='$userID'")->fetch_assoc();
	if (password_verify($password,$query['password'])) {
		return true;
	}
	else {
		return false;
	}
}

function getSettings() {
	/* Get global website settings, get POW time for desktop / mobile */ 
	global $mysql;
	$settings = $mysql->query("SELECT recaptcha_sitekey,recaptcha_secretkey,g_analytics,avg_pow_time FROM system WHERE id='1'")->fetch_assoc();
	$pow_time_parts = explode('|',$settings['avg_pow_time']);
	$avgPOW_desktop = explode(':',$pow_time_parts[0]);
	$avgPOW_mobile = explode(':',$pow_time_parts[1]);
	$settings['pow-desktop'] = round(($avgPOW_desktop[1] / 60000),2); 
	$settings['pow-mobile'] = round(($avgPOW_mobile[1] / 60000),2);
	return $settings;
}

if (isLogged()) {
	$settings = getSettings();
	if (isset($_GET['confirm'])) {
		$hash = $_GET['confirm'];
		$new_email = decrypt($_GET['confirm']);
		$query = $mysql->query("SELECT id FROM users WHERE hash='$hash'");
		if ($query->num_rows > 0) {
			$mysql->query("UPDATE users SET email='$new_email',hash='-' WHERE hash='$hash'");
			$_SESSION['email'] = $new_email;
			$success = 'Email successfully updated!';
		}
		else {
			$error = 'Invalid confirmation code!';
		}
	}
	if (isset($_POST['new-email'])) {
		if (filter_var($_POST['new-email'],FILTER_VALIDATE_EMAIL)) {
			$email = trim(addslashes($_POST['new-email']));
			if (!ifUserExists($email)) {
				$userID = $_SESSION['id'];
				$security_hash = encrypt($email);
				$query = $mysql->query("UPDATE users SET hash='$security_hash' WHERE id='$userID'");
				if ($query) {
					$subject = 'Confirm your email | IOTA Web Wallet';
					$message = file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/email/confirm-registration.php?file=settings.php&confirm='.$security_hash);
					$mailed = sendMail($email,'User',$subject,$message);
					if ($mailed['status']) {
						$info = 'Email with the verification link has been sent to: <b>'.$email.'</b>. <br />Please open your email account (check <i>spam</i> mail too) and click on confirmation link!';
					}
					else {
						$error = 'An error occurded while trying to save user data. Please try again!<br /> ERROR: '.$mailed['errorInfo'];
					}
				}
				else {
					$error = 'Unable to save new email! Please try again or contact support!';
				}
			} 
		}
		else {
			$error = 'Invalid email address!';
		}
	}
	if (isset($_POST['old-password']) && isset($_POST['new-password'])) {
		if (checkPassword($_POST['old-password'])) {
			if ($_POST['new-password'] == $_POST['new-password2']) {
				$password = trim($_POST['new-password']);
				$hashed_password = password_hash($password,PASSWORD_DEFAULT);
				$userID = $_SESSION['id'];
				$query = $mysql->query("UPDATE users SET password='$hashed_password' WHERE id='$userID'");
				if ($query) {
					$_SESSION['password'] = $password;
					$success = 'Password successfully changed!';
				}
				else {
					$error = 'Unable to save new password! Please try again!';
				}
			}
			else {
				$error = 'New passwords are not the same! Try again!';
			}
		}
		else {
			$error = 'Wrong current password! Please try again!';
		}
	}
	if (!empty($_POST['encrypted-seed'])) {
		$type = $_POST['pin-type'];
		$hint = ucfirst(trim($_POST[$type.'-pin-hint']));
		if (empty($hint)) {
			$hint = '-';
		}
		$seed = encrypt($_POST['encrypted-seed']);
		$userID = $_SESSION['id'];
		$query = $mysql->query("UPDATE users SET pin_type='$type',hint='$hint',seed='$seed' WHERE id='$userID'");
		if ($query) {
			$success = 'You have successfully changed your PIN! <b>From now on always use your new PIN when asked!</b>';
		}
		else {
			$error = 'An error occurred while trying to save new PIN! Try again or contact support!';
		}
	}
	$detect = new Mobile_Detect;
	$iotusd = getPairValue('iotusd','mid');
	$user = getUser($_SESSION['id']);
	if (!empty($_SESSION['accountData'])) {
		$latestValidAddress = end($_SESSION['accountData']['addresses']);
	}
	else {
		$transactions = $latestValidAddress = null;
	}
	if ($detect->isMobile()) {
		$avg_pow = $settings['pow-mobile'];
	}
	else {
		$avg_pow = $settings['pow-desktop'];
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="IOTA web wallet user area">
    <meta name="author" content="Nikola Rogina">
    <title>Settings | IOTA Web Wallet</title>
    <!-- Bootstrap Core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- MetisMenu CSS -->
    <link href="css/metisMenu.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/theme.css" rel="stylesheet">
	<link href="css/loader-default.css" rel="stylesheet">
    <!-- Custom Fonts -->
    <link href="css/font-awesome.min.css" rel="stylesheet" type="text/css">
	<link rel="shortcut icon" type="image/x-icon" sizes="16x16 32x32 48x48" href="img/favicon.ico">
	<?php echo $settings['g_analytics']; ?>
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
	<script src='https://www.google.com/recaptcha/api.js'></script>
	<!-- REQUIRED -->
	<script>
		var encrypted = '<?php echo decrypt($user['seed']); ?>';
		var iotusd = '<?php echo $iotusd; ?>';
	</script>
</head>
<body>
	<div class="loader loader-default"></div>
	<!-- PIN Modal -->
	<div class="modal fade" id="pin" tabindex="-1" role="dialog" aria-labelledby="PINlabel" aria-hidden="true">
		<div class="modal-dialog <?php if ($user['pin_type'] == 'simple') {echo 'small-modal';} else {echo 'medium-modal';} ?>">
			<div class="modal-content text-center">
				<form action="<?php echo $_SERVER['PHP_SELF']; ?>" action="post" id="confirm-pin">
					<div class="modal-header">
						<a href="logout.php" id="modal-logout" class="close" style="display:none;">&times;</a>
						<button type="button" id="modal-close-x" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title text-center" id="PINlabel">PIN</h4>
					</div>
					<div class="modal-body">
						<?php 
							if ($user['pin_type'] == 'simple') {
								echo '<input type="text" id="pin-number" class="form-control text-center" style="width:150px;margin: 0 auto;" autocomplete="off" autofocus />';
							}
							if ($user['pin_type'] == 'advanced') {
								echo '<input type="password" class="form-control text-center" style="display:inline-block;" id="pin-text" autocomplete="off">';
							}
						?>
						<p class="help-block text-center"><a href="javascript:void();" onclick="$(this).parent().html('<?php echo $user['hint']; ?>');">Hint</a></p>
					</div>
					<div class="modal-footer" style="margin-top:-20px;">
						<button type="submit" class="btn btn-primary btn-block">Confirm</button>
						<input type="hidden" id="command" value="account-info" />
					</div>
				</form>
			</div>
			<!-- /.modal-content -->
		</div>
		<!-- /.modal-dialog -->
	</div>
	<!-- Receive Modal -->
	<div class="modal fade" id="receive" tabindex="-1" role="dialog" aria-labelledby="Addresslabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content text-center">
				<div class="modal-header">
					<button type="button" id="modal-close-x" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h4 class="modal-title text-center" id="Addresslabel">Your IOTA Address</h4>
				</div>
				<div class="modal-body">
					<div class="form-group input-group">
						<input type="text" id="iota-address" placeholder="No address, please generate new one..." class="form-control text-center disabled" style="margin: 0 auto;">
						<span class="input-group-btn">
							<button class="btn btn-default copy" type="button" data-clipboard-target="#iota-address" data-toggle="popover" data-placement="bottom" data-content="Copied!"><i class="fa fa-copy"></i></button>
						</span>
					</div>
					<div class="form-group">
						<button type="button" id="generate-address" class="btn btn-success"><i class="fa fa-refresh fa-fw"></i>Generate new address</button>
					</div>
				</div>
				<div class="modal-footer" style="margin-top:-10px;">
					<button type="button" class="btn btn-primary btn-block" data-dismiss="modal">Close</button>
				</div>
			</div>
			<!-- /.modal-content -->
		</div>
		<!-- /.modal-dialog -->
	</div>
	<!-- Donate Modal -->
	<div class="modal fade" id="donations" tabindex="-1" role="dialog" aria-labelledby="Donationlabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="donation-form">
					<div class="modal-header">
						<button type="button" id="modal-close-x" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title text-center" id="Donationlabel">Your donation to IOTA Web Wallet</h4>
					</div>
					<div class="modal-body text-center">
						<div class="alert alert-info text-center">Money collected through donations will be used only to buy new dedicated servers in order to improve and speed up this web wallet!</div>
						<label style="display:inline-block;">Donation: </label>
						<div class="form-group input-group dropdown" style="width:200px;margin:0 auto;" id="donation-amount-div">
							<input type="number" step="any" class="form-control text-center" id="donation-amount" style="margin: 0 auto;">
							<span class="input-group-btn">
								<button class="btn btn-default dropdown-toggle" id="iota-donation-unit-button" type="button" data-toggle="dropdown">Mi <i class="fa fa-caret-down"></i></button>
								<input type="hidden" id="iota-donation-selected-unit" value="Mi" />
								<ul class="dropdown-menu" id="iota-donation-unit" style="width:100px;">
									<li><a href="javascript:void();">iota</a></li>
									<li><a href="javascript:void();">Ki</a></li>
									<li><a href="javascript:void();">Mi</a></li>
									<li><a href="javascript:void();">Gi</a></li>
									<li><a href="javascript:void();">Ti</a></li>
									<li><a href="javascript:void();">Pi</a></li>
								</ul>
							</span>
						</div>
					</div>
					<div class="modal-footer">
						<input type="hidden" id="user-balance" value="<?php echo $_SESSION['accountData']['balance']; ?>" />
						<button type="submit" class="btn btn-primary btn-block"><i class="fa fa-heart fa-fw"></i> Donate <i class="fa fa-heart fa-fw"></i></button>
					</div>
				</form>
			</div>
			<!-- /.modal-content -->
		</div>
		<!-- /.modal-dialog -->
	</div>
	<!-- Canvas for QR code for .pdf -->
	<div id="qrcode" style="display:none;"></div>
	<!-- End QR -->
    <div id="wrapper">
        <!-- Navigation -->
        <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="transactions.php">
					<img src="img/logo-black.png" class="hidden-xs" style="width:28%;margin-top:-10px;" alt="IOTA Web Wallet"/>
					<img src="img/logo-black-no-text.png" class="visible-xs" style="width:28%;margin-top:-10px;" alt="IOTA Web Wallet"/>
				</a>
            </div>
            <!-- /.navbar-header -->	
			<ul class="nav navbar-top-links navbar-right hidden-xs" style="margin-top:7px;">
				<li>
					<button type="button" class="btn btn-primary">1 MIOTA = <?php echo round($iotusd,2); ?> USD</button>
				</li>
			</ul>
            <div class="navbar-default sidebar" role="navigation">
                <div class="sidebar-nav navbar-collapse collapse">
                    <ul class="nav" id="side-menu">
                        <li class="sidebar-search">
                            <a href="javascript:void();" class="text-center" style="color:purple;font-size:1.15em;"><i class="fa fa-money fa-fw"></i> Balance: <span id="balance">0 iota</span></a>
                        </li>
						<li>
							<button type="button" class="btn btn-success btn-block" id="refresh-account" style="border-radius:0px;"><i class="fa fa-refresh fa-fw"></i> Refresh</button>
						</li>
                        <li>
                            <a href="transactions.php"><i class="fa fa-bar-chart-o fa-fw"></i> Transactions</a>
                        </li>
                        <li>
                            <a href="settings.php"><i class="fa fa-cogs fa-fw"></i> Settings</a>
                        </li>
                        <li>
                            <a href="support.php"><i class="fa fa-support fa-fw"></i> Contact support</a>
                        </li>
						<li>
                            <a href="logout.php"><i class="fa fa-sign-out fa-fw"></i> Logout</a>
                        </li>
						<li style="border-bottom:0px;">
							<button type="button" class="btn btn-info btn-block" style="text-align:left;border-radius:0px;" data-toggle="modal" data-target="#donations" data-backdrop="static" data-keyboard="false"><i class="fa fa-heart fa-fw"></i> Donations</button>
						</li>
						<li>
							<button type="button" class="btn btn-primary btn-block" style="text-align:left;border-radius:0px;" onclick="downloadSeed();"><i class="fa fa-file-pdf-o fa-fw"></i> Download SEED</button>
                        </li>
                    </ul>
                </div>
                <!-- /.sidebar-collapse -->
            </div>
            <!-- /.navbar-static-side -->
        </nav>
        <!-- Page Content -->
        <div id="page-wrapper">
                <div class="row">
                    <div class="col-lg-12">
						<h1 class="page-header">
							Settings
							<div class="btn-group pull-right">
								<a href="send.php" class="btn btn-primary">Send</a>
								<button type="button" data-toggle="modal" data-target="#receive" data-backdrop="static" data-keyboard="false" class="btn btn-success">Receive</button>
							</div>
						</h1>
					</div>
				</div>
				<div class="row">
					<?php 
						if (isset($success)) {
							echo '<div class="alert alert-success text-center" style="margin:0 10px 15px 10px;">'.$success.'</div>';
						}
						if (isset($info)) {
							echo '<div class="alert alert-info text-center" style="margin:0 10px 15px 10px;">'.$info.'</div>';
						}
						if (isset($warning)) {
							echo '<div class="alert alert-warning text-center" style="margin:0 10px 15px 10px;"">'.$warning.'</div>';
						}
						if (isset($error)) {
							echo '<div class="alert alert-danger text-center" style="margin:0 10px 15px 10px;"">'.$error.'</div>';
						}
					?>
					<div class="col-lg-7">
						<div class="panel panel-primary">
							<div class="panel-heading">
								Change email
							</div>
							<div class="panel-body">
								<div class="row">
									<div class="col-lg-12">
										<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="change-email-form">
											<div class="form-group">
												<label>Current email</label>
												<input type="email" class="form-control" value="<?php echo $user['email']; ?>" disabled>
											</div>
											<div class="form-group" id="new-email-div">
												<label>New email</label>
												<input type="email" name="new-email" id="new-email" class="form-control" placeholder="Enter your new email" required>
												<p class="help-block" style="display:none;"></p>
											</div>
											<button type="submit" name="change-email" class="btn btn-primary">Change email</button>
										</form>
									</div>
								</div>
							</div>
						</div>
						<div class="panel panel-primary">
							<div class="panel-heading">
								Change password
							</div>
							<div class="panel-body">
								<div class="row">
									<div class="col-lg-12">
										<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="change-password-form">
											<div class="form-group" id="old-password-div">
												<label>Current password</label>
												<input type="password" name="old-password" id="old-password" class="form-control" placeholder="Enter your current password" required />
												<p class="help-block" style="display:none;">Minimum 8 caracthers!</p>
											</div>
											<div class="form-group" id="new-password-div">
												<label>New password</label>
												<input type="password"  name="new-password" class="form-control" id="new-password" placeholder="Enter your new password" required />
												<p class="help-block" style="display:none;">Minimum 8 caracthers!</p>
											</div>
											<div class="form-group" id="new-password2-div">
												<label>Confirm new password</label>
												<input type="password"  name="new-password2" class="form-control" id="new-password2" placeholder="Confirm your new password" required />
												<p class="help-block" style="display:none;">Passwords do not match!</p>
											</div>
											<button type="submit" name="change-password" class="btn btn-primary">Change password</button>
										</form>
									</div>
								</div>
							</div>
						</div>
						<div class="panel panel-primary">
							<div class="panel-heading">
								Change PIN
							</div>
							<div class="panel-body">
								<div class="row">
									<div class="col-lg-12">
										<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="change-pin-form">
											<div class="form-group text-center" id="pin-options">
												<button type="button" class="btn btn-outline btn-primary" id="simple-pin-button">Simple PIN</button>
												<button type="button" class="btn btn-outline btn-success" id="advanced-pin-button">Advanced PIN</button>
											</div>
											<div id="simple-pin" style="display:none;">
												<div class="form-group text-center">
													<label>New PIN (4 numbers)</label>
													<span id="new-simple-pin-div">
														<input type="text" class="form-control text-center" style="width:150px;margin:0 auto;" id="simple-pin-number" autocomplete="off" />
													</span>
													<input type="text" class="form-control text-center" style="width:250px;margin:10px auto;" placeholder="PIN hint (Optional)" name="simple-pin-hint" autocomplete="off">
													<p class="help-block" style="font-weight:700;color:red;">This will be your new PIN. If you forget it, you won't be able to access your account!</p>
												</div>
												<button type="button" class="btn btn-outline btn-success pull-right" id="switch-to-advanced">Switch to advanced PIN</button>
											</div>
											<div id="advanced-pin" class="text-center" style="display:none;">
												<label>New PIN (8-32 characters)</label>
												<div class="form-group">
													<span id="new-avanced-pin-div">
														<input type="text" class="form-control text-center" style="display:inline-block;" id="advanced-pin-text" autocomplete="off" />
													</span>
													<input type="text" class="form-control text-center" style="width:250px;margin:10px auto;" placeholder="PIN hint (Optional)" name="advanced-pin-hint" autocomplete="off">
													<p class="help-block" style="font-weight:700;color:red;">This will be your new PIN. If you forget it, you won't be able to access your account!</p>
												</div>
												<button type="button" class="btn btn-outline btn-success pull-right" id="switch-to-simple">Switch to simple PIN</button>
											</div>
											<input type="hidden" id="pin-type" name="pin-type" />
											<input type="hidden" id="encrypted-seed" name="encrypted-seed" />
											<button type="submit" style="display:none;" name="change-pin" class="btn btn-primary pull-left">Change PIN</button>
										</form>
									</div>
								</div>
							</div>
						</div>
						<button type="button" name="change-pin" class="btn btn-danger btn-block" style="margin-bottom:20px;">Close account</button>
					</div>
					<!-- /.col-lg-7 -->
					<div class="col-lg-5">
						<div class="panel panel-info">
							<div class="panel-heading">
								About security
							</div>
							<div class="panel-body">
								<h4>Seed encryption</h4>
								<p>
									Your SEED is encrypted with your PIN number - <b>and only YOU know your PIN</b>.<br />
									We store your SEED in database in double-encrypted form - that means your seed is:
									<ol>
										<li>Encrypted with your PIN</li>
										<li>Already encrypted SEED is then aditionally encrypted whith another password from our side</li>
									</ol>
									Why do we do this double encryption?<br />
									In case of a someone manages to steal user database, some users may use simple PIN (4 numbers). That can be easily cracked using brute-force attacks.
									So, in order to protect thoose accounts, every already encrypted SEED stored in database is encrypted using 64-character key.<br />
									<span style="font-weight:700;text-align:center;">Thats why if someone manages to steal user database, he would need around 10^6 years using <a href="https://en.wikipedia.org/wiki/Tianhe-2" target="_blank">worlds strongest supercomputer</a> to crack every single SEED.</span><br /><br />
									We use standard <b>AES-256-CTR</b> encryption.
								</p>
								<hr />
								<h4>PIN security</h4>
								<p>
									PIN made of 4 numbers has 10,000 combinations. In case that attacker somehow manages to access .php file with the password to decrypt the database, this PIN can be easily cracked. <br />
								</p>
								<p style="text-align:center;font-weight:700;">
									In order to totally secure your SEED, please switch to "advanced PIN" = 8-32 letters / numbers / special characters.<br /><span style="font-style:italic;color:red;">In that case, not even that highly unrealistic security breach would jeopardise your account and your funds!</span>
								</p>
							</div>
						</div>
					</div>
				</div>
				<!-- /.col-lg-12 -->
				<div class="row">
					<div class="col-lg-12">
						<footer class="text-center">
							<p>&copy; <?php echo date("Y"); ?> - Author: <a href="mailto:admin@iota.hr">Nikola Rogina</a> | <a href="javascript:void();" title="Click to check node status" onclick="showNodeInfo();">Node status</a> | Average PoW time: <b><?php echo $avg_pow; ?></b> min | <?php echo $_SERVER[HTTP_HOST]; ?></p>
						  </footer>
					</div>
				</div>
			</div>
			<!-- /.row -->
        </div>
        <!-- /#page-wrapper -->
    </div>
    <!-- /#wrapper -->
    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<script>window.jQuery || document.write('<script src="js/jquery.min.js"><\/script>')</script>
	<!-- AES encryption algorithm -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.2/rollups/aes.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.2/rollups/pbkdf2.js"></script>
	<!-- JS Clipboard -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.7.1/clipboard.min.js"></script>
	<!-- IOTA.js Library & Helper functions -->
    <script src="js/iota.min.js"></script>
	<script src="js/curl.min.js"></script>
	<script src="js/iota.helper.php" type="text/javascript"></script>
    <!-- Bootstrap Core JavaScript -->
    <script src="js/bootstrap.min.js"></script>
	<!-- Bootstrap Modal Wrapper JavaScript -->
    <script src="js/bootstrap.model.wrapper.min.js"></script>
	<!-- Moment.js Plugin JavaScript -->
    <script src="js/moment.min.js"></script>
    <!-- Metis Menu Plugin JavaScript -->
    <script src="js/metisMenu.min.js"></script>
	<!-- Masked Input -->
	<script src="js/jquery.maskedinput.min.js"></script>
	<!-- Javascript PDF & QR Code -->
	<script src="js/jspdf.min.js"></script>
	<script src="js/qrcode.min.js"></script>
    <!-- Custom Theme JavaScript -->
    <script src="js/theme.js"></script>	
	<!-- Custom JavaScript -->
    <script src="js/custom.js"></script>
	<script>	
		function validateEmail(email) {
			if (email.length > 5) {
				var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
				return emailReg.test(email);
			}
			else {
				return false;
			}
		}
		$(function() {
			<?php if ($_GET['donation'] == 'true') {echo 'thankYou();';} ?>
			$('#simple-pin-button').click(function() {
				$('#pin-options').fadeOut(function(){
					$('#change-pin-form').find(':submit').show();
					$('#simple-pin').show();
					$('#pin-type').val('simple');
				});
			});
			$('#advanced-pin-button').click(function() {
				$('#pin-options').fadeOut(function(){
					$('#change-pin-form').find(':submit').show();
					$('#advanced-pin').show();
					$('#pin-type').val('advanced');
				});
			});
			$('#switch-to-advanced').click(function() {
				$('#simple-pin').fadeOut(function(){
					$('#advanced-pin').show();
					$('#pin-type').val('advanced');
				});
			});
			$('#switch-to-simple').click(function() {
				$('#advanced-pin').fadeOut(function(){
					$('#simple-pin').show();
					$('#pin-type').val('simple');
				});
			});
			if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
				$("#simple-pin-number").attr({"type":"number"});
				$("#simple-pin-number").keydown(function(e){
					if ($(this).val().length >= 4) { 
						$(this).val($(this).val().substr(0, 4));
					}
				});
				$("#simple-pin-number").keyup(function(e){
					if ($(this).val().length >= 4) { 
						$(this).val($(this).val().substr(0, 4));
					}
				});
			}
			else {
				$("#simple-pin-number").mask("9 9 9 9");
			}
			$("#advanced-pin-text").keydown(function(e){
				if ($(this).val().length >= 32) { 
					$(this).val($(this).val().substr(0,32));
				}
			});
			$("#advanced-pin-text").keyup(function(e){
				if ($(this).val().length >= 32) { 
					$(this).val($(this).val().substr(0,32));
				}
			});
			$('#new-email').change(function() {
				if (!validateEmail($(this).val())) {
					$('#new-email-div').addClass('has-error');
					$('#new-email-div').find('p').html('Please enter a valid email!').show();
				}
				else {
					$.post('ajax.check-email.php',{email:$(this).val()},function(response) {
						if (response == '0') {
							$('#new-email-div').addClass('has-error');
							$('#new-email-div').find('p').html('Email aready in use!').show();
						}
					});
					$('#new-email-div').removeClass('has-error').addClass('has-success');
					$('#new-email-div').find('p').hide();
				}
			});
			$('#change-email-form').submit(function(e) {
				e.preventDefault();
				if (validateEmail($('#new-email').val())) {
					$.post('ajax.check-email.php',{email:$('#new-email').val()},function(response) {
						if (response == '1') {
							$('#command').val('change-email');
							$('#pin').modal({
								backdrop: 'static',
								keyboard: false
							});
						}
					});
				}
			});
			$('#old-password').change(function() {
				if ($(this).val().length < 8) {
					$('#old-password-div').addClass('has-error');
					$('#old-password-div').find('p').show();
				}
				else {
					$('#old-password-div').removeClass('has-error').addClass('has-success');
					$('#old-password-div').find('p').hide();
				}
			});
			$('#new-password').change(function() {
				if ($(this).val().length < 8) {
					$('#new-password-div').addClass('has-error');
					$('#new-password-div').find('p').show();
				}
				else {
					$('#new-password-div').removeClass('has-error').addClass('has-success');
					$('#new-password-div').find('p').hide();
				}
			});
			$('#new-password2').change(function() {
				if ($(this).val() !== $('#new-password').val()) {
					$('#new-password2-div').addClass('has-error');
					$('#new-password2-div').find('p').show();
				}
				else {
					$('#new-password2-div').removeClass('has-error').addClass('has-success');
					$('#new-password2-div').find('p').hide();
				}
			});
			$('#change-password-form').submit(function(e) {
				e.preventDefault();
				if ($('#new-password').val().length > 7 && $('#new-password2').val() === $('#new-password').val()) {
					$('#command').val('change-password');
					$('#pin').modal({
						backdrop: 'static',
						keyboard: false
					});
				}
			});
			$('#simple-pin-number').change(function() {
				var simple_pin = $(this).val().replace(/\s+/g,'');
				if (/^[0-9]{4}$/.test(simple_pin)) {
					$('#new-simple-pin-div').removeClass('has-error').addClass('has-success');
				}
				else {
					$('#new-simple-pin-div').addClass('has-error');
				}
			});
			$('#advanced-pin-text').change(function() {
				var advanced_pin_text = $(this).val().replace(/\s+/g,'');
				if (advanced_pin_text.length >= 8) {
					$('#new-avanced-pin-div').removeClass('has-error').addClass('has-success');
				}
				else {
					$('#new-avanced-pin-div').addClass('has-error');
				}
			});
			$('#change-pin-form').submit(function(e) {
				e.preventDefault();
				if ($('#pin-type').val() == 'simple') {
					if (/^[0-9]{4}$/.test($('#simple-pin-number').val().replace(/\s+/g,''))) {
						$('#command').val('change-pin');
						$('#pin').modal({
							backdrop: 'static',
							keyboard: false
						});
					}
					else {
						$('#new-simple-pin-div').addClass('has-error');
					}
				}
				if ($('#pin-type').val() == 'advanced') {
					if ($('#advanced-pin-text').val().replace(/\s+/g,'').length >= 8) {
						$('#command').val('change-pin');
						$('#pin').modal({
							backdrop: 'static',
							keyboard: false
						});
					}
					else {
						$('#new-avanced-pin-div').addClass('has-error');
					}
				}
			});
			<?php if (!empty($_SESSION['accountData'])) {$account = 'true';} else {$account = 'false';} ?>
			var clipboard = new Clipboard('.copy');
			clipboard.on('success', function(e) {
				$('[data-toggle="popover"]').popover('show');
				setTimeout(function() {
					$('[data-toggle="popover"]').popover('hide');
				},4000);
			});
			var accountData = <?php echo $account; ?>;
			if (accountData) {
				$('#balance').html('<?php echo formatBalance($_SESSION['accountData']['balance']); ?> <br />(&asymp; <?php echo number_format(((float)$_SESSION['accountData']['balance'] / 1000000) * $iotusd, 2, '.', ''); ?> $)');
				<?php if (!empty($latestValidAddress)) {
					if (strlen($latestValidAddress) == 81) {
						echo "$('#iota-address').val(iota.utils.addChecksum('$latestValidAddress'));";
					}
					else {
						echo "$('#iota-address').val('$latestValidAddress');";
					}
				} ?>		
				$('#refresh-account').click(function() {
					$('#command').val('account-info');
					$('#pin').modal({
						backdrop: 'static',
						keyboard: false
					});
				});
				$('#generate-address').click(function() {
					$('#command').val('generate-address');
					$('#receive').modal('toggle');
					$('#pin').modal({
						backdrop: 'static',
						keyboard: false
					});
				});
			}
			else {
				$('#modal-close-x').hide();
				$('#modal-logout').show();
				$('#pin').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
		});
	</script>
</body>
</html>
<?php } else {
	$redirect = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	header("Location: index.php?redirect=$redirect");
} ?>