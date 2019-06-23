<?php
	require '../system/top.php';
	header ('Content-type: application/javascript');
	$csrf = CSRF_generate();
?>
fillCSRF ('<?=$csrf?>');
<?php
	if (isset ($data)) $_SESSION[$data['site']['id']] = $session;
?>
