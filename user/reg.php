<?php require $_SERVER['DOCUMENT_ROOT'].'/system/top.php'; ?>
<?php
	if (!$user) {
		echo '<div style="width:500px; margin: 0 auto;">';
		$did = sysRegister ($input);
		echo '<p><a href="index.php">Вход</a></p>';
		echo '</div>';
		if ($did == 'done') $direct = '/';
	} else {
		$direct = '/';
	}
?>

<?php require $_SERVER['DOCUMENT_ROOT'].'/system/bottom.php'; ?>
