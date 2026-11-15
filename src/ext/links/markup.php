<?php declare(strict_types=1);

namespace LinksExtension;

/**
 * URI part of the Message-ID URL schemes.
 *
 * "mid:" is defined in RFC 2392: the message-id is an RFC 822/5322 addr-spec,
 * URL-encoded, without angle brackets. Apple Mail also uses "message:".
 *
 * Allowed characters: RFC 5322 atext, dots, "@", domain-literal brackets and
 * colon, percent-encoded octets and "/" as a separator for an optional
 * content-id. Comma and semicolon are not allowed in addr-spec and are treated
 * as punctuation.
 */
final class LinksExtMessageIdUri
{
    const CHARS = 'A-Za-z0-9!#$%&\'*+\-\/=?^_`{|}~.@:\[\]\/%';

    /**
     * Pattern with two capture groups: the URI and trailing punctuation.
     */
    static function pattern(bool $midEnabled, bool $messageEnabled): string
    {
        $chars = self::CHARS;
        $alternatives = [];
        if ($midEnabled) {
            $alternatives[] = 'mid:(?!<|>)['. $chars .']+?';
        }
        if ($messageEnabled) {
            $alternatives[] = 'message:<['. $chars .']+?>';
            $alternatives[] = 'message:['. $chars .']+?';
        }
        if (!$alternatives) {
            return '((?!)['. $chars .']+?)([,;:.!?]+)?';
        }
        return '((?:'. implode('|', $alternatives) .'))([,;:.!?]+)?';
    }

    /**
     * Apple Mail expects a message-id in "message:" URLs to be enclosed in
     * percent-encoded angle brackets. Add them when the user pasted a bare
     * message-id. The "mid:" scheme defined in RFC 2392 uses a bare id.
     */
    static function href(string $uri): string
    {
        if (stripos($uri, 'message:') !== 0) {
            return $uri;
        }
        $scheme = substr($uri, 0, strlen('message:'));
        $id = substr($uri, strlen('message:'));
        // Assume the user adds angle brackets on both sides or does not add them at all.
        if (preg_match('/^(?:%3C|<)(.+)(?:%3E|>)$/is', $id, $m)) {
            $id = $m[1];
        }
        return $scheme . '%3C' . $id . '%3E';
    }
}

class LinksExtParsedown extends \MTTParsedown
{
    private bool $midEnabled;
    private bool $messageEnabled;
    private string $midPattern;

    function __construct(bool $mid = true, bool $message = true)
    {
        parent::__construct();

        $this->midEnabled = $mid;
        $this->messageEnabled = $message;
        $this->midPattern = '/^'. LinksExtMessageIdUri::pattern($mid, $message) .'(?=\s|$|[<>"\'()\[\]{}])/i';

        // Allow Message-ID URI schemes in links even in Safe Mode
        if ($this->midEnabled) {
            $this->safeLinksWhitelist[] = 'mid:';
        }
        if ($this->messageEnabled) {
            $this->safeLinksWhitelist[] = 'message:';
        }

        // Auto-link plain "mid:..." and "message:..." occurrences in note text
        if ($this->midEnabled || $this->messageEnabled) {
            $this->InlineTypes['m'][]= 'MidLink';
            $this->inlineMarkerList .= 'm';
        }
    }

    protected function inlineMidLink($excerpt)
    {
        // Match Message-ID URI scheme: mid:<message-id> or message:<message-id>
        // Trailing punctuation is not a part of the URI and is not consumed.
        if (preg_match($this->midPattern, $excerpt['text'], $matches))
        {
            $uri = $matches[1];
            $attrs = array(
                'href' => $this->messageEnabled ? LinksExtMessageIdUri::href($uri) : $uri,
                'target' => '_blank',
                'rel' => 'noopener',
            );
            return array(
                'extent' => strlen($uri),
                'element' => array(
                    'name' => 'a',
                    'text' => $uri,
                    'attributes' => $attrs,
                ),
            );
        }
    }

    protected function nonNestableInlineTypes(): array
    {
        return array('MidLink');
    }
}

class LinksExtParsedownConverter extends \MTTParsedownWrapper
{
    function __construct(bool $mid = true, bool $message = true)
    {
        $this->converter = new LinksExtParsedown($mid, $message);
        $this->converter->setSafeMode(true);
        $this->converter->setBreaksEnabled(true);
        $this->converter->setBrTagEnabled(true);
    }
}


class LinksExtTitleMarkupConverter extends \MTTTitleMarkupConverter implements \MTTTitleMarkupInterface
{
    private bool $midEnabled;
    private bool $messageEnabled;
    private string $midPattern;

    function __construct(bool $mid = true, bool $message = true)
    {
        $this->midEnabled = $mid;
        $this->messageEnabled = $message;
        $this->midPattern = '/(^|[\s>"\'(\[{])'. LinksExtMessageIdUri::pattern($mid, $message) .'(?=\s|$|[<>"\'()\[\]{}])/iu';
    }

    public function convert(string $title): string
    {
        // Replace message-id URIs with placeholders before the parent converter
        // escapes HTML and creates http/www links. This keeps the raw URI and
        // avoids matching inside already created HTML attributes.
        $links = [];
        $title = preg_replace_callback(
            $this->midPattern,
            function (array $m) use (&$links): string {
                $key = "\x01mid". count($links) ."\x01";
                $links[$key] = $m[2];
                return $m[1] . $key . ($m[3] ?? '');
            },
            $title
        );

        $title = parent::convert($title);

        foreach ($links as $key => $uri) {
            $text = htmlspecialchars($uri, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $href = htmlspecialchars($this->messageEnabled ? LinksExtMessageIdUri::href($uri) : $uri, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $title = str_replace($key, '<a href="'. $href .'" target="_blank" rel="noopener">'. $text .'</a>', $title);
        }

        return $title;
    }
}
