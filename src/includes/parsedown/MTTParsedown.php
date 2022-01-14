<?php

class MTTParsedown extends Parsedown
{

    function __construct()
    {
        $this->InlineTypes['#'][]= 'TaskId';

        $this->inlineMarkerList .= '#';
    }

    protected function inlineTaskId($excerpt)
    {
        if (preg_match('/^#(\d+)/', $excerpt['text'], $matches))
        {
            return array(

                // How many characters to advance the Parsedown's
                // cursor after being done processing this tag.
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'a',
                    'text' => '#'. $matches[1],
                    'attributes' => array(
                        'href' => get_mttinfo('url'). '?task='. $matches[1],
						'target' => '_blank',
						'class' => 'mtt-link-to-task',
						'target-id' => $matches[1],
                    ),
                ),

            );
        }
    }

}
