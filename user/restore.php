<?php require $_SERVER['DOCUMENT_ROOT'].'/system/top.php'; ?>
<?php
	echo '<div style="width:500px; margin: 0 auto;">';
	$did = sysPassRestore ($input);
	echo '<p><a href="/">На главную</a></p>';
	echo '</div>';
	if ($did == 'done') $direct = '/';
?>

<?php require $_SERVER['DOCUMENT_ROOT'].'/system/bottom.php'; ?>
