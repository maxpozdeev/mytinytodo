<?php

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

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
                $attrs['target-id'] = $matches[1];
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

}
