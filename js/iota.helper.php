<?php
require('../config.php');
$mysql = new mysqli($dbhost,$dbuser,$dbpass,$dbname);
$mysql->query("SET NAMES 'utf8'");

function isHTTPS() {
  return
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || $_SERVER['SERVER_PORT'] == 443;
}

function getDonationAddress() {
	global $mysql;
	$result = $mysql->query("SELECT donation_address FROM system WHERE id='1'")->fetch_assoc();
	return $result['donation_address'];
}

function getRandomNode($type=null) {
	global $mysql;
	if (!empty($type)) {
		$query = $mysql->query("SELECT id,name,url,port FROM nodes WHERE type='$type' AND status='1'");
	}
	else {
		$query = $mysql->query("SELECT id,name,url,port FROM nodes WHERE status='1'");
	}
	$nodes = array();
	$result = array();
	$i = 1;
	while ($row = $query->fetch_assoc()) {
		$nodes[$i]['url'] = $row['url'];
		$nodes[$i]['port'] = $row['port'];
		$i++;
	}
	$k = array_rand($nodes);
	$result['url'] = $nodes[$k]['url'];
	$result['port'] = $nodes[$k]['port'];
	return $result;
}

if (isHTTPS()) {
	$node = getRandomNode('https');
}
else {
	$node = getRandomNode();
}

$donationAddress = getDonationAddress();

$server = "var iota = new IOTA({'provider':'".$node['url'].":".$node['port']."'});";

$js = "var latestAddress = null, balance = null, inputs = {}, addresses = {}, transactions = {}, account = {}, reload = true, redirect = null, receiver = null, money = 0, donation = 0, message = {}, hash = null, seed = null, command = null, latestValidAddress = null, unit = null, override = false, autoUpdateInterval = null, scriptTimeStart = 0, scriptTimeEnd = 0, donationAddress = '$donationAddress';";

$js .= "
override = true;
const curl = window.curl;
const MAX_TIMESTAMP_VALUE = (Math.pow(3,27) - 1) / 2; // from curl.min.js
curl.init();
const localAttachToTangle = function(trunkTransaction, branchTransaction, minWeightMagnitude, trytes, callback) {
	const ccurlHashing = function(trunkTransaction, branchTransaction, minWeightMagnitude, trytes, callback) {
		const iotaObj = iota;

		// inputValidator: Check if correct hash
		if (!iotaObj.valid.isHash(trunkTransaction)) {
			return callback(new Error('Invalid trunkTransaction'));
		}

		// inputValidator: Check if correct hash
		if (!iotaObj.valid.isHash(branchTransaction)) {
			return callback(new Error('Invalid branchTransaction'));
		}

		// inputValidator: Check if int
		if (!iotaObj.valid.isValue(minWeightMagnitude)) {
			return callback(new Error('Invalid minWeightMagnitude'));
		}

		var finalBundleTrytes = [];
		var previousTxHash;
		var i = 0;

		function loopTrytes() {
			getBundleTrytes(trytes[i], function(error) {
				if (error) {
					return callback(error);
				} else {
					i++;
					if (i < trytes.length) {
						loopTrytes();
					} else {
						// reverse the order so that it's ascending from currentIndex
						return callback(null, finalBundleTrytes.reverse());
					}
				}
			});
		}

		function getBundleTrytes(thisTrytes, callback) {
			// PROCESS LOGIC:
			// Start with last index transaction
			// Assign it the trunk / branch which the user has supplied
			// IF there is a bundle, chain  the bundle transactions via
			// trunkTransaction together

			var txObject = iotaObj.utils.transactionObject(thisTrytes);
			txObject.tag = txObject.obsoleteTag;
			txObject.attachmentTimestamp = Date.now();
			txObject.attachmentTimestampLowerBound = 0;
			txObject.attachmentTimestampUpperBound = MAX_TIMESTAMP_VALUE;
			// If this is the first transaction, to be processed
			// Make sure that it's the last in the bundle and then
			// assign it the supplied trunk and branch transactions
			if (!previousTxHash) {
				// Check if last transaction in the bundle
				if (txObject.lastIndex !== txObject.currentIndex) {
					return callback(new Error(\"Wrong bundle order. The bundle should be ordered in descending order from currentIndex\"));
				}

				txObject.trunkTransaction = trunkTransaction;
				txObject.branchTransaction = branchTransaction;
			} else {
				// Chain the bundle together via the trunkTransaction (previous tx in the bundle)
				// Assign the supplied trunkTransaciton as branchTransaction
				txObject.trunkTransaction = previousTxHash;
				txObject.branchTransaction = trunkTransaction;
			}

			var newTrytes = iotaObj.utils.transactionTrytes(txObject);

			curl.pow({trytes: newTrytes, minWeight: minWeightMagnitude}).then(function(nonce) {
				$('.loader').attr('data-text','Preforming Proof of Work...');
				var returnedTrytes = newTrytes.substr(0, 2673-81).concat(nonce);
				var newTxObject= iotaObj.utils.transactionObject(returnedTrytes);

				// Assign the previousTxHash to this tx
				var txHash = newTxObject.hash;
				previousTxHash = txHash;

				finalBundleTrytes.push(returnedTrytes);
				callback(null);
			}).catch(callback);
		}
		loopTrytes()
	}
	ccurlHashing(trunkTransaction, branchTransaction, minWeightMagnitude, trytes, function(error, success) {
		if (error) {
			$('.loader').attr('data-text',error);
		} else {
			$('.loader').attr('data-text','Got Proff of Work!');
		}
		if (callback) {
			return callback(error, success);
		} else {
			return success;
		}
	})
}

// curl.overrideAttachToTangle(iota.api);
// using this because of bug with using curl.overrideAttachToTangle()
iota.api.attachToTangle = localAttachToTangle;

function showNodeInfo() {
	$('.loader').addClass('is-active');
	iota.api.getNodeInfo(function(e,node) {
		if (e) {
			var content = '<div><div class=\"alert alert-danger text-center\">Node is offline!</div></div>';
			var circleColor = '#a94442';
		}
		else {
			if (node.latestMilestoneIndex == node.latestSolidSubtangleMilestoneIndex) {
				var alert = '<div class=\"alert alert-success text-center\">Node is online and synced!</div>';
				var milestoneColor = '#3c763d';
				var circleColor = '#3c763d';
			}
			else {
				var alert = '<div class=\"alert alert-warning text-center\">Node is online, but not synced!</div>';
				var milestoneColor = '#8a6d3b';
				var circleColor = '#8a6d3b';
			}
			var content = '<div>';
				content += alert;
				content += '<p class=\"node-info\"><span>IRI Version:</span><span class=\"pull-right\"><b>'+node.appVersion+'</b></span></p>';
				content += '<p class=\"node-info\"><span>Latest Milestone Index:</span><span class=\"pull-right\" style=\"color:'+milestoneColor+';\"><b>'+node.latestMilestoneIndex+'</b></span></p>';
				content += '<p class=\"node-info\"><span>Latest Solid Subtangle Milestone Index:</span><span class=\"pull-right\" style=\"color:'+milestoneColor+';\"><b>'+node.latestSolidSubtangleMilestoneIndex+'</b></span></p>';
				content += '<p class=\"node-info\"><span>Neighbors:</span><span class=\"pull-right\"><b>'+node.neighbors+'</b></span></p>';
				content += '<p class=\"node-info\"><span>JRE Version:</span><span class=\"pull-right\"><b>'+node.jreVersion+'</b></span></p>';
				content += '<p class=\"node-info\"><span>JRE Max. Memory:</span><span class=\"pull-right\"><b>'+node.jreMaxMemory+'</b></span></p>';
				content += '<p class=\"node-info\"><span>JRE Free Memory:</span><span class=\"pull-right\"><b>'+node.jreFreeMemory+'</b></span></p>';
				content += '<p class=\"node-info\"><span>JRE Total Memory:</span><span class=\"pull-right\"><b>'+node.jreTotalMemory+'</b></span></p>';
				content += '<p class=\"node-info\"><span>JRE Available Processors:</span><span class=\"pull-right\"><b>'+node.jreAvailableProcessors+'</b></span></p>';
				content += '<p class=\"node-info\"><span>Tips:</span><span class=\"pull-right\"><b>'+node.tips+'</b></span></p>';
				content += '<p><span>Transactions to request:</span><span class=\"pull-right\"><b>'+node.transactionsToRequest+'</b></span></p>';
			content += '</div>';
		}
		showBSModal({
			title: '<i class=\"fa fa-circle\" style=\"color:'+circleColor+';\"></i> ".$node['url'].":".$node['port']."',
			body: content
		});
		$('.loader').removeClass('is-active');
	});
}

function isEmpty(obj) {
    for (var prop in obj) {
        if (obj.hasOwnProperty(prop))
            return false;
    }
    return JSON.stringify(obj) === JSON.stringify({});
}

function openInNewTab(url) {
	var win = window.open(url, '_blank');
	win.focus();
}

function isSeedValid(seed) {
	if (/^[A-Z9]+$/.test(seed) && seed.length === 81) {
		return true;
	}
	else {
		return false;
	}
}

function session(obj) {
	/* Store data in SESSION array */
	var i = 1;
	var length = Object.keys(obj).length;
	for (var key in obj) {
		if (obj.hasOwnProperty(key)) {
			$.post('ajax.session.php',{
				'key':key,
				'value':obj[key]
			}, function(result) {
				if (i === length) {
					if (reload === true) {location.reload();}
					if (redirect !== null) {window.location = redirect;}
				}
				else {
					i++;
				}
			});
		}
	}
}

function updatePOWtime(time) {
	/* Store average PoW time */
	if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
		var device = 'mobile';
	}
	else {
		var device = 'desktop';
	}
	$.post('ajax.pow-time.php',{time:time,device:device},function(result){
		return true;
	});
}

function getAccountInfo(seed) {
	if (command == 'account-info') {
		$('.loader').attr('data-text','Getting account info, this may take few minutes...');
	}
	iota.api.getAccountData(seed,function(e,accountData) {
		if (e) {
			$('.loader').attr('data-text',e);
		}
		else {
			if (command == 'account-info') {
				$('.loader').attr('data-text','Getting transactions...');
				$(window).off('beforeunload');
			}
			transactions = iota.utils.categorizeTransfers(accountData.transfers,accountData.addresses);
			account['latestAddress'] = accountData.latestAddress;
			account['balance'] = accountData.balance;
			for (var i = 0; i < accountData.inputs.length; i++) {
				inputs[accountData.inputs[i]['address']] = accountData.inputs[i]['balance'];
			}
			account['inputs'] = inputs;
			for (var i = 0; i < accountData.addresses.length; i++) {
				addresses[i] = accountData.addresses[i];
			}
			account['addresses'] = addresses;
			if (command == 'send-money') {
				$('.loader').attr('data-text','Everything done!');
				$(window).off('beforeunload');
			}
			if (command == 'send-donation') {
				$('.loader').attr('data-text','Everything done. Thank you!');
				$(window).off('beforeunload');
			}
			if (command == 'reattach') {
				$(window).off('beforeunload');
			}
			session({'transactions':transactions,'accountData':account,'latestValidAddress':accountData.addresses.slice(-1)[0]});
		}
	});
}

function genAddress(seed) {
	$('.loader').attr('data-text','Generating new address...');
	iota.api.getNewAddress(seed, {checksum: true}, function(e,address) {
		if (!e) {
			$('.loader').attr('data-text','Attaching address to Tangle, this may take few minutes...');
			latestValidAddress = address;
			sendTransfer(seed,address,0,null,null);
			return address;
		}
		else {
			$('.loader').attr('data-text',e);
		}
	});
}

function sendTransfer(seed,address,value,message,tag) {
	var messageTrytes = iota.utils.toTrytes(JSON.stringify(message));
	var tagTrytes = iota.utils.toTrytes(tag);
	var transfer = [{
		'address': address,
		'value': parseInt(value),
		'message': messageTrytes,
		'tag': tagTrytes
	}];
	if (command == 'send-money') {
		$('.loader').attr('data-text','Starting transfer, this may take 5-10 minutes...');
		powTimeStart = new Date().getTime();
	}
	if (command == 'send-donation') {
		$('.loader').attr('data-text','Sending donation, this may take few minutes...');
		powTimeStart = new Date().getTime();
	}
	iota.api.sendTransfer(seed,4,14,transfer,function(e) {
		if (e) {
			$('.loader').attr('data-text',e);
		} else {
			if (command == 'generate-address') {
				$('.loader').attr('data-text','Address attached to Tangle!');
				$(window).off('beforeunload');
				session({'latestValidAddress':latestValidAddress});
				setTimeout(function() {
					$('#iota-address').val(latestValidAddress);
					$('.loader').removeClass('is-active');
					$('#receive').modal('toggle');
				},2500);
			}
			if (command == 'send-money') {
				powTimeEnd = new Date().getTime();
				var powTime = powTimeEnd - powTimeStart;
				updatePOWtime(powTime);
				$('.loader').attr('data-text','Money sent! Updating your account info...');
				getAccountInfo(seed);
			}
			if (command == 'send-donation') {
				powTimeEnd = new Date().getTime();
				var powTime = powTimeEnd - powTimeStart;
				updatePOWtime(powTime);
				$('.loader').attr('data-text','Donation sent! Updating your account info...');
				getAccountInfo(seed);
			}
		}
	});
}

function reAttach() {
	powTimeStart = new Date().getTime();
	$('.loader').attr('data-text','Re-attaching transaction, this may take up to 5 minutes...');
	iota.api.replayBundle(hash,4,14,function(e,data) {
		if (e) {
			$('.loader').attr('data-text',e);
		} else {
			$('.loader').attr('data-text','Transaction re-attached! Updating transactions...');
			powTimeEnd = new Date().getTime();
			var powTime = powTimeEnd - powTimeStart;
			updatePOWtime(powTime);
			getAccountInfo(seed);
		}
	});
}
";

$mysql->close();
header('Content-Type: application/javascript');
echo $server;
echo $js;
?>