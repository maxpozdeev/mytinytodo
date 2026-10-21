<?php

use PHPUnit\Framework\TestCase;

class CommonProcsTest extends TestCase
{
    public function testUrlDir()
    {
        $this->assertEquals('/', url_dir('http://www.site.com'));
        $this->assertEquals('/', url_dir('http://www.site.com/'));
        $this->assertEquals('/', url_dir('http://www.site.com/ '));
        $this->assertEquals('/', url_dir('https://www.site.com/'));
        $this->assertEquals('/', url_dir('ftp://www.site.com/'));
        $this->assertEquals('/', url_dir('http://www.site.com/file'));
        $this->assertEquals('/dir/', url_dir('http://www.site.com/dir/'));
        $this->assertEquals('/dir/', url_dir('http://www.site.com/dir/file'));
        $this->assertEquals('/dir/', url_dir('http://www.site.com/dir/file?p=v#hash'));
        $this->assertEquals('/dir/subdir/', url_dir('http://www.site.com/dir/subdir/'));
        $this->assertEquals('/dir/subdir/', url_dir('http://www.site.com/dir/subdir/?param=v&param2=v2'));
        $this->assertEquals('/', url_dir(''));
        $this->assertEquals('/', url_dir('/'));
        $this->assertEquals('/', url_dir('http://www.site.com:80'));
        $this->assertEquals('/', url_dir('http://user@www.site.com:80'));
        $this->assertEquals('/', url_dir('http://user@www.site.com:80/'));
    }

    public function testUrlDirWithDomain()
    {
        $this->assertEquals('https://localhost/',        url_dir('https://localhost', false) );
        $this->assertEquals('http://www.site.com/dir/',  url_dir('http://www.site.com/dir/file.txt?hello', false) );
        $this->assertEquals('http://www.site.com/',      url_dir('http://www.site.com/', false) );
        $this->assertEquals('http://www.site.com/',      url_dir('http://www.site.com/ ', false) );
        $this->assertEquals('http://www.site.com//',     url_dir('http://www.site.com//', false) );
        $this->assertEquals('http://www.site.com/',      url_dir('http://www.site.com', false) );

        $this->assertEquals('http//',          url_dir('http//malformed', false) );
        $this->assertEquals('/',               url_dir('malformed', false) );
        $this->assertEquals('malformed/dir/',  url_dir('malformed/dir/file', false) );
    }

    public function testUuid()
    {
        $uuid = generateUUID(); //smth like ceaf449f-a5a1-438e-8f15-7b2cb9670855

        $this->assertTrue( is_string($uuid) );
        $this->assertEquals(36, strlen($uuid));
        $this->assertEquals( 1, preg_match("/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/", $uuid) );
        $this->assertEquals('4', substr($uuid, 14, 1));
    }

    public function testPasswordHash()
    {
        $hash = "sha256:6b86b273ff34fce19d6b804eff5a3f5747ada4eaa22f1d49c01e52ddb7875b4b"; // 1

        $this->assertEquals( $hash, passwordHash('1') );
        $this->assertFalse( $hash === passwordHash('2') );

        $this->assertTrue( isPasswordEqualsToHash( '1', $hash) );
        $this->assertFalse( isPasswordEqualsToHash( '2', $hash) );
    }
}
