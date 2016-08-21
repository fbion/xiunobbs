<?php

define('DEBUG', 2);
define('APP_PATH', realpath(dirname(__FILE__).'/../').'/');
define('INSTALL_PATH', dirname(__FILE__).'/');

define('MESSAGE_HTM_PATH', './view/htm/message.htm');

// 切换到上一级目录，操作很方便。

$conf = (include APP_PATH.'conf/conf.default.php');
$lang = include APP_PATH."lang/$conf[lang]/bbs.php";
$lang += include APP_PATH."lang/$conf[lang]/bbs_install.php";

include APP_PATH.'xiunophp/xiunophp.php';
include APP_PATH.'model/misc.func.php';
include APP_PATH.'model/plugin.func.php';
include APP_PATH.'model/user.func.php';
include APP_PATH.'model/group.func.php';
include APP_PATH.'model/forum.func.php';
include INSTALL_PATH.'install.func.php';

$action = param('action');

// 安装初始化检测,放这里
is_file(APP_PATH.'conf/conf.php') AND empty($action) AND !DEBUG AND message(0, jump(lang('installed_tips'), '../'));

// 第一步，阅读
if(empty($action)) {
	
	if($method == 'GET') {
		$input = array();
		$input['lang'] = form_select('lang', array('zh-cn'=>'简体中文', 'zh-tw'=>'繁体中文', 'en-us'=>'English'), $conf['lang']);
		
		// 修改 conf.php
		include INSTALL_PATH."view/htm/index.htm";
	} else {
		$_lang = param('lang');
		$conf['lang'] = $_lang;
		xn_copy(APP_PATH.'./conf/conf.default.php', APP_PATH.'./conf/conf.backup.php');
		$r = file_replace_var(APP_PATH.'conf/conf.default.php', array('lang'=>$_lang));
		$r === FALSE AND message(-1, jump(lang('please_set_conf_file_writable'), ''));
		http_location('index.php?action=license');
	}
	
} elseif($action == 'license') {
	
	
	// 设置到 cookie
	
	include INSTALL_PATH."view/htm/license.htm";
	
} elseif($action == 'env') {
	
	if($method == 'GET') {
		$succeed = 1;
		$env = $write = array();
		get_env($env, $write);
		include INSTALL_PATH."view/htm/env.htm";
	} else {
	
	}
	
} elseif($action == 'db') {
	
	if($method == 'GET') {
		
		$succeed = 1;
		$mysql_support = function_exists('mysql_connect');
		$pdo_mysql_support = extension_loaded('pdo_mysql');
		(!$mysql_support && !$pdo_mysql_support) AND message(0, lang('evn_not_support_php_mysql'));

		include INSTALL_PATH."view/htm/db.htm";
		
	} else {
		
		$type = param('type');	
		$host = param('host');	
		$name = param('name');	
		$user = param('user');
		$password = param('password');
		$force = param('force');
		
		$adminemail = param('adminemail');
		$adminuser = param('adminuser');
		$adminpass = param('adminpass');
		
		empty($host) AND message('host', lang('dbhost_is_empty'));
		empty($name) AND message('name', lang('dbname_is_empty'));
		empty($user) AND message('user', lang('dbuser_is_empty'));
		empty($adminpass) AND message('adminpass', lang('adminuser_is_empty'));
		empty($adminemail) AND message('adminemail', lang('adminpass_is_empty'));
		
		
		
		// 设置超时尽量短一些
		set_time_limit(60);
		ini_set('mysql.connect_timeout',  5);
		ini_set('default_socket_timeout', 5); 

		$conf['db']['type'] = $type;	
		$conf['db']['mysql']['master']['host'] = $host;
		$conf['db']['mysql']['master']['name'] = $name;
		$conf['db']['mysql']['master']['user'] = $user;
		$conf['db']['mysql']['master']['password'] = $password;
		$conf['db']['pdo_mysql']['master']['host'] = $host;
		$conf['db']['pdo_mysql']['master']['name'] = $name;
		$conf['db']['pdo_mysql']['master']['user'] = $user;
		$conf['db']['pdo_mysql']['master']['password'] = $password;
		
		$db = db_new($conf['db']);
		if(db_connect($db) === FALSE) {
			message(-1, "$errstr (errno: $errno)");
		} 
		
		// 连接成功以后，开始建表，导数据。
		
		install_sql_file('./install.sql');
		
		// 初始化
		copy(APP_PATH.'conf/conf.default.php', APP_PATH.'conf/conf.php');
		
		// 管理员密码
		$salt = xn_rand(16);
		$password = md5(md5($adminpass).$salt);
		$update = array('username'=>$adminuser, 'email'=>$adminemail, 'password'=>$password, 'salt'=>$salt, 'create_date'=>$time, 'create_ip'=>$longip);
		db_update('user', array('uid'=>1), $update);
		
		$replace = array();
		$replace['db'] = $conf['db'];
		$replace['auth_key'] = xn_rand(64);
		$replace['installed'] = 1;
		file_replace_var(APP_PATH.'conf/conf.php', $replace);
		
		// 处理语言包
		group_update(0, array('name'=>lang('group_0')));
		group_update(1, array('name'=>lang('group_1')));
		group_update(2, array('name'=>lang('group_2')));
		group_update(4, array('name'=>lang('group_4')));
		group_update(5, array('name'=>lang('group_5')));
		group_update(6, array('name'=>lang('group_6')));
		group_update(7, array('name'=>lang('group_7')));
		group_update(101, array('name'=>lang('group_101')));
		group_update(102, array('name'=>lang('group_102')));
		group_update(103, array('name'=>lang('group_103')));
		group_update(104, array('name'=>lang('group_104')));
		group_update(105, array('name'=>lang('group_105')));
		
		forum_update(1, array('name'=>lang('default_forum_name'), 'brief'=>lang('default_forum_brief')));
				
		xn_mkdir(APP_PATH.'tmp/src', 0777);
		
		message(0, lang('conguralation_installed'));
	}
}


?>
