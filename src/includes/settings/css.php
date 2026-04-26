<?php

if (!defined('MTT_PAGE')) {
    die("Unexpected usage");
}

$cssFilename = MTT_CONTENT_PATH. MTT_THEME. '/custom.css';

if (isset($_POST['save']))
{
    check_token();

    $css = _post('css');
    $t = [
        'ok' => false,
    ];

    if (!file_exists($cssFilename)) {
        @touch($cssFilename);
    }
    if (!is_writable($cssFilename)) {
        $t['error'] = __('set_customcss_not_writable', true, $cssFilename);
        jsonExit($t);
    }
    @file_put_contents($cssFilename, $css);

    $t['ok'] = true;
    $t['msg'] = __('set_saved', true);
    jsonExit($t);
}

?>

<h4> <?php _e('set_customcss');?> </h4>

<form action="<?php mtt_settings_page_url(); ?>" method="post">
<div class="mtt-settings-table">

<?php

$css = @file_get_contents($cssFilename) ?? '';
$e = function($s) { return __($s, true); };

echo <<<EOD
<div class="tr">
 <div class="th"> {$e('set_customcss_h')}
  <div class="descr">{$e('set_customcss_d')}</div>
 </div>
 <div class="td"> <textarea name="css" class="inmax monospace">$css</textarea> </div>
</div>
EOD;

?>

<div class="tr form-bottom-buttons">
  <button type="submit"><?php _e('set_submit'); ?></button>
</div>

</div>
</form>

