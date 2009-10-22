<?php

require_once('./init.php');

if($needAuth && !is_logged())
{
	die("Access denied!<br> Disable password protection or Log in.");
}

if(isset($_POST['save']))
{
	stop_gpc($_POST);
	$t = array();
	$langs = getLangs();
	Config::$params['lang']['options'] = array_keys($langs);
	$config['lang'] = _post('lang');
	if(isset($_POST['password']) && $_POST['password'] != '') $config['password'] = $_POST['password'];
	elseif(!_post('allowpassword')) $config['password'] = '';
	if(isset($_POST['allowread'])) $config['allowread'] = (int)_post('allowread');
	$config['smartsyntax'] = (int)_post('smartsyntax');
	$config['autotz'] = (int)_post('autotz');
	$config['autotag'] = (int)_post('autotag');
	$config['session'] = _post('session');
	$config['firstdayofweek'] = (int)_post('firstdayofweek');
	$config['duedateformat'] = (int)_post('duedateformat');
	$config['clock'] = (int)_post('clock');
	$config['dateformat'] = _post('dateformat');
	$config['dateformatshort'] = _post('dateformatshort');
	Config::save($config);
	$t['saved'] = 1;
	echo json_encode($t);
	exit;
}


function _c($key)
{
	global $config;
	return Config::get($key, $config);
}

function getLangs()
{
    if (!$h = opendir('./lang')) return false;
    $a = array();
    while(false !== ($file = readdir($h)))
	{
		if(preg_match('/(.+)\.php$/', $file, $m) && $file != 'class.default.php') {
			$a[$m[1]] = $m[1];
		}
    }
    closedir($h);
    return $a;
}

function selectOptions($a, $value, $default=null)
{
	if(!$a) return '';
	$s = '';
	if($default !== null && !isset($a[$value])) $value = $default;
	foreach($a as $k=>$v) {
		$s .= '<option value="'.htmlspecialchars($k).'" '.($k===$value?'selected':'').'>'.htmlspecialchars($v).'</option>';
	}
	return $s;
}

?>

<h3>Settings</h3>

<div id="settings_msg" style="display:none"></div>

<form method="post" action="settings.php" onSubmit="saveSettings(this);return false;">

<table class="mtt-settings-table">

<tr>
<th>Language:</th>
<td> <SELECT name="lang"><?php $langs = getLangs(); echo selectOptions($langs, $config['lang']); ?></SELECT> </td>
</tr>

<tr>
<th>Password protection:</th>
<td>
 <label><input type="radio" name="allowpassword" value="1" <?php if(_c('password')!='') echo "checked"; ?> onClick='$(this.form).find("input[name=password],input[name=allowread]").attr("disabled",false)'>Enabled</label> <br>
 <label><input type="radio" name="allowpassword" value="0" <?php if(_c('password')=='') echo "checked"; ?> onClick='$(this.form).find("input[name=password],input[name=allowread]").attr("disabled","disabled")'>Disabled</label> <br>
</td></tr>

<tr>
<th>New password:<br><span class="descr">(leave blank if won't change current password)</span></th>
<td> <input type="password" name="password" <?php if(_c('password')=='') echo "disabled"; ?>> </td>
</tr>

<tr>
<th>Allow read-only:<br><span class="descr">(grant access to unauthorized users to view your tasks )</span></th>
<td>
 <label><input type="radio" name="allowread" value="1" <?php if(_c('allowread')) echo "checked"; if(_c('password')=='') echo " disabled"; ?>>Enabled</label> <br>
 <label><input type="radio" name="allowread" value="0" <?php if(!_c('allowread')) echo "checked"; if(_c('password')=='') echo " disabled"; ?>>Disabled</label>
</td></tr>

<tr>
<th>Smart syntax:<br><span class="descr">(/priority/ task /tags/)</span></th>
<td>
 <label><input type="radio" name="smartsyntax" value="1" <?php if(_c('smartsyntax')) echo "checked"; ?>>Enabled</label> <br>
 <label><input type="radio" name="smartsyntax" value="0" <?php if(!_c('smartsyntax')) echo "checked"; ?>>Disabled</label>
</td></tr>

<tr>
<th>Automatic timezone:<br><span class="descr">(determines timezone offset of user environment with javascript)</span></th>
<td>
 <label><input type="radio" name="autotz" value="1" <?php if(_c('autotz')) echo "checked"; ?>>Enabled</label> <br>
 <label><input type="radio" name="autotz" value="0" <?php if(!_c('autotz')) echo "checked"; ?>>Disabled</label>
</td></tr>

<tr>
<th>Autotagging:<br><span class="descr">(automatically adds tag of current tag filter to newly created task)</span></th>
<td>
 <label><input type="radio" name="autotag" value="1" <?php if(_c('autotag')) echo "checked"; ?>>Enabled</label> <br>
 <label><input type="radio" name="autotag" value="0" <?php if(!_c('autotag')) echo "checked"; ?>>Disabled</label>
</td></tr>

<tr>
<th>Session handling mechanism:</span></th>
<td>
 <label><input type="radio" name="session" value="default" <?php if(_c('session')=='default') echo "checked"; ?>>PHP</label> <br>
 <label><input type="radio" name="session" value="files" <?php if(_c('session')=='files') echo "checked"; ?>>Files</label> <span class="descr">(in &lt;mytinytodo_dir&gt;/tmp/sessions)</span>
</td></tr>

<tr>
<th>First day of week:</span></th>
<td>
 <SELECT name="firstdayofweek"><?php echo selectOptions(array(0=>'Sunday',1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday'), _c('firstdayofweek')); ?></SELECT>
</td></tr>

<tr>
<th>Duedate calendar format:</span></th>
<td>
 <SELECT name="duedateformat"><?php echo selectOptions(array(1=>'yyyy-mm-dd ('.date('Y-m-d').')', 2=>'m/d/yyyy ('.date('n/j/Y').')', 3=>'dd.mm.yyyy ('.date('d.m.Y').')', 4=>'dd/mm/yyyy ('.date('d/m/Y').')'), _c('duedateformat')); ?></SELECT>
</td></tr>

<tr>
<th>Date format:</span></th>
<td>
 <input name="dateformat" value="<?php echo htmlspecialchars(_c('dateformat'));?>">
 <SELECT onChange="if(this.value!=0) this.form.dateformat.value=this.value;">
 <?php echo selectOptions(array('F j, Y'=>date('F j, Y'), 'M d, Y'=>date('M d, Y'), 'j M Y'=>date('j M Y'), 'd F Y'=>date('d F Y'),
	'n/j/Y'=>date('n/j/Y'), 'd.m.Y'=>date('d.m.Y'), 'j. F Y'=>date('j. F Y'), 0=>'Custom'), _c('dateformat'), 0); ?></SELECT>
</td></tr>

<tr>
<th>Short Date format:</span></th>
<td>
 <input name="dateformatshort" value="<?php echo htmlspecialchars(_c('dateformatshort'));?>">
 <SELECT onChange="if(this.value!=0) this.form.dateformatshort.value=this.value;">
 <?php echo selectOptions(array('M d'=>date('M d'), 'j M'=>date('j M'), 'n/j'=>date('n/j'),	'd.m'=>date('d.m'), 0=>'Custom'), _c('dateformatshort'), 0); ?></SELECT>
</td></tr>

<tr>
<th>Clock format:</span></th>
<td>
 <SELECT name="clock"><?php echo selectOptions(array(12=>'12-hour ('.date('g:i A').')', 24=>'24-hour ('.date('H:i').')'), _c('clock')); ?></SELECT>
</td></tr>

<tr><td colspan="2" class="form-buttons">

<input type="submit" value="Submit changes">
<input type="button" value="Cancel" onClick="closeSettings()">

</td></tr>
</table>

</form>