<?php
	function makeSiteMap () {
		global $con, $data, $vars;
		ini_set('display_errors', 'Off');
		error_reporting('E_NONE');
		$pr = $data['mysql']['pref'].'_';
		$res = $con->query("select * from {$pr}struct where alias != '' order by alias;");
		$list = [];
		$list['/'] = ['always','1.0',0];
		$stopAliases = ['index','404','restore','multilang'];
		while ($row = $res->fetch()) {
			if ($row['alias'] == 'index') {
				$list['/'][2] = $row['lastmod'];
				inCacheAdd ($row['id']);
			}
			if (strpos ($row['alias'], '.') !== false) continue;
			if (in_array ($row['alias'], $stopAliases)) continue;
			if (!grantedForMe ($row['id'], VIEW_PAGE)) continue;
			inCacheAdd ($row['id']);
			$row2 = $con->query("select * from {$pr}data where elem={$row['id']} and var in (select id from {$pr}columns where caption='html');")->fetch();
			addVars ($vars, 'prioritySelf', '0.8');
			addVars ($vars, 'changefreqSelf', 'weekly');
			addVars ($vars, 'priorityChildren', '0.5');
			addVars ($vars, 'changefreqChildren', 'daily');
			$code = applyCode ($row2['value'], $vars);
			$code = applyWiki ($code, true);
			$list['/'.$row['alias'].'/'] = [getVars($vars,'changefreqSelf'),getVars($vars,'prioritySelf'),$row['lastmod']];
			preg_match_all ('/\/'.$row['alias'].'\/[^\'" >]*/ui', $code, $x);
			$q = count ($x[0]);
			for ($a=0;$a<$q;$a++) {
				$link = $x[0][$a];
				preg_match_all ('/\d+/ui', $link, $y);
				$row3 = $con->query("select * from {$pr}struct where hid='{$y[0][0]}';")->fetch();
				if ($row3) inCacheAdd ($row3['id']);
				$list[$link] = [getVars($vars,'changefreqChildren'),getVars($vars,'priorityChildren'),$row2['lastmod']];
			}
		}
		$langs = [];
		$reslang = $con->query("select * from {$pr}data where elem in (select id from {$pr}struct where parent in (select id from {$pr}struct where alias='multilang')) and var in (select id from {$pr}columns where vrname = 'lang') order by sort;");
		while ($rowlang = $reslang->fetch()) $langs[] = $rowlang['value'];
		if ($langs != []) {
			$tmp = [];
			foreach ($list as $vr => $vl) {
				foreach ($langs as $langId) {
					$tmp['/'.$langId.$vr] = $vl;
				}
			}
			$list = $tmp;
		}
		$protocol = (!empty($_SERVER['HTTPS']) && 'off' !== strtolower($_SERVER['HTTPS'])?"https://":"http://");
		$ret = '';
		$ret .= '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$ret .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
		foreach ($list as $link => $linkdata) {
			$ret .= '<url>'."\n";
			$ret .= "\t".'<loc>'.$protocol.$_SERVER['HTTP_HOST'].$link.'</loc>'."\n";
			if ($linkdata[2]) $ret .= "\t".'<lastmod>'.date('Y-m-d\TH:i:sO', $linkdata[2]).'</lastmod>'."\n";
			$ret .= "\t".'<changefreq>'.$linkdata[0].'</changefreq>'."\n";
			$ret .= "\t".'<priority>'.$linkdata[1].'</priority>'."\n";
			$ret .= '</url>'."\n";
		}
		$ret .= '</urlset>'."\n";
		ini_set('display_errors','On');
		error_reporting('E_ALL');
		return $ret;
	}
