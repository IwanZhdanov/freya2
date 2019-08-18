<?php
	require $_SERVER['DOCUMENT_ROOT'].'/system/top.php';
	
	if (is_file ($_SERVER['DOCUMENT_ROOT'].'/settings.php')) require $_SERVER['DOCUMENT_ROOT'].'/settings.php';
	if (isset ($data)) {
		// Корректировка базы данных
		$q = $con->query("show columns from {$data['mysql']['pref']}_columns where Field = 'caption' and Type = 'char(100)';")->rowCount();
		if ($q) $con->exec ("alter table {$data['mysql']['pref']}_columns change caption caption text;");
		$q = $con->query("show columns from {$data['mysql']['pref']}_columns where Field = 'typ2' and Type = 'char(20)';")->rowCount();
		if ($q) $con->exec ("alter table {$data['mysql']['pref']}_columns change typ2 typ2 text;");
		$q = $con->query("show columns from {$data['mysql']['pref']}_struct where Field = 'lastmod';")->rowCount();
		if (!$q) $con->exec ("alter table {$data['mysql']['pref']}_struct add lastmod int default 0;");
		
		// Запрет на переустановку
		header ('Location: /freya/');
		die();
	}
	
	$def = ['host'=>'localhost', 'port'=>'3306'];
	$def['pref'] = unic ('abcdefghijklmnopqrstuvwxyz', 4);
	
	if (isset ($_GET['act'])) {
		$def = $input;
		// Проверить правильность подключения
		if (!$err) {
			try {
				$con = new PDO('mysql:host='.$def['host'].';dbname='.$def['base'].';charset=utf8', $def['user'], $def['pass']);
			} 
			catch (PDOException $e) { /*$e->getMessage();*/
				$err .= 'Не удалось подключиться к базе данных. Проверьте правильность введённых настроек.';
			}
		}
		// проверить если уже существует
		if (!$err) {
			$users = $con->query("show tables;");
			$flag = false;
			while ($u = $users->fetch()) if ($u[0] == $def['pref'].'_users') $flag = true;
			if ($flag && (!isset($def['erase']) || !$def['erase'])) {
				$err.='В указанной базе данных уже существует таблицы с таким префиксом. Измените префикс или подтвердите удаление существующего сайта.';
				$def['erase'] = 0;
			}
		}
		// проверить логин/пароль админа
		if (!$err) {
			if (strlen ($def['u_login']) < 3) $err.='Логин администратора не короче 3х символов.<br>';
			if ($def['u_pass1'] != $def['u_pass2']) $err.='Введённые пароли администратора не совпадают.<br>';
			 else if (strlen ($def['u_pass1']) < 3) $err .= 'Пароль администратора не короче 3х символов.<br>';
		}
		// Установить стандартные таблицы
		if (!$err) {
			if ($flag) {
				$tables = ['cache','columns','data','files','rights','struct','users'];
				foreach ($tables as $tab) $con->exec ('drop table '.$def['pref'].'_'.$tab.';');
			}
			$con->exec ("create table {$def['pref']}_cache (id int primary key auto_increment, elem int, hash char(100));");
			$con->exec ("create table {$def['pref']}_columns (id int primary key auto_increment, sort int, groupid int,  caption text, vrname char(50), typ char(20), typ2 char(20), format char(100), def char(200), keep int);");
			$con->exec ("create table {$def['pref']}_data (id int primary key auto_increment, sort int, elem int, var int, value text);");
			$con->exec ("create table {$def['pref']}_files (id int primary key auto_increment, col int, elem int, nam char(200), path char(200));");
			$con->exec ("create table {$def['pref']}_rights (id int primary key auto_increment, basis int, uid int, grants int);");
			$con->exec ("create table {$def['pref']}_struct (id int primary key auto_increment, hid char(20), sort int, parent int, caption char(200), alias char(20), lastmod int default 0);");
			$con->exec ("create table {$def['pref']}_users (id int primary key auto_increment, login char(100), pass char(100), salt char(50), email char(100), stamp int, restore char(100));");
		}
		// Добавить админа и выдать ему права
		if (!$err) {
			setPassw ($def['u_pass1'], $pass, $salt);
			$con->exec("insert into {$def['pref']}_users (login, pass, salt, email, stamp) values ('{$def['u_login']}', '$pass', '$salt', '{$def['email']}', ".time().");");
			$admin = $con->query("select * from {$def['pref']}_users;")->fetch();
			$con->exec ("insert into {$def['pref']}_rights (basis, uid, grants) values (0, '{$admin['id']}', 1);");
		}
		// Сохраняем
		if (!$err) {
			$c = '';
			$c .= "<?php\n";
			$c .= "\t\$data = [\n";
			$c .= "\t\t'site'=>[\n";
			$c .= "\t\t\t'id'=>'{$def['pref']}',\n";
			$c .= "\t\t],\n";
			$c .= "\t\t'mysql'=>[\n";
			$c .= "\t\t\t'host'=>'{$def['host']}',\n";
			$c .= "\t\t\t'port'=>'{$def['port']}',\n";
			$c .= "\t\t\t'user'=>'{$def['user']}',\n";
			$c .= "\t\t\t'pass'=>'{$def['pass']}',\n";
			$c .= "\t\t\t'base'=>'{$def['base']}',\n";
			$c .= "\t\t\t'pref'=>'{$def['pref']}',\n";
			$c .= "\t\t],\n";
			$c .= "\t];\n";
			$f = fopen ($_SERVER['DOCUMENT_ROOT'].'/settings.php', 'w');
			fwrite ($f, $c);
			fclose ($f);
			chmod ($_SERVER['DOCUMENT_ROOT'].'/settings.php', 0x644);
			header ("Location: /freya/");
			die();
		}
	}

	$title = 'Установка';
	echo '<h1 style="width: 100%; text-align: center;">Установка CMS Freya</h1>';	
	$form = [
		'defaults'=>$def,
		'fields'=>[
			['','','info','Подключение к MySQL'],
			['Хост','host','text'],
			['Порт','port','text'],
			['Пользователь','user','text'],
			['Пароль','pass','pass'],
			['База данных','base','text'],
			['Префикс','pref','text'],
			['','','info','Администратор сайта'],
			['Логин','u_login','text'],
			['Пароль','u_pass1','pass'],
			['Пароль ещё раз','u_pass2','pass'],
			['E-mail','email','text'],
		],
		'submit'=>'?act=go',
	];
	if (isset ($def['erase'])) {
		$form['fields'][] = ['','','info','Удалить предыдущий сайт?'];
		$form['fields'][] = ['Удалить','erase','select','opts'=>[0=>'Нет',1=>'Да, удалить весь сайт полностью без возможности восстановления']];
	}
	echo makeForm ($form);
	
	require $_SERVER['DOCUMENT_ROOT'].'/system/bottom.php';
