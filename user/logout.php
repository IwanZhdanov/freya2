<?php require $_SERVER['DOCUMENT_ROOT'].'/system/top.php'; ?>
<?php
	if (!$user) {
		$direct = '/';
	} else {
		$did = sysLogout ($input);
		$direct = '/';
	}
?>

<?php require $_SERVER['DOCUMENT_ROOT'].'/system/bottom.php'; ?>
