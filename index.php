<?php
	require $_SERVER['DOCUMENT_ROOT'].'/system/top.php';

			if (!isset ($_POST) || $_POST==[]) {
				$cache_hash = hash('SHA256', $_SERVER['HTTP_HOST'].' -> '.$_SERVER['REQUEST_URI']);
				$cache_filename = $_SERVER['DOCUMENT_ROOT'].'/cache/'.$cache_hash.'.html';
				if (is_file($cache_filename)) {
					$html = file_get_contents ($cache_filename);
					echo $html;
					die();
				}
			}

	preg_match_all ('/^\/([^\/]+)\//ui', $_SERVER['REQUEST_URI'], $x);
	if (count ($x[0])) $input['page'] = $x[1][0];
	$html_code = 'Freya v2.0<br />';
	$vars = [];
	$struct = isset ($input['page']) ? $input['page'] : 'index';
	while (true) {
		$elem = $con->query("select * from {$data['mysql']['pref']}_struct where alias='$struct' or hid='$struct';")->fetch();
		if ($elem || $struct == 'index') break;
		$struct = 'index';
	}
	if ($elem && grantedForMe($elem['id'], VIEW_PAGE)) {
		$dat = $con->query("select * from {$data['mysql']['pref']}_data where elem='{$elem['id']}' and var in (select id from {$data['mysql']['pref']}_columns where caption='HTML');")->fetch();
		if ($dat) {
			if (is_array ($input)) foreach ($input as $vr => $vl) {
				addVars ($vars, $vr, $vl, ['get']);
			}
			if (isset ($input['p'])) addVars ($vars, 'pageId', ($input['p']+1));
			addVars ($vars, 'template.id', $elem['id']);
			addVars ($vars, 'title', $elem['caption'], ['page']);
			addVarsFrom ($vars, $elem['id'], ['page']);
			$html_code = $dat['value'];//applyWiki ($dat['value']);
		}
		if (!isset ($input['type']) || $input['type'] == 'css') {
			$dat = $con->query("select * from {$data['mysql']['pref']}_data where elem='{$elem['id']}' and var in (select id from {$data['mysql']['pref']}_columns where caption='CSS');")->fetch();
			if ($dat && $dat['value']) {
				header ('Content-type: text/css');
				echo applyCode ($dat['value'], $vars);
				die();
			}
		}
		if (!isset ($input['type']) || $input['type'] == 'js') {
			$dat = $con->query("select * from {$data['mysql']['pref']}_data where elem='{$elem['id']}' and var in (select id from {$data['mysql']['pref']}_columns where caption='JS');")->fetch();
			if ($dat && $dat['value']) {
				header ('Content-type: application/javascript');
				echo applyCode ($dat['value'], $vars);
				die();
			}
		}
	}
	$html_code = applyTemplates ($html_code, $vars);
	$html_code = applyWiki ($html_code);
	
	require $_SERVER['DOCUMENT_ROOT'].'/system/bottom.php';

