<?php
	require $_SERVER['DOCUMENT_ROOT'].'/system/top.php';
	needAuth();
	
	require 'a_top.php';
	
	if (isset ($input['act']) && $input['act'] == 'struct_del' && sysDelFromStruct ($input) == 'done') $direct = $_SERVER['HTTP_REFERER'];
	
	$id = (isset ($input['id'])) ? $input['id'] : 0;
	$pr = $data['mysql']['pref'].'_';
	$elem = $con->query("select * from {$pr}struct where id='$id';")->fetch();
	if ($id && !$elem) $direct = '?id=0';
	$input['id'] = $id;
	
	$h1 = ($id == 0) ? 'Корень' : $elem['caption'];
	$title = $h1 . ' - ' . $title;
	$tab = 'elem2';
	if (isset ($input['tab'])) $tab = 'elem'.$input['tab'];
	if (isset ($input['grant'])) $tab = 'elem4';
?>

<h1><?=$h1?></h1>

<div class="hdr">
	<div class="tab1 tab_hdr elem1" onclick="Tabs('tab1', 'elem1');">Элемент</div>
	<div class="tab1 tab_hdr elem2" onclick="Tabs('tab1', 'elem2');">Данные</div>
	<div class="tab1 tab_hdr elem3" onclick="Tabs('tab1', 'elem3');">Поля</div>
	<div class="tab1 tab_hdr elem4" onclick="Tabs('tab1', 'elem4');">Доступ</div>
</div>
<div class="dat">
	<div class="tab1 tab_dat elem1">
		<? if (sysStructInfo ($input) == 'done') $direct = '?id='.$input['id'].'&tab=1'; ?>
		<? sysGetStruct ($id, -1, 0); ?>
		<? if (sysAddToStruct ($input) == 'done') $direct = '?id='.$input['id'].'&tab=1'; ?>
		<p>
		<?php if (grantedForMe($input['id'], 'VIEW_TABLE')) { ?><button type="button" onclick="location.href='export.php?id=<?=$input['id']?>';">Экспорт</button><?php } ?>
		<?php if (grantedForMe ($elem['id'], 'INSERT_TO_TABLE')
			&& grantedForMe ($elem['id'], 'EDIT_TABLE_DATA')
			&& grantedForMe ($elem['id'], 'EDIT_COLUMN_LIST')) { 
			$form = [
				'caption'=>'Импорт компонента',
				'fields'=>[
					['Файл','fl','file'],
				],
				'submit'=>'import.php?id='.$elem['id'],
				'spoiler'=>'Импорт',
			];
			echo makeForm ($form);
			 } ?>
		</p>
	</div>
	<div class="tab1 tab_dat elem2">
		<? if (sysShowVarStruct ($input) == 'done') $direct = '?id='.$input['id'].'&tab=2'; ?>
		<? if ($id && sysAddVarToStruct ($input) == 'done') $direct = '?id='.$input['id'].'&tab=2'; ?>
		<? if ($id) { if ($id && isset($input['similar'])) sysShowSimilarVars ($id); else echo '<button onclick="location.href=\'?id='.$input['id'].'&similar=yes\';">Добавить похожие поля</button>'; } ?>
	</div>
	<div class="tab1 tab_dat elem3">
	 <? if (sysShowStructFields ($input) == 'done') $direct = '?id='.$input['id'].'&tab=3'; ?>
	</div>
	<div class="tab1 tab_dat elem4">
	 <? sysShowStructGrants ($input) ?>
	 <? if (sysEditStructGrants ($input) == 'done') $direct = '?id='.$input['id'].'&tab=4'; ?>
	</div>
</div>
<script>Tabs('tab1', '<?=$tab?>');</script>

<?php
	require 'a_bottom.php';
	require $_SERVER['DOCUMENT_ROOT'].'/system/bottom.php';
