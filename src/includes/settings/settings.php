<?php

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2009-2011,2020-2026 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

if (!defined('MTT_PAGE')) {
    die("Unexpected usage");
}

function _c($key)
{
    return Config::get($key);
}

function selectOptions($a, $value, $default=null)
{
    if(!$a) return '';
    $s = '';
    if($default !== null && !isset($a[$value])) $value = $default;
    foreach($a as $k=>$v) {
        $s .= '<option value="'.htmlspecialchars($k).'" '.($k===$value?'selected="selected"':'').'>'.htmlspecialchars($v).'</option>';
    }
    return $s;
}


/**
 * @param array $a             array of id=>array(name, optional title)
 * @param mixed $key           Key of OPTION to be selected
 * @param mixed $default       Default key if $key is not present in $a
 */
function selectOptionsA($a, $key, $default=null)
{
    if(!$a) return '';
    $s = '';
    if ($default !== null && !isset($a[$key])) $key = $default;
    else if ($default === null && !isset($a[$key])) {
        $s .= '<option hidden disabled selected value></option>';
    }
    foreach($a as $k=>$v) {
        if (!is_array($v)) {
            $v = array('name' => $k);
        }
        $s .= '<option value="'.htmlspecialchars($k).'" '.($k===$key?'selected="selected"':'').
            (isset($v['title']) ? ' title="'.htmlspecialchars($v['title']).'"' : '').
            '>'.htmlspecialchars($v['name']).'</option>';
    }
    return $s;
}

?>

<div id="page_settings">

<h3 class="page-title"><?php _e('set_header');?></h3>

<div id="settings_msg" style="display:none"></div>

<div id="settings_container">

<div id="settings_menu">
   <p><a href="<?php mtturl('settings/general'); ?>"><?php _e('set_general');?><a></p>
   <p><a href="<?php mtturl('settings/extensions'); ?>"><?php _e('set_extensions');?></a></p>
  </div>

  <div id="settings_content">
<?php
    if (isset(MTTVars::$settingsPageFile)) {
        require_once(MTTVars::$settingsPageFile);
    }
    else {
        echo "Content not found";
    }
?>
  </div>

</div>

</div>
