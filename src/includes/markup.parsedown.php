<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

# We do not use composer autoloader because only one class is declared in Parsedown.
require_once(MTTINC. 'vendor/erusev/parsedown/Parsedown.php');

class MTTParsedownWrapper implements MTTMarkdownInterface
{
    /** @var MTTParsedown */
    protected $converter;

    function __construct()
    {
        $this->converter = new MTTParsedown();
        $this->converter->setSafeMode(true);
        //$this->converter->setBreaksEnabled(true);
    }

    public function convert(string $s, bool $toExternal = false)
    {
        $this->converter->setToExternal($toExternal);
        return $this->converter->text($s);
    }
}


class MTTParsedown extends Parsedown
{

    protected $toExternal;

    function __construct()
    {
        $this->toExternal = false;

        $this->InlineTypes['#'][]= 'TaskId';
        $this->inlineMarkerList .= '#';
    }

    public function setToExternal(bool $v)
    {
        $this->toExternal = $v;
    }

    protected function inlineTaskId($excerpt)
    {
        if (preg_match('/^#(\d+)/', $excerpt['text'], $matches))
        {
            $attrs = array(
                'href' => get_mttinfo('url'). '?task='. $matches[1],
                'target' => '_blank',
            );
            if (!$this->toExternal) {
                $attrs['class'] = 'mtt-link-to-task';
                $attrs['data-target-id'] = $matches[1];
            }
            return array(

                // How many characters to advance the Parsedown's
                // cursor after being done processing this tag.
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'a',
                    'text' => '#'. $matches[1],
                    'attributes' => $attrs,
                ),

            );
        }
    }

    protected function inlineLink($Excerpt) {
        $a = parent::inlineLink($Excerpt);
        if (is_array($a) && isset($a['element']['attributes']['href'])) {
            $a['element']['attributes']['target'] = '_blank';
        }
        return $a;
     }

     protected function inlineUrl($Excerpt) {
        $a = parent::inlineUrl($Excerpt);
        if (is_array($a) && isset($a['element']['attributes']['href'])) {
            $a['element']['attributes']['target'] = '_blank';
        }
        return $a;
     }
}
