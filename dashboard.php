<?php 
require_once('plugins/curl/curl.php');
use \Curl\Curl;

function getPairValue($pair,$type='ask') {
	$curl = new Curl;
	$ticker = (array)$curl->get("https://api.bitfinex.com/v1/pubticker/$pair");
	return $ticker[$type];
}

$iotusd = getPairValue('iotusd','mid');
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>IOTA Wallet v1.0</title>

    <!-- Bootstrap Core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- MetisMenu CSS -->
    <link href="css/metisMenu.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="css/sb-admin-2.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="css/font-awesome.min.css" rel="stylesheet" type="text/css">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
	
	<!-- REQUIRED -->
	<script>
		var encrypted = 'U2FsdGVkX19JYBBUmMAEeyW173AnDw30phNaQETYijrkQGIyMPd4B4CqRRpP4kl2rCb/2qc8EZkUHFKm0STMoHL5Vxlkm1pxsFQEHEDK8bdTf3hx9R3pRZMRWJX43YNugjdikU+uO7EFqm8r7U7N8Q==';
		var iotusd = '<?php echo $iotusd; ?>';
	</script>
</head>

<body>
	<!-- PIN Modal -->
	<div class="modal fade" id="pin" tabindex="-1" role="dialog" aria-labelledby="PINlabel" aria-hidden="true">
		<div class="modal-dialog small-modal">
			<div class="modal-content text-center">
				<form action="<?php echo $_SERVER['PHP_SELF']; ?>" action="post" id="confirm-pin">
					<div class="modal-header">
						<button type="button" id="modal-close-x" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title text-center" id="PINlabel">PIN</h4>
					</div>
					<div class="modal-body">
						<input type="text" id="pin-number" class="form-control text-center" style="width:150px;margin: 0 auto;">
					</div>
					<div class="modal-footer">
						<button type="submit" class="btn btn-primary btn-block">Confirm</button>
					</div>
				</form>
			</div>
			<!-- /.modal-content -->
		</div>
		<!-- /.modal-dialog -->
	</div>
	<div id="overlay" style="display:none;"><img src="img/loading.gif" style="width:128px;" /></div>
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
                <a class="navbar-brand" href="index.html">
					<img src="img/logo-black.png" class="hidden-xs" style="width:28%;margin-top:-10px;" alt="IOTA Wallet v1.0"/>
					<img src="img/logo-black-no-text.png" class="visible-xs" style="width:28%;margin-top:-10px;" alt="IOTA Wallet v1.0"/>
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
                            <a href="index.html" class="text-center" style="color:purple;font-size:1.15em;"><i class="fa fa-money fa-fw"></i> Balance: <span id="balance">0 iota</span></a>
                        </li>
						<li>
							<button type="button" class="btn btn-success btn-block" id="refresh-account" style="border-radius:0px;"><i class="fa fa-refresh fa-fw"></i> Refresh</button>
						</li>
                        <li>
                            <a href="index.html"><i class="fa fa-home fa-fw"></i> Home</a>
                        </li>
                        <li>
                            <a href="transactions.php"><i class="fa fa-bar-chart-o fa-fw"></i> Transactions</a>
                        </li>
                        <li>
                            <a href="tables.html"><i class="fa fa-cogs fa-fw"></i> Settings</a>
                        </li>
						<li>
                            <a href="forms.html" style="color:green;"><i class="fa fa-file-pdf-o fa-fw"></i> Download SEED</a>
                        </li>
                        <li>
                            <a href="forms.html"><i class="fa fa-support fa-fw"></i> Contact support</a>
                        </li>
						<li>
                            <a href="forms.html"><i class="fa fa-sign-out fa-fw"></i> Logout</a>
                        </li>
						<li>
							<button type="button" class="btn btn-info btn-block" style="border-radius:0px;"><i class="fa fa-diamond fa-fw"></i> Donations</button>
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
								Dashboard
								<div class="btn-group pull-right">
									<a href="send.php" class="btn btn-primary">Send</a>
									<a href="receive.php" class="btn btn-success">Receive</a>
								</div>
							</h1>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-8">
						<div class="panel panel-primary">
							<div class="panel-heading">
								Recent activity
								<div class="pull-right">
									<div class="btn-group">
										<button type="button" class="btn btn-default btn-xs dropdown-toggle">
											<i class="fa fa-exchange fa-fw"></i>Switch to USD
										</button>
									</div>
								</div>
							</div>
							<div class="panel-body">
								<div class="list-group">
									<a href="#" class="list-group-item" style="color:green;">
										<i class="fa fa-money fa-fw"></i> Payment received
										<span class="pull-right text-muted small"><em>+12 MIOTA</em>
										</span>
									</a>
									<a href="#" class="list-group-item" style="color:red;">
										<i class="fa fa-money fa-fw"></i> Payment sent
										<span class="pull-right text-muted small"><em>-12 MIOTA</em>
										</span>
									</a>
									<a href="#" class="list-group-item" style="color:purple;">
										<i class="fa fa-wrench fa-fw"></i> Updated settings
										<span class="pull-right text-muted small"><em>Yesterday, 18:56</em>
										</span>
									</a>
									<a href="#" class="list-group-item">
										<i class="fa fa-user fa-fw"></i> User registered
										<span class="pull-right text-muted small"><em>30.08.2017, 12:03</em>
										</span>
									</a>
								</div>
								<!-- /.list-group -->
							</div>
							<div class="panel-footer text-right">
								Panel Footer
							</div>
						</div>
					</div>
					<!-- /.col-lg-8 -->
					<div class="col-lg-4">
						<div class="panel panel-info">
							<div class="panel-heading">
								System notifications
							</div>
							<div class="panel-body">
								<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum tincidunt est vitae ultrices accumsan. Aliquam ornare lacus adipiscing, posuere lectus et, fringilla augue.</p>
							</div>
							<div class="panel-footer">
								Panel Footer
							</div>
						</div>
					</div>
				</div>
				<!-- /.col-lg-12 -->
				<div class="row">
					<div class="col-lg-12">
						<footer class="text-center">
							<p>&copy; Company 2017</p>
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
    <script src="js/jquery.min.js"></script>
	
	<!-- AES encryption algorithm -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.2/rollups/aes.js"></script>
	
	<!-- JS Cookie -->
	<script src="js/js.cookie.js"></script>
	
	<!-- IOTA.js Library & Helper functions -->
    <script src="js/iota.min.js"></script>
	<script src="js/iota.helper.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="js/bootstrap.min.js"></script>
	
	<!-- Moment.js Plugin JavaScript -->
    <script src="js/moment.min.js"></script>

    <!-- Metis Menu Plugin JavaScript -->
    <script src="js/metisMenu.min.js"></script>
	
	<!-- Masked Input -->
	<script src="js/jquery.maskedinput.min.js"></script>

    <!-- Custom Theme JavaScript -->
    <script src="js/sb-admin-2.js"></script>
	
	<!-- Custom JavaScript -->
    <script src="js/custom.js"></script>
	
	<script>
		function formatBalance(iotas) {
			var sizes = ['iota', 'Ki', 'Mi', 'Gi', 'Ti'];
			if (iotas == 0) return '0 iota';
			var i = parseInt(Math.floor(Math.log(iotas) / Math.log(1000)));
			return Math.round(iotas / Math.pow(1000, i), 2) + ' ' + sizes[i];
		}
		
		$(function() {
			var accountData = Cookies.getJSON('userID');
			if (accountData) { //accountData not empty, undefined etc.
				// global
				$('#balance').html(formatBalance(accountData.balance));
				
				$('#refresh-account').click(function() {
					$('#pin').modal({
						backdrop: 'static',
						keyboard: false
					});
				});
			}
			else {
				$('#modal-close-x').hide();
				$('#pin').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
		});
	</script>
	
</body>

</html>
