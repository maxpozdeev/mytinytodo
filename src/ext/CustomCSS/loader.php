<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

if (!defined('MTTPATH')) {
    die("Unexpected usage.");
}

function mtt_ext_customcss_instance(): MTTExtension
{
    return new CustomCssExtension();
}

class CustomCssExtension extends MTTExtension implements MTTExtensionSettingsInterface
{
    //the same as dir name
    const bundleId = 'CustomCSS';

    // settings domain
    const domain = "ext.customcss.json";

    const cssFilename = 'custom.css';

    function init()
    {
        $prefs = self::preferences();
        if (isset($prefs['css'])) {
            $href = htmlspecialchars( get_unsafe_mttinfo('theme_url'). self::cssFilename. '?v='. ($prefs['edited'] ?? 0) );
            $cb = function() use ($href) {
                print "<link rel='stylesheet' type='text/css' href='$href'>\n";
            };
            add_action('theme_head_end', $cb);
        }
    }

    function settingsPage(): string
    {
        $e = function($s) { return __($s, true); };
        $prefs = self::preferences();
        $css = htmlspecialchars($prefs['css'] ?? '');

        return
<<<EOD
<div class="tr">
 <div class="th"> {$e('customcss.h_css')}
  <div class="descr">{$e('customcss.d_css')}</div>
 </div>
 <div class="td"> <textarea name="css" class="inmax monospace">$css</textarea> </div>
</div>
EOD;
    }

    function settingsPageType(): int
    {
        return 0; //default page
    }

    function saveSettings(array $params, ?string &$outMessage): bool
    {
        if (defined('MTT_DEMO')) {
            $outMessage = "Demo";
            return true;
        }
        $css = $params['css'] ?? '';

        $cssFilename = MTT_THEME_PATH. self::cssFilename;
        if (!file_exists($cssFilename)) {
            @touch($cssFilename);
        }
        if (!is_writable($cssFilename)) {
            $outMessage = __('customcss.not_writable');
            return false;
        }
        @file_put_contents($cssFilename, $css);
        $outMessage = __('customcss.saved');
        return true;
    }

    static function preferences(): array
    {
        $prefs['cssFilename'] = $cssFilename = MTT_THEME_PATH. self::cssFilename;
        if (file_exists($prefs['cssFilename'])) {
            $prefs['edited'] = filemtime($cssFilename) ?? 0;
            $prefs['css'] = @file_get_contents($cssFilename) ?? '';
        }
        return $prefs;
    }

}
