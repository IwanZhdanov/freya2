<?php
	require $_SERVER['DOCUMENT_ROOT'].'/system/top.php';
	needAuth();
	
	$id = $input['id'];
	$pr = $data['mysql']['pref'].'_';
	$elem = $con->query("select * from {$pr}struct where id='$id';")->fetch();
	if ($id && !$elem) $direct = 'index.php';
	if (!grantedForMe ($elem['id'], 'INSERT_TO_TABLE')
	|| !grantedForMe ($elem['id'], 'EDIT_TABLE_DATA')
	|| !grantedForMe ($elem['id'], 'EDIT_COLUMN_LIST')) $direct = 'index.php';
	
	function deq ($s) {
		global $str;
		foreach ($str as $vr => $vl) if ($vr && $vr != $vl['hid']) $s = preg_replace ('/'.$vr.'/ui', $vl['hid'], $s);
		return 'concat('.de_quotes($s,"'\"", ',').')';
	}
	
	if (!$direct) {
		$cont = file_get_contents ($_FILES['fl']['tmp_name']);
		$Arr = unserialize ($cont);
		$str = [];
		$fls = $Arr['files'];
		foreach ($Arr['elements'] as $vr => $vl) $str[$vr] = ['hid'=>$vr, 'par'=>$vl['par'], 'ischild'=>false];
		foreach ($str as $vr => $vl) if (isset ($str[$vl['par']])) $str[$vr]['ischild'] = true;
		foreach ($str as $vr => $vl) {
			if ($con->query("select * from {$pr}struct where hid='{$vr}';")->rowCount()) $str[$vr]['hid'] = unic('123456789', 8, $pr.'struct', 'hid');
		}
		foreach ($str as $vr => $vl) if (!$vl['ischild']) $str[$vr]['par'] = $elem['hid'];
		
		foreach ($Arr['elements'] as $vr => $vl) {
			$con->exec("insert into {$pr}struct (hid, caption, alias, lastmod) values ('{$str[$vr]['hid']}', ".deq($vl['capt']).", ".deq($vl['alias']).", ".deq($vl['lastmod']).");");
			$str[$vr]['id'] = $con->query("select * from {$pr}struct order by -id limit 1;")->fetch()['id'];
		}
		$str['']['id'] = $elem['id'];
		$str['']['hid'] = $elem['hid'];
		$str[$elem['hid']]['id'] = $elem['id'];
		$str[$elem['hid']]['hid'] = $elem['hid'];
		foreach ($str as $vr => $vl) if ($vr != '' && $vr != $elem['hid']) {
			$con->exec ("update {$pr}struct set parent='{$str[$vl['par']]['id']}' where hid='{$vl['hid']}';");
		}
		foreach ($Arr['columns'] as $vr => $vl) {
			foreach ($vl as $vr2 => $vl2) {
				if (isset ($str[$vr]['id'])) $grid = $str[$vr]['id']; else $grid = $id;
				$q = $con->query ("select * from {$pr}columns where groupid = '$grid' and vrname = '{$vl2['name']}';")->rowCount();
				if (!$q) {
					$t2 = deq($vl2['type2']);
					if ($vl2['type'] == 'select' && isset ($str[$vl2['type2']])) $t2 = $str[$vl2['type2']]['hid'];
					$con->exec ("insert into {$pr}columns (groupid, caption, vrname, typ, typ2, format, def, keep) values ('$grid', ".deq($vl2['capt']).", ".deq($vl2['name']).", ".deq($vl2['type']).", ".$t2.", ".deq($vl2['format']).", ".deq($vl2['default']).", ".deq($vl2['keep']).");");
				}
			}
		}
		foreach ($Arr['data'] as $vr => $vl) {
			foreach ($vl as $vr2 => $vl2) {
				$var = $con->query("select * from {$pr}columns where groupid = '{$str[$str[$vr]['par']]['id']}' and vrname = '{$vl2['var']}';")->fetch();
				$i = $con->query("select * from {$pr}data where elem='{$str[$vr]['id']}' and var = '{$var['id']}';")->fetch();
				$value = $vl2['value'];
				if ($var['typ'] == 'file') {
					$x = explode ('.', $fls[$value]['path']);
					$newPath = unic ('123456789', 10) . '.' . $x[count($x)-1];
					$fls[$value]['newPath'] = $newPath;
					$content = base64_decode ($fls[$value]['content']);
					$f = fopen ($_SERVER['DOCUMENT_ROOT'].'/files/'.$newPath, 'w');
					fwrite ($f, $content);
					fclose ($f);
					$con->exec("insert into {$pr}files (col, elem, nam, path) values ('{$var['id']}', '{$str[$vr]['id']}', ".deq($fls[$value]['filename']).", '{$newPath}');");
					$value = $con->query("select * from {$pr}files order by -id limit 1;")->fetch()['id'];
				}
				if ($var['typ'] == 'select') {
					$value = $str[$value]['hid'];
				}
				if ($i) {
					$con->exec ("update {$pr}data set value = ".deq($value)." where id='{$i['id']}';");
				} else {
					$con->exec ("insert into {$pr}data (elem, var, value) values ('{$str[$vr]['id']}', '{$var['id']}', ".deq($value).");");
				}
			}
		}
		foreach ($Arr['grants'] as $vr => $vl) {
			$g = false;
			if ($vl['uid'] == 'a') $g = 0;
			if ($vl['uid'] && $vl['uid'][0] == 'u') {
				$row2 = $con->query("select * from {$pr}users where login='".substr($vl['uid'],1)."';")->fetch();
				if ($row2) $g = $row2['id'];
			}
			if ($g !== false) {
				$con->exec ("insert into {$pr}rights (basis, uid, grants) values ('{$str[$vl['id']]['id']}', '$g', '{$vl['grants']}');");
			}
		}
		normal ($pr.'struct');
		normal ($pr.'columns');
		normal ($pr.'data');
		
		//print_r ($str);
		//echo '<hr>';
		//print_r ($Arr);
		//die();
		$direct = 'struct.php?id='.$id.'&tab=1';
	}

	if ($direct) header ('Location: '.$direct);
