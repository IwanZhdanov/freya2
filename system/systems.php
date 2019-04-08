<?php
	define ('VIEW_PAGE', 2);
	define ('VIEW_TABLE', 4);
	define ('INSERT_TO_TABLE', 8);
	define ('EDIT_TABLE_DATA', 16);
	define ('DELETE_FROM_TABLE', 32);
	define ('EDIT_COLUMN_LIST', 64);
	define ('CHANGE_GRANTS', 128);


	function getGrantsForMe ($id) {
		global $rights, $user, $con, $data;
		$prefix = $data['mysql']['pref'].'_';
		$list = $id;
		$ptr = intval ($id);
		while ($ptr > 0) {
			$ptr = $con->query("select * from {$prefix}struct where id='$ptr';")->fetch()['parent'];
			$list = $ptr . ', ' . $list;
		}
		$groups = '0';
		if ($user) {
			$groups = $user['id'] .', '. $groups;
			/// добавить группы
			//$res = $con->query("select * from {$prefix}usergroup where userid={$user['id']};");
			//while ($row = $res->fetch()) $groups = '-'.$row['groupid'] . ', ' . $groups;
		}
		$res = $con->query("select * from {$prefix}rights where basis in ($list) and uid in ($groups);");
		$ret = 0;
		while ($row = $res->fetch()) {
			$ret = $ret | $row['grants'];
		}
		return $ret;
	}
	
	function grantedForMe ($id, $action) {
		$right = intval ($action);
		$grants = getGrantsForMe ($id);
		if (($grants) & ($right | 1)) return true;
		return false;
	}
	
	function grantedAnyForMe () {
		global $user, $con, $data;
		$pr = $data['mysql']['pref'].'_';
		$q = $con->query("select * from {$pr}rights where uid = '{$user['id']}' and (grants | 2 != 2);")->rowCount();
		if ($q) return true;
		return false;
	}

	function sysLogin ($p, $mode='') {
		global $session, $user, $input, $err, $con, $data;
		if (!$mode) {
			$mode = 'form';
			if (isset ($p['act']) && $p['act'] == 'login') $mode = 'do';
		}
		$pr = $data['mysql']['pref'].'_';
		$ret = 'form';
		if ($mode == 'do') {
			$ret = 'err';
			if (!$err) {
				$row = $con->query("select * from {$pr}users where login={$input['login_q']};")->fetch();
				if (!$row) {
					$err.='Логин/пароль указан неверно<br />';
				}
			}
			if (!$err) {
				if (!checkPassw ($input['pass'], $row['pass'], $row['salt'])) $err.='Логин/пароль указан неверно<br />';
			}
			if (!$err) {
				$user = $row;
				$session['user'] = $row['id'];
				$ret = 'done';
			}
			$mode = 'form';
		}
		if ($mode == 'form' && !isset($p['act'])) {
			$form = [
				'caption'=>'Авторизация',
				'defaults'=>$p,
				'fields'=>[
					['Логин','login','text'],
					['Пароль','pass','pass'],
				],
				'submit'=>'?act=login',
			];
			if (isset ($p['back'])) {
				$form['hidden']['back'] = $p['back'];
				$form['cancel'] = urldecode($p['back']);
			}
			echo makeForm ($form);
		}
		return $ret;
	}
	
	function sysLogout ($p, $mode='') {
		global $session;
		unset ($session['user']);
	}
	
	function sysRegister ($p, $mode='') {
		global $con, $err, $data, $input;
		if (!$mode) {
			$mode = 'form';
			if (isset ($p['act']) && $p['act'] == 'register') $mode = 'do';
		}
		$pr = $data['mysql']['pref'].'_';
		$ret = 'form';
		if ($mode == 'do') {
			$ret = 'err';
			if (!$err) {
				if (strlen ($p['login']) < 3) $err.='Логин не короче 3х символов<br />';
				if ($p['pass1'] != $p['pass2']) $err.='Введённые пароли не совпадают<br />';
				 else if (strlen ($p['pass1']) < 3) $err.= 'Пароль не короче 3х символов<br />';
			}
			if (!$err) {
				$row = $con->query("select * from {$pr}users where login = {$p['login_q']};")->fetch();
				if ($row) $err.='Пользователь с таким логином уже существует<br />';
			}
			if (!$err) {
				setPassw ($p['pass1'], $pass, $salt);
				$con->exec ("insert into {$pr}users (login, pass, salt, email, stamp) values ('{$p['login']}', '$pass', '$salt', '{$p['email']}', '".time()."');");
				$ret = 'done';
			}
			$mode = 'form';
		}
		if ($mode == 'form' && !isset($p['act'])) {
			$form = [
				'caption'=>'Регистрация',
				'defaults'=>$p,
				'fields'=>[
					['Логин','login','text'],
					['Пароль','pass1','pass'],
					['Повторите пароль','pass2','pass'],
					['E-mail','email','text'],
				],
				'submit'=>'?act=register',
				'cancel'=>'?',
			];
			echo makeForm ($form);
		}
		return $ret;
	}
	
	function sysGetStruct ($from=0, $id=-1, $lvls=-1, $open=[]) {
		global $con, $data;
//		if ($id >=0 && !grantedForMe ($id, VIEW_TABLE)) return;
		$pr = $data['mysql']['pref'].'_';
		if ($open == [] && $id > 0) {
			$ptr = $id;
			while ($ptr > 0) {
				$row = $con->query("select * from {$pr}struct where id='$ptr';")->fetch();
				$open[] = $row['id'];
				$ptr = $row['parent'];
			}
		}
		$new_lvls = ($lvls > 0) ? ($lvls - 1) : $lvls;
		$res = $con->query("select * from {$pr}struct where parent = '$from' order by sort;");
		if (!$res->rowCount()) return;
		if ($lvls == 0) echo '<form action="?act=struct_del" method="post">';
		if (in_array ($from, $open)) $sel = ' class="open"'; else $sel = '';
		$show = grantedForMe ($from, VIEW_TABLE);
		if ($show) echo '<ul'.$sel.'>';
		$delFlag = false;
		while ($row = $res->fetch()) {
			if ($show) {
				if ($row['id'] == $id) $sel = ' class="current"'; else $sel = '';
				echo '<li'.$sel.'>';
				if ($lvls == 0) {
					echo '<input type="checkbox" name="del['.$row['id'].']"> ';
					$delFlag = true;
				}
			}
			if ($show || grantedForMe ($row['id'], VIEW_TABLE)) echo '<a href="struct.php?id='.$row['id'].'">'.$row['caption'].'</a>';
			if ($lvls != 0) sysGetStruct ($row['id'], $id, $new_lvls, $open);
			if ($show) echo '</li>';
		}
		if ($show) echo '</ul>';
		if ($delFlag) echo '<button type="submit" onclick="if(!confirm(\'Удалить выбранные элементы?\')){return false;}">Удалить</button></form>';
	}
	
	function sysAddToStruct ($p, $mode='') {
		global $session, $user, $input, $err, $con, $data;
		if (!grantedForMe ($p['id'], INSERT_TO_TABLE)) return;
		if (!$mode) {
			$mode = 'form';
			if (isset ($p['act']) && $p['act'] == 'struct_add') $mode = 'do';
		}
		$pr = $data['mysql']['pref'].'_';
		$ret = 'form';
		if ($mode == 'do') {
			$ret = 'err';
			if (!$err) {
				if ($p['id']) {
					$par = $con->query("select * from {$pr}struct where id={$p['id_q']};")->fetch();
					if (!$par) $err .= 'Неверно указан идентификатор элемента<br />';
				}
			}
			if (!$err) {
				preg_match_all ('/[^\r\n]+/ui', $p['captions'], $x);
				$q = count ($x[0]);
				for ($a=0;$a<$q;$a++) {
					$con->exec ("insert into {$pr}struct (parent, caption) values ('{$p['id']}', '{$x[0][$a]}');");
				}
				inCacheDel ($p['id']);
				normal ($pr.'struct');
				$ret = 'done';
			}
			$mode = 'form';
		}
		if ($mode == 'form' && !isset($p['act'])) {
			$form = [
				'caption'=>'Добавить элементы структуры',
				'defaults'=>$p,
				'hidden'=>['id'=>$p['id']],
				'fields'=>[
					['Название элементов<br>(по одному в строке)','captions','area'],
				],
				'submit'=>'?act=struct_add',
				'spoiler'=>'Добавить элементы',
			];
			echo makeForm ($form);
		}
		return $ret;
	}
	
	function sysAddOneToStruct ($p, $mode='', &$vars) {
		global $session, $user, $input, $err, $con, $data;
		if (!grantedForMe ($p['id'], INSERT_TO_TABLE)) return;
		if (!$mode) {
			$mode = 'form';
			if (isset ($p['act']) && $p['act'] == 'struct_add_one') $mode = 'do';
		}
		$pr = $data['mysql']['pref'].'_';
		$ret = 'form';
		if ($mode == 'do') {
			$ret = 'err';
			if (!$err) {
				if ($p['id']) {
					$par = $con->query("select * from {$pr}struct where id={$p['id_q']};")->fetch();
					if (!$par) $err .= 'Неверно указан идентификатор элемента<br />';
				}
			}
			if (!$err) {
				$caption = '';
				if (isset ($p['dat']) && is_array ($p['dat'])) foreach ($p['dat'] as $vr => $vl) if (strpos($vr,'_q')===false) {
					if (isset ($p['dat'][$vr.'_q'])) $vl = $p['dat'][$vr.'_q'];
					if (!$caption) {
						$caption = $vl;
						$con->exec ("insert into {$pr}struct (parent, caption) values ('{$p['id']}', $caption);");
						$elem = $con->query("select * from {$pr}struct order by -id limit 1;")->fetch();
					}
					$check = $con->query("select * from {$pr}columns where groupid='{$p['id']}' and id='{$vr}';")->fetch();
					if ($check) {
						$con->exec("insert into {$pr}data (elem, var, value) values ('{$elem['id']}', '$vr', $vl);");
					}
				}
				if ($check) {
					$vars = [];
					$res = $con->query("select * from {$pr}columns where groupid='{$p['id']}' and def != '' order by sort;");
					while ($row = $res->fetch()) {
						$value = applyCode ($row['def'], $vars);
						$con->exec("insert into {$pr}data (elem, var, value) values ('{$elem['id']}', '{$row['id']}', '$value');");
					}
				}
				inCacheDel ($p['id']);
				normal ($pr.'struct');
				normal ($pr.'data');
				$ret = 'done';
			}
			$mode = 'form';
		}
		if ($mode == 'form' && !isset($p['act'])) {
			$fields = [];
			$res = $con->query ("select * from {$pr}columns where groupid='{$p['id']}' order by sort;");
			while ($row = $res->fetch()) {
				if (!$row['def']) $fields[] = [$row['caption'], 'dat['.$row['id'].']', $row['typ'], 'format'=>$row['format']];
			}
			$form = [
				'caption'=>'',
				'defaults'=>$p,
				'hidden'=>['id'=>$p['id'], 'act'=>'struct_add_one'],
				'fields'=>$fields,
				'submit'=>$_SERVER['REQUEST_URI'],
			];
			if ($p['spoiler']) $form['spoiler'] = $p['spoiler'];
			echo makeForm ($form);
		}
		doMail ($vars, $ret=='done');
		return $ret;
	}
	
	function sysDelFromStruct ($p, $mode='') {
		global $session, $user, $input, $err, $con, $data;
		if (!$mode) {
			$mode = 'form';
			if (isset ($p['act']) && $p['act'] == 'struct_del') $mode = 'do';
		}
		$pr = $data['mysql']['pref'].'_';
		$ret = 'form';
		if ($mode == 'do') {
			$ret = 'err';
			if (!$err) {
				if (isset ($p['del'])) {
					if (is_array ($p['del'])) $arr = $p['del']; else $arr[$p['del']] = 'on';
					foreach ($arr as $vr => $vl) if ($vl) {
						$elem = $con->query("select * from {$pr}struct where id='$vr';")->fetch();
						if ($elem && grantedForMe ($elem['parent'], DELETE_FROM_TABLE)) {
							$res = $con->query ("select * from {$pr}struct where parent='$vr';");
							while ($row = $res->fetch()) {
								$x = $p;
								$x['del'] = $row['id'];
								sysDelFromStruct ($x);
							}
							$con->exec("delete from {$pr}data where elem='{$vr}';");
							$con->exec("delete from {$pr}columns where groupid='{$vr}';");
							$con->exec("delete from {$pr}struct where id='{$vr}';");
							inCacheDel ($vr);
						}
					}
				}
				$ret = 'done';
			}
			$mode = 'form';
		}
		if ($mode == 'form' && !isset($p['act'])) {
		}
		return $ret;
	}
	
	function sysStructInfo ($p, $mode='') {
		global $session, $user, $input, $err, $con, $data;
		if (!grantedForMe ($p['id'], VIEW_TABLE)) return;
		if (!$p['id']) return;
		if (!$mode) {
			$mode = 'form';
			if (isset ($p['act']) && $p['act'] == 'struct_info') $mode = 'do';
		}
		$pr = $data['mysql']['pref'].'_';
		$ret = 'form';
		if ($mode == 'do') {
			$ret = 'err';
			if (!$err) {
				$con->exec("update {$pr}struct set caption='{$p['caption']}', alias='{$p['alias']}' where id='{$p['id']}';");
				inCacheDel ($p['id']);
				if ($p['todo'] == 'sort') {
					$me = $con->query("select * from {$pr}struct where id='{$p['id']}';")->fetch();
					inCacheDel ($me['parent']);
					$targ = $con->query("select * from {$pr}struct where id='{$p['sort_pos']}';")->fetch();
					if ($targ['sort'] < $me['sort']) $sql = "select * from {$pr}struct where sort < '{$me['sort']}' and sort >= '{$targ['sort']}' and parent='{$me['parent']}' order by -sort;";
					 else $sql = "select * from {$pr}struct where sort > '{$me['sort']}' and sort <= '{$targ['sort']}' and parent='{$me['parent']}' order by sort;";
					$seqv = $con->query($sql);
					$last = $me['sort'];
					while ($row = $seqv->fetch()) {
						$con->exec("update {$pr}struct set sort='$last' where id='{$row['id']}';");
						$last = $row['sort'];
					}
					$con->exec ("update {$pr}struct set sort='$last' where id='{$me['id']}';");
				}
				if ($p['todo'] == 'move') {
					$res = $con->query("select * from {$pr}data where elem = '{$p['id']}';");
					while ($row = $res->fetch()) {
						$var = $con->query("select * from {$pr}columns where id='{$row['var']}';")->fetch();
						while (true) {
							$need = $con->query("select * from {$pr}columns where groupid='{$p['move_to']}' and caption='{$var['caption']}' and typ='{$var['typ']}';")->fetch();
							if ($need) break;
							$con->exec ("insert into {$pr}columns (groupid, caption, typ) values ('{$p['move_to']}', '{$var['caption']}', '{$var['typ']}');");
						}
						$con->exec("update {$pr}data set var='{$need['id']}' where id='{$row['id']}';");
					}
					$was = $con->query("select * from {$pr}struct where id='{$p['id']}';")->fetch();
					$con->exec("update {$pr}struct set parent='{$p['move_to']}' where id='{$p['id']}';");
					inCacheDel ($was['parent']);
					inCacheDel ($p['move_to']);
					normal ($pr.'columns');
					clearColumns();
				}
				$ret = 'done';
			}
			$mode = 'form';
		}
		if ($mode == 'form' && !isset($p['act'])) {
			$row = $con->query("select * from {$pr}struct where id='{$p['id']}';")->fetch();
			$todo = [
				''=>'(ничего)',
				'sort'=>'Порядок сортировки',
				'move'=>'Переместить',
			];
			$up = [];
			if ($row['parent']) {
				$tmp = $con->query("select * from {$pr}struct where id='{$row['parent']}';")->fetch();
				if ($tmp) {
					$tmp2 = $con->query("select * from {$pr}struct where id='{$tmp['parent']}';")->fetch();
					if ($tmp2) {
						$up[$tmp2['id']] = 'Назад, в '.$tmp2['caption'];
					} else {
						$up[0] = 'Назад, в Корень';
					}
				}
			}
			$form = [
				'caption'=>'Настройки элемента структуры',
				'defaults'=>$p,
				'hidden'=>['id'=>$p['id']],
				'fields'=>[
					['ID','','info', $row['hid']],
					['Название элемента','caption','text', $row['caption']],
					['Алиас','alias','text', $row['alias']],
					['Действие','todo','select', 'opts'=>$todo],
					['Разместить','sort_pos','select', 'sql'=>"select id, case when sort < {$row['sort']} then concat('Вверх, перед ',caption) else concat ('Вниз, после ', caption) end from {$pr}struct where parent={$row['parent']} and id != {$p['id']} order by sort;", 'if'=>'todo.value=="sort"'],
					['Переместить','move_to','select', 'sql'=>"select id, concat('В ', caption) from {$pr}struct where parent='{$row['parent']}' and id != '{$row['id']}' order by sort;", 'opts'=>$up, 'if'=>'todo.value=="move"'],
				],
				'submit'=>'?act=struct_info',
				'spoiler'=>'Настроить элемент',
			];
			echo makeForm ($form);
		}
		return $ret;
	}

	function sysAddVarToStruct ($p, $mode='') {
		global $session, $user, $input, $err, $con, $data;
		if (!grantedForMe ($p['id'], EDIT_COLUMN_LIST)) return;
		if (!$mode) {
			$mode = 'form';
			if (isset ($p['act']) && $p['act'] == 'struct_add_var') $mode = 'do';
		}
		$pr = $data['mysql']['pref'].'_';
		$ret = 'form';
		if ($mode == 'do') {
			$ret = 'err';
			if (!$err) {
				if (strlen ($p['caption']) < 1) $err .= 'Введите название поля<br />';
				if (strlen ($p['caption']) < 1) $err .= 'Введите имя переменной<br />';
			}
			if (!$err) {
				$row = $con->query ("select * from {$pr}struct where id='{$p['id']}';")->fetch();
				if (!$row) $err .= 'Неверно указан идентификатор элемента<br />';
			}
			if (!$err) {
				$con->exec ("insert into {$pr}columns (groupid, caption, vrname, typ) values ('{$row['parent']}', '{$p['caption']}', '{$p['vrname']}', '{$p['typ']}');");
				$vrId = normal ($pr.'columns');
				if ($p['value']) {
					if ($p['caption'] == 'HTML' && $_POST['value']) $value = $_POST['value']; else $value = $p['value'];
					$con->exec ("insert into {$pr}data (elem, var, value) values ('{$p['id']}', '$vrId', {$p['value_q']});");
					inCacheDel ($p['id']);
					normal ($pr.'data');
				}
				$ret = 'done';
			}
			$mode = 'form';
		}
		if ($mode == 'form' && !isset($p['act'])) {
			$types = [
				'text'=>'Строка',
				'number'=>'Число',
				'area'=>'Текст',
				'pass'=>'Пароль',
				'datetime'=>'Дата / время',
				'select'=>'Выбор',
				'file'=>'Файл',
			];
			$form = [
				'caption'=>'Добавить поле данных',
				'defaults'=>$p,
				'hidden'=>['id'=>$p['id']],
				'fields'=>[
					['Название поля','caption','text'],
					['Имя переменной','vrname','text'],
					['Тип','typ','select','opts'=>$types],
					['Значение','value','area','if'=>'typ.value!="file"'],
				],
				'submit'=>'?act=struct_add_var',
				'spoiler'=>'Добавить поле',
			];
			echo makeForm ($form);
		}
		return $ret;
	}
	
	function sysShowVarStruct ($p, $mode='') {
		global $con, $data, $err;
		if (!grantedForMe ($p['id'], EDIT_TABLE_DATA)) return;
		$pr = $data['mysql']['pref'].'_';
		$elem = $con->query("select * from {$pr}struct where id = '{$p['id']}';")->fetch();
		if (!$elem) return;
		if (!$mode) {
			$mode = 'form';
			if (isset ($p['act']) && $p['act'] == 'struct_edit_vars') $mode = 'do';
		}
		$pr = $data['mysql']['pref'].'_';
		$ret = 'form';
		if ($mode == 'do') {
			$ret = 'err';
			if (!$err) {
				if (isset ($p['dat']) && is_array ($p['dat'])) foreach ($p['dat'] as $vr => $vl) if (strpos ($vr, '_q') == false) {
					if (isset ($p['dat'][$vr.'_q'])) $vl = $p['dat'][$vr.'_q'];
					$check = $con->query("select * from {$pr}columns where id={$vr};")->fetch();
					if ($check['groupid'] != $elem['parent']) {
						if (!$vl) continue;
						$con->exec ("insert into {$pr}columns (groupid, caption, vrname, typ) values ('{$elem['parent']}', '{$check['caption']}', '{$check['vrname']}', '{$check['typ']}');");
						$check = $con->query("select * from {$pr}columns order by -id limit 1;")->fetch();
						$vr = $check['id'];
					}
					if ($check['typ'] == 'file') {
						if (isset ($_FILES['dat']['name'][$vr]) && $_FILES['dat']['size'][$vr]) $vl = saveLoadedFile ($vr, $p['id']);
						 else $vl = '!NOP!';
					}
					if (!$p['dat'][$vr]) {
						$con->exec("delete from {$pr}data where elem='{$p['id']}' and var='$vr';");
						clearColumns();
					} else if ($vl == '!NOP!') {
					} else {
						//$variable = $con->query("select * from {$pr}columns where id='{$vr}';")->fetch();
						//if ($variable['caption'] == 'HTML' && $_POST['dat'][$vr]) $value = $_POST['dat'][$vr]; else $value = $vl;
						$dat = $con->query("select * from {$pr}data where elem='{$p['id']}' and var='$vr';")->fetch();
						if ($dat) $con->exec("update {$pr}data set value={$vl} where id='{$dat['id']}';"); else
						 $con->exec ("insert into {$pr}data (elem, var, value) values ('{$p['id']}', '$vr', $vl);");
					}
				}
				inCacheDel ($p['id']);
				normal ($pr.'data');
				$ret = 'done';
			}
			normal ($pr.'columns');
			$mode = 'form';
		}
		if ($mode == 'form' && !isset($p['act'])) {
			$form = [
				'caption'=>'Изменить данные',
				'defaults'=>$p,
				'hidden'=>['id'=>$p['id']],
				'fields'=>[],
				'submit'=>'?act=struct_edit_vars',
			];
			$res = $con->query("select * from {$pr}columns where groupid='{$elem['parent']}' order by  sort;");
			while ($row = $res->fetch()) {
				$dat = $con->query("select * from {$pr}data where elem='{$p['id']}' and var='{$row['id']}';")->fetch();
				$value = $dat ? $dat['value'] : '';
				$form['fields'][] = [$row['caption'].' [ '.$row['vrname'].' ]', 'dat['.$row['id'].']', $row['typ'], $value];
			}
			if (count ($form['fields'])) echo makeForm ($form);
		}
		return $ret;
	}
		
	function sysShowStructFields ($p, $mode='') {
		global $con, $data, $err;
		if (!grantedForMe ($p['id'], EDIT_COLUMN_LIST)) return;
		$pr = $data['mysql']['pref'].'_';
		$elem = $con->query("select * from {$pr}struct where id = '{$p['id']}';")->fetch();
		if (!$elem) return;
		if (!$mode) {
			$mode = 'form';
			if (isset ($p['act']) && $p['act'] == 'struct_edit_fields') $mode = 'do';
		}
		$pr = $data['mysql']['pref'].'_';
		$ret = 'form';
		if ($mode == 'do') {
			$ret = 'err';
			if (!$err) {
				if ($p['field']) {
					$line = $con->query("select * from {$pr}columns where groupid='{$p['id']}' and id='{$p['field']}'")->fetch();
					if (!$line) $err .= 'Указанное поле не найдено<br />';
				}
			}
			if (!$err) {
				if ($p['field'] == 0) {
					$con->exec ("insert into {$pr}columns (groupid, caption, vrname, typ, format, keep) values ('{$p['id']}', {$p['caption_q']}, '{$p['vrname']}', '{$p['typ']}', {$p['format_q']}, '{$p['keep']}');");
				} else
				if ($p['keep'] == 'del') {
					$con->exec ("delete from {$pr}columns where id='{$p['field']}';");
					$con->exec ("delete from {$pr}data where var='{$p['field']}';");
				} else {
					if ($p['move']) {
						$curr = $con->query("select * from {$pr}columns where id='{$p['field']}'")->fetch();
						$targ = $con->query("select * from {$pr}columns where id='{$p['move']}';")->fetch();
						if ($targ['sort'] < $curr['sort'])
						 $sql = "select * from {$pr}columns where sort < '{$curr['sort']}' and sort >= '{$targ['sort']}' order by -sort;";
						 else $sql = "select * from {$pr}columns where sort > '{$curr['sort']}' and sort <= '{$targ['sort']}' order by sort;";
						$res = $con->query($sql);
						$last = $curr['sort'];
						while ($row = $res->fetch()) {
							$con->exec("update {$pr}columns set sort='$last' where id='{$row['id']}';");
							$last = $row['sort'];
						}
						$con->exec ("update {$pr}columns set sort='$last' where id='{$curr['id']}';");
					}
					$con->exec("update {$pr}columns set caption = {$p['caption_q']}, vrname = '{$p['vrname']}', typ='{$p['typ']}', format={$p['format_q']}, def={$p['def_q']}, keep='{$p['keep']}' where id='{$p['field']}';");
				}
				inCacheDel ($p['id']);
				normal ($pr.'data');
				normal ($pr.'columns');
				$ret = 'done';
			}
			$mode = 'form';
		}
		if ($mode == 'form' && !isset($p['act'])) {
			$types = [
				'text'=>'Строка',
				'number'=>'Число',
				'area'=>'Текст',
				'pass'=>'Пароль',
				'datetime'=>'Дата / время',
				'select'=>'Выбор',
				'file'=>'Файл',
			];
			$res = $con->query("select * from {$pr}columns where groupid='{$elem['id']}' order by sort;");
			$moves = [];
			while ($row = $res->fetch()) $moves[$row['id']] = $row;
			$res = $con->query("select * from {$pr}columns where groupid='{$elem['id']}' order by sort;");
			while ($row = $res->fetch()) {
				$move = [0=>'(не сортировать)'];
				foreach ($moves as $m => $n) if ($m != $row['id']) {
					if ($n['sort'] < $row['sort']) $s = 'Вверх, перед ';
					 else $s = 'Вниз, после ';
					$s .= $n['caption'];
					$move[$m] = $s;
				}
				$form = [
					'caption'=>'Поле: '.$row['caption'],
					'defaults'=>$row,
					'hidden'=>['id'=>$p['id'], 'field'=>$row['id']],
					'fields'=>[
						['Название поля','caption','text'],
						['Имя переменной','vrname','text'],
						['Тип','typ','select', 'opts'=>$types],
						['Формат','format','text'],
						['По умолчанию','def','text'],
						['Сортировать','move','select','opts'=>$move],
						['Хранить пустое','keep','select', 'opts'=>[0=>'Нет','1'=>'Да','del'=>'УДАЛИТЬ']],
					],
					'submit'=>'?act=struct_edit_fields',
					'spoiler'=>'Изменить поле: '.$row['caption'],
				];
				echo makeForm ($form);
			}
			$form = [
				'caption'=>'Новое поле',
				'hidden'=>['id'=>$p['id'], 'field'=>0],
				'fields'=>[
					['Название поля','caption','text'],
					['Имя переменной','vrname','text'],
					['Тип','typ','select', 'opts'=>$types],
					['Формат','format','text'],
					['По умолчнию','def','text'],
					['Хранить пустое','keep','select', 1, 'opts'=>[0=>'Нет','1'=>'Да']],
				],
				'submit'=>'?act=struct_edit_fields',
				'spoiler'=>'Добавить новое поле',
			];
			echo makeForm ($form);
		}
		return $ret;
	}
		
	function _sysCollectSimilarVars ($id, $len, $level, $elid, $parent, $arr, &$list) {
		global $con, $data, $err;
		$pr = $data['mysql']['pref'].'_';
		if ($id == $elid) return;
		$res = $con->query("select * from {$pr}struct where parent={$id} order by sort;");
		while ($row = $res->fetch()) {
			if (in_array ($row['id'], $arr)) $newLen = $len; else $newLen = $len + 10;
			_sysCollectSimilarVars ($row['id'], $newLen, $level+1, $elid, $parent, $arr, $list);
			if ($level == count($arr) || $row['id'] == $parent) {
				$L = $len;
				if ($row['id'] == $parent) $L = 5;
				if ($row['id'] == $elid) $L = -1;
				$res2 = $con->query("select * from {$pr}columns where groupid = {$row['id']} and typ!='file' order by sort;");
				while ($row2 = $res2->fetch()) {
					if (!isset ($list[$row2['caption']]) || $list[$row2['caption']]['len'] > $L) {
						$list[$row2['vrname']] = [
							'len'=>$L,
							'var'=>$row2['id'],
						];
					}
				}
			}
		}
	}

	function sysShowSimilarVars ($id) {
		global $con, $data, $err;
		if (!grantedForMe ($id, EDIT_COLUMN_LIST)) return;
		$pr = $data['mysql']['pref'].'_';
		$elem = $con->query("select * from {$pr}struct where id = '{$id}';")->fetch();
		$i = $elem['parent'];
		$ptr = $con->query("select * from {$pr}struct where id = '{$i}';")->fetch();
		$par = $ptr['parent'];
		$arr = [];
		while ($ptr) {
			$arr[] = $ptr['id'];
			$ptr = $con->query("select * from {$pr}struct where id={$ptr['parent']};")->fetch();
		}
		$list = [];
		_sysCollectSimilarVars (0, 0, 1, $i, $par, $arr, $list);
		$list2 = [];
		foreach ($list as $m => $n) if ($n['len'] >= 0) $list2[] = $n;
		$q = count ($list2);
		for ($a=0;$a<$q-1;$a++) {
			$c = $a;
			for ($b=$a+1;$b<$q;$b++) if ($list2[$b]['len'] < $list2[$c]['len']) $c = $b;
			if ($c != $a) {
				$tmp = $list2[$a]; $list2[$a] = $list2[$c]; $list2[$c] = $tmp;
			}
		}
		$fields = [];
		foreach ($list2 as $vr => $vl) {
			$row = $con->query("select * from {$pr}columns where id='{$vl['var']}';")->fetch();
			$fields[] = [$row['caption'].' [ '.$row['vrname'].' ]', 'dat['.$row['id'].']', $row['typ'], ''];
		}
		$form = [
			'caption'=>'Похожие поля',
			'hidden'=>['id'=>$id],
			'fields'=>$fields,
			'submit'=>'?act=struct_edit_vars',
			'cancel'=>'?id='.$id,
		];
		echo makeForm ($form);
	}


	
	