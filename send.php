<?php 
require('config.php');
require_once('plugins/cryptor/cryptor.php'); // Encryption / Decryption class
require_once('plugins/curl/curl.php'); // Curl wrapper class
require_once('plugins/Mobile_Detect.php'); // PHP detect mobile devices
session_start();
$mysql = new mysqli($dbhost,$dbuser,$dbpass,$dbname);
$mysql->query("SET NAMES 'utf8'");
use \Curl\Curl;

function decrypt($string) {
	/* Function to decrypt DB string (AES-256) */ 
	global $key;
	$result = Cryptor::Decrypt($string,$key);
	return $result;
}

function isHTTPS() {
  return
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || $_SERVER['SERVER_PORT'] == 443;
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
    <title>Send money | IOTA Web Wallet</title>
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
							Send <span class="hidden-xs">money</span>
							<div class="btn-group pull-right">
								<a href="send.php" class="btn btn-primary">Send</a>
								<button type="button" data-toggle="modal" data-target="#receive" data-backdrop="static" data-keyboard="false" class="btn btn-success">Receive</button>
							</div>
						</h1>
					</div>
				</div>
				<div class="row">
					<?php 
						if (isset($_GET['sent'])) {
							echo '<div class="alert alert-success text-center">Money has been sent! Open transactions tab to track transaction status!</div>'; 
						} 
					?>
					<div class="col-lg-6">
						<div class="panel panel-default">
							<div class="panel-heading">
								Send IOTA's
							</div>
							<div class="panel-body">
								<div class="form-group" id="receiver-address-div">
									<label>Receiver*</label>
									<input class="form-control" id="receiver-address" />
									<p class="help-block">Address is min. 90 characters (A-Z + #9). This is required.</p>
								</div>
								<div class="form-group" id="message-div">
									<label>Message</label>
									<textarea class="form-control" id="message" placeholder="Optional"></textarea>
								</div>
								<div class="form-group" id="tag-div">
									<label>Tag</label>
									<input class="form-control" id="tag" placeholder="Optional">
								</div>
								<label>Amount</label>
								<div class="form-group input-group dropdown" style="width:200px;" id="amount-div">
									<input type="number" step="any" class="form-control text-center" id="amount" style="margin: 0 auto;">
									<span class="input-group-btn">
										<button class="btn btn-default dropdown-toggle" id="iota-unit-button" type="button" data-toggle="dropdown">Mi <i class="fa fa-caret-down"></i></button>
										<input type="hidden" id="iota-selected-unit" value="Mi" />
										<ul class="dropdown-menu" id="iota-unit" style="width:100px;">
											<li><a href="javascript:void();">iota</a></li>
											<li><a href="javascript:void();">Ki</a></li>
											<li><a href="javascript:void();">Mi</a></li>
											<li><a href="javascript:void();">Gi</a></li>
											<li><a href="javascript:void();">Ti</a></li>
											<li><a href="javascript:void();">Pi</a></li>
										</ul>
									</span>
								</div>
								<p class="help-block" style="margin:-15px 0 5px 5px;">Min. amount is 1 iota</p>
								<div class="form-group" id="recaptcha-div">
									<div class="g-recaptcha" data-sitekey="<?php echo $settings['recaptcha_sitekey']; ?>"></div>
									<p class="help-block text-center" style="display:none;color:red;">Please confirm your humanity!</p>
								</div>
								<button type="button" id="send-money" class="btn btn-primary btn-block">Send money</button>
							</div>
						</div>
					</div>
					<div class="col-lg-6">
						<div class="panel panel-info">
							<div class="panel-heading">
								About sending IOT
							</div>
							<div class="panel-body">
								<p>In IOTA network, in order to send your transaction you will need to confirm 2 other transactions. This is made by doing so called "Proof of Work" - a complex mathematical task for your CPU.<br />Making Proof of Work (PoW) sometimes lasts more than 10 minutes, so please be patient and <b>leave this page open - if you switch tabs, PoW pauses automatically!</b></p>
								<hr />
								<p style="font-size:1.4em;">
									Average Proof of Work time:<br />
									<ul style="font-size:1.1em;">
										<li><b>Desktop</b>: <?php echo $settings['pow-desktop']; ?> min</li>
										<li><b>Smartphone &amp; Tablet</b>: <?php echo $settings['pow-mobile']; ?> min</li>
									</ul>
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
		function doubleSpendWarning() {
			showBSModal({
				title: 'Double spend warning',
				body: '<div class="text-center"><b>You currently have a pending transaction!</b><br /> If you choose to proceed with this transaction, <u>this will be a double spend</u>!<br /><br /> <i style="font-weight:700;color:red;">That means that the old pending transaction will stuck forever pending and it will never be executed</u>!</div>',
				size: "small",
				actions: [{
					label: 'Proceed anyway',
					cssClass: 'btn-block btn-primary',
					onClick: function(e){
						$(e.target).parents('.modal').modal('hide');
						$('#command').val('send-money');
						$('#pin').modal({
							backdrop: 'static',
							keyboard: false
						});
					}
				}]
			});
		}
		
		$(function() {
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
				$('#iota-unit li').click(function() {
					$('#iota-selected-unit').val($(this).text());
					$('#iota-unit-button').html($(this).text()+' <i class="fa fa-caret-down"></i>');
				});
				$('#receiver-address').change(function() {
					if (iota.valid.isAddress($(this).val())) {
						$('#receiver-address-div').removeClass('has-error').addClass('has-success');
						$('#receiver-address-div').find('p').hide();
					}
					else {
						$('#receiver-address-div').removeClass('has-success').addClass('has-error');
						$('#receiver-address-div').find('p').show();
					}
				});
				$('#amount').change(function() {
					if (iota.valid.isNum($(this).val())) {
						$('#amount-div').removeClass('has-error').addClass('has-success');
					}
					else {
						$('#amount-div').removeClass('has-success').addClass('has-error');
					}
				});
				$('#send-money').click(function() {
					if (grecaptcha.getResponse().length > 0) {
						$('#recaptcha-div').find('p').hide();
						unit = $('#iota-selected-unit').val();
						if (unit == 'iota') {money = $('#amount').val();}
						if (unit == 'Ki') {money = $('#amount').val() * Math.pow(10,3);}
						if (unit == 'Mi') {money = $('#amount').val() * Math.pow(10,6);}
						if (unit == 'Gi') {money = $('#amount').val() * Math.pow(10,9);}
						if (unit == 'Ti') {money = $('#amount').val() * Math.pow(10,12);}
						if (unit == 'Pi') {money = $('#amount').val() * Math.pow(10,15);}
						if (money < 1) {
							$('#amount-div').removeClass('has-success').addClass('has-error');
							alert('Minimal amount is 1 iota!');	
						}
						if (money > <?php echo $_SESSION['accountData']['balance']; ?>) {
							$('#amount-div').removeClass('has-success').addClass('has-error');
							alert('Insufficient funds!');
						}
						if (!iota.valid.isAddress($('#receiver-address').val())) {
							$('#receiver-address-div').removeClass('has-success').addClass('has-error');
							$('#receiver-address-div').find('p').show();
						}
						if (iota.valid.isAddress($('#receiver-address').val()) && money >= 1 && money <= <?php echo $_SESSION['accountData']['balance']; ?>) {
							$('#receiver-address-div').removeClass('has-error').addClass('has-success');
							$('#receiver-address-div').find('p').hide();
							$('#amount-div').removeClass('has-error').addClass('has-success');
							<?php 
								if (!$_SESSION['double-spend']) {
									echo "$('#command').val('send-money');
											$('#pin').modal({
												backdrop: 'static',
												keyboard: false
											});";
								} 
								else {
									echo 'doubleSpendWarning();';
								}
							?>
						}
					}
					else {
						$('#recaptcha-div').find('p').show();
					}
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
<?php } else {header("Location: index.php");} ?>