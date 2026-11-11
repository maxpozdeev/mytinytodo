<?php

use PHPUnit\Framework\TestCase;

if (file_exists(MTTPATH. 'includes/vendor/autoload.php')) {
    require_once(MTTPATH. 'includes/vendor/autoload.php');
}
require_once(MTTPATH. 'includes/class.dbsetup.php');
require_once(MTTPATH. 'includes/class.db.sqlite.php');

/**
 * Tests for TaskRepo::changeTaskPosition() and TaskRepo::renumberTaskOrder().
 */
class TaskRepoPositionTest extends TestCase
{
    private const USER_ID = 1;

    private string $dbFile;
    private AbstractDatabase $db;
    private TaskRepo $repo;
    private ListRepo $listRepo;
    private int $listId;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is required');
        }

        $this->dbFile = sys_get_temp_dir(). '/mtt-position-'. uniqid('', true). '.sqlite';

        $db = new SqliteDatabase(null);
        $db->connect(['filename' => $this->dbFile]);
        DBConnection::init($db);
        DBConnection::setTablePrefix('');
        MTTDBSetup::createSqliteTables($db);

        $this->db = $db;
        $this->repo = new TaskRepo($db);
        $this->listRepo = new ListRepo($db);

        # create the list with the application method, the same way as ListsController::createList() does
        $listId = $this->listRepo->createList('Test list', self::USER_ID);
        $this->assertIsInt($listId);
        $this->listId = $listId;
    }

    protected function tearDown(): void
    {
        @unlink($this->dbFile);
    }


    /* Helpers */

    private function addTask(int $ow, int $compl = 0, ?int $listId = null): Task
    {
        $task = Task::create('Task ow='. $ow. ' '. uniqid(), $listId ?? $this->listId);
        $task->isCompleted = ($compl != 0);
        $task->tagNames = [];

        $list = $this->listRepo->findRealListById($listId ?? $this->listId);

        $this->repo->saveTask($task, $list);

        # saveTask() always appends a task to the end (10 + MAX(ow)), so the weight is
        # overridden here to reproduce the exact order required by the test scenario
        if ($task->ow != $ow) {
            $this->setWeight((int)$task->id, $ow);
            $task->ow = $ow;
        }
        return $task;
    }

    private function setWeight(int $taskId, int $ow): void
    {
        $this->db->ex("UPDATE todolist SET ow=? WHERE id=?", [$ow, $taskId]);
    }

    /** Creates an additional list and returns its id */
    private function addList(string $name = 'Another list'): int
    {
        $listId = $this->listRepo->createList($name, self::USER_ID);
        $this->assertIsInt($listId);
        return $listId;
    }

    /** @return int[] ids of tasks in manual order (low-level check of stored state) */
    private function ids(int $listId = 0, int $compl = 0): array
    {
        $listId = $listId ?: $this->listId;
        $ids = [];
        $q = $this->db->dq(
            "SELECT id FROM todolist WHERE list_id=? AND compl=? ORDER BY ow ASC, id ASC",
            [$listId, $compl]
        );
        while ($r = $q->fetchAssoc()) {
            $ids[] = (int)$r['id'];
        }
        return $ids;
    }

    private function owOf(int $id): int
    {
        return (int)$this->db->sq("SELECT ow FROM todolist WHERE id=?", [$id]);
    }

    /** @return array<int,int> id => ow map (low-level check of stored state) */
    private function weights(int $listId = 0, int $compl = 0): array
    {
        $listId = $listId ?: $this->listId;
        $a = [];
        $q = $this->db->dq(
            "SELECT id,ow FROM todolist WHERE list_id=? AND compl=? ORDER BY ow ASC, id ASC",
            [$listId, $compl]
        );
        while ($r = $q->fetchAssoc()) {
            $a[(int)$r['id']] = (int)$r['ow'];
        }
        return $a;
    }


    /* changeTaskPosition() */

    public function testMoveAfterAnchorWithGap(): void
    {
        $t1 = $this->addTask(10);
        $t2 = $this->addTask(20);
        $t3 = $this->addTask(30);

        $this->assertTrue($this->repo->changeTaskPosition($t3, $t1->id, true));

        $this->assertSame(11, $this->owOf($t3->id));
        $this->assertSame([$t1->id, $t3->id, $t2->id], $this->ids());
        # entity is updated too
        $this->assertSame(11, $t3->ow);
        $this->assertSame(20, $this->owOf($t2->id));
    }

    public function testMoveBeforeAnchorWithGap(): void
    {
        $t1 = $this->addTask(10);
        $t2 = $this->addTask(20);
        $t3 = $this->addTask(30);

        $this->assertTrue($this->repo->changeTaskPosition($t3, $t2->id, false));

        $this->assertSame(19, $this->owOf($t3->id));
        $this->assertSame([$t1->id, $t3->id, $t2->id], $this->ids());
    }

    public function testMoveAfterAnchorWhenWeightsAreDense(): void
    {
        $t1 = $this->addTask(10);
        $t2 = $this->addTask(11);
        $t3 = $this->addTask(12);

        $this->assertTrue($this->repo->changeTaskPosition($t3, $t1->id, true));

        $this->assertSame([$t1->id, $t3->id, $t2->id], $this->ids());
        $this->assertSame(11, $this->owOf($t3->id));
        # the following task has been shifted
        $this->assertSame(12, $this->owOf($t2->id));
    }

    public function testMoveBeforeFirstAnchorWithoutGap(): void
    {
        $t1 = $this->addTask(1);
        $t2 = $this->addTask(2);

        $this->assertTrue($this->repo->changeTaskPosition($t2, $t1->id, false));

        $this->assertSame([$t2->id, $t1->id], $this->ids());
        $this->assertSame(1, $this->owOf($t2->id));
        $this->assertSame(2, $this->owOf($t1->id));
    }

    public function testMoveToBottom(): void
    {
        $t1 = $this->addTask(10);
        $t2 = $this->addTask(20);
        $t3 = $this->addTask(30);

        $this->assertTrue($this->repo->changeTaskPosition($t1, $t3->id, true));

        $this->assertSame([$t2->id, $t3->id, $t1->id], $this->ids());
        $this->assertSame(31, $this->owOf($t1->id));
    }

    public function testMoveIsIdempotent(): void
    {
        $t1 = $this->addTask(10);
        $t2 = $this->addTask(20);
        $t3 = $this->addTask(30);

        $this->assertTrue($this->repo->changeTaskPosition($t3, $t1->id, true));
        $weights = $this->weights();

        $this->assertTrue($this->repo->changeTaskPosition($t3, $t1->id, true));

        $this->assertSame($weights, $this->weights());
        $this->assertSame([$t1->id, $t3->id, $t2->id], $this->ids());
    }

    public function testAnchorInAnotherListReturnsFalse(): void
    {
        $otherListId = $this->addList();
        $t1 = $this->addTask(10);
        $t2 = $this->addTask(20);
        $foreign = $this->addTask(10, 0, $otherListId);

        $this->assertFalse($this->repo->changeTaskPosition($t1, $foreign->id, true));

        # nothing has been changed
        $this->assertSame([$t1->id, $t2->id], $this->ids());
        $this->assertSame(10, $this->owOf($t1->id));
        $this->assertSame(10, $this->owOf($foreign->id));
    }

    public function testAnchorWithAnotherCompletionStateReturnsFalse(): void
    {
        $open = $this->addTask(10);
        $completed = $this->addTask(20, 1);

        $this->assertFalse($this->repo->changeTaskPosition($open, $completed->id, true));

        $this->assertSame(10, $this->owOf($open->id));
        $this->assertSame([$open->id], $this->ids($this->listId, 0));
        $this->assertSame([$completed->id], $this->ids($this->listId, 1));
    }

    public function testUnknownAnchorReturnsNull(): void
    {
        $t1 = $this->addTask(10);

        $this->assertNull($this->repo->changeTaskPosition($t1, 999999, true));

        $this->assertSame(10, $this->owOf($t1->id));
    }

    public function testTaskWithoutIdThrows(): void
    {
        $task = Task::create('New task', $this->listId);
        $task->ow = 10;

        $this->expectException(InvalidArgumentException::class);

        $this->repo->changeTaskPosition($task, 1, true);
    }

    public function testTaskWeightIsUpdated(): void
    {
        $t1 = $this->addTask(10);
        $t2 = $this->addTask(20);

        $this->assertTrue($this->repo->changeTaskPosition($t2, $t1->id, false));

        $this->assertSame($this->owOf($t2->id), $t2->ow);
        $this->assertSame(9, $t2->ow);
    }

    public function testDuplicateWeightsKeepDeterministicOrder(): void
    {
        $t1 = $this->addTask(10);
        $t2 = $this->addTask(10);
        $t3 = $this->addTask(20);

        # ties are resolved by id
        $this->assertSame([$t1->id, $t2->id, $t3->id], $this->ids());

        $this->assertTrue($this->repo->changeTaskPosition($t3, $t1->id, false));

        $this->assertSame([$t3->id, $t1->id, $t2->id], $this->ids());
    }


    /* renumberTaskOrder() */

    public function testRenumberTaskOrder(): void
    {
        $a = $this->addTask(5);
        $b = $this->addTask(5);
        $c = $this->addTask(100);
        $completed = $this->addTask(3, 1);
        $otherListId = $this->addList();
        $foreign = $this->addTask(7, 0, $otherListId);

        $this->assertSame(3, $this->repo->renumberTaskOrder($this->listId, 0));

        # order is kept, ties are resolved by id
        $this->assertSame([$a->id, $b->id, $c->id], $this->ids());
        $this->assertSame(
            [$a->id => 10, $b->id => 20, $c->id => 30],
            $this->weights()
        );

        # other buckets are not touched
        $this->assertSame(3, $this->owOf($completed->id));
        $this->assertSame(7, $this->owOf($foreign->id));
    }

    public function testRenumberTaskOrderOfEmptyBucket(): void
    {
        $this->addTask(10, 1);

        $this->assertSame(0, $this->repo->renumberTaskOrder($this->listId, 0));
    }


    /* findTasks() */

    public function testOrderThroughFindTasks(): void
    {
        $t1 = $this->addTask(30);
        $t2 = $this->addTask(10);
        $t3 = $this->addTask(20);

        $tags = [
            'excludeAll' => false,
            'includeAny' => false,
            'include' => [],
            'exclude' => [],
            'search' => [],
        ];

        $tasks = $this->repo->findTasks([$this->listId], false, $tags, '', TaskRepo::SORT_MANUAL);
        $this->assertSame([$t2->id, $t3->id, $t1->id], array_map(fn(Task $t) => $t->id, $tasks));

        # move the first task to the bottom
        $this->assertTrue($this->repo->changeTaskPosition($t2, $t1->id, true));

        $tasks = $this->repo->findTasks([$this->listId], false, $tags, '', TaskRepo::SORT_MANUAL);
        $this->assertSame([$t3->id, $t1->id, $t2->id], array_map(fn(Task $t) => $t->id, $tasks));
    }
}
