<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class MTTSmartSyntax implements MTTSmartSyntaxInterface
{
    protected $tagPrefix = '#';
    protected $duedatePrefix = '@!';
    protected $weekdays = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat']; //3-letter not present in lang

    /** @var MTTSmartSyntaxInterface */
    protected static $instance;

    public static function instance(): MTTSmartSyntaxInterface
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function parse(string $title): array
    {
        $a = [
            'prio' => 0,
            'title' => $title,
            'tags' => '',
            'duedate' => null,
        ];
        // priority
        if ( preg_match("|^([-+]{1}\d+)(.+)|", $a['title'], $m) ) {
            $a['prio'] = (int) $m[1];
            if ( $a['prio'] < -1 ) $a['prio'] = -1;
            elseif ( $a['prio'] > 2 ) $a['prio'] = 2;
            $a['title'] = trim($m[2]);
        }
        // duedate
        if ( preg_match("|(.+)[{$this->duedatePrefix}]{1}(\S+)$|", $a['title'], $m) ) {
            $rest = $m[1];
            $duepre = $m[2];
            $duedate = $this->findDuedate($duepre);
            if ($duedate) {
                $a['duedate'] = $duedate;
                $a['title'] = $rest;
            }
        }
        // tags
        $tags = [];
        $a['title'] = trim( preg_replace_callback(
            "/(?:^|\s+)[{$this->tagPrefix}]{1}([^{$this->tagPrefix}\s]+)/",
            function ($matches) use (&$tags) {
                $tags[] = $matches[1];
                return '';
            },
            $a['title']
        ) );
        if (count($tags) > 0) {
            $a['tags'] = implode( ',' , $tags );
        }
        return $a;
    }

    private function findDuedate(string $s): ?string
    {
        if (preg_match("|^(\d+)([dwmy]{1})$|",$s, $m)) { // 5d,2w...
            $count = (int)$m[1];
            $period = $m[2];
            if ($period == 'd' || $period == 'w') { // days, weeks
                if ($period == 'w') $count *= 7;
                return date("Y-m-d", time() + 86400*$count);
            }
            else if ($period == 'm' || $period == 'y') { //months,years
                if ($period == 'y') $count *= 12;
                $a = explode(',', date('Y,m,d'));
                $y = (int)$a[0];
                $m = (int)$a[1] + $count;
                $d = (int)$a[2];
                if ($m > 12) {
                    $yy = (int)floor($m/12);
                    $y += $yy;
                    $m = $m - $yy*12;
                }
                $d = min($d, daysInMonth($m, $y));
                return "$y-$m-$d";
            }
        }

        if (null !== $duedate = $this->parseDuedate($s)) {
            return $duedate;
        }

        $lang = Lang::instance();
        //TODO: add 3-letter short?
        $needle = mb_strtolower($s);
        $wd = null;
        foreach ($lang->get('days_min') as $idx => $weekday) {
            if ($needle === mb_strtolower($weekday)) {
                $wd = $idx;
                break;
            }
        }
        if (null === $wd) {
            foreach ($this->weekdays as $idx => $weekday) {
                if ($needle === $weekday) {
                    $wd = $idx;
                    break;
                }
            }
        }
        if (null === $wd) {
            foreach ($lang->get('days_long') as $idx => $weekday) {
                if ($needle === mb_strtolower($weekday)) {
                    $wd = $idx;
                    break;
                }
            }
        }
        if (null !== $wd) {
            $curWD = (int)date('w');
            $daysAdd = 0;
            if ($wd <= $curWD) { //next week
                $daysAdd = 7 - ($curWD - $wd);
            }
            else { //current week
                $daysAdd = $wd - $curWD;
            }
            $oDue = new DateTime();
            $oDue->add( new DateInterval("P{$daysAdd}D") );
            return $oDue->format('Y-m-d');
        }

        return null;
    }

    /**
     * Try to parse input string as a duedate and return in format "Y-m-d".
     * Return null if fail.
     * @param string $s
     * @return null|string
     */
    public static function parseDuedate(string $s): ?string
    {
        $df2 = Config::get('dateformat2');
        if (max((int)strpos($df2,'n'), (int)strpos($df2,'m')) > max((int)strpos($df2,'d'), (int)strpos($df2,'j'))) $formatDayFirst = true;
        else $formatDayFirst = false;

        $y = $m = $d = 0;
        if (preg_match("|^(\d+)-(\d+)-(\d+)\b|", $s, $ma)) {
            $y = (int)$ma[1]; $m = (int)$ma[2]; $d = (int)$ma[3];
        }
        elseif (preg_match("|^(\d+)\/(\d+)\/(\d+)\b|", $s, $ma))
        {
            if($formatDayFirst) {
                $d = (int)$ma[1]; $m = (int)$ma[2]; $y = (int)$ma[3];
            } else {
                $m = (int)$ma[1]; $d = (int)$ma[2]; $y = (int)$ma[3];
            }
        }
        elseif (preg_match("|^(\d+)\.(\d+)\.(\d+)\b|", $s, $ma)) {
            $d = (int)$ma[1]; $m = (int)$ma[2]; $y = (int)$ma[3];
        }
        elseif (preg_match("|^(\d+)\.(\d+)\b|", $s, $ma)) {
            $d = (int)$ma[1]; $m = (int)$ma[2];
            $a = explode(',', date('Y,m,d'));
            if( $m<(int)$a[1] || ($m==(int)$a[1] && $d<(int)$a[2]) ) $y = (int)$a[0]+1;
            else $y = (int)$a[0];
        }
        elseif (preg_match("|^(\d+)\/(\d+)\b|", $s, $ma))
        {
            if($formatDayFirst) {
                $d = (int)$ma[1]; $m = (int)$ma[2];
            } else {
                $m = (int)$ma[1]; $d = (int)$ma[2];
            }
            $a = explode(',', date('Y,m,d'));
            if( $m<(int)$a[1] || ($m==(int)$a[1] && $d<(int)$a[2]) ) $y = (int)$a[0]+1;
            else $y = (int)$a[0];
        }
        else return null;
        if ($y < 100) $y = 2000 + $y;
        elseif ($y < 1000 || $y > 2099) $y = 2000 + (int)substr((string)$y, -2);
        if ($m > 12) $m = 12;
        $maxdays = daysInMonth($m,$y);
        if ($m < 10) $m = '0'.$m;
        if ($d > $maxdays) $d = $maxdays;
        elseif ($d < 10) $d = '0'.$d;
        return "$y-$m-$d";
    }
}

interface MTTSmartSyntaxInterface
{
    public function parse(string $title): array;
}

function parseSmartSyntax(string $title): ?array
{
    $a = MTTSmartSyntax::instance()->parse($title);
    do_filter('parseSmartSyntax', $title, $a);
    return $a;
}
