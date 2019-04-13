<?php require $_SERVER['DOCUMENT_ROOT'].'/system/top.php'; ?>
<?php
	if ($user) {
		echo '<div style="width:500px; margin: 0 auto;">';
		$did = sysChPass ($input);
		echo '<p><a href="/">На главную</a></p>';
		echo '</div>';
		if ($did == 'done') $direct = '/';
	} else {
		$direct = 'index.php';
	}
?>

<?php require $_SERVER['DOCUMENT_ROOT'].'/system/bottom.php'; ?>
