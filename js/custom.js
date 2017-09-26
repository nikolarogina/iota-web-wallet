function chunk(str,n) {
	// Function to group SEED in .pdf in groups of 4 characters
	var ret = [];
	var i;
	var len;
	for(i = 0, len = str.length; i < len; i += n) {
	   ret.push(str.substr(i,n));
	}
	return ret;
}

function downloadSeed() {
	$('#command').val('download-seed');
	$('#pin').modal({
		backdrop: 'static',
		keyboard: false
	});
}

function formatBalance(iotas) {
	var sizes = ['iota', 'Ki', 'Mi', 'Gi', 'Ti', 'Pi'];
	if (iotas == 0) return '0 iota';
	var i = parseInt(Math.floor(Math.log(iotas) / Math.log(1000)));
	return Math.round(iotas / Math.pow(1000, i), 6) + ' ' + sizes[i];
}

function thankYou() {
	showBSModal({
		title: 'Thank you!',
		body: '<div class="alert alert-success text-center">Thank you for your donation. This means a lot.<br /><br /><b>Every single iota colleted through donations will be used to buy dedicated servers in order to speed up and improve this web wallet!</b></div>'
	});
}

function autoTxUpdate() {
	// Auto-updating transactions when "auto-monitor" is switched ON
	reload = true;
	var pin = sessionStorage.getItem("PIN");
	getAccountInfo(decryptAES(encrypted,pin).toString(CryptoJS.enc.Utf8));
}

function generatePDF(seed) {
	$('.loader').attr('data-text','Generating PDF, please wait...');
	var doc = new jsPDF();
	// generate QR code
	new QRCode(document.getElementById("qrcode"), {
		text: seed,
		width: 240,
		height: 240,
		colorDark : "#000000",
		colorLight : "#ffffff",
		correctLevel : QRCode.CorrectLevel.H
	});
	// wait 3 seconds, then generate .pdf
	setTimeout(function() {
		var QRcode = $('#qrcode').find('img').attr('src');
		var logo = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAoAAAAJgCAYAAAAaidKhAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAF4NSURBVHhe7b1N1iTHdaYpoCY9Q2kFEDfQKM560gfivM+htAKKK2BpBRRXwK4VoDDrWVEroDDrmYQVqDDrYWEFan8yPmdGRlrEFx5uP/eaPc8574lIIDPCw93c7LV7r5n/lYiIiPzVf9n0T5v+bdN/VNS/bPqvm/5mk4iIiIgM5j9vwpzVNn33hBn8h00iIiIi0hmMH9G+/7WpZNRa639u4vs5DhERERFpyGjjdyuOw4igiIiISCP+bhORt5IRGy1S0NQgioiIiEgFiPr9aVPJeEUT0UkREREROQFRtahRv3siGmhtoIiIiMgLUFsXpdbvqDhuU8IiIiIiB8D8lYxVJmEC/3aTiIiIiLwD+/qVDFVWuUpYRERE5AEzRP5KMh0sIiIiUmBW84esCRQRERG5AXNUMk4zidXMrg4WERER2cAUZV3te1Q8S1ikCv/p7VVERCQj/8+mby5vp+dvNv286f/98CcRERGRBZltxe+zwgiKiIiILMdKqd9bmQoWERGRJfnvm0rmaBX93SYRERGRZSAFWjJFK4lVwSIv8+Xbq4iISBb+6e11Zb7e5FNC5GW+eHsVERHJANG/f7+8XZ6fNrkgRF7CCKCIiGSClb9ygSjg317eihzDfQBFRCQT7Pv3v13eygaZvD9d3oqIiIjMBytfSwsiVhZb4fiIODmMKWAREcmCW598zlebTAPLYTSAIiKSBQ1gGc+LHEYDKCIiGfgvm4h2yecYAZTDaABFRCQDmpz7sBrY7WDkEBpAERHJABFAuY/nRw6hARQRkQwY4XqMBlAOoQEUEZEMfPv2KmU0yHIIDaCIiEh+NIByCA2giIhEx/SmSGU0gCIiEh2fdPE+psjlEBpAERERkcXQAIqIiIgshgZQREREZDE0gCIiIiKLoQEUEZHo/K+3V7nPD2+vIk+hARQRkej829uriFRCAygiIhn4+e1VyvzPt1eRp9AAiohIBowCPkYDKIfQAIqISAY0gI/5l7dXkafQAIqISAY0gI/x/MghNIAiIpIBI1z3+WmTK6XlEBpAERHJADVuGB35nD+9vYo8jQZQRESyoNEpY3RUREREpuVvN/2H+kSmfuUljACKiEgWiHSZBv4Uo6LyEv/p7VVERCQD/3kTkUC58NtN/9/lrYiIiMic/M2mUip0RVn7JyIiIsvw3zeVDNFqMhIqIiIiy2AU0OifnMQaQBERyQYrX/960//x4U9rQu2fz/8VERGRpWAxCEawFB2bXa78FRERkWX5u00lgzSzML2YXxEREZFlIRpWMkqzCtMrIiIisjREw6iFK5ml2cTqZ5EqfPH2KiL12bdouN2qYV+95yo+kTr8l03cT199+NOc/LiJvsRHv4mIBINB6P/e9G+bSrP3kvi7/Bv+rYi8zj9sKt1jM8i6PxGRgDDw1EhB8Rl8loi8xowmEPPnBFFEJBC1jN+tNIIirzOTCdT8iYgEgqcQUG9U6rBriu8w7SNynBlMoOZPRCQQbMFAx1zqsFuI7/J5nyLH6X2v1hS1wU7+RESCMDKqYEpY5DhE0I4syoogtnrR/ImIBCFCSimqCSRC+U+bSFmXaiKJwvD/WO3sJrbSG8wUbe+2XUYT94n3h4hIICLVE0UZIKiDJFLxSoqNf4Nh5DNEesFEpcWirRriaSZG/UQkDXvkh86rFP3hz/x3/j9/L2MtG8d8/ZtGC/M00jjtxq90bK+IduHAJ72grdHmXpm4tBD9Y8Z+UUQWg86TaNjZZ2/y7/mc6AM/xxdloLgWNU0j+K+bWpwPJgoOgtKT0UYQ4xe1pENE5C+cSfc9Ep/H50ZNBdaMdNUWZqwXDJZnTf8zckCU3uyT2h4LRfb+zsmOiISHzrFX8TQdI98XhWip31sxmPQ4X3xHz1WUPY2tyDVMRGl/ROdKbfMVEd2mb3Nxh4ikYcQeWnxflI6y5iDQSqSwWtLb/O0yEigRYBKIIcTA0R+8t4CEv4O4L2nDUTMbIiJFGPRHpz5HRwOjR/92tY4CjjTBpslEREQ6MSriUxLHMcoEjjbAR9QqWkYUo/R9vdQrxS0iIrI07Jj/XoqjtzABI56F2Tv1fUYszqgN57z0Xb2FERcREZFGUKsS1fRwXD1raahBLB1HZNUmUv2jdVQiIg348u1V1oU0G1Gkrz78KR4cF8fXKx04IuJ4lpr1cnzWt5e3IWi90EVERGRJeuzvVkNEpXoQKfr1rGpunRKxPVgLKCIiUhGMQ2nAjaoee8Rlqv/bVStKhtEqff5ouS2MiIhIJaitKg220dW6Jqz0ndFVKzqK0Sp9/mi1WOgiIrI01gCuS9YVlq4MbUfU+kf3BBQRqYwGcE2iFfofgePWELQhqgFkIVDv1cCkw2lnyBpEERGZgowLHa7VckFI6fuiq9b5iLYP5LVamn4MHtv/EF1+tBE6/49nY/tcVxERSQcDaWlwy6ZW0arSd0VXLQNY+uwoamEAiSpi+l5Z+INZdnGKiIikgQGvNKBlU6tawJVXAZc+O4pqG8Baj7rDCLaMToqIiFQho8Epid/Rgozp8Vrb45Q+O4pqmSwixy2ed91jiyIRkWq4CGQtqF2K+sSPo/A7WkReMAfZqHXMP729zgrmD4P/zYc/1eWPm1yhLiJp0ACuxWypqha/p+UCkxb8vKnmIpConD223fy1nAD9ZpOPrhMRkXBkTG8+UguzFvVpGPdUc5NkVriWviOCzsA17bnC2VXCIiISitJglVmt6gAzLZSpuRIV41L6jtE6a/R7P9+YdunegSIiEoKsj357Ty0gtVz6rmiqbYCjRj/PLLAYdS1NBYuISAiymJqjarUfYIZ0eQuT0Tta9ozOPAVk1HU0CigiIiGY1QC2WAgC0c9XK4MR7XefqXEcHfV2axgRCYurgEXKEDn64fI2JJiLFjWQ0X43C1NeZfSTOnxSiIiIDMcI4HGIIGGySt87UmcXRbxHlLZydoVzhDS+aWARERmKBvA1oq2M7VVbNnpLGH7nmdo/KH1ub7VunyIiIg+Z1QD2MEO1nht7VpiiVotebuG8tnhk2rM6mz7l+Euf21vWAYqIyHBKA1R29SLC3oC9NxjGbI5IgZ+p+9uJMuFxOxgRERlOxHq2M+r93N5RaVGu26hUYm8TWOt5uhpAEZEHuAp4LXobptb0/j2k8367iefv9uLHTZiZ1gs/7sE55vt7/OY/bHLlrIiISGWi1LLV0iizQFSsR30cEccoq0g5jlaraokw1k5vWwMoIvKAL95eZQ0wLv96eTsFv9jEQ/5HgQHFpH314U/1YB8+zPqoqN8jav/m7ze12tMQAzaaX22KeB2lPkTKWbmO6GvvTd5oD7R3JpGoRdsXEfkMDNNtlCKjeqd/70EnjymqcV7Z925Urd8R+M0Y1Fd/MwMetX5nt3l5j1YRyyO6ZwIkP7RfJi9n2xl9GZOqDPe+iCRmljRwxNQas/49clc65lthoDB9GMisRoHULYPXeylx/j+mr+dvpY2UjqWXokxSpB77hO+99v6q6BPoQ1pPjkRMAS8IHcu/X96mhQUJ/I7oqROO8V5HzgAyY+rn9jczoKERcBwj2/o/bsIcS34wfkwoUO2Sj3tQHrFH2kVEqhBhT7szolMUeYZRaWDMfY1I5x5xwkjyW/bfwwSC99zLmJJ7Ew05D+ef63l7jXuJa98rai4ik8NgUepoMqjWwCprQF1VqR211tlJCuUERydqGELryOpBPzlqAnEr+r3eG8GLyKQwqyx1NNFl9E+OQp1lqS210plJCv/u7L3J73WSdA7M1sio3z1ZUiAip2GAoLak1MlElUX18gq09Z6D+auRmpr7S/J7+Tw5TvTJMW1Egy8ipxiVHntVDmjyKrSdUpuqrVcj1BxfbZOqCTxOlvpoTKB1nyJyCgasUgcTTRRii5yBNlRqW7WEeXgFojm1In+3wgQaLXqObIvjuLaaQBE5RfSO79WBVeQWot61I23oTG1q6xpFPl8ek8387TIdLCKnidoBav6kNjVXd1JHe2blba8yDFcH3ydLFuSeMIEzQptF1NRyjXbxZ/675Q0iFYlmAjV/0hJSwq8uhCKKyGB0NvrSa5sRvkc+BzNROl/ZlL2vZFLGfpZEq4/ek/x9fj/3sylxkRNEmQ3TGYj0gGgCA8gzAw8DFANNjbRbr4Upu4yYfArXsEU5wCi9uvp8FLvpe3USdk9ERPlcU+MiL0BHMqpjpDMwXSWjYFC6TjsxkLRKN/HZpXuglfg98pHWtZe9RZ+dwfTsE67Sb6gtvseooMhB6Eh63aS7fOSRrETvp0yYBv4IJqR0jrKLPjsqGLFRppvz4tgichA6ytYDFZ9vekpWo3eUne+TC7XTjpEUsS+NUFpE+7e0SOQF6FSYRdUatPgcPk/jJ6tSui9aS9rvBzlakSK9RP1a7XH5qjg/RgNFXoAbh/oozNvRWTQdAWnebMXKIi0o3SOtJXNH/3ZFqHsbWUv+njiu5YMPX7y9irwKhpAbaX+9BdPHzWb9kcinMBD1ZvU+n5KWP1/eTs33m4h0joLv/u7yNjS/3RS5blJERCakd3SE71sdBvvSuZlNXOtRac5sKfaRRllERBak9eKqW60ehccQlc7LrBphbLKZv11LmsAv315FRKQvvQ3Z6gaQ9O9K9P69mKgMad8SHPdq7UNERAZBzWwpGtFKq2+Iu0r6d1fPlD9tuXdJQ21x/G4aLSIiXeiVBl49+gcrrP69VY+VrqTWZzm3LFoUERFpDmmn0kBUW6unt1ar/9vVo7aNrb1K351VPjJRRES60PrxWHz+6vQy2tHU2szMel57RE5FZGGoN2GGTm3So1Qg/49BnMcY2THNR8sUGimtUduBRCLr6tSzap3671XC0FuWTIhIExiMznScmAVm9g7s89CiiJ7Pc8JwIcKzaEeopZGZ3VS7KlhEqkGHWTvSQ/RQIzgHNZ+byudo/j6iAazP7ItqjAKKyGmYSbbsLIn0kB6W/GDmzxbVUy5wdlKAeaTdXivzRGNVA0jf0ALaQ+n7ZhO/U0TkJXoOPMxYjQbOAQPP0TIB/v6ZAYt/S0T5USqayCJtOtt+aasaQNSCVfZU5HeKiBwCI9Z6dWdJRBpN/c0DRovoLm3pNorMn/fFQWcMGf/2qNlEmKosE45VDSBtpDZc89J3zSon1SLyNHQYtWq5XlH04n8MB/WQDMr7CmiEmeG/8f80sX0g6vco4veeaOcZooGrGkDuq9rMvvjjVvxeEZF3GW3+dkUzgRwLtW1HaiH5DRhEzWAb/m5T6bwfVfQJB2gA67FK+nfXtGngL95eRXrAIEG04NFggXnCJPCaEaJYv768Hc6Pm/YIzyj4fgbfbz/86XV+2MTntBjQVoT7kHvsqw9/Ok+EtvYIojg88H81vt9UO4JF//z15e0S/LQpQ5RbJBTcNHv9Umlm9Z5q1Df1JGKUgXM4AiKhr173R2I2bk3OeTDSpfN7RkR4o4I5LR3z7KJPqgn3Xul7ZpcGUORJSC3VHmAwE3xuVIhqlo47gnqft7N1Ze+JCIRp4ddpWcMVdaBc1bjUvvdXNdL8bhF5AJ3NkRqvV8TnR7wZI9T93RPnrFfUrFeBeIa6s6i0iP7tihwFbN03RVRtQ05GpvQ9s6t2JFVkGuhkWg4qJfF9UaINvUzPGfXowHqfB03gcbhnSueyljBZUVlt8UKLaxGxzKWHNIAiBRj0W6b7Honv5ftHkyGywLlqySgTrAk8BlH60nmsqajXI8NEraZaRGNXM9G7CDiIyBVROoORy/QzDSqtzDIDfun7einrivER9IjgRK2XWq0OsEXtb+9MTxRNaQC/fHsVOQIdKabrNx/+NB6Ogxu0V53bNb0XWJyh1bGONODwzSZTNHGIGgEkWvzPl7fTw9Ylo3YAkCRoAOUV6FiimL8d9pnrbQL5rih7/j1Di2PFeGHARvP7TVFXoK7GiInYs0RepFKT0ZMySYAGUI5Cx3J2U99WYER6dnwZtwaoecwM9KwKjIJRwBhETpdxbETHZmcVoysn0ADKERhgo0X+biHK1avzy7j4oKYBpKaw1pMkakCKO3L0KQI9Vum2XnB0ltknCjz9o9U1iLzKuyXR27RIUzAOpeLYqOpRm0c0ofTdkVUzQhpx78NWC11mofU2MFkGygwr918R57/lJAjzXPre2TXlpMEIoDwDHUq2mhKOt3U0KGO0qVadHJ8TofbvlkyLckaA8WmZAs2y8GDWiQLZj5YmfNVImBFAWZass77WprX0ndFVqz4r6vY3dtTv0/LaZaqLbfGs6pHqsR1StkxQLWVq1yLVaJ0yaq2WK0NL3xddtQwgkYbS50dQy2s+Cy1SoNm2HSGCz4Sh9Fuyid/Rqya59P2zK2O2511MAct7ZK99mL3gexSRF8D0MICkmmlbGOqS+H+R09G1U6A/b8qWVsU0zVIywGr8Xhui//j2ugr8XjMLshzMekqzoWxqZQgyRg8wJzXgc0qfH0GttqYhDURZQek774k2wr+JGJWslQruGX1qAe2l9LuyqHd9duTofwu5pY4sSfaOcVerGziyCbqnFQxg7agv5q3G76UdRkslnTWB2c3fzlFjH0W9zR+sVgdo/Z8sScRtPl5Rq72rMhaR1zJHqxjA2pMg2mI0w8QA90pNIG1gpnrLbCZwhPnbaVFDGlGtxg6R0NCxl26IrGox6GI0St8VWbXqtCIbwFq/sZUhIGoWsV6OY3pm0se1nzUqksUEjjR/sEoa2PSvLMks6d9dtdOCkDEVUitiE9kA1jAnPYxAVBNFG9kXuVyL/zblasgb+K2l6xVFHN9oZgsQ3NNMEW6Rp8kyE35WGJYWZFoIUjOdEXmQPNtp92r7s9TPzQhmN9q9zfFEWrU82xhxq9FRVpFhzFL/t4vOswWZOsGa6QwGotJ3jNbZ69z7d/XaukOOw0QiSqSb44gWjZo9Cmj0T5aldENkV4v0VaY0cM0OLeoWQWdm7fymEVGfVtvWSB2ojRy16IHvjRT1u2XWKKC1f7IsUQf3s2pVc5VhRVyLFHjEVdBnBstRaW1M5wq1dZnh+tA+ek0Q6FMiLhS6ZdSkqaW8H2VpMi5ueEatDCAdden7IqnFb4/2u+m4zzByIMsw2MsFrlWrEhkmatnaQtRykFcVOeIq0hwN4HGi1AqV1PIZrZGin2dWR44exKwFzAclFaTvz9773J98Tuaas1lSwUst/Pji7VXkGozSny9vp+JXm1qkQoHVnP96eRsKntHKsWHUWkC04rvL26HwOxlAX40CUvPzu8vbYfz1prNRTBkH9xmiHe66hftwF6Z/FuNPypS+9ZsPf8oJz/xl7FvmHtQAvg43Oo1+jyrd3vC70bi+2bM0LA3gazCL/+PlbRh+u6n1rJZz+u3l7TD+ftOZSCf35+jBq3X7FGkJ4x/30Vcf/pSLsxNImRwaBwM8gwyNpBQ+fk/cHEQaotcYYG5Lx59dLVPAO5FSIb3SGbSXV++JGqqR4i59bm9F2OBX5Ayj+4JXxPFy3CKfQISvVcEvjY4BOmrDKx1zdvVa2dWivRxV71oW7pPScbQW57rGdS19dm9pAGUGMplAzZ98BgNKzyX/pH16RKeOkOUGPqJe0H64pqVj6KHe5m+ntwmkjV6XXZyh9Pm9pQGUWcBUUfpUaudRxORR8yefQJp3lPnBNERpkCMNTAuNqK0akQ4evYFpLxNI513L/EHpO3pLAygzMXoi/EiUjfTKCEkCMF4RUncowkDAMZSOLatGGSMMUY8JBd8RpbaU42j5m1t03qXv6S0NoMxItLGEII/IX6BBlBrKSI0OT/PdpePKqpHmiEgVpqV0XDVEpDHabJbjqf2bMZWtOu9RUf9rOTDJrDCejI4G8v01swaSHAapEWm6ZzU6qhNhUKwhfkcEqPOsaYr4rGi1o7dwfGc7fq4fUYSWJjdCqir6tRQ5CxmR3rWBfF/0nTekMwwmUVK+74mbZgSRzfERjVoUcY99S6FX2h//hn+bbSZLBIA0/JHOH4NL2+8R3RydBYgySRHpAfd16/GXSd2osTMFq24EzYBC48i0a3mPDX1vYdCO+HSLo0TeYJe2yHneoz+3UaD9uHmlw5zBKOy/Gd2au/039r5eo9v695scrGQ1uO9o9/R7NcZjnuZB37FPNuUBKxrAjOZvZ4QJ5FyNfsrDGX7YZGpNngHzOapfqDlJob0TIb6OEmOq+X2zTCJkPmive9vdX7/edI+fNmHyuG+uX0XuQiMphYuzqLeZ4ftKx5FFvc+X5IVIRKkNtVYN40c7Z3KIuSt9x7X4PqONIrIUhIVLHWIm0cFfz+x7kNU01xhYZS1GtPUzkxRSaK8eM9ESi+NFZHqyR7KuRRqnJxjOZyIL0cTgKHIE2kypLbXSmf0pay1cIXJ4W4spIjIFdG7MdkudX1b13jR29CrJo3JTXXmVXqlgJnKvGi9MW+kzX9WZYxERCcsMqd+SeqeCW25mXFOmfuUstQ3WrYiovxqhbvVkBe8bEZkKTFKps5tBGLKeECEgUlA6ligykiG1aDVxJBvxqvlrXcpi5FxEpqH1TH60eq9yxVxFrQc8E1URKVH7ucZE2c5MUHqUsngPiUh6Zo7+7cLg9oYBIlokUPMnrcCwnZ1IYtzObr3Sqw7XVLCIpGf26N+u3rWAECkdzHGMOAeyFrQx+pQjEUHaZq0993ouZPN+EpHURE1V1tbIup3RC2wYkK35k95QesF9R/sjYnYt/jumr6aJIrpdav+tZC2giKSl11YOEURkYCTUSfWMTiDMvZvYyipgyEr3QSuZBhaRtKyS/t01uv6NKByDVI+oK1FHo36yEiO2YBIRSckq6d9dUVI2rYwgn4fxszZJVoSIXOm+aCkRmZgv3l5ng2jYv17eLsMPm3pvCfMepOFJ03JcX/EfDvLzJgY+oh9EdEVWhfvg28vbbvxi0+jykgjQfyEmn7u+3rRDP8ViH+A68Z5XJq0iYZnVALJdwh8vb5ci8vXElNOJEiF8ZFTpOBl06ET3TlVkdUYYwL/etKqJ2Sevv/7wp9f4cRMTVyawGmmRTsz66Lf3ZHpUZE4wgKV7vqVWo2UdM0bw0cRXRCoxorOMIDsYkTnpvahtpchfzwVsRAOdqEsIvnx7nY1VV4j6JAyROeldDsEkegWYNHNuf7/plTrlo5BS/vdNURbtiUxHaea1guxUROaEqFHpnm+lWk8viczoUiFM9qrBCpFmlG62FaQBFJmXnqUtMxsTfhtRv9Lv7i3SzmZuZAizpoBFRGaj1wTv+02z1gBi/jDS33z403hIO3M8mkCRSpRmWivICKDI3LSOAmL8Zo3+RYr83cpIoHTHCKCISB6ozWPj4Vbw+TNG/6JF/m7ZI4GuEJZuzGoAf3p7FRGZCTYUZoPiFvy3TWxTMiMs+Ihq/nYwgZz/WSOwEoxZnwTCTKr3rvkR+NUmfruIzA2ROkxNra1LqPubdeUvv+u7y9sUYMR5mtWMkOYmynmd7t634tkjz7zfnwYlcpjem6ZGkTUkIuvA/c5AWeoLnhWD7qzGD4im8RtLvz2yZtnUH7OHmSWyWfqd74l/x783NS5Pw2KIUmOaXSKyFhgc+rtXTA4T5dkH1qzBgOzPDsbAvmr67onP82lX8i40klIDmlmmfkXWBSNIpIR+oNQ/7CKtRup4hYgKv7F0DrIoY2SWsfdsVPo90YY1gvKQUsOZWXTqIiLAAHmr1RYXZC8FyhQFxGy/N/moLSKCpoZPMOsiEFhtIYgLQOQVMAV7YfZtZ0pacS/Izp6SkrWgLfPM3ez8dhNGNjK1FyQdgS2R+H7MoMhfIB1SmjXMqH31lMgzYPjosDF3pfZUEgaQgajVFiQiNZml/49ubKJEWc2AyScwAyw1lBkVfYYoMWCmXCNNw4SDhQerpRQlD0cmN9EV8T7jmGov8jgrx0H5hGgNtJXc/kUeQf1XiwFRIygRmW3yH20xCPd7VIOtCZS/wMBXaiQzybo/uQcdNamRUrupKdLDTkIkChimUjvNqmimJkra9540gfIXeq9M6i1MrsgtGLLes3TqrkRGE92gHFWkBVhZ9tjlOEWmjgIa/ZMSmL9XNgauIWffMpoZJ/0RyDaWGhyRD8waBbSByy0jzd+uqCaQFcykxB/1B0RNOX7SiNY25qR0XbNrdF/PvUAksnRsUcXxeg/Lh6Lg0YNibbnsXW6JYP52RUnBcO9j6F49L/xbJ1q5KF3H7BrdBnvUEreQqWD5wEz7AjqzeR4MANEcBnIiP4+MAP+fleO0lWyLGmgP0SY5I/cM3I1f6bheEW2Dz5T4lK5fdo2+l0rHlEXet/KBWbaFyWZOesMNz4z1bMoCQ4WJyHC+I5Y5cP5GdL4Y/hZmmM90oUtsuFdL1y67Rkayak6kRojjF/kQJcm+QWi0PaEiQZqklcmn3UQ995Gj21yPnvQYrBxQ4kIfX7pm2TXKAGaP/u0yYyYfiFQndVQOPGXopHpFdzGCkWrCIqZ+b9XjfPWe3HkvxqV0vbJrVJ8zS+mUkXv5CxlNoANOGW7sEdeSFHOEWSWRgdLxRRLp6daMiOx7T8akdK2ya5QBzJ4x2xVpL0UJQCYT6EDzOZiv0TWddCoj6wMzRP92tTxP3B+l7+whVxnG42ztb0SN6GdmSf/uGlGPLIHhpoo+w7Hm73MiXTcM2KgVepnSM60mMRHOwchJgHxOxAVRZzUCxp7SsWSVaWD5jAiRpJIwFpFqzaLAYBsx6jXCqGdKz3DNasOMPkJb4DpIHDKURRzRqPYVcVw8o94L0iQRo2rJSqKhRqgvi0aUAf+eeppA2kfpGCKr9oRmZOr3Vkbq40BEvnSNsmrUpv+zpdKtA5SH9FxNWhINdOSGn5HB8ESPeGFOe6UDMw5yNevlotUnObjEIePk6JFGjQmlY8kukXchUtGzjoTBwwjCY7KkIzCBPaK3GdNcNVMwER9NZclGHLL0F++J/mQETGRLx5NdLgSRp6FDb5lmwmRq/N4n02IHVNPo3CNjoXvN7WAipqdcrR8H+tXSNcqmUW2Ksa90PNkVZZKGEeVYGNuYzN+K/+fisiAQ0aFDYWA/W4PGIMhFdybyHJynyHV/99Q6bZPRAKIaRI1OmAaORcZ+41ajTIAGsC6MY4z7r3gI+npMYfW28MXbqxyDC4G4qLw+Svlx8bjg1K/VjICsAjPg31zepuKnTbQNrn0LaEvfXt6mokafQ0f6x8vbcPxik0YwBgyav7+8TckPm0YZFr73z5e3U/GrTT3HYQJHqFZfzbhC+QvjYquxRSQE2WehDECtoBMrfWd01SBi/d+ungM2E0++71pmFj7C+ckcBezZlm7hu0vHlF29zimmr2WZCu2a8aVHvbnIEFrWX/YQN2mrG3RlAxj5txOdbAVt6dlSFM4Rx7L6AMH5Kp2f6OpRR/wIDeBrkPXpuVsF/QBtXGQqiGSUGnw2tbo5MxpAOqsaRP7tLaK+3AtnJkP825Ujg9nulZYTxyOUji27WsK9X/rOHmLCYDRQpmHkzVRTrXbx54YvfV9kMRDXYCUDWOs+wFS0jE5GBvP7XsQ0kqLsBZvpnD2jWhPQWzBeEfokUs5EIEXSE3Gbj1fVIvqS0SATiarBCgaQNtMilcQ1WDFSkCUVXOseqUG2yOl7qjUBvaZ3yvc9YXKfNoFfvr2KRILB7+vL2yloUXfSKrLYkozHfJQaUYZ9UPnmw5/qwop6BsLVTCDG6g+Xt2Fh1W+keq4WhmkktX8P9ymf2eI+fZWvNnFMRgIlLVkLt++pRUE3A3jpuyKrViR05lXAvdKVoxcZjAIjWDofo4Xhj2bKZ1sIUnMizrWKnKU6FAkUiUTUTvpVtdoXjple6fsiquY5oJat9B0RdNbk9kwn1a5XzEK0/oX7OGpEtsdkpIdqROav6Xmfvir63NUi/TIBmYzNs2pBpkhpTbPBzLb0HaN11uSOqOtcNUoQ5d6JVPNXYpbJeM3zHDkDcatVI/2SmFlmnddqUQfI7C7LuaqV/t2JmH45M8hwfkqf2VpMtlYF8zuqHXHfRqr3u8csaeBaE52M52PV1f+SlFIjzq4WBhAyrAZuEeWIOAs/c41HRlpatc0MMInq3ZaIytSeELUke0am1iQnet3fPTHZMBUsaSg14uxqNchmiAK2GOxGRczu6Wz6d+Q1bGHQs0F7am3CMSIZzTbHXPo9WVTrnGeYbN8TkxyRFJQacHa1DMNHXhTRcqHByKjZrc6k89j4t/SZPSUXdiNY05DzedmjrFmjgDWjf9En2u8pU9RZFqbUeLOr9e7+ETvo1vv+RYkCnv2dEdLZ2Q1KC7hnMW9H0378ff4dk4JZUm9RF149EoatlumJPMl+Vp9Nxr94e5Uc0Ji5EREdNp3LvU0of9pER8TgdK0M0Fhn41ebas1GS9A2uL5sBBqBnzfRRlu3OTq131/eDuOXm878TtrFt5e3w2CT5JbR2uzQ19Lv7q+30Nfu/S3GY0Yi3GtH+MdNtVKfXNvsDyegT55lQrIMDOw0YjqWW0d/VHRMzEyjPGvyHqVjz64eERYGpihpip5trMa98apqmKYI14x+QeQ9sqSCa25/Qr9a+o6Mij72yxukD1rebAw6DF61QuQ1GTmgt1Kv8xxhf7Pe21swq2WGXjqWlqplmkqf3Vsto9MyD9xr0ftnjq9mpCtCiUYtOdELDoNn78GMRhHJCDJ7Kx1nZvWEaOOoqFJv87fTO/pZsyMtfX5vaQDlWSJlGm5V2/zBTAEJvIUEZK+XKl20HtojghHgOErHmFUjBlc66Z7tiY6F7xzJXgdZOr6aqj2LLn1Hb2kA5QgRTWAL88fnlb4rsyJm/ZaFBhYpxMxNNHogxwyXji2rahUiH6VX2yJiW7vjfRWOA4NWOs6zYsBrEeGMMJCaGpKj9JpwPaMW5g9mG4uQdYBB6B2lOaLRj4+JmmJ4RT0WgDyC729RT8pnjv5t9+C4apZStCyTaHFtjipK9F9ygekaXbLTcoI9w/Yvt/JeDwADVHSTMzIq0CqK01tc4yjQ5mqcVzr8qMbvFiJ2rxosrl1L47cToeQhy/WUmGCUeo9nTPBat9vZypHQqIyUvMGgVLowEdUqtP4ehKlLx5NNEW82ridtEHPzTJSMv8Pf5d+MaAs1wMQxSGFeH/1mzCK/lfbX67cyiJWOpadEzsL9UmOC+Z4wmhizHvdnj9/TW/RxMohM5m/XKBNYM4U3SlkKbilHwIhca3QtaA+4PhFM7ci2ziAnUgvuqRbGqafx24lQnlFbGsBBMKCWLkgGYQJ7k9EsX4tok8gzjEw1YfZFaoNRow8/UyO4l2GMWrgwtQH0UXD9wPxx4qM8qusVvt/EDd0TIiNZH8Hzi00cv8h7MFjSVnr3Dz9s0gBKD/bMAhHCPTPCuEib/3ETZg8INnAvMF6OCDxcwzGMfkxjbbznO0PnTkMuufFs6r06OGstoGk1OcqIFYcrpPlFXmXGRSBmpjrTYy+2nuo9aJxJIYwQM9kIdWWSj54pJwY3EbnPjAbQ+74jhFpLFyGzeoflMVOYqtKxRJThdXmVXtkCI9Qi7zPLbhTX6p3FW5pZUr+36t2IshhpZ1dyltYmUPMn8hyZF27ekwGKTmRfxfpII9Kc0c+nA6vUopUJtI2KHCNT9ukZ/QVXAbcl8wrWZ/jDpt4RL0zgd5e3oRixQlrmh/vr95e3p/h5E+1zxgJwzDKRGiIb1ytMr2EQx1Aj6iz5s8gzcM/8+vI2Pay2duFXB2aO/u0aEQWEaOfWqIq05MzGutyjmMgR92lL+D27oS397veEEaSMZbbzIvWZaSy3RKkTPVfzjRQ3xwgozo0Qmve5itILjCCm5b2+hfsCY8S9OaPxYxCree9jrktRQxGgzZXaTUbZzjvASS6d/BnFTHoUnOcWdVLPiAFo1O70IrCnPa81cweP+W056ZsxUip1eDUCH0lMHKUDdFSlCzCrRg86tSMC74nOwIFCpA89J3rUbVsjJbcwuSq1l0wala1bjlFRqVHqvSVMCQxZ61kaMyg6AhHpA/dbz8ndLgdLuSVzWZePJO3ETPUCzyrSykLOPxFBGnzpWI+KwQdjaVRApC+ji+81gXJN5iiggYtOzBAqPipMUkQwbZjBozM3/j6LO6zxExnDaPO3SxMo12SsBbwboHEfwPpgOGrs25WNX24auSDkGaglutYOBpZj319FZBxM3JiEffXhT+PJ0LdJH8gw0Ray7O/L/p/cT6aAOzHDaqFXZIhZRM7CAFurfKOWmBhyXCKQKcv3MIv15dur1OM6srQSGkAROQulF9GiK0Qi3e9TdohO//byNjQ8qethfb4p4Powe5358W/3GPFYOBGZByaRf768DcmvNjH4z85eIkPq8DryuZfIMMaZUrxk+35zeRsOH006iFIYdgU5QxaRMxxdrNVbs5o/zB5beREtwuSVfvut+HuzPm3mCBFLvjgmGUTpgqygFWbGItKGLHVVM5W68FswcaXfeVSYjlXLgCI9+MEs3GBKF2UFaQBF5FVqGZHWmiG6sq+yLv2+s+I6rlgHP/rZ9Hy325YFoHRxVpBhZxF5BVKIpT4lqjKnPIkQlX5TTWFGIjwdqjcY3xFlDHzniqY7JCNnASNl6FlEXiHKps/PKmOkBdPKAo7S72klooEr1gfSnntsZcR3GPULxogZQARpAEXkFbKkf3dly3aQ8h0VmMB0rrpIpJUR5DNd4RuUbJ1ZLTkTEZFX6BEtqalMTwUZaf52rWwCgcUxTBrOXAfukeoLbdwHsD5EwlZ8FNwqe2SJSF0Y4LKRYezEdGEcIjxS78dNmNHV4Rxg4nilbo9r9M2mazhXmMV9z0XG1UyTjqUhEnbr3leQiMhRGAxL/Ul0ZSi6xzSUjn2U3Cs2GD4Krj4rOvUf3l5FRFYgugEkE3UbWRrN7zatuldgSDSA9SFk+9Pl7TKY+hURiQHpxahlSG4XFggNYBtYCLISq/1eEZGoRE618px8d4wIggawDSvNcoh2WqAqIjIeUqzfXt6GZcVNokOiAWwDhmiVNLDRPxGRGGQwV6xKdh87mZpID4huKR9BIyJnKPUr0RWx3+OYSscaUWaNZGrY32f2x8JZ0CsiZ8m2ETSKSLagg8GDwZgCbgfmb/Z9j9zXSUTOki0aFHXbq2xbrLglzGA0gG3BIP18eTsd328yjC8iZ8m2jVTU4/3122sWNIAyPRS7lsLfmUV00/C9iNQgU+0aivhIM46pdKyRZQBhMD4LuA/MGKMvzT/CP24y/Tsft8+pvGZ/NiVt2Y2/pTZZ+kh2d4g4+eW+/fPlbSr0IDI9dBizLAhx8J8LFitRPH6kEJ+2zAIgo8BSiyzPUI+6iTHHVTre6BJZgiwd3CMx8GMYZA4wfmcnJhpBqUX01cCR+7+sBtA6QFmGrDcpovOLWPsix2EQI5Jbus6viLbBBEfkDJiBUvuKosiPMNMASgowESyMoMHu9UT3ohDMCPn/PG2Cv88gkz3aQMSk9Fujy53b54D772zU754iD5CSA/r6UtsaLcaiyGgAJSREGzAP3Ni1Bh5uRoxU1qhDNhOo+ZuDluZvF21b5FUYL1q30VcUPfuR9clTkQM6XHMMKucWg414z3/TuL4D5qzHbI7OgkEn+g16SwYTyLk1tTcHPQdWJwxyBgbXUrsaJQb96EQ7Z88qEngITN6R8hh2RmBHjGz+oxl0/qOKeblwmQwL56r0OyKIa2ijnocjnVoN2XbkDFH6xiwRbSJppeOPLCakEaCtYeRKx3hEjJlMFqIuFGrKSON3Kwa7LCFaBsoo520XkdslG/GkjBhMuQdFzjDaBGYrZ4iYOn+k0X1EK8/CdcgQNa4CBqZ3dOFZYWQyLBrBbBFGLv2GnjLlOyejJhh0sCJnGGUCM250H3UBzT2NWjSGJ+jhWYgqTp0JybDyCFOTZSAaaabp8Iz6zcfIKIpRQKlBzyxJ5knwyHv9FY0wR1zbnpHSTP7jaTAKUaN+90Q4P4vBIX3d4/zSODF+2bfWkfuMjgrYtqQG9N2tF85lyRjdg3NU+l0RhaHvzUiDPCraWR1ce08HXVOEZDPd4BwrBq327JfzwM1gxG9uIgwIy9TCSBdaTI75vCw14++RYXcJ1LtfiBAdTW8CuUmymr9dHH/GvDzHvC9RP3oNMHx0DNwERmTWgfu11B56iqiKSG3OTo7pQ+kTM44Fj+C8lH5vJHHuewYfIpi/XRxLGL54e30GDvy7y9v0/LyJwRFjlBVuoL3z4vX6hqJTRNxomX+jnIMJw+8vb4fx0yYnHdIS+j/6c15pa7x+tWmH/p5+cO8PmZTM3C9ibH9zeRuSP2zqFQ2jLRA0uW4Po/nlplTtj8LJkpvNLDoDGofIrNDJltp+b4lIPzDBjG+le3G0CEz0jP5htErHMVK9z8EpMElRG9NZ8buMTsis9FhI9IxEpC/U2JXuxdHqWWsZZQJcUq8I6ClwqbOav13MENK4cZEDaABF1iXagpCeeytmqIUMH3yKGD5tIQvVZUZMAYusC4GNKGM4k9GeZFgNzTGGJXL4tIXcrkJmI8I9TL2LiIwhggnsnWXju0rHEU1kV0NmHyNsH9Fb1gPKbES4j3vP/EXkU0aaQO7/3iYnav1jSaG2hdlZJfV7KwcrmYkIM+EUxc4ik0Nf0Dst2rPm75pM/iWc58jknlso63MgRUqMfhScWy2tBVHnkiQGPZ6Fy+ePGkcjTHqPqneE9C4cyOyrft+TNUsyEyP38GQmLnND+yKyRL9ZagPX4u/wd51kj6VVNBDvQMR/pKEZ2d+9qjATpNUWftxTyLy8hIUoF22G+2dXpMjHM4NzC3kfzQkDPG38TLvi3/IZYaIfC0LNO2nas0GfSNeS4ygdY2RxzCFYPfq3iwYt8gg6T2bRz9wzpGFHRj0woqXjaimjf3OCqa85TtDXGhEcD9cAM/hs/Ry1a/z9aCUeo0teXhHjyHC4sUsHt6qsW5ESu/ErtZn3xGA3ql317hi9f+aC6A6Dfula1xDt02hgHDB23MO3ir5TRss22kohFoJkPHEtFcKVSyiYJdeIfoxYHcfg2mt1XJiUhlQBM8DkpXSta4r26VZccgYN4Atw05UObHU5I5Wd2hHyERMMBvLWZR5OnOaiR5u5Ft8VLa0oedAAvsDqW7/ck0XsAq3KI0aZwFbRHM3fXDAB7mn+dhEJdPItr6ABfIGMJ62HHNCkdQRkxCMIGVxr3/OmfeejV8lAScMHRUmJBvAFSgelLgO/rE3rDoU2NqruCfN51txiEkzZzUeE7TRGTI4kNxlXAQ81gKzsKR2UusjBbV16rYwfGWkmGshgf9QI0mlZIjEnUWrCaZOmguUIESYuRzU0e2L932M5yK1Lz3RChNWP+15g/O7bOkH+G0aV/sKVmnPDdb6+9iNlaYEcgT6s1I4ia6jHiHSzR5QdUF8wF/ueUyNn/72jIFHTXbb/tYgS/du1UhkO2SbuN9KYTLiuo/JMyJyEvQ9jxnX7yaCh17JnlCOjOD/SDm5YOrR77ZBOkA6x99MCekfGo7azqMZU2tC73T+j3vd+Txj8X30cG/W3XC/T5J8ycvHSUWHsh/JKw1tJGsA20Gkx2y2d83viZiEy2AM65dIxtFRE/s+3V1mDiIPnyBrZVmD8amXfGMON1H8k4iTmnkY8FOATSgelPpXU5exedD1umhGRcZGRRE2fDY+SVAaD0iLwwnly0WKuNPDwVH7poNSnknrQQdXo/FpHBUYYwF7RTZESkQvoZ6h5w5j0qLl34WKf83xWlDYNp3Rg6lNJHejEa858W0YCNYCyGkdLMnoq+72B+euZXl89JVx7rGmh4dHaL99eRXrArOyry9sq/G7TTKbJelORMhiozHBvf3N524Xfb1o5Ekg6fHh93QO+38SEYDglZ6o+lZyHzqh0bs+qVX3QiGiIyEhGRL2fVeaI1sh05MwrqJ8h4qImIpMhJjRGAKUXrbYT+XpTi5lu79nZD2+vIjIPGLDfXN4OAfOZPXp6BsaGny9vw0CbwAQORwMoPaDWoWX6o8Ust3c6NkRBsCxNiEFpIvZFHyOh5CZyKrQ1TORbBR9e4bebQpX6lEKU6qOsyzpPj32ZWtAzdTPDKkfJjYtA6hLpfK7ev7QqQTqi0ZOBzyACaOpJWtOj82mxoqrXDUtB8Gx7nUk+jADWg+hfpMjT6quC6cuJvo3iD5tCLsoh9VRyq+qi1W+cGvQoLm8VIegRBZxlds412LVy3VFWuG6l9hlB2eiR9Tgq78lLG++5PQzfFdL47UQO+0dQ6IuXhMwGkE6zZYeRdYJBxJXaoveuLTU4/L3VVyNmoXQNRyvEdhkHibj61LHsAn16j8AXbWD4Xn/vQcdcOnh1UfgLmIAeUbRWBhBoAy1MYLiakHfY01qkq0u/5z1xDjGD1jvGJWJGKNsiBtp36XeMVrb+pjV4n1f7skcKH/W7hk699CPU5ULKeXpEmVunN7iha5rAbJ0xxq/m76dNtL5mchzaeel6jVS2SXjEc4isMy7D9aqRpeL88lnp+rWI4eoIcmuOOrSuLerVsdWKBGaKaBDNaJXC57oZYY9HTaN/VrS9bEQuq5L70Ncx0T3S3/F3ud6p+7HIDXakcPNSh5aDSs9oGrO7V1PaTLRapqpr0yr1fSvvs1hEGg8yto1WE6YaytT/jAZDyPlC3BNo//NUE1d+TKmxrC5TVPUg6lU6xzU0oqaM78QIPmOQiCRnWwRBJ9czEqQJjEWL2qijyhj9Aw2gpMM08Kcy/VsXDFMLQxGhlo4JFAZvnyUiUglZO9tekb9baQLjQNstXaOeyhpl0QBKOiLuWzRSDkb1qd3GMClGaevC+Rxh/nY5QMWBiUzpGvUQfUVWNICSjtEdfyS5Wqodr9bPlWRnVp/Rgxf3nqY+DjXv12cVIap/Bg2gpGTkjC+SMs8+o8PgfnavMSYqRmjrwzktne/eyrbv2+z0NIHZzR+0rHc+KydXchejgKYVe/HqZCPF7upJiVD4v2vEwh65Tw9TM4vxj1xOJfKQ1aOARpb6gZF7NhqIOfHatCNK9G/XDJGg2WChU4sAAZ+ZbZX8I+jXSr9ztEhNi/yFL95eryH6RZTl6w9/WosfNxld6s/1fkvXkR8GBtoiJpFXaQfn95vL2xD8vGlfOS5xYHwgSPC7D386z3/bxOfNdp35PV9d3obhHzdliLJejwWlTADBAISh1dQ2gJNfmkHMLn63yGrQyZbuh9Ey4hsX2gzG7ZWyAf4N/7Y0uM/CiMUz7yny+Wbs5ZxhnEvHfk/8ff6dgZvKRC5kbaFZ6k9EjhK1Zsm9OD+HCBzGmHNzz3wRzWVQ7JVWZfClDXFMRGSuB3He89/4f/ydVQbqaGngqBkUjB/to3TMR8XnaAQrsaeCSyd6NplelJVhcC7dF6OFwZELRG9eiSphwJjczhxti0otY1ND0aLp+ItWQSY+l8+Xk+Cmj4Zks4nfZ+coKxN5orc6tQZK+jnSrtKPKKVU0QIc+IpXSgeOiN/suF4B0gilEzyLrPuT1SndF1G08v3ZYqBkYDQ60o9WUa4jinQP9Qwq8T2mhB9QWgVcgvDxd5e3U/HbTaRVRM7ATBOVOlrSQAzikdOZdJZR+dUmzuFqMPGmb2qxkpQV1rRVS1/as5dSjdpVg1XWUR5sgBnjXu65Otq2Xolo+4SdlSsM5QwYviOrIOmAoq58LB1vFK0YAWSgLJ2LmrL0pR89o17XimR6MMIjzgHiPBj1rsAsJlDzJ6/CoPlKMf61+PeROqTSMUbRagaQ9tVroHRg7EdvExjt2o5eEEMqXirQajf4HuK4Z9pxPiN0SgzqKFsEgolDrbYfqS2Wji+KVjOAvQdKB8Z+9DKB0cxflMDRitmEJtCQaWSlkxxVHC/HLX3B5FGD8mhg4/8xEEW+PmejfvcUYQCOfC+vxKiB0lRwPzjXLe+3aIYeIxolYMQ4I5XgwrYaFGsrWsptBejoXmkf3KTRZmqt2zmfPxL3AYxB7RW/z2p0+1sR6oFrGiPaTsQIV7RN5g0CVYZGN6rjek9Rb4rZqZEqZSYbwbS3Nn+7Ru7RFq2T3oUxXYXR220ZBewP/dtZI8gYF7mmPZo3cLLTgBoNuaY4Do7HqF9/ahqm0bUsvQflUZMVBv/S8YxW5IGtNqOjsFG2CukJ7Z42xlhB5uFaTEA5J70iRvQ19J3PGCb6xeglM8DxlY5/pPAG0ojRRlDjN5YW0bJRJpDv7N2O6fxHEbEOcKX7eFSfuWulaCum70h759rQt/WMkjIZRHzv/j5b+pKxuHQ+R2vURHsZ6LiZPfUK//I9fJ/Gbxwt04jMyHszqvPie0fAoFg6nlFi4FuFCJESTM7s0MbPjkm9jeCo/qAG9Nulczhamc9pOujcCFfXjjBkCYOvQI8UYs8U1Yjo366RA3GvCdsz6jnIjmZ0/d+uWeF+rpli5x7tVZ7Q63taMKoPfU8rTS5DwY1IZ4cDZ3bw7IDD3+Pv8+/49ysNDhnoMdOjM+kV4W0ZzXxGtPERkBopHU9vMbFbCfq10nnorRn7VQIErSY2PdrpqEfI1aB0ziJoREZJ3oEbda912CXx6Zm+6hW6H526GDlDZVArHVMv9TT6UYhiAGfrc+mbWkehjCaVoS2VzlcEaQBFKkEHWLrJWoiZfA9K391TDFqjwHyNXBCy4sQvigGcyXjzW3qVNPQsT8mCBjABX769irxKzwGbdEjrms8IBuSrTaMGY8wn5+CnD3/qy283rdg5jzT810Q5jhowMe2VPv3jphUnLpIcDaCcgZqh3jUqq3S0rY3uIzAC1CH++OFPfcD8rZpOI+I6mhGGvxW03V9f3nbDVPCn9MrWyAk0gHKGEUXjrSNjzuQvYEo4Fz98+FNbVjZ/EMEARjiGWoxYRMREOPOK3dpoABOgAZRsjIyMrcaeDv7Dhz/Vh6jTLzetHj3hPPeMtpaYJfVO9G/UyllrAT8lalTZGsA3NICSjdYRQGeun8MihV9sqhUN/HkTphIzP1Pk6Qyjn8Qxy5NARkbhvtnkBPUjUY2WfbxIBYgOlVZZtVTr9M6I31RS1IGE80PErnTM74lIF2bSfTw/h3NSOmc9NNNj4Eq/r6eMAn4EM146R6Nl/yNSAUxK6QZrqdZ7AY4ciK8VHSKxpNsw5Mz0S78Bw8f/4++M2tw6E68a67PC1M9AhMnbSs9Ufg/6iNI5GikzDiIVYZAv3Wit1MNI9No/7J7spNZkxORjpnqo0U/wQd67nzJqUnNPLtS5whpAOUvvAaTH942exc80KMvzMPFoteCmBLWYMw2Io/bOvIY6QPnIiBXZ96C9G6EVqQgRudJMq4V63bwjUtvXskZlbWjnpXZRW7NFQ6I8UUU+JUoUsHX5kMiS9EqZ9hywiMKVjqG1jP4JkSxSiaX2UUszpsI0gDFhQtu7VOhWjFERIsQi08FgUrrpaqp3bc2ogvJZCvLlHAxWrSYhM5o/0ADGpccY8Uj2qyINaZ22GnEDU79SOpZWilQvIzGoaWqIwsy8GjuCATSCf59RqWC35hFpDBGLVqngUbUbPVJxu/geUxRSgprUs9FAJhezt68I28BgcuQ+vU2g10OkEwxUtWs9Rt/APepX+PyoGz9LHGgj3A/PtkcmZEyeVllUhMEtnYeemjW9XpNeJtCMyjt88fYqUot9kKqxHQJbYoyK/l3DbyLF3eIZo2xNQOTC/cPkCLRJVDJ3tCWEAVwNIqXfXt4OgUcmrnjej4JRxqB99eFPdaFPJe1r9E9kAMzEz9TP0YFGK9ptkQ7m8xjERaQOIxcbWP93DCYvtRc68XmrRLxFQoOJOxLux/hFT6EQkayREl6hJktkBPQjpXuutXzc4WswTpw1gvz7aEGD8JgClh5gdOgc97TVNZgpImGkWHnNADNMjCC/6WgK4/tN/FvTRM9Dm6FzR5z7UnkBaR/azy4GBM9xHJjYcf24lrfXj2vH9UL0A2evG9/13eVtN37YlNWAcE/t99Yu4D6if+a67O9bwvdy7ehXnykh4pzTXmq0GRGRQ2Bs6bD2Dqg0M6XT5P9Tk2LE73kYDIiS3juvz4hBi+vjeR8D5/2ViDlZg92EvAqmpfTZrZStlGO/NkfuL/ox7qde7JM+vpPj5HWfRIiIhIMOigGMVzkOA/+RsoFnhAFhsNMI9oP2f8a8I67Zq9COjhrPV3XmOHvzqim/Fte1d7rbFb0ikoJMA0IUGJjOLBx6RiMGrhUhUlM6/6+IqNOrxp1IUWsTmGmlKefjrCm/1plrcxT7VBFJwf/19irPwcBEurY0yLSQ0YR21DR/u2gbr9LSBGYyfy2uC8JQ9kjJ/u9vryIiMglE5Hql6q6FqTAlXBeuZelc19AZs1U78oUyTSJamb9d3L89TKCIiExC64HpPWkC68F5bG3kz6TvOb4aJQYYyUz1vS1N+bW49t5LIiLyLr0GpvekCaxD7YU7JWG+zrIvMjpqVmknTFgy0cOUX4uV1yIiIndpWZf1ihy4zoGpKp3XFqplwjBHTEKICnL9b9sj/23fvunsljSj6GHKb8X5EhER+QwGXqIppcFjpFxl+Dqcu9I5bSHajrwPaerS+WstU8EiIlKkp1k4KgvZX6O3oc8akevJiOjfrmypchERaUzPVOErMhV8HKI9pXPZUhqMx4y4JtcySpuEL99eRXpCeuJapgzWIHqa9dtNZ1aarsiIqKkRwMeMbsM8x9c+XUQ+dATM2CmofrQXF7Uj+7Mm7TzmI3r0b5dRwGOMqDWzXvMxI9O/u2gXIrIoDPivbLeA+Dd08hrBeWj9mLeashbweTSA8WASUzpvPeU1SoApYGkBWwFQB/KbTV/xHw7Cv/n9JiKGs6XkGDDpHOmkbztqjC//DeOcefuJEpmuozVmkhlKGUREukLErsXsE0OUmTPRUIx09rQ4EbXSb4uqGhsOr8KICKB7zT2mdM56ywigyEJgUFpuB4GxzGaCduNX+j1HhXnMOvAxGJR+U2TNFH1tTen8tZT1ZY8pnbPe0gCKLEJr87crUyQQs/ZKxO89cZ6z1aixuKf0WyLL1cDP0+Pe38U9JY8pnbfe0gCKLEKtKNczit6xYIZbnw8GwUx1aj0NQi05gD0Pk53SOWyhTJPAUUS430zTiywAkZJSB9BSUSNgvSKhu7KYwNKxRxerluU5aPelc9hCpn/fJ0LE3eskMjl0/I/29msl6gGj0dv87cpgAkvHHV0R21hkemzz4zV5jp4R2XsSkckZ2dFEm2H2TIPfKvpsu3TM0aXZOAYToBY1r7v4bBfmPAfnqXQOe4kIpIhMzojo365InQxRuNIx9hKDIwNwVErHHF0awOMwESmdyxrKVPMagZF1gF4rkcmJsLdbBNPTOvLxrCIXx5eON7o0gK/RYjLkgpzjjJqUuoemyALQKZc6gJ6KsFXHyNTvraKmgkvHGl2ajtfBfNSaFM20mpTJIueG7EUpe0LUjv6kVr9mTbKINCHCSrPRg/ToWptbRa29IZpWOt7IchuLc5AhOHPdMUizrCR9ZUN4fv9ZM9U7S4PhFJEFiDCoj07TRYiC3ipioXykKOmzmsV8jAYTcyQShfHhvopc03qEs30E5+PMtle9+igivlG35xKRypQ6gd4abQBLaZzRihi5irAtxVFJXTAHmBHu2WtDyHv+G/9vJtONga05ST4TDewxATP1K7IQpU6gt0YawN7plWcVMQ0TLVX+nkZPLCQ3mL8jUc9nFdUEav5EFqPUEfTWyIE6clQrIi0GxFay/k/O0LI++kyUtPZm3aR9fWa2yILUTG+8qpEGsMeTD15VxFRapjTwLPVn0p/W7fzsnp/0DTVKVzC5EeuNRaQDq68CjmCA7ynirJxBK8J+ie+JVJnIK/Rq4zX6vaOLc3bR77tASmRxIkR0RtaeRDaAI43xIziu0vFGklENeZVe7ftsFPCa68U5pcgg/w3TR3/vvSEiH4hQ2D+yQ9IAHodBK+LK6V2k9UVepWfbtk5VTvPl26vIUejsfri8HQLfzTFIHohcRB24ftoU1ThLfIikfX152wUXX8hpNIByhpH1UtZq3SeyMSad9P3lbSgoJ8CgirxC77q4b99eRUSGMWJ7jwgGp8fmqq8qQ5F2pG1hTKfJWUbsCmBNnpzii7dXkVfBbPz58rYbv9o0cgsYIF34+8vbcPz1pujRLOoBuYbffPjTOIhGupHt/JCipa+6XTzBRIR2ePZ+4TN6R+Ui9IMisji9Vr+hKIX6DCal4xutTHWRDMYjI4GWEcwN7Yu+iXuidP2vdXZ7E4xY6XNbKkOkX0QWoEdKFLNwO4MfCVGD0nGOVDZTw/UckU53wcfcsEjilfsTI/hKH6MBFJGlofMsdVQ1FM38QcQ6wKyDwr4Io/SbaorvcOCcm7P1eEQMSRkfoWcWZJeISChamCJm19HMH2AkSsc7SpnSvyVaRwMxBhHbkdSj1mIMJgpHTGDvjfGz3+siMik1oznRU3UjUj/3NMtiBlY31jSCfJYrJueHtG/p+r+qI1kHzGLpM1qJNi0iEpKz0RyMVYZBu3fHf08zRgRoQ5haSguOTihoP0RljPitAde5RQnBkUVnzyw2qSU3gpbTuA2MtAYTR2fFQP7elh88jYHBnk43k6EhSjl6S5gVtoTAbNOe7qXmaDNo9vMgn9PyHvzFpmf6IyYcf7y8bQr9pBFtEUkFs3Tq5ugo6bAxe7znv2Xv0EZuZ3IkSiEyIy2if7ueLUOhf+sRBZyl1ENEFmam1Zh0/iNMoNEuWZ3Wi7GOZCNq1yHeyvtdRKZgJgMIvU1gxK1xRHpDhK50f9TUkfus1Up2opymfkVkCr5+e52JXiaQSIDmT6Tt/qO7jk5Wa5tAzN+RbWlERGQQtfYjK8mav/gQqaFWi2uFWS9NCvYFK5gFolizRcR7wTm8Pbe19cq1qWUCNX8iIslg0Kg5OGEiNAlxYZDeV7CXrt+zIqJlof/zRDWA8Ooj6XZxz2v+RESSctYIMghoCOJS2+jvwjgQGTTV/5hWNXfXOjPx4vpxHY8YQSYR3vMiIpNAWpBtb54xC5g+oknO/uPSyvjdajeCUqbHIpBaEBHcywKuP59rzH/j/7nJs4jI5GAIMRHX0vDFh4gOA/X1AN5DpgPLcE5K56uWMGYiIiKyMJiNHpv9PhJRZPmUltfEVKyIiMjCYATOFPTXFHVv8hGuTek8nRXX2xpMERGRRWllMM6IlLDm5COcj9J5OiPr8URERBYlovnbZSTwI6Tna0ZoPbcyNV+8vYpEgwUSdOiIKAevQAfPTH9/tUBbWsLCnD9f3obl+03WqV3gPHx3eXuKHzdx7elnRESkMZg+tnQ4mspx41xpAe2xZkSppdwm5iNnN1+mPzG1LiLSAQbaGpu5unmq1KRFTVlL7VFyufQpR/doxDS6wlpEpBMtNnHFCJK+EXmVHpsL1xaGVT6FfoDJ5aOIIP0Fxs+on4hIB+hsW0dYTIvJK9A2s6R+b2Wbvw9mEHGOSBPznkihiIh0ovZqvUdyJZ8cpUY5wihxXxnJEpF3cRWw9AbzR23OVx/+1IdVV0lyrvcox736MKKwpMB4dUX15Vz9++VtWv6wyUigiIiEoUfa9554dusKYPSIYL3yaCyiR/zblTe/zVj7dyuuvYiISBjYXqE0YPXSzMaG31bTXGMiVoyavmKcI2plEy8iIoFgQCoNVD01Y33UnlIv/d4awhCRRl4BfmfpHGSUta8iIjKcSKsqZxoYe6YrV0ih8xtLvz2juN9ERESGEq2uKvvWDxjqEel0UswzrzAdVZ/aSvcW/oiIiHQhWl1V5mjWyIU0aGYTWPq9meVTLUREZBgRav9ulTU9Ntr87ZrRBM5U/7drlZXvIvICX769irQi4mpE9iDMuLCB+sVvLm+HwjHMtmfgjFFNU8AichcNoLQmqtHKZgCpo/z15W0IMIEzRZg0SyIiIpUgqlJKTUVQpggW5qT0GyIoYyS1xAwbQN/KlcAichcjgNISoyp1iBxpc7+5uPR83KKIJEMDKKvy7dtrdHgaR+Rj/XrTik8MERFJjQZQJDYZtvIgfSrx+PHtVUTkMzSAInGhvi7Cqt/3IAros2fjYQ2giNxFAyir8vPba2QypVazp4Fn29YGNIAichcNoKwKmxlHJ1NULdIWNa8wo1nK0MZFZBAaQGlJ5KgKj6eLDCuos63izLwlzIxmSQMoInfRAEprfnp7jUb0wTGjmcq+J+APb6+zMGNaW0QqoQGU1kQdhP709hqVv3l7zUT2fR+jt4kjsALYGkARuYsGUFoTcaNgBscMKeBsZH+e7kwG0A26ReQhGkBpDRHAaGlgB8c2ZI8AMimYZe+8mcysiDRAAyg9iLRRMNu/aADbMMOjxyI/du9ZqGWMHuEWEZFFYEAqPbC+t7I8tYLIaen4o2sGqJ0r/bYsyr4YR0REJoJBqTRY9VSmqEhGAzjLogMev1f6fRnkyl8REQkH0bfSoNVLmSIjGQ3gTOYjSsT6qIz+iYhISKi/Kw1crUVUJxOjztMZzWQAI0Ssj2qG+kUREZkUtgphE+bSANZKGRd9ZExDZqmvfBYMVel3RhQRy+zb8IiIyAL0inBlXfGbMQKV6dnFz9J7svKKqL3MvgWPiIgsROso1z9syky21agzRqD4TdHrAbO3cxEZwBdvryKjIHJBqu3bD3+qA5v5Mihmfxg+0cvfXN6G5583zRgBBNoo9Y0R9zn87aboUW5MNOcQ7ZOE68Uqe+0oEx7uWTTLinIREXmH3bCVIhzPikjNTNGQTGng2aNQmJdokcCo5xyTx7FhTF89Z/w7/j2fM2NkWUREbmCgPTJwECngsVezRp+imY6SVonWYEQi1ARyviNu94JZ414sHfNZzXyPiwzBFLBE5m827amjWzBGaKatR0owqH53eRuWP2yabQXwIyhZ+N3lbXd4zBttgrYfgT3aRz3v1/yHxvBccdpa9LS3iIjIaTC5pahIBGFEVkzREYHrGZ0l6hdtL8vdiJaOt7X43ohRUBERkWoQAS0NghG0eloOU4Y5K52bWiLaFclkE5mPMikhNWyNoIiITMvox+iVZBruI0TDatYHEuHimmO2IsHvbG14j4pzVSoTERERmYJWBfavCLNj5OVzMCJEBV+JkHFOqS+MamaiPxkFcyoiT+IiEJE8YLgwFt98+NM4ft6ESSHyIo/hPO2LmUpwDlH0xUxEezPsSbnagiQREVkETODIrUhI/0WNUEkbMH+lthBVliaIiMiUYAJHDMoYz2g1adKWbOZvlyZQRESmpccK1F2utlyP6DV/7ynatjkiIiLVICLXcnEIBtMnMKwHCypK7SGb3CtQRESmhoGu5t5sGD+K6Y36rQeTimhbvbwqfodtWKSAq4BFLmCgGPhKNW5ZVmoCx0/qi9/zymrhf95ERNEaqnWhnX97eTsFtGmj2CI3aABlVTBKDAqkuo4YpR83MUBikFgUERl+Iyt2d5UiIdfmlt9DxETWhfsh+rOnX+HvNzGxERGRRamZKuVz3HxWZoEJwiyp31sxyRERkQUhAlbL+N2KyJnF5pKdiI8brCknayIii9FrOwu+x4Jzycqs0b9dRgFFRBYBM9b7qRl8n0/KkGzMsu3LezIKKPKGi0BkVvaU71cf/tQXnpVLSjj6IpGIXC9cwcCXzPS+WMWFK/XgPI5+xnQPXBEsIjIxmIbR6Sy+v2Re5HMwy6TPSdGVzuV7wrzw7z3fr4HpLp3XWWWZhojIhNC5v2okagsTyOAqn8N1Yr/C2tcKM2ia7xhch9K5nFW2DxGRCWm10vdVYUjkIxg/Vpu2jtBiLB3on6PlowQjyv0ARUQmI+o2FqQn5ZLq7R2dZUJgavgxo8sleovfKyIikxC9jml1E9JrK557Is0pn0O7LJ2v2WVphojIJERPYxGJWhFSvqTBS+ekt3y+8eewIrZ0rmaXK4FFRCYgSxRjtaeFEGWJYv52YcRdBfqR2Z/+cU/8bpGl+fLtVSQzWdJ7K6UhMVlEZaPtLfftJmsyRUREkoPRKM3wo2qF2qNIad97Mh18YbUVwLtWLckQ+QtGACU72Wp5Vqg9wlxFf6rEbzaZBjQdLrIsGkDJTra6utnrAElz//ryNjy/37RaXaaIiMgURHnqxxHNCuntbHvK0X5WjoJF2zi9l0wBy/IYAZTMMHB/fXmbiln3BCT1+9XlbRpoPyvvEeimyCKLogGUzGQ1UjNGnEilssI2I6SCV90YeNVHFc76u2nH3IuPJCKSHjqzUnonumaMOGVPJa66Kth9APPCBJi+hJXcR1fdE/nlnmVLJBamuRhIRFJB51fq3KJrttWnszxObMVBMOsk6qz+YVNGMGtMVlrU2mIi6VNXjYYvhylgyYyz1hjMEtHMagrOsGoNYKYUMIaMSB3X6n9sYgujFrW2bN30x03/vomoouliEQkLA3ZpJhtds0UAW0QjRmjVerhZrt8RZQDjR7SvdPy9xCr5FSdGIhKcrOmrmTrU2VKIK6a/VnsaSPQtYMhsEPErHfsocc6MCE6GKWCR/jCrnoXZBoUVntRyy2p74mF4o0L7o3/43Yc/xYEV/n/ehDG19EZEQlCarUbXTB1o9tW/t1pxNTBRz9K5mFURo7z0CVkisRjUWfcyFZFEHN3+YLRmiv7BbPVjq9YBZruPXlXE64uZol8oHW9kzbid1VKYApbsZEtfzZZuy/bkj/dgJeSKkNpbgWgRXuqB6RMyPtGIFcOr7p8pIgGgZqY0O40qF4DE14qQgpx9NTC/L1L5RdZdDG6FCbQuUESGkCV9Em0AOsusBnDV+qbZnwoSafulWczfrlVLJ1JjClhmIEsagiJvTKDEZtVoBmngny9vp4PfFSXNjfn77vJ2GiidMB0sIt3Jkr6abY+5WSOAK+93NmsUMEr0L1vJylFpAkWkO9EHrhk7RlPAc5JxReojRUlP0q5mr7NEM9U5i0gCiAJGHbhmq/3bcRHInMx2XSMYeu7/VbbaQatPokSkM1EHrplnxKXfm10yTyo4yl510R7t1louChGR7kQbuGaviZktpeXA9ZHszwiOcu/NGil/T1HqLkVkIej4Sx1Sb2EmZl9R6qPg5iVz2jLSvZf1HNbQbAvfRCQBo03gCuYPZkkV7vLxVp+S0QRGuvdoT6VjXEWzPflIRJIwygSuYv5gtvSWEYvPyWQCI917HMcKq37fE32EiEh3WIDRsxNeMYU4yyDHKnIpg5mJXhNItCnSxGv16N8uo4AiMgyiOq1r1TBBbPK6IlFqLs/KovX3iZryj/KUj2tm20/xjNwWRkSGQiqithHE+DEoRoo89IbOvXRussn073NwH0UxNxxHxBTj7E/8OCoXV4lICBgw6JDOpC4ZeFY3fte0jrC2lgPUMWj3o6OBRP2i3n/Zt9CpLfpaEZFQMFNnIMPAPDKEGD46dep6TGd8Dqa6dN6yyOjfa3DeepcA8H2RrxemtHTcq2vVEhkRSQYmT6N3jKxRQKN/58GQEZE7E1V/JD43uvHbYeFZ6TesLu8zEZFJYXBuZQBaieM1jV8XIj0M9mfbwm76skWOTP+WxfWUQHzx9ioiUgNS5H+8vE3B329iwJY27JF0Jgf7Yg3ef315+4GfNlFiAUSRec9+figjGJ2vLm/lhl9uynpdRUTkHbJEQCJuHSK5wdyW2pq6yCftBMIIoEheGGyudQ1RlGv1hJQqkZxvPvwpJj9sirh9iOSGdPX/uLyVAt9vokZSAqABFMkDJo8BBn3Lf3iSnzdhyBDRuR6GMLIJ/HET5i9DTRLp071G8dqwXht7zrPEgB0Ffn95KwWceImIPAmDPzPmms9ixTD0mIVz7DWPu4Y4nt1QRQODz3Vh4cPR84YZdJui8bgA5H2JiMgDMClEE86upHwkTENrIxjJBDI4RzN/HE9tg4+4trSf29IAaQuTq9L1UB8lIiJ3IMXLAF7qPFsI89E6LcOCi9J39xJmKBIYsxpbpTwjjK9ptz5oAN+XkxIRkRuIBo1MIbU2Sb2NLcLcRkqJco1HmWHMiYNvW0rnXX0qJyMiIldgUnqbo5IwCS3TpHx269Q24vN71DkeAQPcI+L3nqJFQ2eidL7Vp9IAioi8gfmLYAx29YiatTKCPeoajzI6sltStMjoLJTOtfpUGkARkY1o5m8XRqplJPAaImNn6uE4VtKqEQ0NxxQhslsS5zuaWc5O6TyrT6UBDIL7AIqMA3NAyjXqY6NG7JfHOUHUqvFaMqEYKkQUC/E+ItGv784fNpkWrgNt8foxd/I5v9rEfSEisiQYm4iRv1thsOQ4RNZK5zOqiMDKeTA2pfOrPkpEZGkyDRQ+M/cYRP5K5zG6NIHn0QC+LxGRZeFpDaWOMbKs23kOzF+GyO49+bD+c5BKL51XdZEZBRFZFmrbMhqEqHV2kSCtz3kqnb9MYlGOvEbGyV1PWfsXiC/fXkWkD0QIoi8KKEFhu9Ghx5BCnWEBAL/DDaNfwwjXYzSAIrIkDKqlWXEWEbnstTVMNoialc5ZVjlQv07pfKqLjC6LyJIQWSl1ipnkvnGfk2VF91EZ8X0NF4LclxNIEVmOWUyCKa7PmcHYl2TE9zVcCFKWfUcwrAEU6QOpj4y1f7d8s4mVrnKBtP5vLm+ng/ZqFPA4PPZPPsdthkRkSaI9C/aMfGrER2aN/u0yCvgaM6wGry0XFgXDCKBIH2baR889AS/MHP3bMQr4Gka7PuWHTW4lFQwNoEh7SJnOkP7d+fbtdXVWWRDjwp/jaAA/xfMREA2gSHtmrJkzCriOMWJvQ6/3MYh2fX95uzw/bdIABkQDKNKeGWtfVq/nwdTPsOnzs7h/23F8hvYFz0NQNIAi7ZkxAri6AVzNEGkAj8O2J/98ebssRv8CowEUaY+rKOdjtZQo0U5XcR5n9QU07BjASnIJiAZQROQ4K+6FuOJvPgu1gH+4vF0OVv4a/QuMBlBE5BhEwmZa1f0sGsDXoAaOVOhquH1QcDSAIiLHWDUV6krg1yAFutpWOv+4yUe/BUcDKCJyDCNhcpR/2bRKKpiFL678TYAGUKQ9zoTnYtVFPRrfc7AgYvZVwT9ucuPwJGgARdoz4yo4IhqyFivWPdYGc4RJmpGfN/H7XPWbBA2gSHtmNEs+11PkOJgjailnM4GYP36X2Y5EaABF2jNbp0hnrwEUeY3ZTKDmLykaQJH20OHPNOM3/StyjllMoOYvMRpAkT7MZJr+9PYqazFr7doodhP4/Yc/5YP2wJZImj8RkQewgvI/JtHqj7bjubil8zK7jPy2g02TS+c8qnjCh4+4FBF5EmbKpc40k3y00yVqUzo3s8tr3xYmidH7CKKWTIBEROQAbJFQ6lQzCfMj5XMzu9jHTtrDecZola7BSBn1ExE5AatnS51rBpkC/Ejm6/iqNP/9oLYOw1W6Dr3Ffe+1FxE5SeYooIPAR6IMzj1l9Kc/I40gi72850VEKsKMutThRpb1X58yQzr/iFzpORbMNwtFWtcIEtnmOb4YTxERqQyda8Qan3viWI3+fArno3SuZhXmQ2JA/8H1IEJXox9hQkrdoc96Xowv3l5FpC9EkL67vA3PrzZZ//c5DMC/vrydnl9s8ukvMcG4YQr310fROwwjUUSuJfK+FhEZQIY6MiM/91klDaxJEBERqUxkE2jd3/tkSuW/KoyuiIiIVCaiCdT8PQe1U6XzN4tM+4qIiDQkkglkFaA8B4tBZo4CGv0TERFpDIPtSDPBdzvgHyfbc1yflbV/IiIinWAFHwNvaUBuKb7z0epBecwMz3m+lduCiIiIdIZIHPVXpYG5poz61QGzVDq/WWUZgIiIyEBaGUE+k9SlGzzXY5ZUsE/9EBERCQLP5GShyJkaQf4tn/F3m6QNEVd0HxFtxFIAkQXwSSAi+SDdiCG83vn/603X/LSJKB8iooMs6m8PEVXO8zcf/pSPX24yAigiIiJyEExgxkUh1oKKiIiInCCbCdT8iYiIiFRgTweXDFcUUfNHOYGIiIiIVIQtVUrma7SIULrXn4iIiEgjWHl9ZgV3bWFK3QJIREREpDEYrtHbxLAq3JSviIiISGdIu/auDcT4udBDREREZDD75t4lw1ZL1Plp/ERERESCQWoYk/anTSUTd1RE+6jxc4GHiNzhr/7q/wcfnaVXMj6pIwAAAABJRU5ErkJggg==';
		doc.setFontSize(25);
		doc.text(105, 110, 'IOTA SEED', null, null, 'center');
		doc.setFontSize(10);
		doc.text(105, 115, Array(85).join('_'), null, null, 'center');
		doc.setFontSize(16);
		doc.text(105, 130, chunk(seed.substring(0,44),4).join(' '), null, null, 'center');
		doc.text(105, 140, chunk(seed.substring(44),4).join(' '), null, null, 'center');
		doc.setFontSize(15);
		doc.setFontType("italic");
		doc.text(105, 170, 'SEED contains 81 character - UPPERCASE letters A-Z and number 9.', null, null, 'center');
		doc.text(105, 180, '(Spaces here are inserted so you could write down your SEED easier)', null, null, 'center');
		doc.setFontSize(18);
		doc.setFontType("normal");
		doc.text(105, 210, 'Print this paper and store it on a safe place.', null, null, 'center');
		doc.setFontType("bolditalic");
		doc.setTextColor(204, 0, 0);
		doc.text(105, 230, 'If you lose your SEED or it gets stolen, you will', null, null, 'center');
		doc.text(105, 240, 'lose all your money!!!', null, null, 'center');
		doc.setFontSize(10);
		doc.setFontType("normal");
		doc.setTextColor(128, 128, 128);
		doc.text(105, 290, 'www.iota.org', null, null, 'center');
		doc.addImage(logo,'PNG', 72, 15, 56, 56);
		doc.addImage(QRcode,'PNG', 170, 4, 40, 40);
		doc.output('save','seed.pdf');
		$('.loader').removeClass('is-active');
		$('#command').val('account-info');
	},3000);
}

function isNumeric(num) {
	return !isNaN(num);
}

function exitWarning() {
	$(window).bind('beforeunload', function() {
		return 'Are you sure you want to leave? This will interrupt the current process!';
	});
}

$(function() {
	// Donation processing
	$('#iota-donation-unit li').click(function() {
		$('#iota-donation-selected-unit').val($(this).text());
		$('#iota-donation-unit-button').html($(this).text()+' <i class="fa fa-caret-down"></i>');
	});
	$('#donation-amount').change(function() {
		if (iota.valid.isNum($(this).val())) {
			$('#donation-amount-div').removeClass('has-error').addClass('has-success');
		}
		else {
			$('#donation-amount-div').removeClass('has-success').addClass('has-error');
		}
	});
	$('#donation-form').submit(function(e) {
		e.preventDefault();
		var donation_unit = $('#iota-donation-selected-unit').val();
		var user_balance = Number($('#user-balance').val());
		if (donation_unit == 'iota') {donation = $('#donation-amount').val();}
		if (donation_unit == 'Ki') {donation = $('#donation-amount').val() * Math.pow(10,3);}
		if (donation_unit == 'Mi') {donation = $('#donation-amount').val() * Math.pow(10,6);}
		if (donation_unit == 'Gi') {donation = $('#donation-amount').val() * Math.pow(10,9);}
		if (donation_unit == 'Ti') {donation = $('#donation-amount').val() * Math.pow(10,12);}
		if (donation_unit == 'Pi') {donation = $('#donation-amount').val() * Math.pow(10,15);}
		if (donation < 1) {
			$('#donation-amount-div').removeClass('has-success').addClass('has-error');
			alert('Minimal donation is just 1 iota...');	
		}
		if (donation > user_balance) {
			$('#donation-amount-div').removeClass('has-success').addClass('has-error');
			alert('You don\'t have that much money in your account...');
		}
		if (donation >= 1 && donation <= user_balance) {
			$('#donation-amount-div').removeClass('has-error').addClass('has-success');
			$('#donations').modal('toggle');
			$('#command').val('send-donation');
			$('#pin').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
	// Clear written PIN and 'placeholder' on modal open / close
	$('#pin').on('hidden.bs.modal', function () {
		if ($("#pin-text").length) {
			$("#pin-text").val('');
		}
		else {
			$("#pin-number").val('');
		}
	});
	$('#pin').on('shown.bs.modal', function (e) {
		if ($("#pin-text").length) {
			$("#pin-text").attr('placeholder','');
			$("#pin-text").focus();
		}
		else {
			$("#pin-number").attr('placeholder','');
			$("#pin-number").focus();
		}
	})
	// Exceptions for mobile devices (trimming for simple pin)
	if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
		if ($("#pin-number").length) {
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
	}
	else {
		if ($("#pin-number").length) {
			// PIN mask for desktop devices (jquery.maskedinput.min.js)
			$("#pin-number").mask("9 9 9 9");
		}
	}
	// Trimming for advanced PIN
	if ($("#pin-text").length) {
		$("#pin-text").keydown(function(e){
			if ($(this).val().length >= 32) { 
				$(this).val($(this).val().substr(0,32));
			}
		});
		$("#pin-text").keyup(function(e){
			if ($(this).val().length >= 32) { 
				$(this).val($(this).val().substr(0,32));
			}
		});
	}
	$('#confirm-pin').submit(function(e) {
		e.preventDefault();
		command = $('#command').val();
		var button = $(this).find('.modal-footer').html();
		// Remove spaces
		if ($("#pin-text").length) {
			var pin = $("#pin-text").val().replace(/\s+/g,'');
		}
		else {
			var pin = $("#pin-number").val().replace(/\s+/g,'');
		}
		// Check PIN length
		if (pin.length == 4 || pin.length >= 8) {
			if ($("#pin-text").length) {
				$("#pin-text").attr('placeholder','Checking...');
				$("#pin-text").val('');
			}
			else {
				$("#pin-number").attr('placeholder','Checking...');
				$("#pin-number").val('');
			}
			// Try to decrypt SEED with given PIN
			try {
				$(this).find('.modal-footer').html('<span><img src="img/loading.gif" style="width:32px;height:32px;display:block;margin-left:auto;margin-right:auto;" /></span>');
				seed = decryptAES(encrypted,pin).toString(CryptoJS.enc.Utf8);
			}
			catch(err) {
				$('#confirm-pin').find('.modal-footer').html(button);
				seed = null;
			}
			finally {
				// If decrypted string has SEED format (81 char, A-Z + #9) -> PIN is ok!
				if (isSeedValid(seed)) {
					setTimeout(function() {
						$('#pin').modal('toggle');
						$('.loader').addClass('is-active');
						// Refresh account data
						if (command == 'account-info') {
							exitWarning();
							reload = true;
							getAccountInfo(seed);
						}
						// Turn ON tx auto monitoring
						if (command == 'auto-monitor') {
							sessionStorage.setItem("PIN",pin);
							autoUpdateInterval = setInterval(function() {
								autoTxUpdate();
							},180000);
							$('.loader').removeClass('is-active');
							$('#auto-monitoring').val('on');
							$('#auto-monitoring').find('i').css('color','green');
							$('#confirm-pin').find('.modal-footer').html(button);
						}
						// Download seed
						if (command == 'download-seed') {
							$('#confirm-pin').find('.modal-footer').html(button);
							generatePDF(seed);
						}
						if (command == 'change-email') {
							$('#change-email-form').unbind('submit').submit();
						}
						if (command == 'change-password') {
							$('#change-password-form').unbind('submit').submit();
						}
						if (command == 'change-pin') {
							if ($('#pin-type').val() == 'simple') {
								var new_pin = $('#simple-pin-number').val().replace(/\s+/g,'');
							}
							if ($('#pin-type').val() == 'advanced') {
								var new_pin = $('#advanced-pin-text').val().replace(/\s+/g,'');
							}
							$('#encrypted-seed').val(encryptAES(seed,new_pin));
							$('#change-pin-form').unbind('submit').submit();
						}
						// Generating address
						if (command == 'generate-address') {
							exitWarning();
							reload = false;
							genAddress(seed,command);
							$('#confirm-pin').find('.modal-footer').html(button);
						}
						// Re-attaching tx
						if (command == 'reattach') {
							if ($('#auto-monitoring').val() == 'on') {
								clearInterval(autoUpdateInterval);
								sessionStorage.setItem("PIN",null);
								$('#auto-monitoring').val('off');
								$('#auto-monitoring').find('i').css('color','#e50000');
							}
							exitWarning();
							reload = true;
							reAttach();
							$('#confirm-pin').find('.modal-footer').html(button);
						}
						if (command == 'send-donation') {
							exitWarning();
							reload = false;
							redirect = window.location.href+'?donation=true';
							sendTransfer(seed,donationAddress,donation,null,'DONATION');
							$('#confirm-pin').find('.modal-footer').html(button);
						}
						if (command == 'send-money') {
							exitWarning();
							reload = false;
							redirect = window.location.href+'?sent=true';
							receiver = $('#receiver-address').val();
							message = $('#message').val();
							tag = $('#tag').val();
							sendTransfer(seed,receiver,money,message,tag);
							$('#confirm-pin').find('.modal-footer').html(button);
						}
					},1000);
				}
				else {
					// SEED is not in accepted format, given PIN is wrong
					$('#confirm-pin').find('.modal-footer').html(button);
					alert('Wrong PIN!');
					$("#pin-number").attr('placeholder','');
					if ($("#pin-text").length) {
						$("#pin-text").attr('placeholder','');
						$("#pin-number").focus();
					}
				}
			}
		}
		else {
			if ($("#pin-text").length) {
				alert('PIN has minimum 8 characters!');
			}
			else {
				alert('PIN has 4 digits!');
			}
		}
	});
});
