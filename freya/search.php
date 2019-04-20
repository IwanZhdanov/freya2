<?php
	require $_SERVER['DOCUMENT_ROOT'].'/system/top.php';
	needAuth();
	
	require 'a_top.php';
	
	echo '<h1>Поиск</h1>';
	$form = [
		'defaults'=>$input,
		'fields'=>[
			['Запрос', 'query', 'text'],
		],
		'submit'=>'?act=query',
	];
	echo makeForm ($form);
	
	if (isset ($input['query'])) {
		$pr = $data['mysql']['pref'].'_';
		$quest = "concat ('%',".de_quotes($input['query'], "'\"", ',').",'%')";
		$ln = mb_strtolower ($input['query']);

		$in = [
			['struct', 'id', ['hid','caption','alias']], 
			['data', 'elem', ['value']], 
		];
		$found = '';
		foreach ($in as $vl) {
			$where = '';
			$comma = '';
			foreach ($vl[2] as $col) {
				$where .= $comma . $col.' like '.$quest;
				$comma = ' or ';
			}
			$sql = "select {$vl[1]} as found, tab.* from {$pr}{$vl[0]} tab where ".$where.';';
			$res = $con->query ($sql);
			while ($row = $res->fetch()) if (grantedForMe($row['found'], VIEW_TABLE)) {
				$element = $con->query("select * from {$pr}struct where id='{$row['found']}';")->fetch();
				$found .= '<li>['.$element['hid'].'] <a href="struct.php?id='.$row['found'].'">'.$element['caption'].'</a>';
				$t = '';
				foreach ($row as $txt) if (mb_strpos(mb_strtolower($txt), $ln) !== false) $t = $txt;
				if ($t) $found .= '<br />'.$t.'';
				$found .= '</li>';
			}
		}
		if (!$found) echo '<p>Ничего не найдено.</p>'; else {
			echo '<p>Результат поиска:</p><ol>'.$found.'</ol>';
		}
		
	}
	
	require 'a_bottom.php';
	require $_SERVER['DOCUMENT_ROOT'].'/system/bottom.php';
