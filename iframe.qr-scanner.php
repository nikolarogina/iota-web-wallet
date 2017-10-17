<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="IOTA web wallet user area">
    <meta name="author" content="Nikola Rogina">
    <title>QR Code Scanner | IOTA Web Wallet</title>
	<link href="css/loader-default.css" rel="stylesheet">
	<style type="text/css">
		.loader.is-active {
			background-color:rgba(0,0,0,0.99);
		}
	</style>
</head>
<body>
	<div class="loader loader-default is-active"></div>
	<video id="qr-preview-video" style="display:none;width:100%;object-fit:cover;"></video>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<script>window.jQuery || document.write('<script src="js/jquery.min.js"><\/script>')</script>
	<script src="js/instascan.min.js"></script>
	<script>
	$(document).ready(function() {
		let scanner = new Instascan.Scanner({video:document.getElementById('qr-preview-video'),backgroundScan:false,mirror:false});
		$('.loader').fadeOut(function() {
			$('#qr-preview-video').show();
		});
		scanner.addListener('scan', function (content) {
			window.parent.readQRcode(content);
		});
		Instascan.Camera.getCameras().then(function(cameras) {
			if (cameras.length > 0) {
				if (cameras[1]) {
					scanner.start(cameras[1]);
				}
				else {
					scanner.start(cameras[0]);
				}
			} 
			else {
				alert('No cameras found!');
				window.parent.$('#modal').modal('toggle');
			}
		}).catch(function (e) {
			console.error(e);
		});
	});
	</script>
</body>
</html>