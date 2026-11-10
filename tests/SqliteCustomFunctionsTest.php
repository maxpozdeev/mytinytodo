<?php

use PHPUnit\Framework\TestCase;

if (file_exists(MTTPATH. 'includes/vendor/autoload.php')) {
    require_once(MTTPATH. 'includes/vendor/autoload.php');
}
require_once(MTTPATH. 'includes/class.dbconnection.php');
require_once(MTTPATH. 'includes/class.db.sqlite.php');

/**
 * Tests for custom sqlite functions and collations registered in SqliteDatabase::connect():
 *  - utf8_lower()
 *  - utf8_normalized_lower()
 *  - UTF8CI collation
 *  - UTF8CI_NORMALIZED collation
 */
class SqliteCustomFunctionsTest extends TestCase
{
    private string $dbFile;
    private ?SqliteDatabase $db = null;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is required');
        }

        $this->dbFile = sys_get_temp_dir(). '/mtt-sqlite-funcs-'. uniqid('', true). '.sqlite';

        $db = new SqliteDatabase(null);
        $db->connect(['filename' => $this->dbFile]);
        $this->db = $db;
    }

    protected function tearDown(): void
    {
        # close connection before removing the database file
        $this->db = null;
        @unlink($this->dbFile);
    }


    /* utf8_lower() */

    public function testUtf8LowerIsRegistered(): void
    {
        $this->assertSame('привет мир', $this->db->sq("SELECT utf8_lower('ПрИвЕт МиР')"));
        $this->assertSame('abc123', $this->db->sq("SELECT utf8_lower('AbC123')"));
        $this->assertSame('äöüß', $this->db->sq("SELECT utf8_lower('ÄÖÜß')"));
    }

    public function testUtf8LowerReturnsEmptyStringForNull(): void
    {
        $this->assertSame('', $this->db->sq("SELECT utf8_lower(NULL)"));
    }


    /* utf8_normalized_lower() */

    public function testUtf8NormalizedLowerIsRegistered(): void
    {
        # diacritical marks are removed
        $this->assertSame('creme brulee', $this->db->sq("SELECT utf8_normalized_lower('Crème Brûlée')"));
        # decomposed characters (e + combining acute accent) are folded too
        $this->assertSame('e', $this->db->sq("SELECT utf8_normalized_lower('e\u{0301}')"));
        # special ligatures are replaced
        $this->assertSame('aeol', $this->db->sq("SELECT utf8_normalized_lower('ÆØŁ')"));
    }

    public function testUtf8NormalizedLowerReturnsEmptyStringForNull(): void
    {
        $this->assertSame('', $this->db->sq("SELECT utf8_normalized_lower(NULL)"));
    }


    /* UTF8CI collation */

    public function testUtf8ciCollationIsUsedForDefaultOrder(): void
    {
        $this->assertSame('UTF8CI', $this->db->orderCollation);

        $this->createNamesTable();
        # mixed case latin, umlauts and cyrillic words
        foreach (['Banana', 'apple', 'äpfel', 'Öl', 'Яблоко', 'арбуз'] as $name) {
            $this->db->ex("INSERT INTO names (name) VALUES (?)", [$name]);
        }

        # case-insensitive order: latin words first, then umlauts, then cyrillic.
        # A binary (case-sensitive) order would be: Banana, apple, Öl, äpfel, арбуз, Яблоко
        $this->assertSame(
            ['apple', 'Banana', 'äpfel', 'Öl', 'арбуз', 'Яблоко'],
            $this->sortedNames($this->db->orderCollation)
        );
    }

    public function testUtf8ciCollationIsCaseInsensitiveInComparisons(): void
    {
        # case-insensitive: 'banana' < 'apple' is false, binary comparison would return 1
        $this->assertEquals(0, $this->db->sq("SELECT 'Banana' < 'apple' COLLATE UTF8CI"));
        $this->assertEquals(1, $this->db->sq("SELECT 'apple' < 'Banana' COLLATE UTF8CI"));

        # cyrillic: 'яблоко' < 'арбуз' is false, binary comparison would return 1
        $this->assertEquals(0, $this->db->sq("SELECT 'Яблоко' < 'арбуз' COLLATE UTF8CI"));
        $this->assertEquals(1, $this->db->sq("SELECT 'арбуз' < 'Яблоко' COLLATE UTF8CI"));

        # umlauts: 'öl' < 'äpfel' is false, binary comparison would return 1
        $this->assertEquals(0, $this->db->sq("SELECT 'Öl' < 'äpfel' COLLATE UTF8CI"));
        $this->assertEquals(1, $this->db->sq("SELECT 'äpfel' < 'Öl' COLLATE UTF8CI"));
    }

    public function testUtf8ciCollationDoesNotMakeStringsEqual(): void
    {
        # the collation only defines the ordering. When the lowercased strings are equal,
        # it falls back to the comparison of the original strings, so '=' is still case-sensitive
        $this->assertEquals(0, $this->db->sq("SELECT 'Apple' = 'apple' COLLATE UTF8CI"));
        $this->assertEquals(0, $this->db->sq("SELECT 'Яблоко' = 'яблоко' COLLATE UTF8CI"));
        $this->assertEquals(0, $this->db->sq("SELECT 'Öl' = 'öl' COLLATE UTF8CI"));

        # only identical strings are equal
        $this->assertEquals(1, $this->db->sq("SELECT 'Apple' = 'Apple' COLLATE UTF8CI"));
        $this->assertEquals(1, $this->db->sq("SELECT 'Яблоко' = 'Яблоко' COLLATE UTF8CI"));
        $this->assertEquals(1, $this->db->sq("SELECT 'Öl' = 'Öl' COLLATE UTF8CI"));
    }


    /* UTF8CI_NORMALIZED collation */

    public function testUtf8ciNormalizedCollationIsUsedForNormalizedOrder(): void
    {
        $db = new SqliteDatabase(['useNormalizedOrder' => true]);
        $db->connect(['filename' => $this->dbFile]);
        $this->db = $db;

        $this->assertSame('UTF8CI_NORMALIZED', $db->orderCollation);

        $this->createNamesTable();
        foreach (['Zebra', 'Äpfel', 'apple'] as $name) {
            $db->ex("INSERT INTO names (name) VALUES (?)", [$name]);
        }

        # 'Äpfel' is normalized to 'apfel', which goes before 'apple' ('f' < 'p')
        $this->assertSame(['Äpfel', 'apple', 'Zebra'], $this->sortedNames($db->orderCollation));
    }

    public function testUtf8ciNormalizedCollationIgnoresDiacriticalMarksInComparisons(): void
    {
        # normalized: 'apfel' < 'apple', binary comparison of 'Äpfel' and 'apple' would return 0
        $this->assertEquals(1, $this->db->sq("SELECT 'Äpfel' < 'apple' COLLATE UTF8CI_NORMALIZED"));
        $this->assertEquals(0, $this->db->sq("SELECT 'apple' < 'Äpfel' COLLATE UTF8CI_NORMALIZED"));
    }

    public function testUtf8ciNormalizedCollationDoesNotMakeStringsEqual(): void
    {
        # the collation only defines the ordering. When the normalized lowercased strings are equal,
        # it falls back to the comparison of the original strings, so '=' still requires identical strings
        $this->assertEquals(0, $this->db->sq("SELECT 'café' = 'cafe' COLLATE UTF8CI_NORMALIZED"));
        $this->assertEquals(0, $this->db->sq("SELECT 'café' = 'CAFE' COLLATE UTF8CI_NORMALIZED"));

        # the same string in a different unicode form (precomposed vs decomposed) is not equal either
        $this->assertEquals(0, $this->db->sq("SELECT 'café' = 'cafe\u{0301}' COLLATE UTF8CI_NORMALIZED"));

        # only identical strings are equal
        $this->assertEquals(1, $this->db->sq("SELECT 'café' = 'café' COLLATE UTF8CI_NORMALIZED"));
        $this->assertEquals(1, $this->db->sq("SELECT 'cafe' = 'cafe' COLLATE UTF8CI_NORMALIZED"));
    }


    /* UTF8CI_ORIGINAL collation (used for equality comparisons) */

    public function testUtf8ciOriginalCollationIsUsedForEquality(): void
    {
        $this->assertSame('UTF8CI_ORIGINAL', $this->db->equalCollation);

        # case-insensitive equality
        $this->assertEquals(1, $this->db->sq("SELECT 'Apple' = 'apple' COLLATE UTF8CI_ORIGINAL"));
        $this->assertEquals(1, $this->db->sq("SELECT 'apple' = 'APPLE' COLLATE UTF8CI_ORIGINAL"));
        $this->assertEquals(1, $this->db->sq("SELECT 'Яблоко' = 'яблоко' COLLATE UTF8CI_ORIGINAL"));
        $this->assertEquals(1, $this->db->sq("SELECT 'Öl' = 'öl' COLLATE UTF8CI_ORIGINAL"));

        # different strings are still not equal
        $this->assertEquals(0, $this->db->sq("SELECT 'apple' = 'banana' COLLATE UTF8CI_ORIGINAL"));
        $this->assertEquals(0, $this->db->sq("SELECT 'Яблоко' = 'арбуз' COLLATE UTF8CI_ORIGINAL"));
    }

    public function testUtf8ciOriginalCollationDoesNotNormalizeDiacriticalMarks(): void
    {
        # unlike UTF8CI_NORMALIZED it does not remove diacritical marks, only the case is ignored
        $this->assertEquals(0, $this->db->sq("SELECT 'café' = 'cafe' COLLATE UTF8CI_ORIGINAL"));
        $this->assertEquals(0, $this->db->sq("SELECT 'Äpfel' = 'Apfel' COLLATE UTF8CI_ORIGINAL"));
        $this->assertEquals(1, $this->db->sq("SELECT 'café' = 'CAFÉ' COLLATE UTF8CI_ORIGINAL"));
    }

    public function testUtf8ciOriginalCollationIsCaseInsensitiveForOrderAndComparison(): void
    {
        $this->createNamesTable();
        # mixed case latin, umlauts and cyrillic words
        foreach (['Banana', 'apple', 'äpfel', 'Öl', 'Яблоко', 'арбуз'] as $name) {
            $this->db->ex("INSERT INTO names (name) VALUES (?)", [$name]);
        }

        $this->assertSame(
            ['apple', 'Banana', 'äpfel', 'Öl', 'арбуз', 'Яблоко'],
            $this->sortedNames('UTF8CI_ORIGINAL')
        );

        # the collation compares only lowercased strings, so strings that differ only in case
        # are "equal" for < and > as well (the collation returns 0 for both directions)
        $this->assertEquals(0, $this->db->sq("SELECT 'Apple' < 'apple' COLLATE UTF8CI_ORIGINAL"));
        $this->assertEquals(0, $this->db->sq("SELECT 'apple' < 'Apple' COLLATE UTF8CI_ORIGINAL"));
        $this->assertEquals(0, $this->db->sq("SELECT 'Яблоко' < 'яблоко' COLLATE UTF8CI_ORIGINAL"));
    }

    public function testEqualCollationCanBeUsedInWhereClause(): void
    {
        $this->createNamesTable();
        foreach (['Ünïcode', 'Crème', 'apple'] as $name) {
            $this->db->ex("INSERT INTO names (name) VALUES (?)", [$name]);
        }

        $collate = "COLLATE {$this->db->equalCollation}";

        $this->assertSame(
            'Ünïcode',
            $this->db->sq("SELECT name FROM names WHERE name=? $collate", ['ÜNÏCODE'])
        );
        $this->assertSame(
            'Crème',
            $this->db->sq("SELECT name FROM names WHERE name=? $collate", ['CRÈME'])
        );
        # diacritical marks are not ignored, so the normalized variant is not found
        $this->assertNull(
            $this->db->sq("SELECT name FROM names WHERE name=? $collate", ['CREME'])
        );
        $this->assertNull(
            $this->db->sq("SELECT name FROM names WHERE name=? $collate", ['banana'])
        );
    }


    /* Usage of custom functions by SqliteDatabase helpers */

    public function testLikeAndCiEqualsUseCustomFunctions(): void
    {
        $this->createNamesTable();
        foreach (['Crème', 'Ünïcode'] as $name) {
            $this->db->ex("INSERT INTO names (name) VALUES (?)", [$name]);
        }

        $this->assertSame(
            'Ünïcode',
            $this->db->sq("SELECT name FROM names WHERE ". $this->db->like('name', '%%%s%%', 'UNICODE'))
        );
        $this->assertSame(
            'Crème',
            $this->db->sq("SELECT name FROM names WHERE ". $this->db->ciEquals('name', 'CRÈME'))
        );
        $this->assertNull(
            $this->db->sq("SELECT name FROM names WHERE ". $this->db->like('name', '%%%s%%', 'unknown'))
        );
    }

    public function testLikeUsesPlainUtf8LowerWhenNormalizedSearchIsDisabled(): void
    {
        $db = new SqliteDatabase(['useNormalizedSearch' => false]);
        $db->connect(['filename' => $this->dbFile]);
        $this->db = $db;

        $this->createNamesTable();
        $db->ex("INSERT INTO names (name) VALUES (?)", ['Ünïcode']);

        # diacritical marks are not removed, so 'UNICODE' does not match
        $this->assertNull(
            $db->sq("SELECT name FROM names WHERE ". $db->like('name', '%%%s%%', 'UNICODE'))
        );
        # but the same diacritical marks in a different case do match
        $this->assertSame(
            'Ünïcode',
            $db->sq("SELECT name FROM names WHERE ". $db->like('name', '%%%s%%', 'ÜNÏCODE'))
        );
    }


    /* Helpers */

    private function createNamesTable(): void
    {
        $this->db->ex("CREATE TABLE names (name TEXT)");
    }

    /** @return string[] */
    private function sortedNames(string $collation): array
    {
        $names = [];
        $q = $this->db->dq("SELECT name FROM names ORDER BY name COLLATE $collation");
        while ($r = $q->fetchRow()) {
            $names[] = $r[0];
        }
        return $names;
    }
}
