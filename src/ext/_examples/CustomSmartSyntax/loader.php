<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

if (!defined('MTTPATH')) {
    die("Unexpected usage.");
}

function mtt_ext_customsmartsyntax_instance(): MTTExtension
{
    return new CustomSmartSyntaxExtension();
}

class CustomSmartSyntaxExtension extends MTTExtension implements MTTFilterInterface
{
    //the same as dir name
    const bundleId = 'CustomSmartSyntax';

    function init() {
        \MTTFilterCenter::addFilterForAction('parseSmartSyntax', $this);
    }

    // parseSmartSyntax
    function filter($title, &$out)
    {
        $a = [
            'prio' => 0,         // int: -1 .. 2
            'title' => $title,   // string
            'tags' => '',        // string: "tag1, tag2, tag3,..."
            'duedate' => null,   // string: "Y-m-d" format
        ];

        // This filter just overwrites results of default parser

        $out = $a;
    }
}
