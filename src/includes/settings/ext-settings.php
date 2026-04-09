<?php

if (!defined('MTT_PAGE')) {
    die("Unexpected usage");
}

if (isset($_POST['save']) && isset($_POST['ext'])) {

    $ext = _post('ext');

    $instance = MTTExtensionLoader::extensionInstance($ext);
    if (!$instance || ! ($instance instanceof MTTExtensionSettingsInterface)) {
        error_log("Failed to instantiate extension '$ext'");
        return page_500();
    }
    $saved = $instance->saveSettings($_POST, $userError);
    $a = [ 'saved' => (int)$saved ];
    if ($userError) {
        $a['msg'] = $userError;
    }
    jsonExit($a);
}
else if ('' !== $ext = _get('ext')) {
    $instance = MTTExtensionLoader::extensionInstance($ext);
    if (!$instance || ! ($instance instanceof MTTExtensionSettingsInterface)) {
        error_log("Failed to instantiate extension '$ext'");
        return page_404();
    }

    $meta = MTTExtension::extMetaInfo($ext);
    if (!$meta || !isset($meta['name'])) {
        error_log("No meta for extension '$ext'");
        return page_500();
    }

    $data = $instance->settingsPage();

    $lang = Lang::instance();
    $nameKey = 'ext.'. $ext. '.name';
    if ($lang->hasKey($nameKey)) {
        $name = htmlspecialchars($lang->get($nameKey));
    }
    else {
        $name = htmlspecialchars($meta['name']);
    }
    $escapedExt = htmlspecialchars($ext);
    $e = function($s) use($lang) { return htmlspecialchars($lang->get($s)); };

    $formStart = '';
    $formEnd = '';
    $formButtons = '';
    if ($instance->settingsPageType() == 0) {
        $formStart = "<form data-ext='$escapedExt' action='". mtt_get_settings_page_url(). "' method='post'>";
        $formEnd = "</form>";
        $formButtons =
<<<EOD
<div class="tr form-bottom-buttons">
    <button type="submit">{$e('set_submit')}</button>
</div>
EOD;
    }

    echo
<<<EOD
  <h4> $name </h4>
  $formStart
  <div class="mtt-settings-table">
    $data
    $formButtons
  </div>
  $formEnd
EOD;

}


