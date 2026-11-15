<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use LinksExtension\LinksExtParsedownConverter;
use LinksExtension\LinksExtTitleMarkupConverter;

require_once(MTTPATH. 'ext/links/markup.php');

class LinksExtMarkupTest extends TestCase
{
    private function titleHtml(string $title, bool $mid = true, bool $message = true): string
    {
        return (new LinksExtTitleMarkupConverter($mid, $message))->convert($title);
    }

    private function noteHtml(string $note, bool $mid = true, bool $message = true): string
    {
        return (new LinksExtParsedownConverter($mid, $message))->convert($note);
    }

    public function testTitleLinksMidScheme(): void
    {
        $this->assertSame(
            '<a href="mid:abc@def" target="_blank" rel="noopener">mid:abc@def</a>',
            $this->titleHtml('mid:abc@def')
        );
    }

    public function testTitleLinksMessageScheme(): void
    {
        $html = $this->titleHtml('See message:abc@def and mid:x@y, ok.');
        $this->assertStringContainsString('href="message:%3Cabc@def%3E"', $html);
        $this->assertStringContainsString('>message:abc@def</a>', $html);
        $this->assertStringContainsString('href="mid:x@y"', $html);
        $this->assertStringContainsString('</a>, ok.', $html);
    }

    public function testTitleLinksSchemeCaseInsensitive(): void
    {
        $this->assertStringContainsString('href="MID:ABC@DEF"', $this->titleHtml('MID:ABC@DEF'));
        $this->assertStringContainsString('href="Message:%3CABC@DEF%3E"', $this->titleHtml('Message:ABC@DEF'));
    }

    public function testTitleLinksSchemeInParentheses(): void
    {
        $this->assertSame(
            '(<a href="mid:abc@def" target="_blank" rel="noopener">mid:abc@def</a>)',
            $this->titleHtml('(mid:abc@def)')
        );
    }

    public function testTitleDoesNotIncludeTrailingPunctuationInLink(): void
    {
        $html = $this->titleHtml('mid:abc@def, next');
        $this->assertStringContainsString('href="mid:abc@def"', $html);
        $this->assertStringContainsString('</a>, next', $html);

        $html = $this->titleHtml('mid:abc@def.');
        $this->assertStringContainsString('href="mid:abc@def"', $html);
        $this->assertStringContainsString('</a>.', $html);
    }

    public function testTitleDoesNotLinkBracketedMessageId(): void
    {
        $html = $this->titleHtml('mid:<abc@def>');
        $this->assertStringNotContainsString('<a ', $html);
        $this->assertStringContainsString('mid:&lt;abc@def&gt;', $html);
    }

    public function testTitleDoesNotNestLinksInsideUrls(): void
    {
        $html = $this->titleHtml('http://example.com/mid:y');
        $this->assertSame(1, substr_count($html, '<a '), $html);
    }

    public function testNoteLinksPlainMidScheme(): void
    {
        $this->assertSame(
            '<p><a href="mid:abc@def" target="_blank" rel="noopener">mid:abc@def</a></p>',
            $this->noteHtml('mid:abc@def')
        );
        $this->assertStringContainsString(
            'href="message:%3Cabc@def%3E"',
            $this->noteHtml('message:abc@def')
        );
    }

    public function testNoteLinksMarkdownMidScheme(): void
    {
        $this->assertSame(
            '<p><a href="mid:abc@def" target="_blank" rel="noopener">x</a></p>',
            $this->noteHtml('[x](mid:abc@def)')
        );
    }

    public function testNoteKeepsLineBreaks(): void
    {
        $this->assertSame(
            "<p>line1<br />\nline2</p>",
            $this->noteHtml("line1\nline2")
        );
    }

    public function testNoteDoesNotIncludeTrailingPunctuationInLink(): void
    {
        $this->assertSame(
            '<p><a href="mid:abc@def" target="_blank" rel="noopener">mid:abc@def</a>, ok</p>',
            $this->noteHtml('mid:abc@def, ok')
        );
        $this->assertSame(
            '<p>see (<a href="message:%3Cabc@def%3E" target="_blank" rel="noopener">'.
                'message:abc@def</a>)</p>',
            $this->noteHtml('see (message:abc@def)')
        );
    }

    public function testNoteLinksPercentEncodedMessageId(): void
    {
        $html = $this->noteHtml('mid:%3Cabc@def%3E');
        $this->assertStringContainsString('href="mid:%3Cabc@def%3E"', $html);
    }

    public function testNoteLinksRfcAtextCharacters(): void
    {
        $html = $this->noteHtml('mid:a!b*c@example');
        $this->assertStringContainsString('href="mid:a!b*c@example"', $html);
    }

    public function testNoteLinksContentIdPart(): void
    {
        $html = $this->noteHtml('mid:a@b/c@d');
        $this->assertStringContainsString('href="mid:a@b/c@d"', $html);
    }

    public function testNoteDoesNotLinkBracketedMessageId(): void
    {
        $this->assertStringNotContainsString('href="mid:', $this->noteHtml('mid:<abc@def>'));
    }

    public function testTitleLinksPercentEncodedMessageId(): void
    {
        $html = $this->titleHtml('mid:%3Cabc@def%3E');
        $this->assertStringContainsString('href="mid:%3Cabc@def%3E"', $html);
    }

    public function testTitleLinksRfcAtextCharacters(): void
    {
        $html = $this->titleHtml('mid:a!b*c@example');
        $this->assertStringContainsString('href="mid:a!b*c@example"', $html);
    }

    public function testTitleLinksContentIdPart(): void
    {
        $html = $this->titleHtml('mid:a@b/c@d');
        $this->assertStringContainsString('href="mid:a@b/c@d"', $html);
    }

    public function testTitleDoesNotLinkApostropheSuffix(): void
    {
        $html = $this->titleHtml("mid:abc@def's");
        $this->assertStringContainsString('href="mid:abc@def"', $html);
        $this->assertStringContainsString('</a>&#039;s', $html);
    }
    public function testNoteEncodesMessageIdInAppleUrl(): void
    {
        $html = $this->noteHtml('message:A29C9C87-F01F-4CC9-BC17-64A1F58763E7@heli.li');
        $this->assertStringContainsString(
            'href="message:%3CA29C9C87-F01F-4CC9-BC17-64A1F58763E7@heli.li%3E"',
            $html
        );
        $this->assertStringContainsString(
            '>message:A29C9C87-F01F-4CC9-BC17-64A1F58763E7@heli.li</a>',
            $html
        );
    }

    public function testNoteDoesNotDoubleEncodeMessageId(): void
    {
        $html = $this->noteHtml('message:%3Cabc@def%3E');
        $this->assertStringContainsString('href="message:%3Cabc@def%3E"', $html);
        $this->assertStringNotContainsString('%253C', $html);

        $html = $this->noteHtml('message:%3cabc@def%3e');
        $this->assertStringContainsString('href="message:%3Cabc@def%3E"', $html);
        $this->assertStringNotContainsString('%253C', $html);
    }

    public function testNoteLinksMessageIdInAngleBrackets(): void
    {
        $this->assertSame(
            '<p><a href="message:%3Cabc@def%3E" target="_blank" rel="noopener">'.
                'message:&lt;abc@def&gt;</a></p>',
            $this->noteHtml('message:<abc@def>')
        );
    }

    public function testNoteMarkdownMessageLinkIsHandledByParsedown(): void
    {
        $this->assertSame(
            '<p><a href="message:abc@def" target="_blank" rel="noopener">x</a></p>',
            $this->noteHtml('[x](message:abc@def)')
        );
    }

    public function testNoteDoesNotAutoLinkInsideLinkText(): void
    {
        $this->assertSame(
            '<p><a href="http://x" target="_blank" rel="noopener">mid:abc@def</a></p>',
            $this->noteHtml('[mid:abc@def](http://x)')
        );

        $this->assertSame(
            '<p><a href="http://x" target="_blank" rel="noopener">see mid:abc@def</a></p>',
            $this->noteHtml('[see mid:abc@def](http://x)')
        );
    }

    public function testTitleEncodesAppleMessageId(): void
    {
        $this->assertSame(
            '<a href="message:%3Cabc@def%3E" target="_blank" rel="noopener">'.
                'message:abc@def</a>',
            $this->titleHtml('message:abc@def')
        );
    }

    public function testTitleLinksMessageIdInAngleBrackets(): void
    {
        $this->assertSame(
            '<a href="message:%3Cabc@def%3E" target="_blank" rel="noopener">'.
                'message:&lt;abc@def&gt;</a>',
            $this->titleHtml('message:<abc@def>')
        );
    }

    public function testTitleDoesNotWrapMidScheme(): void
    {
        $html = $this->titleHtml('mid:abc@def');
        $this->assertStringContainsString('href="mid:abc@def"', $html);
        $this->assertStringNotContainsString('%3C', $html);
    }

    public function testOnlyMidSchemeEnabled(): void
    {
        $html = $this->noteHtml('mid:abc@def and message:abc@def', true, false);
        $this->assertStringContainsString('href="mid:abc@def"', $html);
        $this->assertStringNotContainsString('href="message:', $html);

        $html = $this->noteHtml('[x](message:abc@def)', true, false);
        $this->assertStringNotContainsString('href="message:', $html);

        $html = $this->titleHtml('mid:abc@def and message:abc@def', true, false);
        $this->assertStringContainsString('href="mid:abc@def"', $html);
        $this->assertStringNotContainsString('href="message:', $html);
    }

    public function testOnlyMessageSchemeEnabled(): void
    {
        $html = $this->noteHtml('mid:abc@def and message:abc@def', false, true);
        $this->assertStringNotContainsString('href="mid:', $html);
        $this->assertStringContainsString('href="message:%3Cabc@def%3E"', $html);

        $html = $this->noteHtml('[x](mid:abc@def)', false, true);
        $this->assertStringNotContainsString('href="mid:', $html);

        $html = $this->noteHtml('[x](message:abc@def)', false, true);
        $this->assertStringContainsString('href="message:abc@def"', $html);

        $html = $this->titleHtml('mid:abc@def and message:abc@def', false, true);
        $this->assertStringNotContainsString('href="mid:', $html);
        $this->assertStringContainsString('href="message:%3Cabc@def%3E"', $html);
    }

    public function testNoSchemeEnabled(): void
    {
        $htmls = [
            $this->noteHtml('mid:abc@def message:abc@def', false, false),
            $this->titleHtml('mid:abc@def message:abc@def', false, false),
        ];
        foreach ($htmls as $html) {
            $this->assertStringNotContainsString('href="mid:', $html);
            $this->assertStringNotContainsString('href="message:', $html);
        }
    }
}
