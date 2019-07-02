<?php
	needProcess();
	if (!grantedAnyForMe()) $direct = '/';
?>
<div class="row">
	<div class="col-12" style="text-align: right; background:black; color: white;">
		<a href="/" style="color:white;">Сайт: <?=$_SERVER['HTTP_HOST']?></a> --
		CMS Freya <?=$cms_version?>,
		[<?=$user['login']?>]
		<a href="/user/logout.php" style="color:white;">Выход</a> 
	</div>
</div>
<div class="row">
	<div class="col-2" style="border-right:#ccc solid 1px">
		<p><a href="search.php">Поиск</a></p>
		<p><a href="struct.php?id=0">Корень</a></p>
		<div class="left_list">
		<?php
			$id = isset ($input['id']) ? $input['id'] : -1;
			sysGetStruct (0, $id);
		?>
		</div>
	</div>
	<div class="col-10">

