<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

require_once(MTTINC. 'vendor/autoload.php');

use League\CommonMark\MarkdownConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\Mention\MentionExtension;
use League\CommonMark\Extension\Mention\Mention;

class MTTCommonmarkWrapper implements MTTMarkdownInterface
{
    protected $toExternal;

    /** @var MarkdownConverter */
    protected $converter;

    function __construct()
    {
        $this->toExternal = false;

        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'mentions' => [
                'task_id' => [
                    'prefix'    => '#',
                    'pattern'   => '\d+',
                    'generator' => function ($mention) {
                        if (!($mention instanceof Mention)) {
                            return null;
                        }
                        $mention->setUrl(\sprintf(get_mttinfo('url'). "?task=%d", $mention->getIdentifier()));
                        if (!$this->toExternal) {
                            $mention->data->append('attributes/class', 'mtt-link-to-task');
                            $mention->data->append('attributes/data-target-id', $mention->getIdentifier());
                        }
                        return $mention;
                    },
                ]
            ],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new MentionExtension());
        $this->converter = new MarkdownConverter($environment);
    }

    public function convert(string $s, bool $toExternal = false)
    {
        $this->toExternal = $toExternal;
        return (string) $this->converter->convert($s);
    }
}
