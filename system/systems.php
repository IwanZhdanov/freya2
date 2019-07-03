<?php
	define ('VIEW_PAGE', 2);
	define ('VIEW_TABLE', 4);
	define ('INSERT_TO_TABLE', 8);
	define ('EDIT_TABLE_DATA', 16);
	define ('DELETE_FROM_TABLE', 32);
	define ('EDIT_COLUMN_LIST', 64);
	define ('CHANGE_GRANTS', 128);

	function getFormCaption ($x) {
		$x = str_replace ('}}{{', '}} / {{', $x);
		$x = preg_replace ('/\{\{.*?\}\}/ui', '', $x);
		return $x;
	}

	function getGrantsForMe ($id) {
		if (!$id) $id = 0;
		global $rights, $user, $con, $data;
		$prefix = $data['mysql']['pref'].'_';
		$list = $id;
		$ptr = intval ($id);
		while ($ptr > 0) {
			$ptr = $con->query("select * from {$prefix}struct where id='$ptr';")->fetch()['parent'];
			$list = intval($ptr) . ', ' . $list;
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
	
	function varsOnFormArr ($arr) {
		$ret = [];
		foreach ($arr as $vr => $vl) {
			if (strpos ($vr, '_q') === false) {
				if (is_array ($vl)) $ret[$vr] = varsOnFormArr($vl);
				else $ret[$vr] = $vl;
			}
		}
		return $ret;
	}
	
	function varsOnForm (&$vars, $flag, $p=[]) {
		global $session, $user, $input, $err, $msg, $con, $data;
		if (isset ($vars['errmsg_'.$err])) $errmsg = $vars['errmsg_'.$err];
		 else $errmsg = getVars ($vars, 'errmsg');
		$okmsg = getVars ($vars, 'okmsg');
		//if ($okmsg) $vars = []; ///
		if (!$flag && $errmsg) {
			$err = $errmsg;
		}
		if ($flag && $okmsg) {
			$msg = $okmsg;
			unset ($session['lastform']);
		}
		if (!$flag && is_array($p)) {
			$session['lastform'] = varsOnFormArr ($p);
			unset ($session['lastform']['act']);
		}
	}

	function sysLogin ($p, $mode='') {
		global $session, $user, $input, $err, $msg, $con, $data;
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
		global $con, $err, $msg, $data, $input;
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
	
	function sysChPass ($p, $mode='') {
		global $con, $err, $msg, $data, $input, $user;
		if (!$user['id']) return;
		if (!$mode) {
			$mode = 'form';
			if (isset ($p['act']) && $p['act'] == 'chpass') $mode = 'do';
		}
		$pr = $data['mysql']['pref'].'_';
		$ret = 'form';
		if ($mode == 'do') {
			$ret = 'err';
			if (!$err) {
				if ($p['pass1'] != $p['pass2']) $err.='Введённые пароли не совпадают<br />';
				 else if (strlen ($p['pass1']) < 3) $err.= 'Пароль не короче 3х символов<br />';
			}
			if (!$err) {
				$row = $con->query("select * from {$pr}users where id='{$user['id']}';")->fetch();
				if (!checkPassw($p['pass'], $row['pass'], $row['salt'])) $err.='Текущий пароль указан неверно<br />';
			}
			if (!$err) {
				setPassw ($p['pass1'], $pass, $salt);
				$con->exec ("update {$pr}users set pass = '$pass', salt = '$salt' where id='{$user['id']}';");
				$ret = 'done';
			}
			$mode = 'form';
		}
		if ($mode == 'form' && !isset($p['act'])) {
			$form = [
				'caption'=>'Сменить пароль',
				'defaults'=>$p,
				'fields'=>[
					['Логин','','info',$user['login']],
					['Текущий пароль','pass','pass'],
					['Новый пароль','pass1','pass'],
					['Повторите пароль','pass2','pass'],
				],
				'submit'=>'?act=chpass',
				'cancel'=>'?',
			];
			echo makeForm ($form);
		}
		return $ret;
	}
	
	function sysPassRestore ($p, $mode='') {
		global $con, $err, $msg, $data, $input, $user, $mailList;
		if (!$mode) {
			$mode = 'form';
			if (isset ($p['act']) && $p['act'] == 'restorePass') $mode = 'do';
		}
		$pr = $data['mysql']['pref'].'_';
		$ret = 'form';
		if ($mode == 'do') {
			$ret = 'err';
			$restore = false;
			if (isset ($p['code']) && strlen($p['code']) == 32) {
				$restore = $con->query("select * from {$pr}users where restore like concat('%',{$p['code_q']},'%');")->fetch();
				if ($restore) {
					$rst = explode ('_', $restore['restore']);
					if ($rst[0] < time()) {
						$con->exec ("update {$pr}users set restore = '' where id='{$restore['id']}';");
						$restore = false;
					}
				}
			}
			if (!$restore) {
				if (!$err) {
					$lines = $con->query("select * from {$pr}users where email = '{$p['email']}';");
					if (!$lines->rowCount()) $err.='Указанный e-mail не найден<br>';
				}
				if (!$err) {
					while ($line = $lines->fetch()) {
						$cd = unic('0123456789ABCDEF', 32);
						$code = (time()+86400) . '_' . $cd;
						$link = 'http://'.$_SERVER['HTTP_HOST'].'/user/restore.php?code='.$cd;
						$con->exec("update {$pr}users set restore='{$code}' where id='{$line['id']}';");
						$mailList = [];
						$mails = $con->query("select * from {$pr}struct where alias='restore';")->fetch();
						if ($mails) {
							$mailList[$mails['id']] = true;
							addVars ($vars, 'email', $input['email']);
							addVars ($vars, 'code', $cd);
							addVars ($vars, 'url', $link);
							doMail ([], $vars, true);
						} else {
							mail ($input['email'], 'Восстановление пароля',$link);
						}
					}
					$ret = 'done';
				}
			} else {
				$direct = '/user/restore.php?code='.$rst[1];
				if (!$err) {
					if ($p['pass1'] != $p['pass2']) $err.='Введённые пароли не совпадают<br />';
					 else if (mb_strlen ($p['pass1']) < 3) $err.='Пароль не короче 3х сиволов<br />';
				}
				if (!$err) {
					setPassw ($p['pass1'], $pass, $salt);
					$con->exec("update {$pr}users set pass='$pass', salt='$salt', restore='' where id='{$restore['id']}';");
					$direct = '/user/';
					$ret = 'done';
				}
			}
			$mode = 'form';
		}
		if ($mode == 'form' && !isset($p['act'])) {
			$restore = false;
			if (isset ($p['code']) && strlen($p['code']) == 32) {
				$restore = $con->query("select * from {$pr}users where restore like concat('%',{$p['code_q']},'%');")->fetch();
			}
			if (!$restore) {
				$form = [
					'caption'=>'Восстановить пароль',
					'defaults'=>$p,
					'fields'=>[
						['Ваш e-mail','email','text'],
						['Код восстановления (если есть)','code','text'],
					],
					'submit'=>'?act=restorePass',
					'cancel'=>'?',
				];
			} else {
				$form = [
					'caption'=>'Установить новый пароль',
					'hidden'=>[
						'code'=>$p['code'],
					],
					'fields'=>[
						['Логин','','info',$restore['login']],
						['Новый пароль','pass1','pass'],
						['Повторите','pass2','pass'],
					],
					'submit'=>'?act=restorePass',
				];
			}
			echo makeForm ($form);
		}
		return $ret;
	}
	
	function sysShowStructGrants ($p, $mode='') {
		global $session, $user, $input, $err, $msg, $con, $data;
		if (!grantedForMe ($p['id'], CHANGE_GRANTS)) return;
		if (!$mode) {
			$mode = 'form';
			if (isset ($p['act']) && $p['act'] == 'struct_show_grants') $mode = 'do';
		}
		$pr = $data['mysql']['pref'].'_';
		$ret = 'form';
		if ($mode == 'do') {
			$ret = 'err';
			if (!$err) {
				$ret = 'done';
			}
			$mode = 'form';
		}
		if ($mode == 'form' && !isset($p['act'])) {
			$grnt = [
				1=>'Полный доступ',
				2=>'Просмотр страницы',
				4=>'Просмотр данных',
				8=>'Добавление субэлементов',
				16=>'Редактирование уще существующих субэлементов',
				32=>'Удаление субэлементов',
				64=>'Редактирование полей элемента',
				128=>'Выдача прав',
			];
			echo '<div class="tab-wiki"><table class="wiki">';
			echo '<tr><th>№</th><th>Элемент</th><th>Кому</th><th>Права</th></tr>';
			$ptr = $p['id'];
			$N = 0;
			while (true) {
				$row = $con->query("select * from {$pr}struct where id='$ptr';")->fetch();
				if (!$row && $ptr) break;
				if ($row) $capt = $row['caption']; else $capt = 'Корень';
				$res2 = $con->query("select * from {$pr}rights where basis='{$row['id']}';");
				while ($row2 = $res2->fetch()) {
					$N++;
					if ($row2['uid'] == 0) $to = '(всем)'; else {
						$u = $con->query("select * from {$pr}users where id='{$row2['uid']}';")->fetch();
						$to = $u['login'];
					}
					$grants = '';
					foreach ($grnt as $vr => $vl) if ($row2['grants'] & $vr) $grants .= $vl . '<br />';
					$ta = '<a href="?id='.$p['id'].'&grant='.$row2['id'].'">';
					echo '<tr>';
					echo '<td>'.$ta.$N.'</a></td>';
					echo '<td>'.$ta.$capt.'</a></td>';
					echo '<td>'.$ta.$to.'</a></td>';
					echo '<td>'.$ta.$grants.'</a></td>';
					echo '</tr>';
				}
				if ($ptr == 0) break;
				$ptr = $row['parent'];
			}
			echo '<tr><td></td><td><a href="?id='.$p['id'].'&grant=0">Добавить</a></td><td></td><td></td></tr>';
			echo '</table></div>';
		}
		return $ret;
	}
	
	function sysEditStructGrants ($p, $mode='') {
		global $session, $user, $input, $err, $msg, $con, $data;
		if (!grantedForMe ($p['id'], CHANGE_GRANTS)) return;
		if (!$mode) {
			$mode = 'form';
			if (isset ($p['act']) && $p['act'] == 'struct_grants') $mode = 'do';
		}
		$pr = $data['mysql']['pref'].'_';
		$ret = 'form';
		$grnt = [
			1=>'Полный доступ',
			2=>'Просмотр страницы',
			4=>'Просмотр данных',
			8=>'Добавление субэлементов',
			16=>'Редактирование уще существующих субэлементов',
			32=>'Удаление субэлементов',
			64=>'Редактирование полей элемента',
			128=>'Выдача прав',
		];
		if ($mode == 'do') {
			$ret = 'err';
			if (!$err) {
				$gr = false;
				if (!isset($p['grant'])) $err.='Номер записи в списке прав не указан<br />';
			}
			if (!$err) {
				$gr = $con->query("select * from {$pr}rights where id='{$p['grant']}';")->fetch();
				if ($gr) $elid = $gr['basis']; else $elid = $p['id'];
				if (!grantedForMe ($elid, CHANGE_GRANTS)) $err.='У Вас нет прав на редактирование прав доступа к указанному элементу<br />';
			}
			if (!$err) {
				if ($p['for_type'] == 0) $uid = 0; else {
					$u = $con->query("select * from {$pr}users where login='{$p['username']}';")->fetch();
					if ($u) {
						$uid = $u['id'];
						if ($uid == $user['id']) $err.='Нельзя редактировать права доступа самому себе<br />';
					} else $err.='Указанный логин не найден<br />';
				}
			}
			if (!$err) {
				$grants = 0;
				if ($gr) $grants = $gr['grants'];
				foreach ($grnt as $vr => $vl) if (grantedForMe ($elid, $vr)) $grants &= !$vr;
				$x = $p['grants'];
				foreach ($p['grants'] as $vr => $vl) if (grantedForme ($elid, $vr) && $vl) $grants |= $vr;
				if ($gr) {
					if ($grants)
					 $con->exec("update {$pr}rights set uid='$uid', grants='$grants' where id='{$gr['id']}';");
					else
					 $con->exec("delete from {$pr}rights where id='{$gr['id']}';");
				} else {
					if ($grants)
					 $con->exec ("insert into {$pr}rights (basis, uid, grants) values ('$elid','$uid','$grants');");
				}
				$ret = 'done';
			}
			$mode = 'form';
		}
		if ($mode == 'form' && !isset($p['act'])) {
			$gr = false;
			if (isset($p['grant'])) {
				$gr = $con->query("select * from {$pr}rights where id='{$p['grant']}';")->fetch();
				if (!grantedForMe ($gr['basis'], CHANGE_GRANTS)) $gr = false;
			}
			if ($gr) {
				$capt = $con->query("select * from {$pr}struct where id='{$gr['basis']}';")->fetch()['caption'];
				if (!$capt) $capt = 'Корень';
				if ($gr['uid']) {
					$gr['for_type'] = 1;
					$gr['username'] = $con->query("select * from {$pr}users where id='{$gr['uid']}';")->fetch()['login'];
				} else {
					$gr['for_type'] = 0;
				}
				$opts = [];
				foreach ($grnt as $vr=>$vl) if (grantedForMe($gr['basis'], $vr)) $opts[$vr] = $vl;
				$form = [
					'caption'=>'Изменить право доступа',
					'defaults'=>$gr,
					'hidden'=>['id'=>$p['id'], 'grant'=>$p['grant']],
					'fields'=>[
						['Элемент','','info',$capt],
						['Кому','for_type','select','opts'=>[0=>'Всем',1=>'Пользователю']],
						['Логин','username','text','if'=>'for_type.value==1'],
						['Права','grants','maskselect','opts'=>$opts],
					],
					'submit'=>'?act=struct_grants',
					'cancel'=>'?id='.$p['id'],
				];
				echo makeForm ($form);
			} else if (isset($p['grant'])) {
				$elid = $p['id'];
				$capt = $con->query("select * from {$pr}struct where id='{$elid}';")->fetch()['caption'];
				if (!$capt) $capt = 'Корень';
				$opts = [];
				foreach ($grnt as $vr=>$vl) if (grantedForMe($elid, $vr)) $opts[$vr] = $vl;
				$form = [
					'caption'=>'Новое право доступа',
					'hidden'=>['id'=>$p['id'], 'grant'=>$p['grant']],
					'fields'=>[
						['Элемент','','info',$capt],
						['Кому','for_type','select','opts'=>[0=>'Всем',1=>'Пользователю']],
						['Логин','username','text','if'=>'for_type.value==1'],
						['Права','grants','maskselect','opts'=>$opts],
					],
					'submit'=>'?act=struct_grants',
					'cancel'=>'?id='.$p['id'],
				];
				echo makeForm ($form);
			}
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
		global $session, $user, $input, $err, $msg, $con, $data;
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
					$par = $con->query("select * from {$pr}struct where id='{$p['id']}';")->fetch();
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
					['Название элементов<br>(по одному в строке)','captions','code'],
				],
				'submit'=>'?act=struct_add',
				'spoiler'=>'Добавить элементы',
			];
			echo makeForm ($form);
		}
		return $ret;
	}
	
	function sysAddOneToStruct ($p, $mode='', &$vars) {
		global $session, $user, $input, $err, $msg, $con, $data;
		if (!grantedForMe ($p['elem_id'], INSERT_TO_TABLE)) return;
		if (!$mode) {
			$mode = 'form';
			if (isset ($p['act']) && $p['act'] == 'struct_add_one' && $p['id'] == $p['elem_id']) $mode = 'do';
		}
		$pr = $data['mysql']['pref'].'_';
		$ret = 'form';
		if ($mode == 'do' && $p['id'] == $p['elem_id']) {
			$ret = 'err';
			if (!$err) {
				if (!CSRF_check ($p['csrf'])) $err = 'csrf';
			}
			if (!$err) {
				if ($p['id']) {
					$par = $con->query("select * from {$pr}struct where id='{$p['id']}';")->fetch();
					if (!$par) $err .= 'Неверно указан идентификатор элемента<br />';
				}
			}
			if (!$err) {
				$res = $con->query("select id from {$pr}columns where groupid = '{$p['id']}' and typ in ('area','code');");
				while ($row = $res->fetch()) {
					if (preg_match ('/\.[A-Za-z]{2}/ui', $p['dat'][$row['id']])) $err = 'lnk';
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
					$res = $con->query("select * from {$pr}columns where groupid='{$p['id']}' and def != '' order by sort;");
					while ($row = $res->fetch()) {
						$value = applyCode ($row['def'], $vars);
						$con->exec("insert into {$pr}data (elem, var, value) values ('{$elem['id']}', '{$row['id']}', '$value');");
					}
					varsOnForm ($vars, true);
				}
				inCacheDel ($p['id']);
				normal ($pr.'struct');
				normal ($pr.'data');
				$ret = 'done';
			} else {
				varsOnForm ($vars, false, $p);
				$mode = 'form';
			}
		}
		if ($mode == 'form'/* && !isset($p['act'])*/) {
			$ret = 'form';
			$fields = [];
			$res = $con->query ("select * from {$pr}columns where groupid='{$p['elem_id']}' order by sort;");
			while ($row = $res->fetch()) {
				if (!$row['def']) {
					if ($row['typ'] == 'select') {
						$opts = [0=>''];
						$optA = $con->query("select * from {$pr}struct where parent in (select id from {$pr}struct where hid='{$row['typ2']}');");
						while ($optB = $optA->fetch()) $opts[$optB['hid']] = $optB['caption'];
						$fields[] = [$row['caption'], 'dat['.$row['id'].']', $row['typ'], 'format'=>$row['format'], 'opts'=>$opts];
					} else
					 $fields[] = [$row['caption'], 'dat['.$row['id'].']', $row['typ'], 'format'=>$row['format']];
				}
			}
			$form = [
				'caption'=>'',
				'defaults'=>$p,
				'hidden'=>['id'=>$p['elem_id'], 'act'=>'struct_add_one'],
				'fields'=>$fields,
				'submit'=>$_SERVER['REQUEST_URI'],
			];
			if ($p['spoiler']) $form['spoiler'] = $p['spoiler'];
			echo makeForm ($form);
			//unset ($session['lastform']);
		}
		if (isset ($p['dat'])) doMail ($p['dat'], $vars, $ret=='done');
		return $ret;
	}
	
	function sysDelFromStruct ($p, $mode='') {
		global $session, $user, $input, $err, $msg, $con, $data;
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
		global $session, $user, $input, $err, $msg, $con, $data;
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
							$con->exec ("insert into {$pr}columns (groupid, caption, typ, typ2) values ('{$p['move_to']}', '{$var['caption']}', '{$var['typ']}', '{$var['typ2']}');");
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
					['Разместить','sort_pos','select', 'sql'=>"select id, case when sort < {$row['sort']} then concat('Вверх, перед ',caption) else concat ('Вниз, после ', caption) end from {$pr}struct where parent={$row['parent']} and id != {$p['id']} order by sort;", 'if'=>'todo=="sort"'],
					['Переместить','move_to','select', 'sql'=>"select id, concat('В ', caption) from {$pr}struct where parent='{$row['parent']}' and id != '{$row['id']}' order by sort;", 'opts'=>$up, 'if'=>'todo=="move"'],
				],
				'submit'=>'?act=struct_info',
				'spoiler'=>'Настроить элемент',
			];
			echo makeForm ($form);
		}
		return $ret;
	}

	function sysAddVarToStruct ($p, $mode='') {
		global $session, $user, $input, $err, $msg, $con, $data;
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
				$con->exec ("insert into {$pr}columns (groupid, caption, vrname, typ, typ2) values ('{$row['parent']}', '{$p['caption']}', '{$p['vrname']}', '{$p['typ']}', '{$p['typ2']}');");
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
				'code'=>'Код',
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
					['Выбор из','typ2','text','if'=>'typ.value=="select"'],
					['Значение','value','code','if'=>'typ.value!="file"'],
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
			$time = time();
			if (!$err) {
				if (isset ($p['dat']) && is_array ($p['dat'])) foreach ($p['dat'] as $vr => $vl) if (strpos ($vr, '_q') == false) {
					if (isset ($p['dat'][$vr.'_q'])) $vl = $p['dat'][$vr.'_q'];
					$check = $con->query("select * from {$pr}columns where id={$vr};")->fetch();
					if ($check['groupid'] != $elem['parent']) {
						if (!$p['dat'][$vr]) continue;
						$con->exec ("insert into {$pr}columns (groupid, caption, vrname, typ) values ('{$elem['parent']}', '{$check['caption']}', '{$check['vrname']}', '{$check['typ']}');");
						$check = $con->query("select * from {$pr}columns order by -id limit 1;")->fetch();
						$vr = $check['id'];
					}
					if ($check['typ'] == 'file') {
						if (isset ($_FILES['dat']['name'][$vr]) && $_FILES['dat']['size'][$vr]) $vl = saveLoadedFile ($vr, $p['id']);
						 else $vl = '!NOP!';
					}
					if (isset ($p['dat'][$vr]) && !$p['dat'][$vr]) {
						$con->exec("delete from {$pr}data where elem='{$p['id']}' and var='$vr';");
						clearColumns();
					} else if ($vl == '!NOP!') {
					} else {
						if (is_array ($vl)) {
							if (isset ($p['dat'][$vr.'_qs'])) $vl = $p['dat'][$vr.'_qs'];
							$vl_first = '';
							$vl_q = 0;
							$res = $con->query("select * from {$pr}data where elem in (select id from {$pr}struct where parent in (select id from {$pr}struct where alias='multilang')) and var in (select id from {$pr}columns where vrname = 'lang') order by sort;");
							$combovl = '';
							while ($row = $res->fetch()) if (isset ($vl[$row['value'].'_qs']) && $vl[$row['value'].'_qs']) {
								if (!$vl_first) $vl_first = $vl[$row['value'].'_qs'];
								$vl_q ++;
								$combovl .= '{{ set ('.$row['value'].', '.$row['value'].') if (get.lang = '.$row['value'].') }}';
								$combovl .= $vl[$row['value'].'_qs'];
								$combovl .= '{{ endif set ('.$row['value'].', '.$row['value'].') }}';
							}
							unset ($vl);
							if ($vl_q > 1) $vl = $combovl; else $vl = $vl_first;
							$vl = 'concat('.de_quotes ($vl, '\'"', ', ').')';
						}
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
			$res = $con->query("select * from {$pr}data where elem in (select id from {$pr}struct where parent in (select id from {$pr}struct where alias='multilang')) and var in (select id from {$pr}columns where vrname = 'lang') order by sort;");
			$langs = [];
			while ($row = $res->fetch()) $langs[] = $row['value'];
			$res = $con->query("select * from {$pr}columns where groupid='{$elem['parent']}' order by  sort;");
			while ($row = $res->fetch()) {
				$dat = $con->query("select * from {$pr}data where elem='{$p['id']}' and var='{$row['id']}';")->fetch();
				$value = $dat ? $dat['value'] : '';
				if ($row['typ'] == 'select') {
					$opts = [0=>''];
					$optA = $con->query("select * from {$pr}struct where parent in (select id from {$pr}struct where hid='{$row['typ2']}') order by sort;");
					while ($optB = $optA->fetch()) $opts[$optB['hid']] = $optB['caption'];
					$form['fields'][] = [$row['caption'].' [ '.$row['vrname'].' ]', 'dat['.$row['id'].']', $row['typ'], $value, 'opts'=>$opts];
				} else {
					if (!$row['typ2'] || $row['typ2'] >= 1050 || ($row['typ2'] & 1) == 0) {
						$form['fields'][] = [getFormCaption($row['caption']).' [ '.$row['vrname'].' ]', 'dat['.$row['id'].']', $row['typ'], $value];
					} else {
						$thisvalue = $value;
						foreach ($langs as $langId) {
							preg_match ('/\{\{ set \('.$langId.', '.$langId.'\) if \((?:get\.)?lang = '.$langId.'\) \}\}((?:.|\r|\n)*?)\{\{ endif set \('.$langId.', '.$langId.'\) \}\}/ui', $value, $x);
							if (isset ($x) && isset ($x[1])) $thisvalue = $x[1];
							$form['fields'][] = [getFormCaption($row['caption']).' [ '.$row['vrname'].' ], '.$langId, 'dat['.$row['id'].']['.$langId.']', $row['typ'], $thisvalue];
							$thisvalue = '';
						}
					}
				}
			}
			if (count ($form['fields'])) echo makeForm ($form);
		}
		return $ret;
	}
		
	function sysShowStructFields ($p, $mode='') {
		global $con, $data, $err, $vars;
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
				if ($p['typ'] == 'select') $typ2 = $p['typ2']; else $typ2 = $p['typ3'];
				if (is_array ($p['caption'])) {
					$vl_first = '';
					$vl_q = 0;
					$reslang = $con->query("select * from {$pr}data where elem in (select id from {$pr}struct where parent in (select id from {$pr}struct where alias='multilang')) and var in (select id from {$pr}columns where vrname = 'lang') order by sort;");
					$combovl = '';
					while ($rowlang = $reslang->fetch()) if (isset ($p['caption'][$rowlang['value'].'_qs']) && $p['caption'][$rowlang['value'].'_qs']) {
						if (!$vl_first) $vl_first = $p['caption'][$rowlang['value'].'_qs'];
						$vl_q ++;
						$combovl .= '{{ set ('.$rowlang['value'].', '.$rowlang['value'].') if (get.lang = '.$rowlang['value'].') }}';
						$combovl .= $p['caption'][$rowlang['value'].'_qs'];
						$combovl .= '{{ endif set ('.$rowlang['value'].', '.$rowlang['value'].') }}';
					}
					if ($vl_q > 1) $caption = $combovl; else $caption = $vl_first;
					$caption = 'concat('.de_quotes ($caption, '\'"', ', ').')';
				} else $caption = $p['caption_q'];
				if ($p['field'] == 0) {
					$con->exec ("insert into {$pr}columns (groupid, caption, vrname, typ, typ2, format, keep) values ('{$p['id']}', $caption, '{$p['vrname']}', '{$p['typ']}', '{$typ2}', {$p['format_q']}, '{$p['keep']}');");
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
					$con->exec("update {$pr}columns set caption = $caption, vrname = '{$p['vrname']}', typ='{$p['typ']}', typ2='{$typ2}', format={$p['format_q']}, def={$p['def_q']}, keep='{$p['keep']}' where id='{$p['field']}';");
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
				'code'=>'Код',
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
					$s .= getFormCaption ($n['caption']);
					$move[$m] = $s;
				}
				$form_caption = getFormCaption ($row['caption']);
				$form = [
					'caption'=>'Поле: '.$form_caption,
					'defaults'=>$row,
					'hidden'=>['id'=>$p['id'], 'field'=>$row['id']],
					'fields'=>[],
					'submit'=>'?act=struct_edit_fields',
					'spoiler'=>'Изменить поле: '.$form_caption,
				];
				if (!$row['typ2'] || $row['typ2'] > 1050 || ($row['typ2'] & 2) == 0) {
					$form['fields'][] = ['Название поля','caption','text'];
				} else {
					$thisvalue = $row['caption'];
					$value = $row['caption'];
					$reslang = $con->query("select * from {$pr}data where elem in (select id from {$pr}struct where parent in (select id from {$pr}struct where alias='multilang')) and var in (select id from {$pr}columns where vrname = 'lang') order by sort;");
					$langs = [];
					while ($rowlang = $reslang->fetch()) $langs[] = $rowlang['value'];
					foreach ($langs as $langId) {
						preg_match ('/\{\{ set \('.$langId.', '.$langId.'\) if \((?:get\.)?lang = '.$langId.'\) \}\}((?:.|\r|\n)*?)\{\{ endif set \('.$langId.', '.$langId.'\) \}\}/ui', $value, $x);
						if (isset ($x) && isset ($x[1])) $thisvalue = $x[1];
						$form['fields'][] = ['Название поля, '.$langId, 'caption['.$langId.']','text', $thisvalue];
						$thisvalue = '';
					}
				}
				$form['fields'][] = ['Имя переменной','vrname','text'];
				$form['fields'][] = ['Тип','typ','select', 'opts'=>$types];
				$form['fields'][] = ['Выбор из','typ2','text','if'=>'typ=="select"'];
				$form['fields'][] = ['Мультиязычный','typ3','select', $row['typ2'],'if'=>'typ!="select"','opts'=>['Нет', 'Да, только данные', 'Да, только заголовки', 'Да, полностью']];
				$form['fields'][] = ['Формат','format','text'];
				$form['fields'][] = ['По умолчанию','def','text'];
				$form['fields'][] = ['Сортировать','move','select','opts'=>$move];
				$form['fields'][] = ['Хранить пустое','keep','select', 'opts'=>[0=>'Нет','1'=>'Да','del'=>'УДАЛИТЬ']];
				echo makeForm ($form);
			}
			$form = [
				'caption'=>'Новое поле',
				'hidden'=>['id'=>$p['id'], 'field'=>0],
				'fields'=>[
					['Название поля','caption','text'],
					['Имя переменной','vrname','text'],
					['Тип','typ','select', 'opts'=>$types],
					['Выбор из','typ2','text','if'=>'typ=="select"'],
					['Мультиязычный','typ3','select','if'=>'typ!="select"','opts'=>['Нет', 'Да, только данные', 'Да, только заголовки', 'Да, полностью']],
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
//				$res2 = $con->query("select * from {$pr}columns where groupid = {$row['id']} and typ!='file' order by sort;");
				$res2 = $con->query("select * from {$pr}columns where groupid = {$row['id']} order by sort;");
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


	
	
