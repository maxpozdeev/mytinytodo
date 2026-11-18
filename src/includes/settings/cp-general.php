<?php

if (!defined('MTT_PAGE')) {
    die("Unexpected usage");
}


function _c($key)
{
    return Config::getConfig()->get($key);
}

if (isset($_POST['save']))
{
    $t = array();

    $langs = getLangs();
    Config::$appSchema['lang']['options'] = array_keys($langs);

    $config = AppConfig::requestDictionary(Config::appDomain, Config::$appSchema);
    $allSaved = true;

    # There are several forms on the page, so update only the settings that were submitted

    if (array_key_exists('lang', $_POST)) {
        $config->set('lang', _post('lang'));
    }
    if (array_key_exists('smartsyntax', $_POST)) {
        $config->set('smartsyntax', (int)_post('smartsyntax'));
    }
    if (array_key_exists('timezone', $_POST)) {
        // Do not set invalid timezone
        try {
            $tz = trim(_post('timezone'));
            $testTZ = new DateTimeZone($tz); //will throw Exception on invalid timezone
            $config->set('timezone', $tz);
        }
        catch (Exception $e) {
            $allSaved = false;
        }
    }
    if (array_key_exists('title', $_POST)) {
        $config->set('title', removeNewLines(trim(_post('title'))) );
    }
    if (array_key_exists('autotag', $_POST)) {
        $config->set('autotag', (int)_post('autotag'));
    }
    if (array_key_exists('markdown', $_POST)) {
        $config->set('markup', (int)_post('markdown') == 0 ? 'v1' : 'markdown');
    }
    if (array_key_exists('firstdayofweek', $_POST)) {
        $config->set('firstdayofweek', (int)_post('firstdayofweek'));
    }
    if (array_key_exists('clock', $_POST)) {
        $config->set('clock', (int)_post('clock'));
    }
    if (array_key_exists('dateformat', $_POST)) {
        $config->set('dateformat', removeNewLines(_post('dateformat')) );
    }
    if (array_key_exists('dateformat2', $_POST)) {
        $config->set('dateformat2', removeNewLines(_post('dateformat2')) );
    }
    if (array_key_exists('dateformatshort', $_POST)) {
        $config->set('dateformatshort', removeNewLines(_post('dateformatshort')) );
    }
    if (array_key_exists('showdate', $_POST)) {
        $config->set('showdate', (int)_post('showdate'));
    }
    if (array_key_exists('showtime', $_POST)) {
        $config->set('showtime', (int)_post('showtime'));
    }
    if (array_key_exists('showdateInline', $_POST)) {
        $config->set('showdateInline', (int)_post('showdateInline'));
    }
    if (array_key_exists('exactduedate', $_POST)) {
        $config->set('exactduedate', (int)_post('exactduedate'));
    }
    if (array_key_exists('appearance', $_POST)) {
        $config->set('appearance', removeNewLines(trim(_post('appearance'))) );
    }
    if (array_key_exists('newTaskCounter', $_POST)) {
        $config->set('newTaskCounter', (int)_post('newTaskCounter'));
    }
    if (array_key_exists('newTaskCounterIcon', $_POST)) {
        $config->set('newTaskCounterIcon', (int)_post('newTaskCounterIcon'));
    }
    if (array_key_exists('mailFrom', $_POST)) {
        $mailFrom = trim(_post('mailFrom'));
        if ($mailFrom === '' || isValidEmail($mailFrom)) {
            $config->set('mailFrom', $mailFrom);
        }
        else {
            $allSaved = false;
        }
    }
    if (array_key_exists('mailFromName', $_POST)) {
        $config->set('mailFromName', removeNewLines(trim(_post('mailFromName'))) );
    }

    AppConfig::saveDomain(Config::appDomain, $config->asArray());

    $t['ok'] = true;
    $t['saved'] = 1;
    $t['msg'] = __( $allSaved ? 'set_saved' : 'set_some_saved', true);
    jsonExit($t);
}


?>

<h2> <?php _e('set_general');?> </h2>

<form action="<?php mtt_settings_page_url(); ?>" method="post" data-ok-reload="1" data-form-id="general">
<div class="mtt-settings-table">

<div class="tr">
  <div class="th"> <?php _e('set_title');?> <div class="descr"><?php _e('set_title_descr');?></div></div>
  <div class="td"> <input name="title" value="<?php echo htmlspecialchars(_c('title'));?>" class="in350" autocomplete="off" /> </div>
</div>

<div class="tr">
<div class="th"><?php _e('set_smartsyntax');?> <div class="descr"><?php _e('set_smartsyntax3_descr');?></div></div>
<div class="td">
 <label><input type="radio" name="smartsyntax" value="1" <?php if(_c('smartsyntax')) echo 'checked="checked"'; ?> /> <?php _e('set_enabled');?></label> <br/>
 <label><input type="radio" name="smartsyntax" value="0" <?php if(!_c('smartsyntax')) echo 'checked="checked"'; ?> /> <?php _e('set_disabled');?></label>
</div></div>

<div class="tr">
<div class="th"><?php _e('set_markdown');?> <div class="descr"><?php _e('set_markdown_descr');?></div></div>
<div class="td">
 <label><input type="radio" name="markdown" value="1" <?php if (_c('markup') != 'v1') echo 'checked="checked"'; ?> /> <?php _e('set_enabled');?></label> <br/>
 <label><input type="radio" name="markdown" value="0" <?php if (_c('markup') == 'v1') echo 'checked="checked"'; ?> /> <?php _e('set_disabled');?></label>
</div></div>

<div class="tr">
  <div class="th"> <?php _e('set_mailfrom');?> <div class="descr"><?php _e('set_mailfrom_descr');?></div></div>
  <div class="td"> <input name="mailFrom" value="<?php echo htmlspecialchars(_c('mailFrom'));?>" placeholder="<?php echo htmlspecialchars(suggestedMailFrom()); ?>" class="in350" autocomplete="off" /> </div>
</div>

<div class="tr">
  <div class="th"> <?php _e('set_mailfromname');?> <div class="descr"><?php _e('set_mailfromname_descr');?></div></div>
  <div class="td"> <input name="mailFromName" value="<?php echo htmlspecialchars(_c('mailFromName'));?>" placeholder="myTinyTodo" class="in350" autocomplete="off" /> </div>
</div>

<div class="tr form-bottom-buttons">
  <button type="submit"><?php _e('set_submit'); ?></button>
</div>

</div>
</form>

<h2> <?php _e('set_general_default');?> </h2>

<form action="<?php mtt_settings_page_url(); ?>" method="post" data-ok-reload="1" data-form-id="general-defaults">
<div class="mtt-settings-table">

<div class="tr">
  <div class="th"><?php _e('set_language');?></div>
  <div class="td"> <select name="lang"><?php echo selectOptionsA(getLangs(), _c('lang')); ?></select> </div>
</div>

<div class="tr">
<div class="th"><?php _e('set_timezone');?></div>
<div class="td">
 <select name="timezone"><?php echo selectOptions(timezoneIdentifiers(), _c('timezone')); ?></select>
</div></div>

<div class="tr">
<div class="th"><?php _e('set_firstdayofweek');?></div>
<div class="td">
 <select name="firstdayofweek"><?php echo selectOptions(__('days_long'), _c('firstdayofweek')); ?></select>
</div></div>

<div class="tr">
<div class="th"><?php _e('set_date');?></div>
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
<div class="th"><?php _e('set_date2');?></div>
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
<div class="th"><?php _e('set_shortdate');?></div>
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
<div class="th"><?php _e('set_clock');?></div>
<div class="td">
 <select name="clock"><?php echo selectOptions(array(
    12 => __('set_12hour'). ' ('. date('g:i A', $ts). ')',
    24 => __('set_24hour'). ' ('. date('H:i', $ts). ')'), _c('clock')); ?>
 </select>
</div></div>

<div class="tr">
<div class="th"><?php _e('set_showdate');?></div>
<div class="td">
 <label><input type="radio" name="showdate" value="1" <?php if(_c('showdate')) echo 'checked="checked"'; ?> /> <?php _e('set_enabled');?></label> <br>
 <label><input type="radio" name="showdate" value="0" <?php if(!_c('showdate')) echo 'checked="checked"'; ?> /> <?php _e('set_disabled');?></label> <br>
 <label><input type="checkbox" name="showdateInline" value="1" <?php if(_c('showdateInline')) echo 'checked="checked"'; ?> /> <?php _e('set_showdate_inline');?></label> <br>
 <label><input type="checkbox" name="showtime" value="1" <?php if(_c('showtime')) echo 'checked="checked"'; ?> /> <?php _e('set_showtime');?></label>
</div>
</div>

<div class="tr">
<div class="th"><?php _e('set_exactduedate');?></div>
<div class="td">
 <label><input type="radio" name="exactduedate" value="1" <?php if(_c('exactduedate')) echo 'checked="checked"'; ?> /> <?php _e('set_enabled');?></label> <br/>
 <label><input type="radio" name="exactduedate" value="0" <?php if(!_c('exactduedate')) echo 'checked="checked"'; ?> /> <?php _e('set_disabled');?></label>
</div></div>

<div class="tr">
<div class="th"><?php _e('set_appearance');?></div>
<div class="td">
 <label><input type="radio" name="appearance" value="system" <?php if(_c('appearance') == 'system') echo 'checked="checked"'; ?> /> <?php _e('set_appearance_system');?></label> <br>
 <label><input type="radio" name="appearance" value="light"  <?php if(_c('appearance') == 'light')  echo 'checked="checked"'; ?> /> <?php _e('set_appearance_light');?></label> <br>
 <label><input type="radio" name="appearance" value="dark"  <?php if(_c('appearance') == 'dark')  echo 'checked="checked"'; ?> /> <?php _e('set_appearance_dark');?></label>
</div>
</div>

<div class="tr">
<div class="th"><?php _e('set_autotag');?> <div class="descr"><?php _e('set_autotag_descr');?></div></div>
<div class="td">
 <label><input type="radio" name="autotag" value="1" <?php if(_c('autotag')) echo 'checked="checked"'; ?> /> <?php _e('set_enabled');?></label> <br/>
 <label><input type="radio" name="autotag" value="0" <?php if(!_c('autotag')) echo 'checked="checked"'; ?> /> <?php _e('set_disabled');?></label>
</div></div>

<div class="tr">
  <div class="th"><?php _e('set_newtaskcounter_h');?></div>
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
