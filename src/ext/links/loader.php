<?php declare(strict_types=1);

if (!defined('MTTPATH')) {
    die("Unexpected usage.");
}

require_once(__DIR__. '/markup.php');


function mtt_ext_links_instance(): MTTExtension
{
    return new LinksExtension();
}


class LinksExtension extends MTTExtension implements MTTExtensionSettingsInterface
{
    //the same as dir name
    const bundleId = 'links';

    // settings domain
    const domain = "ext.links.json";

    const defaultPolicy = "strict-origin-when-cross-origin";

    function init()
    {
        $prefs = self::preferences();
        $mid = (bool)($prefs['schema_mid'] ?? false);
        $message = (bool)($prefs['schema_message'] ?? false);

        if ($mid || $message) {
            MTTMarkdown::setInstanceClass(\LinksExtension\LinksExtParsedownConverter::class, [$mid, $message]);
            MTTTitleMarkup::setInstanceClass(\LinksExtension\LinksExtTitleMarkupConverter::class, [$mid, $message]);
        }

        $policy = $prefs['meta_policy'] ?? null;
        if ( ! $this->isValidPolicyValue($policy) ) {
            $policy = self::defaultPolicy;
        }
        if ($policy !== '') {
            $policy = htmlspecialchars($policy);
            add_action('theme_head_end', function() use ($policy) {
                print "<meta name=\"referrer\" content=\"$policy\">\n";
            });
        }
    }

    function isValidPolicyValue(?string $value): bool
    {
        if (is_null($value)) {
            return false;
        }
        if ($value === '') {
            return true;
        }
        if (preg_match("/^[a-zA-Z-]+$/", $value)) {
            return true;
        }
        return false;
    }

    // copy from settings
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

    function settingsPage(): string
    {
        $e = function($s) { return __($s, true); };
        $prefs = self::preferences();
        $policy = $prefs['meta_policy'] ?? '';
        $bc = function($key) use ($prefs) { return boolval($prefs[$key] ?? false) ? 'checked=checked' : ''; };
        $select = $this->selectOptions([
            '' =>  __('links.no_policy', true),
            'no-referrer' => 'no-referrer',
            'no-referrer-when-downgrade' => 'no-referrer-when-downgrade',
            'origin' => 'origin',
            'origin-when-cross-origin' => 'origin-when-cross-origin',
            'same-origin' => 'same-origin',
            'strict-origin' => 'strict-origin',
            'strict-origin-when-cross-origin' => 'strict-origin-when-cross-origin',
            'unsafe-url' => 'unsafe-url'
        ], $policy, self::defaultPolicy);

        return
<<<EOD
<div class="tr">
 <div class="th"> {$e('links.h_ref_policy')}
  <div class="descr">{$e('links.d_ref_policy')}</div>
 </div>
 <div class="td"> <select name="policy">$select</select> </div>
</div>
<div class="tr">
 <div class="th"> {$e('links.h_schemas')}
  <div class="descr">{$e('links.d_schemas')}</div>
 </div>
 <div class="td">
   <label><input type="checkbox" name="schema_mid" value="1" {$bc('schema_mid')}> mid:</label> <br>
   <label><input type="checkbox" name="schema_message" value="1" {$bc('schema_message')}> message:</label> <br>
 </div>
</div>
EOD;
    }

    function settingsPageType(): int
    {
        return 0; //default page
    }

    function saveSettings(array $params, ?string &$outMessage): bool
    {
        $policy = $params['policy'] ?? '';
        if ( ! $this->isValidPolicyValue($policy) ) {
            $policy = self::defaultPolicy;
        }
        $prefs = Config::requestDomain(self::domain);
        $prefs['meta_policy'] = $policy;
        $prefs['schema_mid'] = boolval($params['schema_mid'] ?? false);
        $prefs['schema_message'] = boolval($params['schema_message'] ?? false);
        Config::saveDomain(self::domain, $prefs);
        $outMessage = __('set_saved');
        return true;
    }

    static function preferences(): array
    {
        $prefs = Config::requestDomain(self::domain);
        return $prefs;
    }
}
