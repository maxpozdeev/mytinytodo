<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class MarkupTest extends TestCase
{
    public function testTitleEscapesHtml(): void
    {
        $html = titleMarkup('<script>alert(1)</script>');
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testTitleDoesNotBreakAttributes(): void
    {
        $html = titleMarkup('http://x/" onmouseover="alert(1)');
        $this->assertStringNotContainsString('onmouseover="alert(1)"', $html);
        $this->assertStringContainsString('&quot; onmouseover=&quot;', $html);
    }

    public function testTitleDoesNotLinkUnsafeSchemes(): void
    {
        $payloads = [
            'javascript://%0Aalert%281%29',
            'javascript://%0Aalert%281%29//',
            'vbscript://msgbox%281%29',
            'data://text/html,abc',
            'file:///etc/passwd',
            'see javascript://alert%281%29',
        ];
        foreach ($payloads as $payload) {
            $html = titleMarkup($payload);
            $this->assertStringNotContainsString('<a ', $html, $payload);
            $this->assertStringNotContainsString('href=', $html, $payload);
        }
    }

    public function testTitleLinksSafeUrls(): void
    {
        $html = titleMarkup('see http://example.com/ and https://example.org/x and ftp://example.net/f and www.example.org');
        $this->assertStringContainsString('href="http://example.com/"', $html);
        $this->assertStringContainsString('href="https://example.org/x"', $html);
        $this->assertStringContainsString('href="ftp://example.net/f"', $html);
        $this->assertStringContainsString('href="http://www.example.org"', $html);
        $this->assertStringContainsString('rel="noopener"', $html);
    }

    public function testNoteEscapesRawHtml(): void
    {
        $html = markdownToHtml('<script>alert(1)</script>');
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);

        $html = markdownToHtml('<img src=x onerror=alert(1)>');
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }

    public function testNoteDoesNotBreakAttributes(): void
    {
        $html = markdownToHtml('[x](<https://a/"onmouseover=alert(1)>)');
        $this->assertStringNotContainsString(' onmouseover="', $html);
        $this->assertStringContainsString('&quot;onmouseover=alert(1)', $html);
    }

    public function testNoteFiltersUnsafeSchemes(): void
    {
        $payloads = [
            '[x](javascript:alert(1))',
            '[x](JaVaScRiPt:alert(1))',
            '[x](data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==)',
            '![x](javascript:alert(1))',
            '![x](data:image/svg+xml;base64,PHN2ZyBvbmxvYWQ9YWxlcnQoMSk+)',
            "[x]: javascript:alert(1)\n\n[x]",
        ];
        foreach ($payloads as $payload) {
            $html = strtolower(markdownToHtml($payload));
            $this->assertStringNotContainsString('href="javascript:', $html, $payload);
            $this->assertStringNotContainsString('src="javascript:', $html, $payload);
            $this->assertStringNotContainsString('href="data:', $html, $payload);
            $this->assertStringNotContainsString('src="data:image/svg', $html, $payload);
        }
    }

    public function testNoteLinksSafeUrls(): void
    {
        $html = markdownToHtml('[x](https://example.com/) and ![y](data:image/png;base64,AAAA)');
        $this->assertStringContainsString('href="https://example.com/"', $html);
        $this->assertStringContainsString('rel="noopener"', $html);
        $this->assertStringContainsString('src="data:image/png;base64,AAAA"', $html);
    }

    public function testV1EscapesHtml(): void
    {
        $html = mttMarkup_v1('<b>bold</b><img src=x onerror=alert(1)>');
        $this->assertStringContainsString('<b>bold</b>', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }

    public function testV1HandlesNestedTags(): void
    {
        $cases = [
            // properly nested tags are restored
            '<b><i>bold and italic</i></b>' => '<b><i>bold and italic</i></b>',
            // crossed tags are restored as is (invalid html, but only allowed tags)
            '<b><i>bold and italic</b></i>' => '<b><i>bold and italic</b></i>',
            // unclosed inner tag stays escaped text
            '<b><i>bold and italic</b>' => '<b>&lt;i&gt;bold and italic</b>',
            // completely unclosed tag stays escaped text
            '<b>unclosed' => '&lt;b&gt;unclosed',
            // nested tags of the same name are not restored recursively
            '<b>a<b>c</b>b</b>' => '<b>a&lt;b&gt;c</b>b&lt;/b&gt;',
        ];
        foreach ($cases as $input => $expected) {
            $this->assertSame($expected, mttMarkup_v1($input), $input);
        }
    }

    public function testV1DoesNotLinkUnsafeSchemes(): void
    {
        $html = mttMarkup_v1('javascript://%0Aalert%281%29');
        $this->assertStringNotContainsString('<a ', $html);
        $this->assertStringNotContainsString('href=', $html);
    }

    public function testV1LinksSafeUrls(): void
    {
        $html = mttMarkup_v1('see http://example.com/');
        $this->assertStringContainsString('href="http://example.com/"', $html);
        $this->assertStringContainsString('rel="noopener"', $html);
    }
}
