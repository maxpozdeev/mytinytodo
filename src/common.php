<?php

function htmlarray($a, $exclude=null)
{
	htmlarray_ref($a, $exclude);
	return $a;
}

function htmlarray_ref(&$a, $exclude=null)
{
	if(!$a) return;
	if(!is_array($a)) {
		$a = htmlspecialchars($a);
		return;
	}
	reset($a);
	if($exclude && !is_array($exclude)) $exclude = array($exclude);
	foreach($a as $k=>$v)
	{
		if(is_array($v)) $a[$k] = htmlarray($v, $exclude);
		elseif(!$exclude) $a[$k] = htmlspecialchars($v);
		elseif(!in_array($k, $exclude)) $a[$k] = htmlspecialchars($v);
	}
	return;
}

function stop_gpc(&$arr)
{
	if (!is_array($arr)) return 1;
	
	if (!get_magic_quotes_gpc()) return 1;
	reset($arr);
	foreach($arr as $k=>$v)
	{
		if(is_array($arr[$k])) stop_gpc($arr[$k]);
		elseif(is_string($arr[$k])) $arr[$k] = stripslashes($v);
	}

	return 1;
}
function _post($param,$defvalue = '')
{
	if(!isset($_POST[$param])) 	{
		return $defvalue;
	}
	else {
		return $_POST[$param];
	}
}

function _get($param,$defvalue = '')
{
	if(!isset($_GET[$param])) {
		return $defvalue;
	}
	else {
		return $_GET[$param];
	}
} 

class Config
{
	public static $params = array(
		'db' => array('default'=>'sqlite', 'type'=>'s'),
		'mysql.host' => array('default'=>'localhost', 'type'=>'s'),
		'mysql.db' => array('default'=>'mytinytodo', 'type'=>'s'),
		'mysql.user' => array('default'=>'user', 'type'=>'s'),
		'mysql.password' => array('default'=>'', 'type'=>'s'),
		'title' => array('default'=>'', 'type'=>'s'),
		'lang' => array('default'=>'en', 'type'=>'s'),
		'password' => array('default'=>'', 'type'=>'s'),
		'smartsyntax' => array('default'=>1, 'type'=>'i'),
		'autotz' => array('default'=>1, 'type'=>'i'),
		'autotag' => array('default'=>1, 'type'=>'i'),
		'duedateformat' => array('default'=>1, 'type'=>'i'),
		'firstdayofweek' => array('default'=>1, 'type'=>'i'),
		'session' => array('default'=>'files', 'type'=>'s', 'options'=>array('files','default')),
		'clock' => array('default'=>24, 'type'=>'i', 'options'=>array(12,24)),
		'dateformat' => array('default'=>'j M Y', 'type'=>'s'),
		'dateformatshort' => array('default'=>'j M', 'type'=>'s'),
	);

	public static function save($config)
	{
		$s = '';
		foreach(self::$params as $param=>$v)
		{
			if(!isset($config[$param])) $val = $v['default'];
			elseif(isset($v['options']) && !in_array($config[$param], $v['options'])) $val = $v['default'];
			else $val = $config[$param];
			if($v['type']=='i') {
				$s .= "\$config['$param'] = ".(int)$val.";\n";
			}
			else {
				$s .= "\$config['$param'] = '".str_replace(array("\\","'"),array("\\\\","\\'"),$val)."';\n";
			}
		}
		$f = fopen('./db/config.php', 'w');
		if($f === false) throw new Exception("Error while saving config file");
		fwrite($f, "<?php\n\$config = array();\n$s?>");
		fclose($f);
	}

	public static function get($key, $config)
	{
		if(isset($config[$key])) return $config[$key];
		elseif(isset(self::$params[$key])) return self::$params[$key]['default'];
		else return null;
	}
}

function formatDate3($format, $ay, $am, $ad, $lang)
{
	# F - month long, M - month short
	# m - month 2-digit, n - month 1-digit
	# d - day 2-digit, j - day 1-digit
	$ml = $lang->get('months_long');
	$ms = $lang->get('months_short');
	$Y = $ay;
	$n = $am;
	$m = $n < 10 ? '0'.$n : $n;
	$F = $ml[$am-1];
	$M = $ms[$am-1];
	$j = $ad;
	$d = $j < 10 ? '0'.$j : $j;
	return strtr($format, array('Y'=>$Y, 'F'=>$F, 'M'=>$M, 'n'=>$n, 'm'=>$m, 'd'=>$d, 'j'=>$j));
}

?>