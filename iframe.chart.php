<?php
require_once('plugins/curl/curl.php');
use \Curl\Curl;

function getHistoricalData($pair,$datetime) {
	$curl = new Curl;
	$currency = explode('/',$pair);
	$timestamp = strtotime($datetime);
	$url = 'https://min-api.cryptocompare.com/data/pricehistorical?fsym='.$currency[0].'&tsyms='.$currency[1].'&ts='.$timestamp;
	$result = json_decode(json_encode($curl->get($url)),true);
	return array($currency[0].'/'.$currency[1]=>$result[$currency[0]][$currency[1]]);
}

function getChartData($pair='IOT/USD') {
	$result = array();
	$now = time();
	$monthAgo = strtotime('-1 month',$now);
	for ($i = $monthAgo; $i <= $now; $i = $i+86400) {
		$date = date('Y-m-d',$i);
		$result[$date] = getHistoricalData($pair,$date);
	}
	return $result;
}

$chart = getChartData();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="IOTA web wallet user area">
    <meta name="author" content="Nikola Rogina">
    <title>IOTA Price Chart | IOTA Web Wallet</title>
	<!-- Morris Charts CSS -->
    <link href="plugins/morrisjs/morris.css" rel="stylesheet">
</head>
<body>
	<div id="price-chart"></div>
	<!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<script>window.jQuery || document.write('<script src="js/jquery.min.js"><\/script>')</script>
	<!-- Morris JS Chart -->
	<script src="plugins/raphael/raphael.min.js"></script>
	<script src="plugins/morrisjs/morris.min.js"></script>
	<script type="text/javascript">
		function convertDate(inputFormat) {
			function pad(s) {return (s < 10) ? '0' + s : s;}
			var d = new Date(inputFormat);
			return [pad(d.getDate()), pad(d.getMonth()+1), d.getFullYear()].join('.');
		}
		
		$(function() {
			Morris.Line({
				element: 'price-chart',
				data: [
				<?php 
					$chartData = array();
					foreach ($chart as $date => $pair) {
						$price = array_pop(array_reverse($pair));
						$chartData[] = "{y:'$date',a:$price}";
					}
					echo implode(',',$chartData);
				?>
				],
				xkey: 'y',
				ykeys: ['a'],
				preUnits: '$',
				xLabelFormat: function (x) {return convertDate(x)},
				labels: ['<?php echo key($pair); ?>']
			});
		});
	</script>
</body>
</html>