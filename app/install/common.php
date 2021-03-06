<?php

// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------
// 检测环境是否支持可写
//define('IS_WRITE', true);

use Think\Request;
use think\facade\Session;
use think\facade\Config;
use think\facade\Env;

/**
 * 写入配置文件
 * @param  array $config 配置信息
 * @return string
 */
function write_config($config)
{
    if (is_array($config)) {
        //读取配置内容
        $conf = file_get_contents(Env::get('module_path') . 'data/database.tpl');
        //替换配置项
        foreach ($config as $name => $value) {
            $conf = str_replace("[{$name}]", $value, $conf);
        }
        //写入应用配置文件
        if (file_put_contents(Env::get('config_path') . 'database.php', $conf)) {
             show_msg('配置文件写入成功!');
        } else {
             show_msg('配置文件写入失败！', 'error');
            Session::set('error', true, 'install');
        }
        return true;
    }
}

/**
 * 创建数据表
 * @param  resource $db 数据库连接资源
 * @param string $prefix 数据表前缀
 */
function create_tables($db, $prefix = '') 
{
	// 导入sql数据表
    $sql = file_get_contents('../app/install/data/taoler.sql');
	if ($sql) {
		//sql表前缀
		$orginal = 'tao_';
		($orginal==$prefix) ? true : $sql = str_replace(" `{$orginal}", " `{$prefix}", $sql);
		$sql_array = preg_split("/;[\r\n]+/", $sql);
		//var_dump($sql_array);
	
	//开始写入表
        foreach ($sql_array as $k => $v) {
            if (!empty($v)) {
	            //$v=$v.';';
	           if (substr($v, 0, 12) == 'CREATE TABLE') {
		            $name = preg_replace("/^CREATE TABLE `(\w+)` .*/s", "\\1", $v);
		            $msg = "创建数据表{$name}";
					$res = $db->query($v);
		            if ($res == false) {
		                echo $msg.'失败';
		            }
				} else {
					//执行插入数据
					$res = $db->query($v);
					if ($res == false) {
		                echo '数据插入失败';
		            }
				}
            }
        }
	} else {
		return false;
	}
   return true; 
}

function register_administrator($db, $prefix, $admin) {
    //show_msg('开始注册创始人帐号...');
    $password = password_hash($admin['password'], PASSWORD_DEFAULT);
    $sql="INSERT INTO {$prefix}user(group_id,username,password,email,create_time) VALUE(1,'{$admin['username']}','{$password}','{$admin['email']}','{time()}')";
    //执行sql
    $db->execute($sql);
     //show_msg('创始人帐号注册完成！');
}

/**
 * 更新数据表
 * @param  resource $db 数据库连接资源
 * @param string $prefix
 * @author lyq <605415184@qq.com>
 */
function update_tables($db, $prefix = '') {
    //读取SQL文件
    $sql = file_get_contents(APP_PATH . 'install/data/update.sql');
    $sql = str_replace("\r", "\n", $sql);
    $sql = explode(";\n", $sql);

    //替换表前缀
    $sql = str_replace(" `tao_", " `{$prefix}", $sql);

    //开始安装
     show_msg('开始升级数据库...');
    foreach ($sql as $value) {
        $value = trim($value);
        if (empty($value)) {
            continue;
        }
        if (substr($value, 0, 12) == 'CREATE TABLE') {
            $name = preg_replace("/^CREATE TABLE `(\w+)` .*/s", "\\1", $value);
            $msg = "创建数据表{$name}";
            if (false !== $db->execute($value)) {
                 show_msg($msg . '...成功!');
            } else {
                 show_msg($msg . '...失败！', 'error');
                Session::set('error', true, 'install');
            }
        } else {
            if (substr($value, 0, 8) == 'UPDATE `') {
                $name = preg_replace("/^UPDATE `(\w+)` .*/s", "\\1", $value);
                $msg = "更新数据表{$name}";
            } else if (substr($value, 0, 11) == 'ALTER TABLE') {
                $name = preg_replace("/^ALTER TABLE `(\w+)` .*/s", "\\1", $value);
                $msg = "修改数据表{$name}";
            } else if (substr($value, 0, 11) == 'INSERT INTO') {
                $name = preg_replace("/^INSERT INTO `(\w+)` .*/s", "\\1", $value);
                $msg = "写入数据表{$name}";
            }
            if (($db->execute($value)) !== false) {
                show_msg($msg . '...成功!');
            } else {
                show_msg($msg . '...失败！', 'error');
                Session::set('error', true, 'install');
            }
        }
    }
}

/**
 * 及时显示提示信息
 * @param  string $msg 提示信息
 * @param string $class
 * @param string $jump
 */
function show_msg($msg, $class = '',$jump='') {
    echo "<script type=\"text/javascript\">showmsg(\"{$msg}\", \"{$class}\",\"{$jump}\")</script>";
    flush();
    ob_flush();
}