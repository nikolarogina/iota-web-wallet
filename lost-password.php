<?php
require('config.php');
require_once('plugins/cryptor/cryptor.php'); // Encryption / Decryption class
session_start();
$mysql = new mysqli($dbhost,$dbuser,$dbpass,$dbname);
$mysql->query("SET NAMES 'utf8'");
date_default_timezone_set('Europe/Zagreb');

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
	$reset = false;
	$redirect = false;
	$settings = getSettings();
	if (isset($_GET['key'])) {
		$key = addslashes($_GET['key']);
		$query = $mysql->query("SELECT id FROM users WHERE hash='$key'");
		if ($query->num_rows > 0) {
			$reset = true;
			$query = $query->fetch_assoc();
			$userID = $query['id'];
		}
		else {
			$error = 'Invalid security key!';
		}
	}
	if (isset($_POST['password']) && isset($_POST['user-id'])) {
		$secretKey = $settings['recaptcha_secretkey'];
		$recaptcha = $_POST['g-recaptcha-response'];
		$verify = json_decode(file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$recaptcha"));
		if ($verify->success) {
			$password = trim($_POST['password']);
			$userID = addslashes($_POST['user-id']);
			if (strlen($password) > 7) {
				$hashed_password = password_hash($password,PASSWORD_DEFAULT);
				$query = $mysql->query("UPDATE users SET password='$hashed_password' WHERE id='$userID'");
				if ($query) {
					$redirect = true;
					$success = 'Password updated successfully! You can now login with new password!';
				}
				else {
					$error = 'Unable to save new password! Please try again or contact support!';
				}
			}
			else {
				$error = 'Password too short! Minimum 8 characters!';
			}
		}
		else {
			$error = 'Anti-robot verification not passed. Please try again!';
		}
	}
	if (isset($_POST['email'])) {
		$secretKey = $settings['recaptcha_secretkey'];
		$recaptcha = $_POST['g-recaptcha-response'];
		$verify = json_decode(file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$recaptcha"));
		if ($verify->success) {
			$email = trim($_POST['email']);
			if (ifUserExists($email)) {
				$security_hash = md5(openssl_random_pseudo_bytes(32));
				$mysql->query("UPDATE users SET hash='$security_hash' WHERE email='$email'");
				$subject = 'Reset password | IOTA Web Wallet';
				$message = file_get_contents('https://'.$_SERVER['SERVER_NAME'].'/email/lost-password.php?hash='.$security_hash);
				$mailed = sendMail($email,'User',$subject,$message);
				if ($mailed['status']) {
					$info = 'Email with instructions to reset password has been sent to: <b>'.$email.'</b>. <br />Please open your email account (check <i>spam</i> mail too) and follow instructions!';
				}
				else {
					$error = 'An error occurded while trying to send email with instructions to reset password. Please try again! <br />'.$mailed['errorInfo'];
				}
			}
			else {
				$error = 'User dosen\'t exists in database!';
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
    <meta name="description" content="Reset password page for IOTA web wallet.">
    <meta name="author" content="Nikola Rogina">
    <title>Lost password | IOTA Web Wallet</title>
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
    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
				<a href="index.php"><img src="img/logo-black.png" style="width:90%;margin:5% 0 -15% 5%;" alt="IOTA Web Wallet"/></a>
                <div class="login-panel panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><?php if (!$reset) {echo 'Lost password';} else {echo 'Reset password';} ?></h3>
                    </div>
                    <div class="panel-body">
						<?php
							if (!empty($info)) {
								echo '<div class="alert alert-info text-center">'.$info.'</div>';
							}
							if (!empty($success)) {
								echo '<div class="alert alert-success text-center">'.$success.'</div>';
							}
							if (!empty($error)) {
								echo '<div class="alert alert-danger text-center">'.$error.'</div>';
							}
						?>
                        <form action="<?php echo (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>" method="post" id="<?php if (!$reset) {echo 'lost-password';} else {echo 'reset-password';} ?>">
                            <fieldset>
								<?php if (!$reset) { ?>
									<div class="form-group" id="email-div">
										<input class="form-control" placeholder="Your email address" id="email" name="email" type="email" autofocus required>
										<p class="help-block" style="display:none;"></p>
									</div>
								<?php } else { ?>
									<div class="form-group" id="password-div">
										<input class="form-control" placeholder="Password" name="password" id="password" type="password" required>
										<p class="help-block" style="display:none;">Minimum 8 characters!</p>
									</div>
									<div class="form-group" id="password2-div">
										<input class="form-control" placeholder="Confirm password" id="password2" type="password" value="" required>
										<p class="help-block" style="display:none;">Passwords do not match!</p>
									</div>
									<input type="hidden" name="user-id" value="<?php echo $userID; ?>" />
								<?php } ?>
								<div class="form-group" id="recaptcha-div">
									<div class="g-recaptcha" data-sitekey="<?php echo $settings['recaptcha_sitekey']; ?>"></div>
									<p class="help-block text-center" style="display:none;color:red;">Please confirm your humanity!</p>
								</div>
                                <button type="submit" class="btn btn-lg btn-primary btn-block"><?php if (!$reset) {echo 'Recover';} else {echo 'Reset';} ?> password</button>
								<div class="form-group" style="margin:20px 0 -10px 0;">
									<p class="help-block pull-left"><a href="register.php">Register</a></p>
									<p class="help-block pull-right"><a href="index.php">Login</a></p>
								</div>
                            </fieldset>
                        </form>
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
    <!-- Bootstrap Core JavaScript -->
    <script src="js/bootstrap.min.js"></script>
    <!-- Metis Menu Plugin JavaScript -->
    <script src="js/metisMenu.min.js"></script>
	<!-- Custom Theme JavaScript -->
    <script src="js/theme.js"></script>
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
		
		$(document).ready(function() {
			if (!/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
				$(".g-recaptcha").css("margin-left","15px");
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
			$("#lost-password").submit(function(e) {
				e.preventDefault();
				if (grecaptcha.getResponse().length > 0) {
					$('#recaptcha-div').find('p').hide();
					if (validateEmail($('#email').val())) {
						$(this).unbind('submit').submit();
					}
				}
				else {
					$('#recaptcha-div').find('p').show();
				}
			});
			$("#reset-password").submit(function(e) {
				e.preventDefault();
				if (grecaptcha.getResponse().length > 0) {
					$('#recaptcha-div').find('p').hide();
					if ($('#password').val().length > 7 && $('#password2').val() === $('#password').val()) {
						$(this).unbind('submit').submit();
					}
				}
				else {
					$('#recaptcha-div').find('p').show();
				}
			});
		});
	</script>
</body>
</html>
<?php $mysql->close(); ?>