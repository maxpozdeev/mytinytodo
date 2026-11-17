<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2026 Max Pozdeev <maxpozdeev@gmail.com>
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
        $this->converter->setBreaksEnabled(true);
        $this->converter->setBrTagEnabled(true);
    }

    public function convert(string $s, bool $toExternal = false): string
    {
        $this->converter->setToExternal($toExternal);
        return $this->converter->text($s);
    }
}


class MTTParsedown extends Parsedown
{

    protected bool $toExternal;
    protected bool $brTagEnabled;

    function __construct()
    {
        $this->toExternal = false;
        $this->brTagEnabled = false;

        $this->InlineTypes['#'][]= 'TaskId';
        $this->inlineMarkerList .= '#';

        $this->safeLinksWhitelist[] = 'sms:';
    }

    public function setToExternal(bool $v)
    {
        $this->toExternal = $v;
    }

    public function setBrTagEnabled(bool $v)
    {
        if ($this->brTagEnabled === $v) {
            return;
        }
        $this->brTagEnabled = $v;
        if ($v) {
            $this->InlineTypes['<'][] = 'Br';
        }
        else {
            if (($pos = array_search('Br', $this->InlineTypes['<'])) !== false) {
                unset($this->InlineTypes['<'][$pos]);
            }
        }
    }

    protected function inlineTaskId($excerpt)
    {
        if (preg_match('/^#(\d+)/', $excerpt['text'], $matches))
        {
            $attrs = array(
                'href' => routerMakeUserUrl('task/'. $matches[1]),  # or routerGetUserGoPrefix(). 'task='. $matches[1],
                'target' => '_blank',
                'rel' => 'noopener',
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

    /**
     * Inline types that must not be processed inside a link text.
     */
    protected function nonNestableInlineTypes(): array
    {
        return array();
    }

    protected function inlineLink($Excerpt) {
        $a = parent::inlineLink($Excerpt);
        if (is_array($a) && isset($a['element']['attributes']['href'])) {
            $a['element']['attributes']['target'] = '_blank';
            $a['element']['attributes']['rel'] = 'noopener';
        }
        if (is_array($a)) {
            $nonNestables = $this->nonNestableInlineTypes();
            if ($nonNestables) {
                $a['element']['nonNestables'] = array_merge(
                    $a['element']['nonNestables'] ?? array(),
                    $nonNestables
                );
            }
        }
        return $a;
     }

    protected function inlineUrl($Excerpt) {
        $a = parent::inlineUrl($Excerpt);
        if (is_array($a) && isset($a['element']['attributes']['href'])) {
            $a['element']['attributes']['target'] = '_blank';
            $a['element']['attributes']['rel'] = 'noopener';
        }
        return $a;
    }

    protected function inlineBr($Excerpt) {
        if ( ! $this->safeMode) {
            return;
        }
        if (substr($Excerpt['text'], 1, 2) !== 'br') {
            return;
        }
        if (preg_match("#^(<br\\s*/?".">)#", $Excerpt['text'], $m)) {
            return array(
                'element' => array('name' => 'br'),
                'extent' => strlen($m[1]),
            );
        }
    }
}
