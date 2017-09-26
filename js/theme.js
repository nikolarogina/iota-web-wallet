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
	// salt, iv will be hex 32 in length
	// append them to the ciphertext for use in decryption
	var transitmessage = salt.toString()+ iv.toString() + encrypted.toString();
	return transitmessage;
}

function decryptAES(transitmessage,pin) {
	var salt = CryptoJS.enc.Hex.parse(transitmessage.substr(0, 32));
	var iv = CryptoJS.enc.Hex.parse(transitmessage.substr(32, 32))
	var encrypted = transitmessage.substring(64);
	var key = CryptoJS.PBKDF2(pin, salt, {
		keySize: keySize/32,
		iterations: iterations
	});
	var decrypted = CryptoJS.AES.decrypt(encrypted, key, { 
		iv: iv, 
		padding: CryptoJS.pad.Pkcs7,
		mode: CryptoJS.mode.CBC
	})
	return decrypted;
}

$(function() {
    $('#side-menu').metisMenu();
});

//Loads the correct sidebar on window load,
//collapses the sidebar on window resize.
// Sets the min-height of #page-wrapper to window size

$(function() {
    $(window).bind("load resize", function() {
        var topOffset = 50;
        var width = (this.window.innerWidth > 0) ? this.window.innerWidth : this.screen.width;
        if (width < 768) {
            $('div.navbar-collapse').addClass('collapse');
            topOffset = 100; // 2-row-menu
        } else {
            $('div.navbar-collapse').removeClass('collapse');
        }

        var height = ((this.window.innerHeight > 0) ? this.window.innerHeight : this.screen.height) - 1;
        height = height - topOffset;
        if (height < 1) height = 1;
        if (height > topOffset) {
            $("#page-wrapper").css("min-height", (height) + "px");
        }
    });

    var url = window.location;
    // var element = $('ul.nav a').filter(function() {
    //     return this.href == url;
    // }).addClass('active').parent().parent().addClass('in').parent();
    var element = $('ul.nav a').filter(function() {
        return this.href == url;
    }).addClass('active').parent();

    while (true) {
        if (element.is('li')) {
            element = element.parent().addClass('in').parent();
        } else {
            break;
        }
    }
});
