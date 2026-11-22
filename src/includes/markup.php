<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2021-2025 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

require_once(MTTINC. 'markup.parsedown.php');
//require_once(MTTINC. 'markup.commonmark.php');

interface MTTMarkdownInterface
{
    public function convert(string $s, bool $toExternal = false);
}

final class MTTMarkdown
{
    /** @var MTTMarkdownInterface */
    private static $instance;

    /** @var string */
    private static $instanceClass = MTTParsedownWrapper::class;
    //private static $instanceClass = MTTCommonmarkWrapper::class;

    /**
     *
     * @return MTTMarkdownInterface
     */
    public static function instance() : MTTMarkdownInterface
    {
        if (isset(self::$instance))
            return self::$instance;

        // if (do_filter('markdownConverterClass', self::$instanceClass, $newClass) && $newClass) {
        //     self::setInstanceClass($newClass);
        // }

        self::$instance = new self::$instanceClass();
        return self::$instance;
    }

    public static function setInstanceClass(string $class)
    {
        if (!is_a($class, MTTMarkdownInterface::class, true)) {
            throw new Exception("Class '$class' is not a MTTMarkdownInterface");
        }
        self::$instanceClass = $class;
        self::$instance = null;
    }
}

interface MTTTitleMarkupInterface
{
    public function convert(string $title): string;
}

class MTTTitleMarkupConverter implements MTTTitleMarkupInterface
{
    public function convert(string $title): string
    {
        //escape all unsafe
        $title = htmlspecialchars($title, ENT_QUOTES);

        // make links from text starting with 'www.'
        $title = preg_replace(
            "/(^|\s|>)(www\.([\w\#$%&~\/.\-\+;:=,\?\[\]@]+?))(,|\.|:|)?(?=\s|&quot;|&lt;|&gt;|\"|<|>|$)/iu" ,
            '$1<a href="http://$2" target="_blank" rel="noopener">$2</a>$4' ,
            $title
            );

        // make link from text starting with protocol like http[s]://, ftp[s]://
        $title = preg_replace(
            "/(^|\s|>)((?:https?|ftps?):\/\/([\w\#$%&~\/.\-\+;:=,\?\[\]@]+?))(,|\.|:|)?(?=\s|&quot;|&lt;|&gt;|\"|<|>|$)/iu" ,
            '$1<a href="$2" target="_blank" rel="noopener">$2</a>$4' ,
            $title
        );
        return $title;
    }
}

final class MTTTitleMarkup
{
    /** @var MTTTitleMarkupInterface */
    private static $instance;

    /** @var string */
    private static $instanceClass = MTTTitleMarkupConverter::class;

    public static function instance() : MTTTitleMarkupInterface
    {
        if (isset(self::$instance))
            return self::$instance;

        self::$instance = new self::$instanceClass();
        return self::$instance;
    }

    public static function setInstanceClass(string $class)
    {
        if (!is_a($class, MTTTitleMarkupInterface::class, true)) {
            throw new Exception("Class '$class' is not a MTTTitleMarkupInterface");
        }
        self::$instanceClass = $class;
        self::$instance = null;
    }
}

function noteMarkup($note, $toExternal = false)
{
    if ($note === null) {
        $note = '';
    }
    if (Config::get('markup') == 'v1') {
        return mttMarkup_v1($note);
    }
    return markdownToHtml($note, $toExternal);
}

function markdownToHtml($s, $toExternal = false)
{
    return MTTMarkdown::instance()->convert($s, $toExternal);
}


// Convert note's raw text to html with allowed elements (b,i,u,s and raw urls)
function mttMarkup_v1($s)
{
    // escape everything first, then restore only allowed tags
    $s = htmlspecialchars($s, ENT_QUOTES);

    foreach (['b', 'i', 'u', 's'] as $tag) {
        $s = (string)preg_replace_callback(
            '~&lt;'. $tag. '&gt;([\s\S]*?)&lt;/'. $tag. '&gt;~i',
            function (array $m) use ($tag): string {
                return '<'. $tag. '>'. $m[1]. '</'. $tag. '>';
            },
            $s
        );
    }

    $s = nl2br($s);

    // make links from text starting with 'www.'
    $s = preg_replace(
        "/(^|\s|>)(www\.([\w\#$%&~\/.\-\+;:=,\?\[\]@]+?))(,|\.|:|)?(?=\s|&quot;|&lt;|&gt;|\"|<|>|$)/iu" ,
        '$1<a href="http://$2" target="_blank" rel="noopener">$2</a>$4' ,
        $s
    );

    // make link from text starting with protocol like http[s]://, ftp[s]://
    // only safe schemes are allowed ()
    $s = preg_replace(
        "/(^|\s|>)((?:https?|ftps?):\/\/([\w\#$%&~\/.\-\+;:=,\?\[\]@]+?))(,|\.|:|)?(?=\s|&quot;|&lt;|&gt;|\"|<|>|$)/iu" ,
        '$1<a href="$2" target="_blank" rel="noopener">$2</a>$4' ,
        $s
    );

    return (string)$s;
}

// Convert raw title to html with allowed urls
function titleMarkup($title)
{
    return MTTTitleMarkup::instance()->convert($title);
}

