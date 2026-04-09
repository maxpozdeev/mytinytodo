<?php

if (!defined('MTT_PAGE')) {
    die("Unexpected usage");
}

if (isset($_POST['save']))
{
    check_token();

    $t = array();
    $langs = getLangs();
    Config::$params['lang']['options'] = array_keys($langs);
    Config::set('lang', _post('lang'));

    // in Demo mode we can set only language by cookies
    if (defined('MTT_DEMO')) {
        setcookie('lang', Config::get('lang'), 0, url_dir(Config::get('url')=='' ? getRequestUri() : Config::getUrl('url')));
        $t['saved'] = 1;
        jsonExit($t);
    }

    Config::set('smartsyntax', (int)_post('smartsyntax'));
    // Do not set invalid timezone
    try {
        $tz = trim(_post('timezone'));
        $testTZ = new DateTimeZone($tz); //will throw Exception on invalid timezone
        Config::set('timezone', $tz);
    }
    catch (Exception $e) {
    }
    Config::set('autotag', (int)_post('autotag'));
    Config::set('markup', (int)_post('markdown') == 0 ? 'v1' : 'markdown');
    Config::set('firstdayofweek', (int)_post('firstdayofweek'));
    Config::set('clock', (int)_post('clock'));
    Config::set('dateformat', removeNewLines(_post('dateformat')) );
    Config::set('dateformat2', removeNewLines(_post('dateformat2')) );
    Config::set('dateformatshort', removeNewLines(_post('dateformatshort')) );
    Config::set('title', removeNewLines(trim(_post('title'))) );
    Config::set('showdate', (int)_post('showdate'));
    Config::set('showtime', (int)_post('showtime'));
    Config::set('showdateInline', (int)_post('showdateInline'));
    Config::set('exactduedate', (int)_post('exactduedate'));
    Config::set('appearance', removeNewLines(trim(_post('appearance'))) );
    Config::set('newTaskCounter', (int)_post('newTaskCounter'));
    Config::set('newTaskCounterIcon', (int)_post('newTaskCounterIcon'));
    Config::save();
    $t['saved'] = 1;
    jsonExit($t);
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
                die("false ");
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

?>

<h4> <?php _e('set_general');?> </h4>

<form action="<?php mtt_settings_page_url(); ?>" method="post">
<div class="mtt-settings-table">

<div class="tr">
  <div class="th"> <?php _e('set_title');?>: <div class="descr"><?php _e('set_title_descr');?></div></div>
  <div class="td"> <input name="title" value="<?php echo htmlspecialchars(_c('title'));?>" class="in350" autocomplete="off" /> </div>
</div>

<div class="tr">
  <div class="th"><?php _e('set_language');?>:</div>
  <div class="td"> <select name="lang"><?php echo selectOptionsA(getLangs(), _c('lang')); ?></select> </div>
</div>

<div class="tr">
<div class="th"><?php _e('set_protection');?>:</div>
<div class="td">
 <label><input type="radio" name="allowpassword" value="1" <?php if(_c('password')!='') echo 'checked="checked"'; ?> onclick='$(this.form).find("input[name=password]").attr("disabled",false)' /> <?php _e('set_enabled');?></label> <br/>
 <label><input type="radio" name="allowpassword" value="0" <?php if(_c('password')=='') echo 'checked="checked"'; ?> onclick='$(this.form).find("input[name=password]").attr("disabled","disabled")' /> <?php _e('set_disabled');?></label> <br/>
</div></div>

<div class="tr">
<div class="th"><?php _e('set_newpass');?>: <div class="descr"><?php _e('set_newpass_descr');?></div></div>
<div class="td"><input type="password" name="password" autocomplete="new-password" <?php if(_c('password')=='') echo "disabled"; ?> /> </div>
</div>

<div class="tr">
<div class="th"><?php _e('set_smartsyntax');?>: <div class="descr"><?php _e('set_smartsyntax3_descr');?></div></div>
<div class="td">
 <label><input type="radio" name="smartsyntax" value="1" <?php if(_c('smartsyntax')) echo 'checked="checked"'; ?> /> <?php _e('set_enabled');?></label> <br/>
 <label><input type="radio" name="smartsyntax" value="0" <?php if(!_c('smartsyntax')) echo 'checked="checked"'; ?> /> <?php _e('set_disabled');?></label>
</div></div>

<div class="tr">
<div class="th"><?php _e('set_autotag');?>: <div class="descr"><?php _e('set_autotag_descr');?></div></div>
<div class="td">
 <label><input type="radio" name="autotag" value="1" <?php if(_c('autotag')) echo 'checked="checked"'; ?> /> <?php _e('set_enabled');?></label> <br/>
 <label><input type="radio" name="autotag" value="0" <?php if(!_c('autotag')) echo 'checked="checked"'; ?> /> <?php _e('set_disabled');?></label>
</div></div>

<div class="tr">
<div class="th"><?php _e('set_markdown');?>: <div class="descr"><?php _e('set_markdown_descr');?></div></div>
<div class="td">
 <label><input type="radio" name="markdown" value="1" <?php if (_c('markup') != 'v1') echo 'checked="checked"'; ?> /> <?php _e('set_enabled');?></label> <br/>
 <label><input type="radio" name="markdown" value="0" <?php if (_c('markup') == 'v1') echo 'checked="checked"'; ?> /> <?php _e('set_disabled');?></label>
</div></div>

<div class="tr">
<div class="th"><?php _e('set_timezone');?>:</div>
<div class="td">
 <select name="timezone"><?php echo selectOptions(timezoneIdentifiers(), _c('timezone')); ?></select>
</div></div>

<div class="tr">
<div class="th"><?php _e('set_firstdayofweek');?>:</div>
<div class="td">
 <select name="firstdayofweek"><?php echo selectOptions(__('days_long'), _c('firstdayofweek')); ?></select>
</div></div>

<div class="tr">
<div class="th"><?php _e('set_date');?>:</div>
<div class="td">
 <input name="dateformat" size="8" value="<?php echo htmlspecialchars(_c('dateformat'));?>" />
 <select onchange="if(this.value!=0) this.form.dateformat.value=this.value;">
 <?php $ts = strtotime('2023-09-05 09:15:25');
 echo selectOptions(array(
    'F j, Y' => formatTime('F j, Y', $ts),
    'M j, Y' => formatTime('M j, Y', $ts),
    'j M Y'  => formatTime('j M Y', $ts),
    'j F Y'  => formatTime('j F Y', $ts),
    'n/j/Y'  => formatTime('n/j/Y', $ts),
    'd.m.Y'  => formatTime('d.m.Y', $ts),
    'j. F Y' => formatTime('j. F Y', $ts),
    0 => __('set_custom')), _c('dateformat'), 0); ?>
 </select>
</div></div>

<div class="tr">
<div class="th"><?php _e('set_date2');?>:</div>
<div class="td">
 <input name="dateformat2" size="8" value="<?php echo htmlspecialchars(_c('dateformat2'));?>" />
 <select onchange="if(this.value!=0) this.form.dateformat2.value=this.value;">
 <?php echo selectOptions(array(
       'Y-m-d' => 'yyyy-mm-dd ('. date('Y-m-d', $ts). ')',
       'n/j/y' => 'm/d/yy ('. date('n/j/y', $ts). ')',
       'd.m.y' => 'dd.mm.yy ('. date('d.m.y', $ts). ')',
       'd/m/y' => 'dd/mm/yy ('. date('d/m/y', $ts). ')',
       0 => __('set_custom')), _c('dateformat2'), 0);  ?>
 </select>
</div></div>

<div class="tr">
<div class="th"><?php _e('set_shortdate');?>:</div>
<div class="td">
 <input name="dateformatshort" size="8" value="<?php echo htmlspecialchars(_c('dateformatshort'));?>" />
 <select onchange="if(this.value!=0) this.form.dateformatshort.value=this.value;">
 <?php echo selectOptions(array(
    'M d' => formatTime('M d', $ts),
    'j M' => formatTime('j M', $ts),
    'n/j' => formatTime('n/j', $ts),
    'd.m' => formatTime('d.m', $ts),
    0 => __('set_custom')), _c('dateformatshort'), 0); ?>
 </select>
</div></div>

<div class="tr">
<div class="th"><?php _e('set_clock');?>:</div>
<div class="td">
 <select name="clock"><?php echo selectOptions(array(
    12 => __('set_12hour'). ' ('. date('g:i A', $ts). ')',
    24 => __('set_24hour'). ' ('. date('H:i', $ts). ')'), _c('clock')); ?>
 </select>
</div></div>

<div class="tr">
<div class="th"><?php _e('set_showdate');?>:</div>
<div class="td">
 <label><input type="radio" name="showdate" value="1" <?php if(_c('showdate')) echo 'checked="checked"'; ?> /> <?php _e('set_enabled');?></label> <br>
 <label><input type="radio" name="showdate" value="0" <?php if(!_c('showdate')) echo 'checked="checked"'; ?> /> <?php _e('set_disabled');?></label> <br>
 <label><input type="checkbox" name="showdateInline" value="1" <?php if(_c('showdateInline')) echo 'checked="checked"'; ?> /> <?php _e('set_showdate_inline');?></label> <br>
 <label><input type="checkbox" name="showtime" value="1" <?php if(_c('showtime')) echo 'checked="checked"'; ?> /> <?php _e('set_showtime');?></label>
</div>
</div>

<div class="tr">
<div class="th"><?php _e('set_exactduedate');?>:</div>
<div class="td">
 <label><input type="radio" name="exactduedate" value="1" <?php if(_c('exactduedate')) echo 'checked="checked"'; ?> /> <?php _e('set_enabled');?></label> <br/>
 <label><input type="radio" name="exactduedate" value="0" <?php if(!_c('exactduedate')) echo 'checked="checked"'; ?> /> <?php _e('set_disabled');?></label>
</div></div>

<div class="tr">
<div class="th"><?php _e('set_appearance');?>:</div>
<div class="td">
 <label><input type="radio" name="appearance" value="system" <?php if(_c('appearance') == 'system') echo 'checked="checked"'; ?> /> <?php _e('set_appearance_system');?></label> <br>
 <label><input type="radio" name="appearance" value="light"  <?php if(_c('appearance') == 'light')  echo 'checked="checked"'; ?> /> <?php _e('set_appearance_light');?></label> <br>
 <label><input type="radio" name="appearance" value="dark"  <?php if(_c('appearance') == 'dark')  echo 'checked="checked"'; ?> /> <?php _e('set_appearance_dark');?></label>
</div>
</div>

<div class="tr">
  <div class="th"><?php _e('set_newtaskcounter_h');?>:</div>
  <div class="td">
    <label><input type="checkbox" name="newTaskCounter" value="1" <?php if(_c('newTaskCounter')) echo 'checked="checked"'; ?> /> <?php _e('set_newtaskcounter');?></label> <br>
    <label><input type="checkbox" name="newTaskCounterIcon" value="1" <?php if(_c('newTaskCounterIcon')) echo 'checked="checked"'; ?> /> <?php _e('set_newtaskcountericon');?></label>
  </div>
</div>

<div class="tr form-bottom-buttons">
  <button type="submit"><?php _e('set_submit'); ?></button>
</div>

</div>
</form>
