<?php
	$mailList = [];
	
	function doMail ($p, $vars, $send) {
		global $con, $data, $mailList;
		$pr = $data['mysql']['pref'].'_';
		if ($send) {
			foreach ($p as $vr => $vl) if (mb_strpos ($vr, '_q') === false) {
				$row = $con->query("select * from {$pr}columns where id='{$vr}';")->fetch();
				if ($row) $vars[$row['vrname']] = $vl;
			}
			foreach ($mailList as $element => $tmp) if ($tmp) {
				$inf = [
					'email'=>'',
					'subject'=>'Заголовок сообщения',
					'body'=>'Текст письма не заполнен',
				];
				$res=$con->query("select vrname, value from {$pr}columns c, {$pr}data d where d.var = c.id and d.elem = '$element';");
				while ($row=$res->fetch()) $inf[$row['vrname']] = $row['value'];
				foreach ($inf as $vr => $vl) {
					$inf[$vr] = applyCode ($vl, $vars);
				}
				$inf['email'] = str_replace (',', ' ', $inf['email']);
				$inf['email'] = str_replace (';', ' ', $inf['email']);
				$emails = explode (' ', $inf['email']);
				$inf['body'] = iconv ('UTF-8', 'KOI8R', $inf['body']);
				foreach ($emails as $vr=>$vl) if ($vl) {
					mail ($vl, $inf['subject'], $inf['body']);
				}
			}
		}
		$mailList = [];
	}

	function needAuth () {
		global $session;
		if (!$session['user']) {
			$link = '/user/?back='.urlencode($_SERVER['REQUEST_URI']);
			header ('Location: '.$link);
			die();
		}
	}
	
	function needProcess () {
		if (!defined ('PROCESSING')) {
			header ('Location: /');
			die();
		}
	}
	
	function makeForm ($data) {
		global $session, $con;
		ini_set('display_errors','Off');
		$fchId = rand (1000, 9999);
		$showHide = '';
		$ret = '';
		if (isset ($data['spoiler'])) $data['cancel'] = 'javascript:%%SPOILER%%';
		$multipart = '';
		for ($a=0;$a<count($data['fields']);$a++) if ($data['fields'][$a][2] == 'file') $multipart = ' enctype="multipart/form-data"';
		if ($data['submit']) $ret .= '<form method="post" action="'.$data['submit'].'"'.$multipart.'>';
		 else $ret .= '<form onsubmit="return false;">';
		 /// CSRF !!
//		if (isset($data['hidden']['act']) || isset($data['fields']['act']))
//		 $ret .= '<input type="hidden" name="csrf" value="'.csrfToken().'">';
		foreach ($data['hidden'] as $vr => $vl) {
			if (!$vl) {
				if (isset ($session['lastform'][$vr])) $vl = $session['lastform'][$vr];
				if (isset ($data['defaults'][$vr])) $vl = $data['defaults'][$vr];
			}
			$ret .= '<input type="hidden" name="'.$vr.'" id="'.$vr.'" value="'.$vl.'">';
		}
		if ($data['caption']) {
			$ret .= '<div class="row"><div class="col-12 text-center">'.$data['caption'].'</div></div>';
		}
		$isShowHide = false;
		foreach ($data['fields'] as $line) if (isset ($line['if'])) $isShowHide = true;
		if ($isShowHide) $onchange = ' onchange="formChange_'.$fchId.'();"';
		foreach ($data['fields'] as $line) {
			$currId = '';
			$format = '';
			if (isset ($line['format']) && $line['format']) $format = ' required="" pattern="'.$line['format'].'"';
			if (isset ($session['lastform'][$line[1]])) $line[3] = $session['lastform'][$line[1]];
			if (isset ($data['defaults'][$line[1]]) && $line[2] != 'pass') $line[3] = $data['defaults'][$line[1]];
			if (isset ($line['if'])) {
				$currId = ' id="'.$line[1].'_row"';
				$showHide .= $line[1].'_'.$fchId.'.style.display = ('.$line['if'].')?"flex":"none";'."\n";
			}
			$ret .= '<div class="row" id="'.$line[1].'_'.$fchId.'">';
			$ret .= '<div class="col-4 form_caption">'.$line[0];
			if ($line[0]) $ret .= ': ';
			$ret .= '</div>';
			$ret .= '<div class="col-8 form_field">';
			if ($line['prefix']) $ret .= $line['prefix'].' ';
			if ($line[2] == 'info') {
				$s = $line[3];
				$s = preg_replace ('/\r/i', '', $s);
				$s = preg_replace ('/\n/i', '<br>', $s);
				$ret .= $s;
			}
			if ($line[2] == 'text') $ret .= '<input name="'.$line[1].'" id="'.$line[1].'" value="'.$line[3].'"'.$format.$onchange.'>';
			if ($line[2] == 'number') $ret .= '<input name="'.$line[1].'" id="'.$line[1].'" value="'.$line[3].'" class="number"'.$format.$onchange.'>';
			if ($line[2] == 'pass') $ret .= '<input name="'.$line[1].'" id="'.$line[1].'" type="password" value="'.$line[3].'"'.$onchange.'>';
			if ($line[2] == 'datetime') $ret .= '<input name="'.$line[1].'" id="'.$line[1].'" type="text" onclick="showCalendar(this);" value="'.$line[3].'"'.$format.$onchange.'>';
			if ($line[2] == 'select') {
				$ret .= '<select name="'.$line[1].'" id="'.$line[1].'"'.$onchange.'>';
				if (is_array ($line['opts'])) foreach ($line['opts'] as $vr => $vl) {
					if ($vr == $line[3]) $sel = ' selected'; else $sel = '';
					$ret .= '<option value="'.$vr.'"'.$sel.'>'.$vl.'</option>';
				}
				if (isset ($line['sql'])) {
					$opts = $con->query($line['sql']);
					while ($opt = $opts->fetch()) {
						if ($opt[0] == $line[3]) $sel = ' selected'; else $sel = '';
						$ret .= '<option value="'.$opt[0].'"'.$sel.'>'.$opt[1].'</option>';
					}
				}
				$ret .= '</select>';
			}
			if ($line[2] == 'multiselect') {
				$ret .= makeMultiSelect ($line['sql'], $line[1], $line[3]);
			}
			if ($line[2] == 'maskselect') {
				if (is_array($line['opts'])) foreach ($line['opts'] as $vr => $vl) {
					if ($line[3] & $vr) $chk = ' checked'; else $chk = '';
					$ret .= '<input id="'.$line[1].'_'.$vr.'_'.$chkId.'" name="'.$line[1].'['.$vr.']" type="checkbox" style="width:20px;"'.$chk.'>';
					$ret .= '<label for="'.$line[1].'_'.$vr.'_'.$chkId.'">'.$vl.'</label><br />';
				}
			}
			if ($line[2] == 'area') {
				//$ret .= '<textarea name="'.$line[1].'" id="'.$line[1].'">'.$line[3].'</textarea>';
				$ret .= '<textarea name="'.$line[1].'" id="'.$line[1].'" onkeydown="setTab(event, this);" onkeypress="return noCtrlS(event);" wrap="on"'.$format.'>'.$line[3].'</textarea>';
			}
			if ($line[2] == 'file') {
				$arr = [];
				if ($line[3]) $ret .= '<img src="'.applyCode('{{file('.$line[3].')}}',$arr).'" style="width: 100px; height: 75px; object-fit: contain;" /><br />';
				$ret .= '<input type="file" name="'.$line[1].'" id="'.$line[1].'">';
			}
			if ($line['postfix']) $ret .= ' '.$line['postfix'];
			$ret .= '</div>';
			$ret .= '</div>';
		}
		$ret .= '<div class="row">';
		$ret .= '<div class="col-4"></div>';
		$ret .= '<div class="col-8">';
		if ($data['submit']) $ret .= '<button type="submit">Ok</button> ';
		if ($data['cancel']) {
			$do = $data['cancel'];
			if (substr($do,0,11) == 'javascript:' || $do[1] == '!') $do = $do;//substr($do,11);
			 else $do = "location.href='".$do."';";
			$ret .= '<button type="button" onclick="'.$do.'">Отмена</button> ';
		}
		$ret .= $data['buttons'];
		$ret .= '</div>';
		$ret .= '</div>';
		$ret .= '</form>';
		if ($showHide) {
			$ret .= '<script>function formChange_'.$fchId.' () {'."\n".$showHide.'} formChange_'.$fchId.'();</script>';
		}
		if (isset ($data['spoiler'])) $ret = makeSpoiler ($ret, $data['spoiler']);
		ini_set('display_errors','On');
		return $ret;
	}
	function deobfuscateHTML ($html) {
		preg_match_all ('/((?:[^<>]|\r|\n)*?)<(?:([^<>]*?) )?((?:[^<>]|\r|\n)*?)>\n?/i', $html, $x);
		$q = count ($x[0]);
		$noshift = ['!DOCTYPE', 'meta','link','br','hr','input','img'];
		$ret = '';
		$level = 0;
		$tab = '';
		$textarea = false;
		for ($a=0;$a<$q;$a++) {
			if ($x[1][$a]) {
				$tmp = $x[1][$a];
				if (!$textarea) $tmp = preg_replace('/\r|\n|\t/i', '', $tmp);
				preg_match_all ('/((?:.|\r|\n){0,100}) /i', $tmp.' ', $y);
				$q1 = count($y[0]);
				for ($b=0;$b<$q1;$b++) {
					if (!$textarea) $ret .= substr($tab,0,$level);
					$ret .= $y[1][$b];
					if (!$textarea) $ret .= "\r\n";
				}
			}/**/
			if ($x[3][$a][0] == '/') {
				$level--;
				if ($level < 0) $level = 0;
			}
			if (!$textarea) $ret .= substr ($tab,0,$level);
			$ret .= '<';
			if ($x[2][$a]) $ret .= $x[2][$a] . ' ';
			$ret .= $x[3][$a] . '>';
			if ($x[2][$a] == 'textarea' || $x[3][$a] == 'textarea') $textarea = true;
			if ($x[3][$a] == '/textarea') $textarea = false;
			if (!$textarea) $ret .= "\r\n";
			if ($x[3][$a][0] != '/' && !in_array($x[2][$a], $noshift) && !in_array($x[3][$a], $noshift)) {
				$level++;
				while (strlen ($tab) < $level) $tab .= "\t";
			}
		}
		return $ret;
	}
	function saveLoadedFile ($col, $elem) {
		global $con, $data;
		$pr = $data['mysql']['pref'].'_';
		$folder = $_SERVER['DOCUMENT_ROOT'].'/files/';
		$filename = $_FILES['dat']['name'][$col];
		$x = explode ('/', $_FILES['dat']['type'][$col]);
		$rand = rand (10000000, 999999999) .'.'. $x[1];
		$row = $con->query("select * from {$pr}files where col='$col' and elem='$elem';")->fetch();
		if ($row && is_file ($folder.$row['path'])) unlink ($folder.$row['path']);
		$con->exec("delete from {$pr}files where col='$col' and elem='$elem';");
		if (copy($_FILES['dat']['tmp_name'][$col], $folder.$rand)) {
			$con->exec ("insert into {$pr}files (col, elem, nam, path) values ('$col', '$elem', '$filename', '$rand');");
			$row = $con->query("select * from {$pr}files order by -id limit 1;")->fetch();
			return $row['id'];
		}
		return false;
	}
	
	
	function makeLink ($get, $set=[], $def=[]) {
		foreach ($set as $m => $n) $get[$m] = $n;
		foreach ($def as $m => $n) if ($get[$m] == $n) unset ($get[$m]);
		ksort ($get);
		$comma = '?';
		$ret = '';
		foreach ($get as $m => $n) {
			$ret .= $comma . $m . '=' . $n;
			$comma = '&';
		}
		return $ret;
	}
		
	function applyCode ($html, &$vars) {
		global $con, $data, $input, $direct, $mailList;
		$pr = $data['mysql']['pref'].'_';
$debug = false;
		$html .= '{{}}';
		$ret = '';
		$ifn = 0;
		$template = '';
		$foreach = '';
		$foreachHID = '';
		$foreachAsc = true;
		$foreachLimit = false;
		$foreachSkip = 0;
		$foreachLevel = 0;
		preg_match_all ('/([\s\S]*?)\{\{([\s\S]*?)\}\}/ui', $html, $x);
		$q = count ($x[0]);
		for ($a=0;$a<$q;$a++) {
			if ($foreachLevel == 0) {
				if ($ifn == 0) $ret .= $x[1][$a];
			} else {
				$foreach .= '}}' . $x[1][$a] . '{{';
			}
//			preg_match_all ('/([\w.]+) *(?:\((.*?)\))?/ui', $x[2][$a], $y);
			preg_match_all ('/([\w.]+) *(?:\((.*?(?:\\))?)\))?/ui', $x[2][$a], $y);
			$q1 = count ($y[0]);
			for ($b=0;$b<$q1;$b++) {
				$tmp = preg_replace ('/ *, */ui', ',', str_replace ('\)', ')', $y[2][$b]));
				$v = explode (',', $tmp);
				$q2 = count ($v);
				$vv = [];
				$tmp = '';
				for ($c = $q2-1; $c>=0; $c--) {
					if ($tmp) $tmp = ',' . $tmp;
					$tmp = $v[$c].$tmp;
					$vv[$c] = $tmp;
				}
				switch ($y[1][$b]) {
					case 'foreach':
						if ($ifn == 0) {
							if ($foreachLevel == 0) {
								$foreachHID = getVars ($vars, $v[0]);
								if (isset ($v[1])) $foreachLimit = getVars($vars, $v[1]); else $foreachLimit = false;
								if (isset ($v[2])) $foreachSkip = getVars($vars, $v[2]); else $foreachSkip = 0;
								$foreachAsc = true;
								$elem = $con->query("select * from {$pr}struct where hid='$foreachHID'")->fetch();
								if ($elem) inCacheAdd ($elem['id']);
								$foreach = '{{';
							} else {
								$foreach .= $y[0][$b] .'; ';
							}
							$foreachLevel++;
						}
						break;
					case 'foreachDesc':
						if ($ifn == 0) {
							if ($foreachLevel == 0) {
								$foreachHID = getVars ($vars, $v[0]);
								if (isset ($v[1])) $foreachLimit = getVars($vars, $v[1]); else $foreachLimit = false;
								if (isset ($v[2])) $foreachSkip = getVars($vars, $v[2]); else $foreachSkip = 0;
								$foreachAsc = false;
								$elem = $con->query("select * from {$pr}struct where hid='$foreachHID'")->fetch();
								if ($elem) inCacheAdd ($elem['id']);
								$foreach = '{{';
							} else {
								$foreach .= $y[0][$b] .'; ';
							}
							$foreachLevel++;
						}
						break;
					case 'endforeach':
						if ($ifn == 0) {
							$foreachLevel--;
							if ($foreachLevel == 0) {
								$foreach .= '}}';
								$elem = $con->query("select * from {$pr}struct where hid='{$foreachHID}';")->fetch();
								if ($foreachAsc) $sort = 'sort'; else $sort = '-sort';
								$qua = $con->query("select * from {$pr}struct where parent='{$elem['id']}'")->rowCount();
								if ($foreachLimit) {
									$i = $qua / $foreachLimit;
									$pageQ = intval ($i);
									if ($pageQ != $i) $pageQ++;
									$pag = '';
									if ($pageQ > 1) {
										$pag .= '<ul class="pagination">';
										$s = '&larr;';
										$ii = $foreachSkip - 1;
										if ($ii >= 0) $s = '<a href="'.makeLink($_GET, ['p'=>$ii], ['p'=>0]).'">'.$s.'</a>';
										$pag .= '<li>'.$s.'</li>';
										for ($i=0;$i<$pageQ;$i++) {
											$s = ($i+1);
											if ($i != $foreachSkip) $s = '<a href="'.makeLink($_GET, ['p'=>$i], ['p'=>0]).'">'.$s.'</a>';
											$pag .= '<li>'.$s.'</li>';
										}
										$s = '&rarr;';
										$ii = $foreachSkip + 1;
										if ($ii < $pageQ) $s = '<a href="'.makeLink($_GET, ['p'=>$ii], ['p'=>0]).'">'.$s.'</a>';
										$pag .= '<li>'.$s.'</li>';
										$pag .= '</ul>';
									}
									addVars ($vars, 'pageQ', $pageQ);
									addVars ($vars, 'pagination', $pag);
									$limit = '';
									if ($foreachLimit) {
										$skip = $foreachSkip * $foreachLimit;
										$limit .= ' limit '.$skip.', '.$foreachLimit;
									}
								} else $limit = '';
								$res = $con->query("select * from {$pr}struct where parent='{$elem['id']}' order by $sort$limit;");
								$N = 0;
								while ($row = $res->fetch()) {
									$N++;
									addVars ($vars, 'LineID', $N);
									addVarsFrom ($vars, $row['id'], ['foreach']);
									$ret .= applyCode ($foreach, $vars);
								}
								$foreach = '';
							} else $foreach .= ' endforeach; ';
						}
						break;
					default:
						if ($foreachLevel > 0) {
							$foreach .= $y[0][$b] . '; ';
						} else {
							switch ($y[1][$b]) {
								case 'if':
									if ($ifn > 0) $ifn++;
									if ($ifn < 0) $ifn--;
									if ($ifn == 0) {
										if (!isTrue ($vars, $v[0])) $ifn = 1;
									}
									break;
								case 'elif':
									if ($ifn == 0) $ifn = -1;
									if ($ifn == 1 && isTrue($vars, $x[0])) $ifn = 0;
									break;
								case 'else':
									if ($ifn == 0) $ifn = -1;
									if ($ifn == 1) $ifn = 0;
									break;
								case 'endif':
									if ($ifn > 0) $ifn--;
									if ($ifn < 0) $ifn++;
									break;
								default:
									if ($ifn == 0) switch ($y[1][$b]) {
										case 'template':
											$template = getVars($vars, $v[0]);
											break;
										case 'js':
											if (isset ($v[0]) && $v[0]) $ret .= '<script src="/?page='.getVars ($vars, $v[0]).'&type=js"></script>';
											 else $ret .= '<script src="/static/script.js"></script>';
											break;
										case 'css':
											if (isset ($v[0]) && $v[0]) $ret .= '<link rel="stylesheet" href="/?page='.getVars($vars, $v[0]).'&type=css">';
											 else $ret .= '<link rel="stylesheet" href="/static/style.css">';
											break;
										case 'include':
											$hid = getVars ($vars, $v[0]);
											$elem = $con->query("select * from {$pr}struct where hid='$hid'")->fetch();
											$row = $con->query("select value from {$pr}data where elem = {$elem['id']} and var in (select id from {$pr}columns where caption='HTML');")->fetch();
											if ($row) {
												$ret .= applyCode ($row['value'], $vars);
												inCacheAdd ($elem['id']);
											}
											break;
										case 'file':
											$row = $con->query("select * from {$pr}files where id='".getVars($vars, $v[0])."';")->fetch();
											if ($row) $ret .= '/files/'.$row['path'];
											break;
										case 'load':
											$hid = getVars ($vars, $v[0]);
											$row = $con->query("select * from {$pr}struct where hid='$hid';")->fetch();
											if ($row) {
												addVarsFrom ($vars, $row['id'], ['load']);
											}
											break;
										case 'set':
											addVars ($vars, $v[0], getVars ($vars, $vv[1]));
											break;
										case 'time':
											addVars ($vars, 'year', date('Y'));
											addVars ($vars, 'month', date('m'));
											addVars ($vars, 'date', date('d'));
											addVars ($vars, 'hours', date('H'));
											addVars ($vars, 'minutes', date('i'));
											addVars ($vars, 'seconds', date('s'));
											addVars ($vars, 'day', date('N'));
											break;
										case 'mktime':
											$vls = ['seconds'=>60,'minutes'=>60,'hours'=>24,'date'=>-1,'month'=>12,'year'=>-2];
											foreach ($vls as $vr => $vl) {
												if ($vl == -2) continue;
												$i = getVars ($vars, $vr);
												$max = $vl;
												if ($max) {
													while ($i < 0) {
												}
											}
											break;
										case 'replace':
											addVars ($vars, $v[0], preg_replace ('/'.$v[1].'/ui', $v[2], getVars($vars, $v[3])));
											break;
										case 'link':
											$val = getVars ($vars, $v[0]);
											if (strpos ($val, '/') !== false) $ret .= $val; else {
												$x = explode ('&', $val);
												$arr = [];
												foreach ($x as $m => $n) {
													$y = mb_strpos ($n, '=');
													if ($y === false) $arr[$m] = $n; else
													 $arr[mb_substr($n,0,$y)] = mb_substr($n,$y+1);
												}
												$ret .= makeLink ($_GET, $arr, ['p'=>0]);
											}
											break;
										case 'sendEmail':
											$hid = getVars ($vars, $v[0]);
											$ml = $con->query("select * from {$pr}struct where hid='$hid';")->fetch();
											if ($ml) $mailList[$ml['id']] = true;
											break;
										case 'formAdd':
											$elem = $con->query("select * from {$pr}struct where hid='{$v[0]}';")->fetch();
											if ($elem) {
												ob_start ();
												$p = $input;
												$p['id'] = $elem['id'];
												$spoiler = '';
												if (isset ($v[1])) $spoiler = getVars ($vars, $v[1]);
												$p['spoiler'] = $spoiler;
												if (sysAddOneToStruct ($p, '', $vars) == 'done') $direct = $_SERVER['HTTP_REFERER'];
												$ret .= ob_get_clean();
											}
											break;
										default:
											$ret .= getVars ($vars, $y[1][$b]);
									}
							}
						}
				}
			}
		}
		return $ret;
	}
	
	function applyTemplates ($html, &$vars, &$was=[]) {
		global $con, $data;
		$pr = $data['mysql']['pref'].'_';
		$ret = applyCode ($html, $vars);
		$template = '';
		if (!$template) {
			while ($vars['template.id'] > 0) {
				$elem = $con->query("select * from {$pr}struct where id='{$vars['template.id']}';")->fetch();
				$row = $con->query("select elem, value from {$pr}data where elem = '{$vars['template.id']}' and var in (select id from {$pr}columns where caption='HTML');")->fetch();
				if ($row) {
					preg_match_all ('/\{\{.*?(?!\}\})template *\((.*?)\)/ui', $row['value'], $x);
					if (count ($x[0])) {
						$template = $x[1][0];
						break;
					}
				}
				$vars['template.id'] = $elem['parent'];
			}
		}
		if ($template && !in_array($template, $was)) {
			$was[] = $template;
			$vars['template.id'] = 0;
			$row = $con->query("select elem, value from {$pr}data where elem in (select id from {$pr}struct where hid='$template') and var in (select id from {$pr}columns where caption='HTML');")->fetch();
			if ($row) {
				$elem = $con->query("select * from {$pr}struct where id={$row['elem']};")->fetch();
				$vars['body'] = $ret;
				$ret = applyTemplates ($row['value'], $vars, $was);
				$vars['template.id'] = $elem['parent'];
				inCacheAdd ($elem['id']);
			}
		}
		return $ret;
	}
	
	
	
	function makeSpoiler ($html, $btnCaption) {
		$rnd = rand (10000, 99999);
		$ret = '';
		$ret .= '<div id="spl_a_'.$rnd.'" class="spoiler">';
		$ret .= '<button onclick="spl_a_'.$rnd.'.style.display=\'none\'; spl_b_'.$rnd.'.style.display=\'block\';">'.$btnCaption.'</button>';
		$ret .= '</div>';
		$ret .= '<div id="spl_b_'.$rnd.'" style="display:none;" class="spoiler">';
		$ret .= str_replace ('%%SPOILER%%', 'spl_a_'.$rnd.'.style.display=\'block\'; spl_b_'.$rnd.'.style.display=\'none\';', $html);
		$ret .= '</div>';
		return $ret;
	}
	
	function unic ($gr, $len, $table='', $column='', $pref='', $prefId=0) {
		global $con;
		$max = strlen ($gr) - 1;
		$first = $pref != '';
		while (true) {
			$ret = $pref;
			if ($first) $first = false; else {
				for ($a=0;$a<$len;$a++) 
					$ret .= $gr[rand(0,$max)];
			}
			if ($table == '') break;
			$q = $con->query("select * from $table where $column = '$ret';")->fetch();
			if (!$q) break;
			if ($q['id'] == $prefId) break;
		}
		return $ret;
	}
	
	function add_arr ($inp, $add = []) {
		foreach ($inp as $vr => $vl) {
			if (is_array ($vl)) $add[$vr] = add_arr($vl); else {
				$st = $vl;
				$vl = preg_replace ('/</', '', $vl);
				$vl = preg_replace ('/>/', '', $vl);
				$vl = preg_replace ('/\'/', '', $vl);
				$vl = preg_replace ('/\"/', '', $vl);
				if (!isset ($add[$vr])) $add[$vr] = $vl;
				$add[$vr.'_q'] = 'concat('.de_quotes ($st, "'\"", ', ').')';
			}
		}
		return $add;
	}

	function de_quotes ($str, $quotes="'\"", $delim=',') {
		$len = strlen ($quotes);
		$ptr = 0;
		$ret = '';
		$comma = '';
		while ($str) {
			$a = mb_strpos ($str, $quotes[$ptr]);
			if ($a === false) break;
			$s = mb_substr ($str, 0, $a);
			$str = mb_substr ($str, $a);
			if ($s) {
				$ret .= $comma . $quotes[$ptr] . $s . $quotes[$ptr];
				$comma = $delim;
			}
			$ptr++;
			if ($ptr >= $len) $ptr = 0;
		}
		$ret .= $comma . $quotes[$ptr] . $str . $quotes[$ptr];
		$ret = str_replace ('\\', '\\\\', $ret);
		return $ret;
	}

	function setPassw ($passw, &$pass, &$salt) {
		$salt = unic ('0123456789ABCDEF', 20);
		$str = $passw . '|' . $salt;
		$pass = hash ('SHA256', $str);
	}
	function checkPassw ($passw, $pass, $salt) {
		$str = $passw . '|' . $salt;
		$hash = hash ('SHA256', $str);
		return ($hash == $pass);
	}
	function inCacheAdd ($id, $hash = '') {
		global $con, $data, $cache_hash;
		if (!$hash) $hash = $cache_hash;
		if ($hash) {
			$row = $con->query("select * from {$data['mysql']['pref']}_cache where elem='$id' and hash='$hash';")->fetch();
			if (!$row) $con->exec ("insert into {$data['mysql']['pref']}_cache (elem, hash) values ('$id', '$hash');");
		}
	}
	function inCacheDel ($id) {
		global $con, $data;
		$pr = $data['mysql']['pref'].'_';
		$res = $con->query("select * from {$pr}cache where elem='$id';");
		while ($row = $res->fetch()) {
			$hash = $row['hash'];
			$filename = $_SERVER['DOCUMENT_ROOT'].'/cache/'.$hash.'.html';
			if (is_file ($filename)) unlink ($filename);
			$con->exec ("delete from {$pr}cache where hash='{$hash}';");
		}
	}
	
	function normal ($table) {
		global $con;
		$row = $con->query("select * from $table where sort is null order by -id limit 1;")->fetch();
		$con->exec ("update $table set sort = id where sort is null;");
		$tmp = $con->query("show columns from $table where Field='hid';")->fetch();
		if ($tmp) {
			$res2 = $con->query("select * from $table where hid is null;");
			while ($row2 = $res2->fetch()) {
				$hid = unic ('123456789', 8, $table, 'hid');
				$con->exec ("update $table set hid=$hid where id='{$row2['id']}';");
			}
		}
		if (!$row) return false;
		return $row['id'];
	}
	function clearColumns () {
		global $con, $data;
		$pr = $data['mysql']['pref'].'_';
		$con->exec ("delete from {$pr}columns where id not in (select var from {$pr}data) and keep = 0;");
	}
	function isTrue ($vars, $vr) {
		if (!isset ($vars[$vr])) return false;
		$v = $vars[$vr];
		if ($v == '') return false;
		if ($v == '0') return false;
		if ($v == 'false') return false;
		return true;
	}
	function getVars ($vars, $vr) {
		$ret = $vr;
		preg_match_all ('/[0-9a-z._]+/ui',$vr,$x);
		$q = count ($x[0]);
		for ($a=0;$a<$q;$a++) if (isset ($vars[$x[0][$a]])) $ret = str_replace ($x[0][$a], $vars[$x[0][$a]], $ret);
		return $ret;
	}
	function addVars (&$vars, $vr, $vl, $arr = []) {
		$vars[$vr] = $vl;
		if (is_array ($arr)) foreach ($arr as $m => $n) $vars[$n.'.'.$vr] = $vl;
	}
	function addVarsFrom (&$vars, $id, $arr = []) {
		global $con, $data;
		inCacheAdd ($id);
		$ret = '';
		$elem = $con->query("select * from {$data['mysql']['pref']}_struct where id='$id';")->fetch();
		$hid = $elem['hid'];
		$res = $con->query("select * from {$data['mysql']['pref']}_columns where groupid='{$elem['parent']}';");
		while ($row = $res->fetch()) {
			addVars ($vars, $row['vrname'], '', $arr);
		}
		$res = $con->query("select d.value as val, c.vrname as vr, c.caption as nam, c.typ as typ from {$data['mysql']['pref']}_data as d, {$data['mysql']['pref']}_columns as c where d.var = c.id and d.elem = '{$id}';");
		$arr[] = $hid;
		addVars ($vars, 'id', $elem['hid'], $arr);
		addVars ($vars, 'caption', $elem['caption'], $arr);
		addVars ($vars, 'alias', $elem['alias'], $arr);
		while ($row = $res->fetch()) {
//			if ($row['nam'] != 'HTML' && $row['nam'] != 'JS' && $row['nam'] != 'CSS') {
				$value = $row['val'];
				//if ($row['typ'] == 'area') {
					//$value = applyWiki ($value);
				//}
				addVars ($vars, $row['vr'], $value, $arr);
//			}
		}
		return true;
	}

	
	
	
