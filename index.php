<?php
require('config.php');
require_once('plugins/cryptor/cryptor.php'); // Encryption / Decryption class
session_start();
$mysql = new mysqli($dbhost,$dbuser,$dbpass,$dbname);
$mysql->query("SET NAMES 'utf8'");

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

function decrypt($string) {
	/* Function to decrypt DB string (AES-256) */ 
	global $key;
	$result = Cryptor::Decrypt($string,$key);
	return $result;
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
	/* Function to get timezone adjusted timestamp */ 
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

function confirmEmail($hash) {
	global $mysql;
	$query = $mysql->query("SELECT id FROM users WHERE hash='$hash'");
	if ($query->num_rows > 0) {
		$security_hash = md5(openssl_random_pseudo_bytes(32));
		$mysql->query("UPDATE users SET hash='$security_hash',status='1' WHERE hash='$hash'");
		return true;
	}
	else {
		return false;
	}
}

function login($email,$password) {
	global $mysql;
	$result = array();
	$email = addslashes($email);
	$query = $mysql->query("SELECT id,password,status FROM users WHERE email='$email'");
	if ($query->num_rows > 0) {
		$data = $query->fetch_assoc();
		if ($data['status'] == '1') {
			$hashed_password = $data['password'];
			if (password_verify($password,$hashed_password)) {
				$_SESSION['email'] = $email;
				$_SESSION['password'] = $password;
				$_SESSION['id'] = $data['id'];
				$result['status'] = true;
			} 
			else {
				$result['status'] = false;
				$result['error'] = 'Wrong password!';
			}
		}
		else {
			$result['status'] = false;
			$result['error'] = 'Email not confirmed!';
		}
	}
	else {
		$result['status'] = false;
		$result['error'] = 'User dosen\'t exists!';
	}
	return $result;
}

function lastLogin() {
	global $mysql;
	$userID = $_SESSION['id'];
	$now = timestamp();
	$ip = getIP();
	$query = $mysql->query("UPDATE users SET last_login='$now',last_login_ip='$ip' WHERE id='$userID'");
	return $query;
}

function getBrowser() { 
	/* Function to get user browser data - used to check for WebGL2 support */ 
    $u_agent = $_SERVER['HTTP_USER_AGENT']; 
    $bname = 'Unknown';
    $platform = 'Unknown';
    $version= "";
    if (preg_match('/linux/i', $u_agent)) {
        $platform = 'linux';
    }
    elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
        $platform = 'mac';
    }
    elseif (preg_match('/windows|win32/i', $u_agent)) {
        $platform = 'windows';
    }
    if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) { 
        $bname = 'Internet Explorer'; 
        $ub = "MSIE"; 
    } 
    elseif(preg_match('/Firefox/i',$u_agent)) { 
        $bname = 'Mozilla Firefox'; 
        $ub = "Firefox"; 
    }
    elseif(preg_match('/OPR/i',$u_agent)) { 
        $bname = 'Opera'; 
        $ub = "Opera"; 
    } 
    elseif(preg_match('/Chrome/i',$u_agent)) { 
        $bname = 'Google Chrome'; 
        $ub = "Chrome"; 
    } 
    elseif(preg_match('/Safari/i',$u_agent)) { 
        $bname = 'Apple Safari'; 
        $ub = "Safari"; 
    } 
    elseif(preg_match('/Netscape/i',$u_agent)) { 
        $bname = 'Netscape'; 
        $ub = "Netscape"; 
    } 
    $known = array('Version', $ub, 'other');
    $pattern = '#(?<browser>'.join('|', $known).')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $u_agent, $matches)) {
        // we have no matching number just continue
    }
    $i = count($matches['browser']);
    if ($i != 1) {
        if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
            $version= $matches['version'][0];
        }
        else {
            $version= $matches['version'][1];
        }
    }
    else {
        $version= $matches['version'][0];
    }
    if ($version==null || $version=="") {$version="?";}
    return array(
        'userAgent' => $u_agent,
        'name'      => $bname,
		'version-main' => (int)strtok($version,'.'),
        'version'   => $version,
        'platform'  => $platform,
        'pattern'    => $pattern
    );
} 

function getSettings() {
	/* Get global website settings */ 
	global $mysql;
	return $mysql->query("SELECT recaptcha_sitekey,recaptcha_secretkey,g_analytics FROM system WHERE id='1'")->fetch_assoc();
}

if (isLogged()) {
	if (isset($_REQUEST['redirect'])) {
		header("Location: ".urldecode($_REQUEST['redirect']));
	}
	else {
		header("Location: transactions.php");
	}
}
else {
	$supported = false;
	$settings = getSettings();
	$browser = getBrowser();
	if ($browser['name'] == 'Google Chrome' && $browser['version-main'] > 58) {
		$supported = true;
	}
	if ($browser['name'] == 'Mozilla Firefox' && $browser['version-main'] > 53) {
		$supported = true;
	}
	if ($browser['name'] == 'Opera' && $browser['version-main'] > 46) {
		$supported = true;
	}
	if (isset($_POST['email'])) {
		$secretKey = $settings['recaptcha_secretkey'];
		$recaptcha = $_POST['g-recaptcha-response'];
		$verify = json_decode(file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$recaptcha"));
		if ($verify->success) {
			$email = trim($_POST['email']);
			$password = trim($_POST['password']);
			$login = login($email,$password);
			if ($login['status']) {
				lastLogin();
				if (isset($_REQUEST['redirect'])) {
					header("Location: ".urldecode($_REQUEST['redirect']));
				}
				else {
					header("Location: transactions.php");
				}
			}
			else {
				$error = $login['error'];
			}
		}
		else {
			$error = 'Anti-robot verification not passed. Please try again!';
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="IOTA web wallet. Supported on any modern device!">
    <meta name="author" content="Nikola Rogina">
    <title>Login | IOTA Web Wallet</title>
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
				<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="finish-login" >
					<div class="modal-header">
						<button type="button" id="modal-close-x" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title text-center" id="PINlabel">PIN</h4>
					</div>
					<div class="modal-body text-center">
						<div class="form-group">
						<label>Please enter your desired PIN number (4 digits).<br /> This PIN is used as a key for encrypting your seed. Your SEED is saved in encrypted form in <a href="https://www.w3schools.com/html/html5_webstorage.asp" target="_blank">HTML5 Session Storage</a>. <br /><span style="color:red">You will be asked for this PIN for any important action!</span></label>
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
                        <h3 class="panel-title">Login</h3>
                    </div>
                    <div class="panel-body">
						<?php
							if (!empty($_GET['confirm'])) {
								if (confirmEmail($_GET['confirm'])) {
									echo '<div class="alert alert-success text-center">Email has been successfully confirmed! <br /> Now you can login!</div>';
								}
								else {
									echo '<div class="alert alert-danger text-center">Wrong email confirmation code! Try again or contact support!</div>';
								}
							}
							if (isset($_POST['email'])) {
								if (!empty($error)) {
									echo '<div class="alert alert-danger text-center">'.$error.'</div>';
								}
							}
							if (!$supported) {
								echo '<div class="alert alert-danger text-center">This web wallet requires you to have a <b>WebGL2 supported browser</b>!<br /><a href="http://caniuse.com/#search=webgl%202" target="_blank">Click here to see on which browser you should upgrade!</a></div>';
							}
							else {
						?>
						<div id="login-options">
							<button type="button" id="login-email-btn" class="btn btn-lg btn-primary btn-block">Login with email</button>
							<button type="button" id="login-seed-btn" class="btn btn-lg btn-success btn-block">Login with seed</button>
						</div>
						<form action="<?php echo (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>" method="post" id="login" novalidate>
                            <fieldset>
								<div id="login-seed" style="display:none;">
									<div class="form-group input-group" id="seed-div">
										<input class="form-control" placeholder="Your SEED" id="seed" name="seed" autocomplete="off" type="password" />
										<span class="input-group-btn">
											<button class="btn btn-default" type="button" onclick="toggleSeedView();"><i class="fa fa-eye"></i></button>
										</span>
									</div>
									<p class="help-block" id="seed-error" style="margin-top:-10px;display:none;color:#a94442;">Please enter a valid seed (81 characters A-Z + #9)!</p>
									<div class="form-group text-center" id="seed-login-options">
										<button type="button" class="btn btn-outline btn-primary" id="generate-seed">Generate SEED</button>
										<button type="button" class="btn btn-outline btn-success" id="scan-qr">Scan QR code</button>
									</div>
									<div id="qr-preview" style="text-align:center;margin:0 auto;display:none;">
										<video id="qr-preview-video" style="width:90%;object-fit:cover;"></video>
									</div>
								</div>
								<div id="login-email" style="display:none;">
									<div class="form-group" id="email-div">
										<input class="form-control" placeholder="E-mail" id="email" name="email" type="email" required>
										<p class="help-block" style="display:none;"></p>
									</div>
									<div class="form-group" id="password-div">
										<input class="form-control" placeholder="Password" name="password" id="password" type="password" required>
										<p class="help-block" style="display:none;">Minimum 8 characters!</p>
									</div>
								</div>
								<div class="form-group text-center" style="margin-top:10px;" id="login-explanation">
									<a href="javascript:void();" onclick="explainLogin();">What is the difference?</a>
								</div>
								<div class="form-group" id="recaptcha-div" style="display:none;">
									<div class="g-recaptcha" data-sitekey="<?php echo $settings['recaptcha_sitekey']; ?>"></div>
									<p class="help-block text-center" style="display:none;color:red;">Please confirm your humanity!</p>
								</div>
                                <button type="submit" class="btn btn-lg btn-primary btn-block" style="display:none;">Login</button>
								<div class="form-group" style="margin:20px 0 -10px 0;">
									<p class="help-block pull-left"><a href="register.php">Register</a></p>
									<p class="help-block pull-right"><a href="lost-password.php">Lost password?</a></p>
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
    <!-- JS Cookie Plugin -->
    <script src="js/js.cookie.js"></script>
	<!-- Metis Menu Plugin JavaScript -->
    <script src="js/metisMenu.min.js"></script>
	<!-- Masked Input -->
	<script src="js/jquery.maskedinput.min.js"></script>
	<!-- Custom Theme JavaScript -->
    <script src="js/theme.js"></script>
	<script>
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
		
		function validateEmail(email) {
			if (email.length > 5) {
				var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
				return emailReg.test(email);
			}
			else {
				return false;
			}
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
		
		function toggleSeedView() {
			if ($('#seed').attr('type') == 'password') {
				$('#seed').attr('type','text');
				$('.fa-eye').removeClass('fa-eye').addClass('fa-eye-slash');
			}
			else {
				$('#seed').attr('type','password');
				$('.fa-eye-slash').removeClass('fa-eye-slash').addClass('fa-eye');
			}
		}
		
		function explainLogin() {
			showBSModal({
				title: 'Email login vs. SEED login',
				body: '<div class="text-center">If you choose to login with email, then you need to be <a href="register.php"><b>registered</b></a>. That is useful if you don\'t want to remember your SEED. <br /><br /> If you choose to login with your SEED, then your SEED gets encrypted with PIN and it\'s <a href="https://www.w3schools.com/html/html5_webstorage.asp" target="_blank"><b>stored locally in you browser memory</b></a>. This is the more secure option.</div>'
			});
		}
		
		
		var loginType = null;
		$(document).ready(function() {
			$('#login-email-btn').click(function() {
				$('#login-options').fadeOut(function() {
					$('#login-explanation').hide();
					$('#login-email').show();
					$('#recaptcha-div').show();
					$('#login').find(':submit').show();
					loginType = 'email';
				});
			});
			$('#login-seed-btn').click(function() {
				$('#login-options').fadeOut(function() {
					$('#login-explanation').hide();
					$('#login-seed').show();
					$('#recaptcha-div').show();
					$('#login').find(':submit').show();
					loginType = 'seed';
				});
			});
			$('#generate-seed').click(function() {
				$('#seed-login-options').fadeOut(function() {
					$('#seed').val(generateRandomSeed());
					$('#seed-div, #seed-error').removeClass('has-error').addClass('has-success');
					$('#seed-div').find('button').css('border-color','#3c763d');
					$('#seed-error').hide();
				});
			});
			$('#scan-qr').click(function() {
				if (typeof Instascan == "undefined") {
					$('#seed-login-options').html('<span><img src="img/loading.gif" style="width:48px;height:48px;display:block;margin-left:auto;margin-right:auto;" /></span>');
					$.getScript('js/instascan.min.js', function() {
						$('#seed-login-options').hide();
						$('#qr-preview').show();
						let scanner = new Instascan.Scanner({video:document.getElementById('qr-preview-video'),backgroundScan:false,mirror:false});
							scanner.addListener('scan', function (content) {
								if (isSeedValid(content)) {
									$('#seed').val(content);
									scanner.stop();
									$('#qr-preview').fadeOut(function() {
										$('#seed-div, #seed-error').removeClass('has-error').addClass('has-success');
										$('#seed-div').find('button').css('border-color','#3c763d');
										$('#seed-error').hide();
									});
								}
								else {
									alert('Invalid QR code!');
								}
							});
						Instascan.Camera.getCameras().then(function(cameras) {
							if (cameras.length > 0) {
								if (cameras[1]) {
									$('#qr-preview-video').css({'width':'100%'});
									scanner.start(cameras[1]);
								}
								else {
									scanner.start(cameras[0]);
								}
							} 
							else {
								alert('No cameras found!');
							}
						}).catch(function (e) {
							console.error(e);
						});
					});
				}
			});
			if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
				$("#pin-number").attr({"type":"number"});
				$("#pin-number").keydown(function(e){
					if ($(this).val().length >= 4) { 
						$(this).val($(this).val().substr(0, 4));
					}
				});
				$("#pin-number").keyup(function(e){
					if ($(this).val().length >= 4) { 
						$(this).val($(this).val().substr(0, 4));
					}
				});
			}
			else {
				$(".g-recaptcha").css("margin-left","12px");
				$("#pin-number").mask("9 9 9 9");
			}
			$('#email').change(function() {
				if (!validateEmail($(this).val())) {
					$('#email-div').addClass('has-error');
					$('#email-div').find('p').html('Please enter a valid email!').show();
				}
				else {
					$('#email-div').removeClass('has-error').addClass('has-success');
					$('#email-div').find('p').hide();
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
			$('#seed').change(function() {
				if (!isSeedValid($(this).val())) {
					$('#seed-div, #seed-error').addClass('has-error');
					$('#seed-div').find('button').css('border-color','#a94442');
					$('#seed-error').show();
				}
				else {
					$('#seed-div, #seed-error').removeClass('has-error').addClass('has-success');
					$('#seed-div').find('button').css('border-color','#3c763d');
					$('#seed-error').hide();
				}
			});
			$('#pin-number').change(function() {
				var pin = $(this).val().replace(/\s+/g,'');
				if (pin.length == 4 && isNumeric(pin)) {
					$('#pin-div').removeClass('has-error').addClass('has-success');
					$('#pin-div').find('p').hide();
				}
				else {
					$('#pin-div').addClass('has-error');
					$('#pin-div').find('p').show();
				}
			});
			$("#login").submit(function(e) {
				e.preventDefault();
				if (grecaptcha.getResponse().length > 0) {
					$('#recaptcha-div').find('p').hide();
					if (loginType == 'email') {
						if (validateEmail($('#email').val()) && $('#password').val().length > 7) {
							Cookies.set('login-type',loginType);
							$(this).unbind('submit').submit();
						}
					}
					if (loginType == 'seed') {
						if (isSeedValid($('#seed').val())) {
							$('#pin').modal({
								show: true,
								backdrop: 'static'
							}); 
						}
						else {
							$('#seed-div, #seed-error').addClass('has-error');
							$('#seed-div').find('button').css('border-color','#a94442');
							$('#seed-error').show();
						}
					}
				}
				else {
					$('#recaptcha-div').find('p').show();
				}
			});
			$('#finish-login').submit(function(e) {
				// Stop submitting sensitive data to server, instead store it into HTML5 sessionStorage
				e.preventDefault();
				var pin = $('#pin-number').val().replace(/\s+/g,'');
				if (pin.length == 4 && isNumeric(pin)) {
					$(this).find('.modal-footer').html('<span><img src="img/loading.gif" style="width:32px;height:32px;display:block;margin-left:auto;margin-right:auto;" /></span>');
					var seed = $('#seed').val().replace(/\s+/g,'');
					var encrypted = encryptAES(seed,pin);
					sessionStorage.setItem("seed",encrypted);
					Cookies.set('login-type',loginType);
					Cookies.set('pin-hint',$('#pin-hint').val());
					window.location = '<?php echo (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/transactions.php"; ?>';
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
<?php $mysql->close(); ?>
