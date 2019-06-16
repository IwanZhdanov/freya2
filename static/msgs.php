<?php
	require '../system/top.php';
	$msg = $session['msg'];
	$err = $session['err'];
	$code = '';
	if ($err) {
		$session['err'] = '';
		$code .= '<div class="err">'.$err.'</div>';
	}
	if ($msg) {
		$session['msg'] = '';
		$code .= '<div class="msg">'.$msg.'</div>';
	}
	header ('Content-type: application/javascript');
	if (!$code) echo '&nbsp;'; else {
?>
function showMsgs () {
	var arr = document.getElementsByClassName('msgs-here');
	var a, q = arr.length;
	for (a=0;a<q;a++) {
		arr[a].outerHTML = '<?=$code?>' + arr[a].outerHTML;
	}
}
showMsgs ();
<?php
	}
	if (isset ($data)) $_SESSION[$data['site']['id']] = $session;
?>
