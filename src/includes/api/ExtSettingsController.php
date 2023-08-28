<?php declare(strict_types=1);

class ExtSettingsController extends ApiController {

    /**
     * Get extension settings page
     * @return void
     * @throws Exception
     */
    function get(string $ext)
    {
        checkWriteAccess();

        /** @var MTTExtension|MTTExtensionSettingsInterface $instance */
        $instance = $this->extInstance($ext);
        if (!$instance) {
            return;
        }
        $meta = MTTExtension::extMetaInfo($ext);
        if (!$meta || !isset($meta['name'])) {
            return;
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
            $formStart = "<form id='ext_settings_form' data-ext='$escapedExt'>";
            $formEnd = "</form>";
            $formButtons =
<<<EOD
<div class="tr form-bottom-buttons">
    <button type="submit">{$e('set_submit')}</button>
    <button type="button" class="mtt-back-button">{$e('set_cancel')}</button>
</div>
EOD;
        }
        $data =
<<<EOD
<h3 class="page-title"><a class="mtt-back-button"></a> $name </h3>
<div id="settings_msg" style="display:none"></div>
$formStart
  <div class="mtt-settings-table">
    $data
    $formButtons
  </div>
$formEnd
EOD;

        $this->response->htmlContent($data);
    }

    /**
     * Save extension settings
     * @return void
     * @throws Exception
     */
    function put(string $ext)
    {
        checkWriteAccess();

        /** @var MTTExtension|MTTExtensionSettingsInterface $instance */
        $instance = $this->extInstance($ext);
        if (!$instance) {
            return;
        }
        //$userError = '';
        $saved = $instance->saveSettings($this->req->jsonBody ?? [], $userError);
        $a = [ 'saved' => (int)$saved ];
        if ($userError) {
            $a['msg'] = $userError;
        }
        $this->response->data = $a;
    }

    private function extInstance(string $ext): ?MTTExtensionSettingsInterface
    {
        $instance = MTTExtensionLoader::extensionInstance($ext);
        if (!$instance) {
            $this->response->data = [ 'msg' => "Unknown extension" ];
            $this->response->code = 404;
            return null;
        }
        if (! ($instance instanceof MTTExtensionSettingsInterface) ) {
            $this->response->data = [ 'msg' => "No settings page for extension" ];
            $this->response->code = 500;
            return null;
        }
        return $instance;
    }

}
