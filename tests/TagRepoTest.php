<?php

use PHPUnit\Framework\TestCase;

if (file_exists(MTTPATH. 'includes/vendor/autoload.php')) {
    require_once(MTTPATH. 'includes/vendor/autoload.php');
}
require_once(MTTPATH. 'includes/class.dbsetup.php');
require_once(MTTPATH. 'includes/class.db.sqlite.php');

/**
 * Tests for tag comparison and search rules.
 */
class TagRepoTest extends TestCase
{
    private const USER_ID = 1;

    private string $dbFile;
    private AbstractDatabase $db;
    private TagRepo $repo;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is required');
        }

        $this->dbFile = sys_get_temp_dir(). '/mtt-tags-' . uniqid('', true). '.sqlite';

        $db = new SqliteDatabase(null);
        $db->connect(['filename' => $this->dbFile]);
        DBConnection::init($db);
        DBConnection::setTablePrefix('');
        MTTDBSetup::createSqliteTables($db);

        $this->db = $db;
        $this->repo = new TagRepo($db);
    }

    protected function tearDown(): void
    {
        @unlink($this->dbFile);
    }

    public function testSameNameReturnsSameTagId(): void
    {
        $a = $this->repo->getOrCreateTag('Work', self::USER_ID);
        $b = $this->repo->getOrCreateTag('Work', self::USER_ID);
        $this->assertSame($a->id, $b->id);
    }

    public function testCaseAndDiacriticsAreSignificant(): void
    {
        $work = $this->repo->getOrCreateTag('Work', self::USER_ID);
        $lower = $this->repo->getOrCreateTag('work', self::USER_ID);
        $cafe = $this->repo->getOrCreateTag('cafe', self::USER_ID);
        $accent = $this->repo->getOrCreateTag('Café', self::USER_ID);
        $this->assertNotSame($work->id, $lower->id);
        $this->assertNotSame($cafe->id, $accent->id);
    }

    public function testGetTagIdsByNameIsExact(): void
    {
        $id = $this->repo->getOrCreateTag('Tag', self::USER_ID)->id;
        $this->repo->getOrCreateTag('tag', self::USER_ID);
        $this->repo->getOrCreateTag('TAG', self::USER_ID);
        $this->assertSame([$id], $this->repo->getTagIdsByName('Tag'));
        $this->assertSame([], $this->repo->getTagIdsByName('TaG'));
    }

    public function testSameNameForDifferentUsers(): void
    {
        $a = $this->repo->getOrCreateTag('work', 1);
        $b = $this->repo->getOrCreateTag('work', 2);
        $this->assertNotSame($a->id, $b->id);
        $ids = $this->repo->getTagIdsByName('work');
        sort($ids);
        $expected = [$a->id, $b->id];
        sort($expected);
        $this->assertSame($expected, $ids);
    }

    public function testSearchIsCaseAndDiacriticInsensitive(): void
    {
        $ids = [];
        foreach (['Café', 'cafe', 'CAFÉ', 'tea'] as $name) {
            $ids[] = $this->repo->getOrCreateTag($name, 1)->id;
        }
        $found = $this->repo->getTagIdsBySearchName('cafe');
        sort($found);
        $expected = [$ids[0], $ids[1], $ids[2]];
        sort($expected);
        $this->assertSame($expected, $found);
    }

    public function testUniqueIndexPreventsExactDuplicates(): void
    {
        $this->repo->getOrCreateTag('Tag', self::USER_ID);
        $this->expectException(PDOException::class);
        $this->db->ex(
            'INSERT INTO tags (user_id,name) VALUES (?,?)',
            [self::USER_ID, 'Tag']
        );
    }
    public function testCloudAndSuggestions(): void
    {
        $this->insertList(50, 1, 0);
        $alpha = $this->repo->getOrCreateTag('Alpha', 1);
        $beta = $this->repo->getOrCreateTag('Beta', 1);
        $this->db->ex(
            'INSERT INTO tag2task (tag_id, task_id, list_id) VALUES (?,?,?)',
            [$alpha->id, 100, 50]
        );
        $this->db->ex(
            'INSERT INTO tag2task (tag_id, task_id, list_id) VALUES (?,?,?)',
            [$beta->id, 101, 50]
        );
        $cloud = $this->repo->getCloud(50, 1);
        $this->assertSame(
            ['Alpha', 'Beta'],
            array_column($cloud, 'name')
        );
        $this->assertSame(
            ['Alpha'],
            $this->repo->getSuggestions(50, 'al')
        );
        $this->assertCount(2, $this->repo->getCloud(-1, 1));
    }

    public function testCloudForAnotherUserPublishedOnly(): void
    {
        $this->insertList(60, 2, 1);
        $this->insertList(61, 2, 0);

        $alpha = $this->repo->getOrCreateTag('Alpha', 2);
        $beta = $this->repo->getOrCreateTag('Beta', 2);
        $this->db->ex(
            'INSERT INTO tag2task (tag_id, task_id, list_id) VALUES (?,?,?)',
            [$alpha->id, 200, 60]
        );
        $this->db->ex(
            'INSERT INTO tag2task (tag_id, task_id, list_id) VALUES (?,?,?)',
            [$beta->id, 201, 61]
        );

        $this->assertSame(
            ['Alpha'],
            array_column($this->repo->getCloud(-1, 2, true), 'name')
        );
        $this->assertSame(
            ['Alpha', 'Beta'],
            array_column($this->repo->getCloud(-1, 2), 'name')
        );
        $this->assertSame([], $this->repo->getCloud(-1, 1));
    }

    public function testCloudDoesNotIncludeForeignTags(): void
    {
        $this->insertList(70, 2, 1);

        $own = $this->repo->getOrCreateTag('Own', 1);
        $foreign = $this->repo->getOrCreateTag('Foreign', 2);
        $this->db->ex(
            'INSERT INTO tag2task (tag_id, task_id, list_id) VALUES (?,?,?)',
            [$own->id, 300, 70]
        );
        $this->db->ex(
            'INSERT INTO tag2task (tag_id, task_id, list_id) VALUES (?,?,?)',
            [$foreign->id, 301, 70]
        );

        $this->assertSame(
            ['Foreign'],
            array_column($this->repo->getCloud(-1, 2, true), 'name')
        );
    }

    private function insertList(int $id, int $userId, int $published): void
    {
        $this->db->ex(
            'INSERT INTO lists (id,uuid,name,user_id,published) VALUES (?,?,?,?,?)',
            [$id, 'uuid-'. $id, 'List '. $id, $userId, $published]
        );
    }

}
