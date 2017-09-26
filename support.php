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

function decrypt($string) {
	/* Function to decrypt DB string (AES-256) */ 
	global $key;
	$result = Cryptor::Decrypt($string,$key);
	return $result;
}

function getPairValue($pair,$type='ask') {
	/* Get current IOT/USD ratio */ 
	$curl = new Curl;
	$ticker = (array)$curl->get("https://api.bitfinex.com/v1/pubticker/$pair");
	return $ticker[$type];
}

function createID($length=8) {
	$alphanumeric = 'ABCDEFGHIJKLMNOPRSTUVXYZabcdefghijklmnoprstuvxyz0123456789';
	$b = substr(str_shuffle($alphanumeric.time()),mt_rand(2,10),23);
	$c = str_shuffle(uniqid($alphanumeric).time());
	$d = md5(time().str_shuffle($alphanumeric));
	return substr(str_shuffle($b.$c.$d),mt_rand(4,19),$length);
}

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

function isLogged() {
	global $mysql;
	if ($_COOKIE['login-type'] == 'email') {
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
	if ($_COOKIE['login-type'] == 'seed') {
		return true;
	}
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
	if (isset($_POST['title']) && isset($_POST['message'])) {
		$secretKey = $settings['recaptcha_secretkey'];
		$recaptcha = $_POST['g-recaptcha-response'];
		$verify = json_decode(file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$recaptcha"));
		if ($verify->success) {
			$id = createID(10);
			$title = addslashes(ucfirst(trim(strip_tags($_POST['title']))));
			$message = nl2br(addslashes(ucfirst(trim(strip_tags($_POST['message'])))));
			if ($_COOKIE['login-type'] == 'email') {
				$userID = $_SESSION['id'];
			}
			else {
				$userID = '-';
			}
			$created = timestamp();
			$query = $mysql->query("INSERT INTO tickets (id,title,message,user,created) VALUES ('$id','$title','$message','$userID','$created')");
			if ($query) {
				$success = 'We have received your message. We will try to answer within 24 hours!';
			}
			else {
				$error = 'An error ocurred while trying to send message. Please try again!';
			}
		}
		else {
			$error = 'Anti-robot verification not passed. Please try again!';
		}
	}
	$detect = new Mobile_Detect;
	$iotusd = getPairValue('iotusd','mid');
	if (!empty($_SESSION['accountData'])) {
		$latestValidAddress = $_SESSION['latestValidAddress'];
	}
	else {
		$transactions = $latestValidAddress = null;
	}
	if ($_COOKIE['login-type'] == 'email') {
		$user = getUser($_SESSION['id']);
		$seed = "'".decrypt($user['seed'])."'";
		$pin_type = $user['pin_type'];
		$pin_hint = $user['hint'];
	}
	if ($_COOKIE['login-type'] == 'seed') {
		$seed = 'sessionStorage.getItem("seed")';
		if (!empty($_COOKIE['pin-hint'])) {
			$pin_hint = $_COOKIE['pin-hint'];
		}
		else {
			$pin_hint = '-';
		}
		$pin_type = 'simple';
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
    <title>Support | IOTA Web Wallet</title>
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
		var encrypted = <?php echo $seed; ?>;
		var iotusd = '<?php echo $iotusd; ?>';
	</script>
</head>
<body>
	<div class="loader loader-default"></div>
	<!-- PIN Modal -->
	<div class="modal fade" id="pin" tabindex="-1" role="dialog" aria-labelledby="PINlabel" aria-hidden="true">
		<div class="modal-dialog <?php if ($pin_type == 'simple') {echo 'small-modal';} else {echo 'medium-modal';} ?>">
			<div class="modal-content text-center">
				<form action="<?php echo $_SERVER['PHP_SELF']; ?>" action="post" id="confirm-pin">
					<div class="modal-header">
						<a href="logout.php" id="modal-logout" class="close" style="display:none;">&times;</a>
						<button type="button" id="modal-close-x" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title text-center" id="PINlabel">PIN</h4>
					</div>
					<div class="modal-body">
						<?php 
							if ($pin_type == 'simple') {
								echo '<input type="text" id="pin-number" class="form-control text-center" style="width:150px;margin: 0 auto;" autocomplete="off" autofocus />';
							}
							if ($pin_type == 'advanced') {
								echo '<input type="password" class="form-control text-center" style="display:inline-block;" id="pin-text" autocomplete="off">';
							}
						?>
						<p class="help-block text-center"><a href="javascript:void();" onclick="$(this).parent().html('<?php echo $pin_hint; ?>');">Hint</a></p>
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
						<input type="text" id="iota-address" placeholder="No address, please generate new one..." class="form-control text-center" style="margin: 0 auto;">
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
                        <?php if ($_COOKIE['login-type'] == 'email') { ?>
                        <li>
                            <a href="settings.php"><i class="fa fa-cogs fa-fw"></i> Settings</a>
                        </li>
						<?php } ?>
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
							Support
							<div class="btn-group pull-right">
								<a href="send.php" class="btn btn-primary">Send</a>
								<button type="button" data-toggle="modal" data-target="#receive" data-backdrop="static" data-keyboard="false" class="btn btn-success">Receive</button>
							</div>
						</h1>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<div class="panel panel-primary">
							<div class="panel-heading">
								Contact support
							</div>
							<div class="panel-body">
								<?php
									if (isset($success)) {
										echo '<div class="alert alert-success text-center">'.$success.'</div>';
									}
									if (isset($error)) {
										echo '<div class="alert alert-danger text-center">'.$error.'</div>';
									}
								?>
								<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="submit-ticket-form">
									<div class="form-group" id="title-div">
										<label>Title</label>
										<input type="text" name="title" id="title" class="form-control">
										<p class="help-block" style="color:red;display:none;">Minimum 3 characters!</p>
									</div>
									<div class="form-group" id="message-div">
										<label>Your message</label>
										<textarea name="message" id="message" class="form-control" rows="5"></textarea>
										<p class="help-block" style="color:red;display:none;">This field cannot be empty!</p>
									</div>
									<div class="form-group" id="recaptcha-div">
										<div class="g-recaptcha" data-sitekey="<?php echo $settings['recaptcha_sitekey']; ?>"></div>
										<p class="help-block" style="display:none;color:red;">Please confirm your humanity!</p>
									</div>
									<button type="submit" class="btn btn-primary">Submit ticket</button>
								</form>
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
		$(function() {
			$('#submit-ticket-form').submit(function(e) {
				e.preventDefault();
				if ($('#title').val().length < 4) {
					$('#title-div').addClass('has-error');
					$('#title-div').find('p').show();
				}
				if ($('#message').val().length < 1) {
					$('#message-div').addClass('has-error');
					$('#message-div').find('p').show();
				}
				if (grecaptcha.getResponse().length < 1) {
					$('#recaptcha-div').find('p').show();
				}
				if (grecaptcha.getResponse().length > 0 && $('#message').val().length > 0 && $('#title').val().length >= 3) {
					$('#submit-ticket-form').unbind('submit').submit();
				}
			});
			<?php if ($_GET['donation'] == 'true') {echo 'thankYou();';} ?>
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
