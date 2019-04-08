<?php require $_SERVER['DOCUMENT_ROOT'].'/system/top.php'; ?>
<?php
	if (!$user) {
		echo '<div style="width:500px; margin: 0 auto;">';
		$did = sysLogin ($input);
		echo '<p><a href="reg.php">Зарегистрироваться</a></p>';
		echo '</div>';
		if ($did == 'done') {
			if (isset ($input['back']))	$direct = $input['back'];
			 else $direct = '/';
		}
	} else {
		$direct = '/';
	}
?>

<?php require $_SERVER['DOCUMENT_ROOT'].'/system/bottom.php'; ?>
