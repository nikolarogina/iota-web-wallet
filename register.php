<?php
require('config.php');
require_once('plugins/cryptor/cryptor.php'); // Encryption / Decryption class
session_start();
$mysql = new mysqli($dbhost,$dbuser,$dbpass,$dbname);
$mysql->query("SET NAMES 'utf8'");

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

function createID($length=8) {
	$alphanumeric = 'ABCDEFGHIJKLMNOPRSTUVXYZabcdefghijklmnoprstuvxyz0123456789';
	$b = substr(str_shuffle($alphanumeric.time()),mt_rand(2,10),23);
	$c = str_shuffle(uniqid($alphanumeric).time());
	$d = md5(time().str_shuffle($alphanumeric));
	return substr(str_shuffle($b.$c.$d),mt_rand(4,19),$length);
}

function getIP() {
	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	return $ip;
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

function getSettings() {
	/* Get global website settings */ 
	global $mysql;
	return $mysql->query("SELECT recaptcha_sitekey,recaptcha_secretkey,g_analytics FROM system WHERE id='1'")->fetch_assoc();
}

function getNumOfUsers() {
	global $mysql;
	return $mysql->query("SELECT id FROM users WHERE status='1'")->num_rows;
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
		$mail->Host	= 'wallet.iota.hr';
		$mail->Port	= 465;
		$mail->SMTPAuth = true;
		$mail->Username = 'noreply@wallet.iota.hr';
		$mail->Password = 'lozinka';
	}
	$mail->SetFrom('noreply@wallet.iota.hr','IOTA Wallet');
	$mail->AddReplyTo('admin@iota.hr','Admin | Iota.hr');
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

// Used to limit # of max. allowed registrations
$numUsers = getNumOfUsers();
$settings = getSettings();

if (!empty($_POST['email'])) {
	$secretKey = $settings['recaptcha_secretkey'];
	$email = trim(addslashes($_POST['email']));
	if (!ifUserExists($email)) {
		$recaptcha = $_POST['g-recaptcha-response'];
		$verify = json_decode(file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$recaptcha"));
		if ($verify->success) {
			$password = trim($_POST['password']);
			$seed = encrypt(trim(addslashes($_POST['seed'])));
			$hint = ucfirst(trim(addslashes($_POST['hint'])));
			if (empty($hint)) {$hint = '-';}
			if (filter_var($email,FILTER_VALIDATE_EMAIL) && strlen($password) > 7 && !empty($seed)) {
				$id = createID();
				$hashed_password = password_hash($password,PASSWORD_DEFAULT);
				$ip = getIP();
				$joined = timestamp();
				$security_hash = md5(openssl_random_pseudo_bytes(32));
				$query = $mysql->query("INSERT INTO users (id,email,password,seed,hash,joined,joined_ip,hint) VALUES ('$id','$email','$hashed_password','$seed','$security_hash','$joined','$ip','$hint')");
				if ($query) {
					$subject = 'Confirm your email | IOTA Web Wallet';
					$message = file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/email/confirm-registration.php?confirm='.$security_hash);
					$mailed = sendMail($email,'User',$subject,$message);
					if ($mailed['status']) {
						$info = 'Email with the verification link has been sent to: <b>'.$email.'</b>. <br />Please open your email account (check <i>spam</i> mail too) and click on confirmation link!';
					}
					else {
						$error = 'An error occurded while trying to send confirmation link. Please try again! <br />'.$mailed['errorInfo'];
					}
				}
				else {
					$error = 'An error occurded while trying to save user data. Please try again! <br />'.$mysql->error;
				}
			}
			else {
				$error = 'Error while validating form data. Try again!';
			}
		}	
		else {
			$error = 'Anti-robot verification not passed. Please try again!';
		}
	}
	else {
		$warning = 'User already exists! <a href="index.php">Login?</a>';
	}
}

$mysql->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Registration page for IOTA web wallet.">
    <meta name="author" content="Nikola Rogina">
    <title>Registration | IOTA Web Wallet</title>
    <!-- Bootstrap Core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- MetisMenu CSS -->
    <link href="css/metisMenu.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/theme.css" rel="stylesheet">
    <!-- Custom Fonts -->
    <link href="css/font-awesome.min.css" rel="stylesheet" type="text/css">
	<!-- Favicon -->
	<link rel="shortcut icon" type="image/x-icon" sizes="16x16 32x32 48x48" href="img/favicon.ico">
	<?php echo $settings['g_analytics']; ?>
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
	<script src='https://www.google.com/recaptcha/api.js'></script>
</head>
<body>
	<!-- PIN Modal -->
	<div class="modal fade" id="pin" tabindex="-1" role="dialog" aria-labelledby="PINlabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content text-center">
				<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="finish-registration" >
					<div class="modal-header">
						<button type="button" id="modal-close-x" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title text-center" id="PINlabel">PIN</h4>
					</div>
					<div class="modal-body text-center">
						<div class="form-group">
						<label>Please enter your desired PIN number (4 digits). <br />IMPORTANT: Only you know this PIN, we do not save it. <span style="color:red">IF YOU LOSE YOUR PIN, YOU WILL NO LONGER BE ABLE TO LOG INTO YOUR ACCOUNT!</span></label>
							<div id="pin-div">
								<input type="text" id="pin-number" class="form-control text-center" placeholder="PIN" style="width:150px;margin:10px auto;" />
								<p class="help-block" style="display:none;">PIN has to be 4 numbers!</p>
							</div>
						</div>
						<div class="form-group">
							<input type="text" id="pin-hint" class="form-control text-center" style="width:250px;margin:5px auto;" placeholder="PIN Hint (optional)" maxlength="64" autocomplete="off" />
							<p class="help-block" style="margin-top:-2px">Do not write your PIN as your hint.</p>
						</div>
					</div>
					<div class="modal-footer" style="margin-top:-20px;">
						<button type="submit" class="btn btn-primary btn-block">Confirm</button>
					</div>
				</form>
			</div>
			<!-- /.modal-content -->
		</div>
		<!-- /.modal-dialog -->
	</div>
    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
				<a href="index.php"><img src="img/logo-black.png" style="width:90%;margin:5% 0 -15% 5%;" alt="IOTA Web Wallet"/></a>
                <div class="login-panel panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Registration</h3>
                    </div>
                    <div class="panel-body">
						<?php 
							if (!empty($_POST['email'])) {
								if (isset($info)) {
									echo '<div class="alert alert-info text-center">'.$info.'</div>';
								}
								if (isset($warning)) {
									echo '<div class="alert alert-warning text-center">'.$warning.'</div>';
								}
								if (isset($error)) {
									echo '<div class="alert alert-danger text-center">'.$error.'</div>';
								}
							}
							if ($numUsers > 100) {
								echo '<div class="alert alert-danger text-center"><b>Maksimum number of users reached!</b><br />This wallet is in testing phase, so we currently cannot allow more registrations.<br /> Please check this page tommorow!</div>';
							}
							else {
						?>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="register">
                            <fieldset>
                                <div class="form-group" id="email-div">
                                    <input class="form-control" placeholder="E-mail" id="email" type="email" autofocus required>
									<p class="help-block" style="display:none;"></p>
                                </div>
                                <div class="form-group" id="password-div">
                                    <input class="form-control" placeholder="Password" name="password" id="password" type="password" required>
									<p class="help-block" style="display:none;">Minimum 8 characters!</p>
                                </div>
								<div class="form-group" id="password2-div">
                                    <input class="form-control" placeholder="Confirm password" id="password2" type="password" value="" required>
									<p class="help-block" style="display:none;">Passwords do not match!</p>
                                </div>
                                <div class="form-group text-center" id="seed-buttons">
                                    <button type="button" class="btn btn-outline btn-primary" id="yes-seed">I have a SEED</button>
									<button type="button" class="btn btn-outline btn-success" id="no-seed">I don't have a SEED</button>
                                </div>
								<div id="have-seed" style="display:none;">
									<div class="form-group" id="seed-div">
										<input class="form-control" placeholder="Your SEED" id="seed" type="text" maxlength="81">
										<p class="help-block" style="display:none;">Seed is invalid. It has 81 character and can be any uppercase letter (A-Z) or number 9!</p>
										<span>This seed will be encrypted with PIN and then stored. <b style="color:red;">We DO NOT have access to your funds.</b></span>
									</div>
								</div>
								<div id="new-seed" style="display:none;">
									<div class="form-group text-center">
										<label>Your SEED will be automatically generated. You will be able to access it when you login.<br /><a href="javascript:void();" onclick="whatIsSeed();">What is a SEED?</a></label>
									</div>
								</div>
								<div class="form-group" id="recaptcha-div" style="display:none;">
									<div class="g-recaptcha" data-sitekey="<?php echo $settings['recaptcha_sitekey']; ?>"></div>
									<p class="help-block text-center" style="display:none;color:red;">Please confirm your humanity!</p>
								</div>
								<input type="hidden" name="email" />
								<input type="hidden" name="password" />
								<input type="hidden" name="seed" />
								<input type="hidden" name="hint" />
                                <button type="submit" name="confirm" class="btn btn-lg btn-success btn-block" style="display:none;">Register</button>
								<div class="form-group" style="margin:20px 0 -10px 0;">
									<p class="help-block pull-left"><a href="lost-password.php">Lost password?</a></p>
									<p class="help-block pull-right"><a href="index.php">Login</a></p>
								</div>
                            </fieldset>
                        </form>
						<?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
	<footer class="text-center">
		<p>&copy; <?php echo date("Y").' '.$_SERVER['HTTP_HOST']; ?> | Author: <a href="mailto:admin@iota.hr">Nikola Rogina</a></p>
	</footer>
    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<script>window.jQuery || document.write('<script src="js/jquery.min.js"><\/script>')</script>
	<!-- AES encryption algorithm -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.2/rollups/aes.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.2/rollups/pbkdf2.js"></script>
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
    <!-- Custom Theme JavaScript -->
    <script src="js/theme.js"></script>	
	<script>
		var newSeed = false;
		var keySize = 256;
		var ivSize = 128;
		var iterations = 800;

		function encryptAES(string,pin) {
			var salt = CryptoJS.lib.WordArray.random(128/8);
			var key = CryptoJS.PBKDF2(pin, salt, {
				keySize: keySize/32,
				iterations: iterations
			});
			var iv = CryptoJS.lib.WordArray.random(128/8);
			var encrypted = CryptoJS.AES.encrypt(string, key, { 
				iv: iv, 
				padding: CryptoJS.pad.Pkcs7,
				mode: CryptoJS.mode.CBC
			});
			var transitmessage = salt.toString()+ iv.toString() + encrypted.toString();
			return transitmessage;
		}
		
		function generateRandomSeed() {
			const t = 81;
			var e, n = "ABCDEFGHIJKLMNOPQRSTUVWXYZ9",
			i = "";
			if (window.crypto && window.crypto.getRandomValues) {
				var r = new Uint32Array(t);
				for (window.crypto.getRandomValues(r), e = 0; e < t; e++) i += n[r[e] % n.length];
				return i;
			}
			alert("Your browser do not support secure seed generation. Try upgrading your browser!");
		}
		
		function isSeedValid(seed) {
			if (/^[A-Z9]+$/.test(seed) && seed.length === 81) {
				return true;
			}
			else {
				return false;
			}
		}
		
		function isNumeric(num) {
			return !isNaN(num);
		}
		
		function validateEmail(email) {
			if (email.length > 5) {
				var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
				return emailReg.test(email);
			}
			else {
				return false;
			}
		}
		
		function whatIsSeed() {
			showBSModal({
				title: 'What is an IOTA seed?',
				body: '<div class="text-center">Think about your seed as the combined username and password that grants access to your bank account. <b>If anyone obtains your seed, they can login and access your funds</b>.<br /><br />It contains random 81 character using only A-Z and the number 9.<br /><br /> We will automatically generate your seed and it is <i style="font-weight:700;">stored in encrypted form</i> with the PIN only you know, so <b style="color:red;">we DO NOT have access to your funds</b>!</div>'
			});
		}

		$(document).ready(function() {
			$('#email').change(function() {
				if (!validateEmail($(this).val())) {
					$('#email-div').addClass('has-error');
					$('#email-div').find('p').html('Please enter a valid email!').show();
				}
				else {
					$.post('ajax.check-email.php',{email:$(this).val()},function(response) {
						if (response == '0') {
							$('#email-div').addClass('has-error');
							$('#email-div').find('p').html('User already exists! <a href="index.php">Login?</a>').show();
						}
						else {
							$('#email-div').removeClass('has-error').addClass('has-success');
							$('#email-div').find('p').hide();
						}
					});
				}
			});
			$('#password').change(function() {
				if ($(this).val().length < 8) {
					$('#password-div').addClass('has-error');
					$('#password-div').find('p').show();
				}
				else {
					$('#password-div').removeClass('has-error').addClass('has-success');
					$('#password-div').find('p').hide();
				}
			});
			$('#password2').change(function() {
				if ($(this).val() !== $('#password').val()) {
					$('#password2-div').addClass('has-error');
					$('#password2-div').find('p').show();
				}
				else {
					$('#password2-div').removeClass('has-error').addClass('has-success');
					$('#password2-div').find('p').hide();
				}
			});
			$("#register").submit(function() {
				if (grecaptcha.getResponse().length > 0) {
					$('#recaptcha-div').find('p').hide();
					if (validateEmail($('#email').val()) && $('#password').val().length > 7 && $('#password2').val() === $('#password').val()) {
						$.post('ajax.check-email.php',{email:$('#email').val()},function(response) {
							if (response == '1') {
								var seed = $('#seed').val();
								if (!newSeed) {
									if (isSeedValid(seed)) {
										$('#pin').modal({
											show: true,
											backdrop: 'static'
										}); 
									}
									else {
										$('#seed-div').addClass('has-error');
										$('#seed-div').find('p').show();
									}
								}
								else {
									$('#pin').modal({
										show: true,
										backdrop: 'static'
									}); 
								}
							}
						});
					}
				}
				else {
					$('#recaptcha-div').find('p').show();
				}
				return false;
			});
			$('#finish-registration').submit(function(e) {
				// Prevent form submission before SEED gets encrypted with PIN! PIN is not transfered with form data
				e.preventDefault();
				var pin = $("#pin-number").val().replace(/\s+/g,'');
				if (pin.length == 4 && isNumeric(pin)) {
					if (!newSeed) {
						var seed = $('#seed').val().replace(/\s+/g,'');
					}
					else {
						var seed = generateRandomSeed();
					}
					$(this).find('.modal-footer').html('<span><img src="img/loading.gif" style="width:32px;height:32px;display:block;margin-left:auto;margin-right:auto;" /></span>');
					var encrypted = encryptAES(seed,pin);
					$('#register').find('input[name=email]').val($('#email').val());
					$('#register').find('input[name=password]').val($('#password').val());
					$('#register').find('input[name=seed]').val(encrypted);
					$('#register').find('input[name=hint]').val($('#pin-hint').val());
					$('#register').unbind('submit').submit();
				}
				else {
					$('#pin-div').addClass('has-error');
					$('#pin-div').find('p').show();
				}
			});
			
			$('#yes-seed').click(function() {
				$('#seed-buttons').fadeOut(function(){
					$('#have-seed').show();
					$('#recaptcha-div').show();
					$('#register').find(':submit').show();
				});
			});
			
			$('#no-seed').click(function() {
				$('#seed-buttons').fadeOut(function(){
					$('#new-seed').show();
					$('#recaptcha-div').show();
					$('#register').find(':submit').show();
					newSeed = true;
				});
			});
			
			$('#seed').change(function() {
				if (isSeedValid($(this).val())) {
					$('#seed-div').removeClass('has-error').addClass('has-success');
					$('#seed-div').find('p').hide();
				}
				else {
					$('#seed-div').addClass('has-error');
					$('#seed-div').find('p').show();
				}
			});
				
			$('#pin').on('hidden.bs.modal', function () {
				$("#pin-number").val('');
			});
			
			if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
				$("#pin-number").attr({"type":"number"});
			}
			else {
				$("#pin-number").mask("9 9 9 9");
				$('#pin').on('shown.bs.modal', function (e) {
					$("#pin-number").focus();
				});
				$(".g-recaptcha").css("margin-left","12px");
			}
			
			$('#pin-number').change(function() {
				var pin = $("#pin-number").val().replace(/\s+/g,'');
				if (pin.length == 4 && isNumeric(pin)) {
					$('#pin-div').removeClass('has-error').addClass('has-success');
					$('#pin-div').find('p').hide();
				}
				else {
					$('#pin-div').addClass('has-error');
					$('#pin-div').find('p').show();
				}
			});
		});
	</script>
</body>
</html>