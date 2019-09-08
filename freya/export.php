<?php
	require $_SERVER['DOCUMENT_ROOT'].'/system/top.php';
	needAuth();
	
	$id = $input['id'];
	$pr = $data['mysql']['pref'].'_';
	$elem = $con->query("select * from {$pr}struct where id='$id';")->fetch();
	if ($id && !$elem) $direct = 'index.php';
	if (!grantedForMe ($elem['id'], VIEW_TABLE)) $direct = 'index.php';
	
	function checkEl ($id) {
		global $con, $pr, $elemList, $elemCols, $elemData, $elemFiles, $elemGrants;
		$row = $con->query("select * from {$pr}struct where id='$id';")->fetch();
		$par = $con->query("select * from {$pr}struct where id='{$row['parent']}';")->fetch();
		if (isset ($elemList[$row['hid']])) return;
		$elemList[$row['hid']] = [
			'id'=>$row['hid'],
			'par'=>$par['hid'],
			'capt'=>$row['caption'],
			'alias'=>$row['alias'],
			'lastmod'=>$row['lastmod'],
		];
		if (!isset ($elemCols[$par['hid']])) {
			$res2 = $con->query ("select * from {$pr}columns where groupid='{$par['id']}' order by sort;");
			$elemCols[$par['hid']] = [];
			while ($row2 = $res2->fetch()) {
				$elemCols[$par['hid']][] = [
					'capt'=>$row2['caption'],
					'name'=>$row2['vrname'],
					'type'=>$row2['typ'],
					'type2'=>$row2['typ2'],
					'format'=>$row2['format'],
					'default'=>$row2['def'],
					'keep'=>$row2['keep'],
				];
			}
		}
		$res2 = $con->query("select * from {$pr}data where elem='{$row['id']}';");
		while ($row2 = $res2->fetch()) {
			$var = $con->query("select * from {$pr}columns where id='{$row2['var']}';")->fetch();
			$value = $row2['value'];
			if ($var['typ'] == 'file') {
				$fl = $con->query("select * from {$pr}files where id='{$row2['value']}';")->fetch();
				$value = $fl['path'];
				$elemFiles[$value] = [
					'filename'=>$fl['nam'],
					'path'=>$fl['path'],
					'content'=>base64_encode (file_get_contents($_SERVER['DOCUMENT_ROOT'].'/files/'.$fl['path'])),
				];
			}
			if ($var['typ'] == 'select') {
				checkEl ($con->query("select * from {$pr}struct where hid='{$var['typ2']}';")->fetch()['id']);
			}
			$elemData[$row['hid']][] = [
				'var'=>$var['vrname'],
				'value'=>$value,
			];
		}
		$res2 = $con->query("select * from {$pr}rights where basis='{$row['id']}';");
		while ($row2 = $res2->fetch()) {
			$grt = '';
			if ($row2['uid'] == 0) $grt = 'a';
			if ($row2['uid'] > 0) {
				$row3 = $con->query("select * from {$pr}users where id='{$row2['uid']}';")->fetch();
				if ($row3) $grt = 'u'.$row3['login'];
			}
			if ($grt) $elemGrants[] = [
				'id'=>$row['hid'],
				'uid'=>$grt,
				'grants'=>$row2['grants'],
			];
		}
		$res2 = $con->query("select * from {$pr}struct where parent='{$row['id']}' order by sort;");
		while ($row2 = $res2->fetch()) checkEl ($row2['id']);
	}
	
	if (!$direct) {
		$elemCols = [];
		$elemList = [];
		$elemData = [];
		$elemFiles = [];
		$elemGrants = [];
		checkEl ($elem['id']);
		$Arr = ['elements'=>$elemList, 'columns'=>$elemCols, 'data'=>$elemData, 'grants'=>$elemGrants, 'files'=>$elemFiles];
		$result = serialize($Arr);
		
		if ($id == 0) $filename = 'site_'.$_SERVER['HTTP_HOST']; else {
			$filename = $elem['caption'];
			$filename = preg_replace ('/([^A-Za-zА-Яа-я0-9])/ui', '_', $filename);
			$filename = preg_replace ('/(^_+|(_)_+)/ui', '$2', $filename);
		}
		$filename .= '.frc';

		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename='.$filename);
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . strlen($result));
		echo $result;
	}


