<?php

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

function getLangs()
{
    $langDir = Lang::instance()->langDir();
    if ( ! $h = opendir($langDir) ) {
            return false;
    }
    $a = array();
    while ( false !== ($file = readdir($h)) )
    {
        if ( preg_match('/(.+)\.json$/', $file, $m) ) {
            $jsonText = file_get_contents($langDir. $file);
            if (false === $jsonText) {
                continue;
            }
            $a[$m[1]] = $m[1];

            $j = json_decode($jsonText, true);
            if ( isset($j['_header']['language']) && isset($j['_header']['original_name']) ) {
                $a[$m[1]]= [
                    'name' => $j['_header']['original_name'],
                    'title' => $j['_header']['language']
                ];
            }
        }
    }
    closedir($h);
    uasort($a, 'cmpLangs');
    return $a;
}

function cmpLangs($a, $b) : int
{
    //return strcmp( mb_strtoupper($a['name']), mb_strtoupper($b['name']) );
    return strcasecmp($a['title'], $b['title']);
}

function timezoneIdentifiers()
{
    $zones = DateTimeZone::listIdentifiers();
    $a = array();
    foreach($zones as $v) {
        $a[$v] = $v;
    }
    return $a;
}

