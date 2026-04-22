<?php

if (!defined('MTT_PAGE')) {
    die("Unexpected usage");
}

if (isset($_POST['activate']))
{
    check_token();

    $t = array('saved'=>0, 'ok'=>true);

    // in Demo mode we do nothing
    if (defined('MTT_DEMO')) {
        $t['saved'] = 1;
        jsonExit($t);
    }

    $activate = (int)_post('activate');
    $ext = _post('ext');

    $extBundles = MTTExtensionLoader::bundles();
    $exts = array_keys($extBundles);

    $config = AppConfig::requestDictionary(Config::appDomain, Config::$appSchema);
    $a = $config->getList('extensions') ?? [];

    if (in_array($ext, $exts)) {
        if ($activate) {
            try {
                MTTExtensionLoader::loadExtension($ext);
                $a[] = $ext;
            }
            catch (Exception $e) {
                http_response_code(500);
                logAndDie($e->getMessage());
            }
        }
        else $a = array_values(array_diff($a, [$ext]));
        $config->set('extensions', $a);
        AppConfig::saveDictionary(Config::appDomain, $config);
    }
    else if (!$activate && in_array($ext, $a)) {
        $a = array_values(array_diff($a, [$ext]));
        $config->set('extensions', $a);
        AppConfig::saveDictionary(Config::appDomain, $config);
    }
    $t['saved'] = 1;
    jsonExit($t);
}

function listExtensions()
{
    $extBundles = MTTExtensionLoader::bundles();
    $activatedExts = Config::getConfig()->getList('extensions') ?? [];
    $a = [];
    foreach ($extBundles as $ext => $meta) {
        $h = $d = $v = '';
        $h = htmlspecialchars($meta['name']);
        $v = htmlspecialchars('v'. $meta['version']);
        $isCompatible = MTTExtensionLoader::isBundleCompatible($meta);
        $isActive = true;
        if (!$isCompatible) {
            $v .= " <span style='color:red'>&lt;Not compatible&gt;</span> ";
        }
        if (in_array($ext, $activatedExts)) {
            $activatedExts = array_diff($activatedExts, [$ext]);
            $d .= "<a href='#' data-settings-action='ext-deactivate' data-ext='". htmlspecialchars($ext).  "'>". __('set_deactivate', true). '</a>';
            if ($isCompatible) {
                $instance = MTTExtensionLoader::extensionInstance($ext);
                if ($instance instanceof MTTExtensionSettingsInterface) {
                    $d .= " &nbsp; <a href='". get_mtturl('controlpanel/ext-settings','ext='.$ext). "' data-ext='". htmlspecialchars($ext). "'>". __('a_settings', true). "</a>";
                }
            }
        }
        else {
            $d .= "<a href='#' data-settings-action='ext-activate' data-ext='". htmlspecialchars($ext). "'>". __('set_activate', true). '</a>';
            $isActive = false;
        }
        $a[] = [
            'h' => $h,
            'v' => $v,
            'd' => $d,
            'active' => $isActive ? 0 : 1,
        ];
    }
    usort($a, function($v1,$v2){
        $r = $v1['active'] - $v2['active'];
        if ($r == 0)
            return strcmp($v1['h'], $v2['h']);
        return $r;
    });

    // removed and not deactivated
    foreach ($activatedExts as $ext) {
        $h = "$ext";
        $v = " &lt;extension not found&gt; ";
        $d = "<a href='#' data-settings-action='ext-deactivate' data-ext='". htmlspecialchars($ext). "'>". __('set_deactivate', true). '</a>';
        $a[] = [
            'h' => $h,
            'v' => $v,
            'd' => $d,
        ];
    }

    foreach ($a as $tr) {
        echo "<div class=tr>".
            "<div class=th>{$tr['h']}<div class=descr>{$tr['v']}</div></div>".
            "<div class=td>{$tr['d']}</div>".
            "</div>\n";
    }
}

?>

<h4> <?php _e('set_extensions');?> </h4>

<form action="<?php mtt_settings_page_url(); ?>" method="post">
<div class="mtt-settings-table">

<?php listExtensions(); ?>

</div>
</form>

