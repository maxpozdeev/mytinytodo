<?php

use PHPUnit\Framework\TestCase;

if (file_exists(MTTPATH. 'includes/vendor/autoload.php')) {
    require_once(MTTPATH. 'includes/vendor/autoload.php');
}
require_once(MTTPATH. 'includes/class.dbsetup.php');
require_once(MTTPATH. 'includes/class.db.sqlite.php');

/**
 * HTTP integration tests for the public API.
 *
 * The server runs from a temporary fake application root (serve-fakeroot.sh),
 * so the real config.php and database are never touched.
 */
class ApiHttpTest extends TestCase
{
    private const ALICE = 1;
    private const BOB = 2;

    private const ALICE_PRIVATE_LIST = 1;
    private const ALICE_PUBLIC_LIST = 2;
    private const BOB_PRIVATE_LIST = 3;
    private const BOB_PUBLIC_LIST = 4;

    private const ALICE_PRIVATE_TASK = 100;
    private const ALICE_PUBLIC_TASK = 101;
    private const BOB_PRIVATE_TASK = 102;
    private const BOB_PUBLIC_TASK = 103;

    private const ALICE_PASSWORD = 'alice-password';
    private const ALICE_APP_PASSWORD = 'alice-app-password';
    private const BOB_PASSWORD = 'bob-password';

    private static bool $enabled = false;
    private static string $baseDir = '';
    private static string $root = '';
    private static string $configFile = '';
    private static string $seedFile = '';
    private static string $mailDir = '';
    private static string $sendmailFile = '';
    private static string $serverLog = '';
    private static int $port = 0;
    /** @var resource|null */
    private static $server = null;

    private string $baseUrl = '';
    /** @var array<string, string> */
    private array $cookies = [];
    private ?string $token = null;

    public static function setUpBeforeClass(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            return;
        }

        self::$baseDir = sys_get_temp_dir(). '/mtt-api-http-'. uniqid('', true);
        self::$root = self::$baseDir. '/root';
        self::$configFile = self::$baseDir. '/config.php';
        self::$seedFile = self::$baseDir. '/seed.sqlite';
        self::$mailDir = self::$baseDir. '/mail';
        self::$sendmailFile = self::$baseDir. '/save-test-mail.sh';
        self::$serverLog = self::$baseDir. '/server.log';

        mkdir(self::$root. '/db', 0777, true);
        mkdir(self::$mailDir, 0777, true);

        self::writeConfig();
        self::createSendmailScript();
        self::createSeedDatabase();

        self::$port = self::findFreePort();
        self::startServer();
        self::$enabled = true;
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$enabled) {
            return;
        }

        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
            self::$server = null;
        }
        self::removeDir(self::$baseDir);
        self::$enabled = false;
    }

    protected function setUp(): void
    {
        if (!self::$enabled) {
            $this->markTestSkipped('pdo_sqlite extension is required');
        }

        if (!copy(self::$seedFile, self::$root. '/db/todolist.db')) {
            $this->fail('Failed to restore the test database');
        }
        foreach (glob(self::$mailDir. '/*.eml') ?: [] as $file) {
            @unlink($file);
        }

        $this->baseUrl = 'http://localhost:'. self::$port;
        $this->cookies = [];
        $this->token = null;
    }


    /* Guest and CSRF */

    public function testGuestWithoutCsrfTokenIsDenied(): void
    {
        $r = $this->api('GET', '/lists', null, ['token' => false, 'cookies' => false]);
        $this->assertDenied($r);

        $r = $this->api('GET', '/tasks?list='. self::ALICE_PUBLIC_LIST, null, ['token' => false, 'cookies' => false]);
        $this->assertDenied($r);

        $r = $this->api('GET', '/user/alice/lists', null, ['token' => false, 'cookies' => false]);
        $this->assertDenied($r);

        $r = $this->api('GET', '/user/unknownuser/lists', null, ['token' => false, 'cookies' => false]);
        $this->assertDenied($r);

        $r = $this->api('GET', '/login', null, ['token' => false, 'cookies' => false]);
        $this->assertDenied($r);
    }

    public function testGuestReadsOnlyPublishedLists(): void
    {
        $this->bootstrapGuestSession();

        $this->assertNotSame('', $this->token);
        $this->assertArrayHasKey('mtt-session', $this->cookies);

        $r = $this->api('GET', '/user/alice/lists');
        $this->assertStatus(200, $r);
        $this->assertSame(1, $r['json']['total']);
        $this->assertSame(self::ALICE_PUBLIC_LIST, $r['json']['list'][0]['id']);

        $r = $this->api('GET', '/user/alice/lists/'. self::ALICE_PUBLIC_LIST); # not ready yet
        $this->assertStatus(404, $r);
        #$this->assertSame(self::ALICE_PUBLIC_LIST, $r['json']['id']);

        $r = $this->api('GET', '/lists/'. self::ALICE_PUBLIC_LIST);
        $this->assertStatus(200, $r);
        $this->assertSame(self::ALICE_PUBLIC_LIST, $r['json']['id']);

        $this->assertStatus(404, $this->api('GET', '/lists/'));
        $this->assertStatus(403, $this->api('GET', '/lists/-1'));

        $this->assertDenied($this->api('GET', '/lists/'. self::ALICE_PRIVATE_LIST));
    }

    public function testGuestReadsOnlyPublishedTasksAndTags(): void
    {
        $this->bootstrapGuestSession();

        $r = $this->api('GET', '/user/alice/tasks?list=-1');
        $this->assertStatus(200, $r);
        $this->assertSame(1, $r['json']['total']);
        $this->assertSame(self::ALICE_PUBLIC_TASK, $r['json']['list'][0]['id']);

        $this->assertStatus(200, $this->api('GET', '/user/alice/tasks?list='. self::ALICE_PUBLIC_LIST));
        $this->assertDenied($this->api('GET', '/user/alice/tasks?list='. self::ALICE_PRIVATE_LIST));

        $this->assertStatus(200, $this->api('GET', '/tasks/'. self::ALICE_PUBLIC_TASK));
        $this->assertDenied($this->api('GET', '/tasks/'. self::ALICE_PRIVATE_TASK));

        $this->assertStatus(200, $this->api('GET', '/user/alice/tags/'. self::ALICE_PUBLIC_LIST));
        $this->assertDenied($this->api('GET', '/user/alice/tags/'. self::ALICE_PRIVATE_LIST));
        $this->assertStatus(200, $this->api('GET', '/user/alice/tags/-1'));

        // the target user is scoped by the route, so hidden data is never returned
        $r = $this->api('GET', '/user/bob/tasks?list=-1');
        $this->assertStatus(200, $r);
        $this->assertSame(1, $r['json']['total']);
        $this->assertSame(self::BOB_PUBLIC_TASK, $r['json']['list'][0]['id']);

        $this->assertDenied($this->api('GET', '/user/bob/tasks?list='. self::BOB_PRIVATE_LIST));
        $this->assertDenied($this->api('GET', '/user/bob/tags/'. self::BOB_PRIVATE_LIST));

        $this->assertDenied($this->api('POST', '/tasks', [
            'action' => 'newSimple',
            'list' => self::ALICE_PRIVATE_LIST,
            'title' => 'guest task',
        ]));
    }

    public function testUnknownUserIsNotFound(): void
    {
        $this->bootstrapGuestSession();
        $r = $this->api('GET', '/user/unknown/lists');
        $this->assertStatus(404, $r);
        $this->assertFalse($r['json']['ok'] ?? true);
        $this->assertSame('User not found', $r['json']['error']);

        $r = $this->api('GET', '/user/unknown/tasks?list=-1');
        $this->assertStatus(404, $r);
        $this->assertFalse($r['json']['ok'] ?? true);
        $this->assertSame('User not found', $r['json']['error']);

        $r = $this->api('GET', '/user/unknown/tags/-1');
        $this->assertStatus(404, $r);
        $this->assertFalse($r['json']['ok'] ?? true);
        $this->assertSame('User not found', $r['json']['error']);
    }


    /* Session-authenticated user */

    public function testSessionLoginAndOwnData(): void
    {
        $this->login('alice', self::ALICE_PASSWORD);

        $r = $this->api('GET', '/lists');
        $this->assertStatus(200, $r);
        $this->assertGreaterThanOrEqual(1, $r['json']['total']);

        $this->assertStatus(200, $this->api('GET', '/lists/-1'));
        $this->assertStatus(200, $this->api('GET', '/lists/'. self::ALICE_PRIVATE_LIST));
        $this->assertDenied($this->api('GET', '/lists/'. self::BOB_PRIVATE_LIST));
        $this->assertStatus(200, $this->api('GET', '/lists/'. self::BOB_PUBLIC_LIST));

        $r = $this->api('GET', '/user/alice/lists');
        $this->assertStatus(200, $r);
        $this->assertGreaterThanOrEqual(2, $r['json']['total']);

        $r = $this->api('GET', '/user/bob/lists');
        $this->assertStatus(200, $r);
        $this->assertSame(1, $r['json']['total']);
        $this->assertSame(self::BOB_PUBLIC_LIST, $r['json']['list'][0]['id']);

        $this->assertStatus(200, $this->api('GET', '/tasks?list='. self::ALICE_PRIVATE_LIST));
        $this->assertDenied($this->api('GET', '/tasks?list='. self::BOB_PRIVATE_LIST));
        $this->assertStatus(200, $this->api('GET', '/tasks?list='. self::BOB_PUBLIC_LIST));

        $this->assertStatus(200, $this->api('GET', '/tasks/'. self::ALICE_PRIVATE_TASK));
        $this->assertDenied($this->api('GET', '/tasks/'. self::BOB_PRIVATE_TASK));
        $this->assertStatus(200, $this->api('GET', '/tasks/'. self::BOB_PUBLIC_TASK));

        $this->assertStatus(200, $this->api('GET', '/suggestTags?list='. self::ALICE_PRIVATE_LIST));
        $this->assertDenied($this->api('GET', '/suggestTags?list='. self::BOB_PRIVATE_LIST));
    }

    public function testUserTasksRouteForForeignUser(): void
    {
        $this->login('alice', self::ALICE_PASSWORD);

        $r = $this->api('GET', '/user/bob/tasks?list=-1');
        $this->assertStatus(200, $r);
        $this->assertSame(1, $r['json']['total']);
        $this->assertSame(self::BOB_PUBLIC_TASK, $r['json']['list'][0]['id']);
        $this->assertSame(self::BOB_PUBLIC_LIST, $r['json']['list'][0]['listId']);
    }

    public function testUserTasksRouteForPrivateList(): void
    {
        $this->login('bob', self::BOB_PASSWORD);

        $r = $this->api('GET', '/user/bob/tasks?list='. self::BOB_PRIVATE_LIST);
        $this->assertStatus(200, $r);
        $this->assertSame(1, $r['json']['total']);
        $this->assertSame(self::BOB_PRIVATE_TASK, $r['json']['list'][0]['id']);

        // switching the session to another user must not allow reading bob's private list
        $this->login('alice', self::ALICE_PASSWORD);
        $this->assertDenied($this->api('GET', '/user/bob/tasks?list='. self::BOB_PRIVATE_LIST));
    }

    public function testTargetUserScopesListLookup(): void
    {
        $this->login('bob', self::BOB_PASSWORD);

        // without target user the list exists but is not readable by bob
        $this->assertDenied($this->api('GET', '/tasks?list='. self::ALICE_PRIVATE_LIST));

        // with target user the lookup is limited to bob, so the list is not found
        $r = $this->api('GET', '/user/bob/tasks?list='. self::ALICE_PRIVATE_LIST);
        $this->assertStatus(404, $r);
        $this->assertFalse($r['json']['ok'] ?? true);
    }

    public function testUserTagsRouteRespectsTargetUser(): void
    {
        $this->login('bob', self::BOB_PASSWORD);
        $this->assertStatus(200, $this->api('GET', '/user/bob/tags/'. self::BOB_PRIVATE_LIST));
        $this->assertStatus(200, $this->api('GET', '/user/bob/tags/-1'));

        $this->login('alice', self::ALICE_PASSWORD);
        $this->assertDenied($this->api('GET', '/user/bob/tags/'. self::BOB_PRIVATE_LIST));

        // the all tasks list is readable, but only published tags are returned to a non-owner
        $r = $this->api('GET', '/user/bob/tags/-1');
        $this->assertStatus(200, $r);
        $this->assertSame(1, $r['json']['total']);
        $this->assertSame('bob-tag', $r['json']['items'][0]['tagText']);
    }

    public function testInvalidLogin(): void
    {
        $this->bootstrapGuestSession();
        $r = $this->api('POST', '/login', [
            'username' => 'alice',
            'password' => 'wrong-password',
        ]);
        $this->assertStatus(200, $r);
        $this->assertSame(0, $r['json']['logged'] ?? null);
        $this->assertFalse($r['json']['ok'] ?? true);
    }



    /* Lists API */

    public function testListsCrud(): void
    {
        $this->login('alice', self::ALICE_PASSWORD);

        $r = $this->api('POST', '/lists', ['action' => 'new', 'name' => 'HTTP list']);
        $this->assertStatus(200, $r);
        $this->assertTrue($r['json']['ok'] ?? false);
        $listId = (int)$r['json']['list'][0]['id'];
        $this->assertGreaterThan(0, $listId);

        $r = $this->api('GET', '/lists/'. $listId);
        $this->assertStatus(200, $r);
        $this->assertSame('HTTP list', $r['json']['name']);

        $this->assertStatus(200, $this->api('PUT', '/lists/'. $listId, [
            'action' => 'rename',
            'name' => 'Renamed HTTP list',
        ]));
        $this->assertStatus(200, $this->api('PUT', '/lists/'. $listId, [
            'action' => 'sort',
            'sort' => 1,
        ]));
        $this->assertStatus(200, $this->api('PUT', '/lists/'. $listId, [
            'action' => 'publish',
            'publish' => 1,
        ]));

        $r = $this->api('PUT', '/lists/'. $listId, [
            'action' => 'enableFeedKey',
            'enable' => 1,
        ]);
        $this->assertStatus(200, $r);
        $this->assertNotSame('', $r['json']['list'][0]['feedKey']);

        $this->assertStatus(200, $this->api('PUT', '/lists/'. $listId, [
            'action' => 'showNotes',
            'shownotes' => 1,
        ]));
        $this->assertStatus(200, $this->api('PUT', '/lists/'. $listId, [
            'action' => 'hide',
            'hide' => 1,
        ]));
        $this->assertStatus(200, $this->api('PUT', '/lists/'. $listId, [
            'action' => 'clearCompleted',
        ]));

        // POST is a compatibility alias for PUT
        $this->assertStatus(200, $this->api('POST', '/lists/'. $listId, [
            'action' => 'rename',
            'name' => 'Alias renamed',
        ]));

        $this->assertStatus(200, $this->api('PUT', '/lists', [
            'action' => 'order',
            'order' => [$listId],
        ]));
        $this->assertStatus(200, $this->api('POST', '/lists', [
            'action' => 'order',
            'order' => [$listId],
        ]));

        $this->assertDenied($this->api('PUT', '/lists/'. self::BOB_PRIVATE_LIST, [
            'action' => 'rename',
            'name' => 'Nope',
        ]));
        $this->assertDenied($this->api('DELETE', '/lists/'. self::BOB_PRIVATE_LIST));

        $this->assertStatus(200, $this->api('DELETE', '/lists/'. $listId));
        $this->assertStatus(404, $this->api('GET', '/lists/'. $listId));
    }

    public function testRequiresExplicitAction(): void
    {
        $this->login('alice', self::ALICE_PASSWORD);

        $cases = [
            'POST /lists: missing action' => [
                'POST', '/lists', ['name' => 'No action list'],
            ],
            'POST /lists: empty action' => [
                'POST', '/lists', ['action' => '', 'name' => 'Empty action list'],
            ],
            'POST /lists: unknown action' => [
                'POST', '/lists', ['action' => 'unknown', 'name' => 'Unknown action list'],
            ],
            'PUT /lists: missing action' => [
                'PUT', '/lists', ['order' => [self::ALICE_PRIVATE_LIST]],
            ],
            'PUT /lists: unknown action' => [
                'PUT', '/lists', ['action' => 'unknown'],
            ],
            'POST /lists/{id}: missing action' => [
                'POST', '/lists/'. self::ALICE_PRIVATE_LIST, ['name' => 'No action'],
            ],
            'PUT /lists/{id}: missing action' => [
                'PUT', '/lists/'. self::ALICE_PRIVATE_LIST, ['name' => 'No action'],
            ],
            'PUT /lists/{id}: unknown action' => [
                'PUT', '/lists/'. self::ALICE_PRIVATE_LIST, ['action' => 'unknown'],
            ],
            'POST /tasks/{id}: missing action' => [
                'POST', '/tasks/'. self::ALICE_PRIVATE_TASK, [],
            ],
            'PUT /tasks/{id}: missing action' => [
                'PUT', '/tasks/'. self::ALICE_PRIVATE_TASK, [],
            ],
            'PUT /tasks/{id}: unknown action' => [
                'PUT', '/tasks/'. self::ALICE_PRIVATE_TASK, ['action' => 'unknown'],
            ],
            'PATCH /tasks: no compl or listId' => [
                'PATCH', '/tasks', ['ids' => [self::ALICE_PRIVATE_TASK]],
            ],
        ];
        foreach ($cases as $label => [$method, $path, $body]) {
            $r = $this->api($method, $path, $body);
            $message = $label. "\n". $this->requestMessage($r);
            $this->assertStatus(400, $r);
            $this->assertSame(false, $r['json']['ok'] ?? null, $message);
            $this->assertSame('Unexpected action', $r['json']['error'] ?? null, $message);
        }
    }

    public function testListsMethodOverride(): void
    {
        $this->login('alice', self::ALICE_PASSWORD);
        $r = $this->api('POST', '/lists', [
            '_method' => 'PUT',
            'action' => 'order',
            'order' => [self::ALICE_PRIVATE_LIST, self::ALICE_PUBLIC_LIST],
        ]);
        $this->assertStatus(200, $r);
        $this->assertTrue($r['json']['ok'] ?? false);
    }


    /* Tasks API */

    public function testTasksCrud(): void
    {
        $this->login('alice', self::ALICE_PASSWORD);

        $r = $this->api('POST', '/tasks', [
            'action' => 'newSimple',
            'list' => self::ALICE_PRIVATE_LIST,
            'title' => 'HTTP task',
        ]);
        $this->assertStatus(200, $r);
        $this->assertTrue($r['json']['ok'] ?? false);
        $taskId = (int)$r['json']['list'][0]['id'];

        $r = $this->api('POST', '/tasks', [
            'action' => 'newFull',
            'list' => self::ALICE_PRIVATE_LIST,
            'title' => 'HTTP full task',
            'note' => 'note',
            'prio' => 1,
        ]);
        $this->assertStatus(200, $r);
        $secondId = (int)$r['json']['list'][0]['id'];

        $r = $this->api('GET', '/tasks/'. $taskId);
        $this->assertStatus(200, $r);
        $this->assertSame($taskId, $r['json']['list'][0]['id']);

        $this->assertStatus(200, $this->api('PUT', '/tasks/'. $taskId, [
            'action' => 'edit',
            'title' => 'Edited HTTP task',
        ]));
        $this->assertStatus(200, $this->api('PUT', '/tasks/'. $taskId, [
            'action' => 'note',
            'note' => 'edited note',
        ]));
        $this->assertStatus(200, $this->api('PUT', '/tasks/'. $taskId, [
            'action' => 'priority',
            'prio' => 2,
        ]));
        $this->assertStatus(200, $this->api('PUT', '/tasks/'. $taskId, [
            'action' => 'complete',
            'compl' => 1,
        ]));

        // move to another own list
        $this->assertStatus(200, $this->api('PUT', '/tasks/'. $taskId, [
            'action' => 'move',
            'to' => self::ALICE_PUBLIC_LIST,
        ]));

        // POST is a compatibility alias for PUT
        $this->assertStatus(200, $this->api('POST', '/tasks/'. $taskId, [
            'action' => 'complete',
            'compl' => 0,
        ]));

        $this->assertStatus(200, $this->api('PUT', '/tasks/'. $secondId. '/position', [
            'afterId' => self::ALICE_PRIVATE_TASK,
        ]));

        $this->assertStatus(400, $this->api('PUT', '/tasks/'. $secondId. '/position', [
            'afterId' => self::ALICE_PRIVATE_TASK,
            'beforeId' => self::ALICE_PRIVATE_TASK,
        ]));

        $this->assertStatus(200, $this->api('PATCH', '/tasks', [
            'ids' => [$taskId, $secondId],
            'compl' => 1,
        ]));

        $this->assertStatus(200, $this->api('DELETE', '/tasks', [
            'ids' => [$taskId],
        ]));
        $this->assertStatus(200, $this->api('DELETE', '/tasks/'. $secondId));
        $this->assertStatus(404, $this->api('GET', '/tasks/'. $secondId));

        $this->assertDenied($this->api('POST', '/tasks', [
            'action' => 'newSimple',
            'list' => self::BOB_PRIVATE_LIST,
            'title' => 'foreign task',
        ]));
        $this->assertDenied($this->api('PUT', '/tasks/'. self::BOB_PRIVATE_TASK, [
            'action' => 'edit',
            'title' => 'Nope',
        ]));
    }

    public function testTaskUtilityRoutes(): void
    {
        $this->login('alice', self::ALICE_PASSWORD);

        $r = $this->api('POST', '/tasks/parseTitle', ['title' => 'Task +prio #tag']);
        $this->assertStatus(200, $r);
        $this->assertArrayHasKey('title', $r['json']);

        $r = $this->api('POST', '/tasks/newTaskCounter', [
            'list' => self::ALICE_PRIVATE_LIST,
            'later' => 0,
            'lists' => [
                ['listId' => self::ALICE_PRIVATE_LIST, 'later' => 0],
                ['listId' => self::BOB_PRIVATE_LIST, 'later' => 0],
            ],
        ]);
        $this->assertStatus(200, $r);
        $this->assertTrue($r['json']['ok'] ?? false);
    }



    /* Authentication routes */

    public function testSessionEndpointReturnsToken(): void
    {
        // /session is kept for API compatibility; the UI uses the login page token.
        $r = $this->api('POST', '/session', null, ['token' => false, 'cookies' => false]);
        $this->assertStatus(200, $r);
        $this->assertNotSame('', $r['json']['token'] ?? '');
        $this->assertArrayHasKey('mtt-session', $this->cookies);
    }

    public function testLogoutInvalidatesCurrentToken(): void
    {
        $this->login('alice', self::ALICE_PASSWORD);
        $oldToken = (string)$this->token;

        $r = $this->api('POST', '/logout');
        $this->assertStatus(200, $r);
        $this->assertSame(0, $r['json']['logged'] ?? null);

        $this->token = $oldToken;
        $this->assertDenied($this->api('GET', '/lists'));
    }

    public function testPasswordResetFlowStoresMailAndChangesPassword(): void
    {
        $this->bootstrapGuestSession();

        $r = $this->api('POST', '/resetPassword', ['email' => 'alice@example.com']);
        $this->assertStatus(200, $r);
        $this->assertTrue($r['json']['ok'] ?? false);

        $mail = $this->waitForMail();
        $this->assertStringContainsString('alice@example.com', $mail);

        $decoded = html_entity_decode(quoted_printable_decode($mail));
        $this->assertMatchesRegularExpression('/[?&]code=([^&\s"\']+)/', $decoded);
        preg_match('/[?&]code=([^&\s"\']+)/', $decoded, $m);
        $code = urldecode($m[1]);

        $newPassword = 'alice-new-password';
        $r = $this->api('POST', '/newPassword', [
            'email' => 'alice@example.com',
            'code' => $code,
            'newpassword' => $newPassword,
            'newpassword2' => $newPassword,
        ]);
        $this->assertStatus(200, $r);
        $this->assertTrue($r['json']['ok'] ?? false);

        // The old password is no longer valid.
        $r = $this->api('POST', '/login', [
            'username' => 'alice',
            'password' => self::ALICE_PASSWORD,
        ]);
        $this->assertStatus(200, $r);
        $this->assertSame(0, $r['json']['logged'] ?? null);

        // The new password works.
        $r = $this->api('POST', '/login', [
            'username' => 'alice',
            'password' => $newPassword,
        ]);
        $this->assertStatus(200, $r);
        $this->assertSame(1, $r['json']['logged'] ?? null);
    }

    public function testResetPasswordValidationDoesNotSendMail(): void
    {
        $this->bootstrapGuestSession();

        $r = $this->api('POST', '/resetPassword', ['email' => 'unknown@example.com']);
        $this->assertStatus(200, $r);
        // In non-debug mode the response intentionally does not reveal whether the email exists.
        $this->assertTrue($r['json']['ok'] ?? false);
        $this->assertCount(0, glob(self::$mailDir. '/*.eml') ?: []);

        $r = $this->api('POST', '/newPassword', ['email' => 'alice@example.com']);
        $this->assertStatus(200, $r);
        $this->assertFalse($r['json']['ok'] ?? true);
    }

    public function testApplicationPasswordBypassesCsrfToken(): void
    {
        $basic = ['basic' => ['alice', self::ALICE_APP_PASSWORD], 'token' => false, 'cookies' => false];

        $r = $this->api('GET', '/lists', null, $basic);
        $this->assertStatus(200, $r);
        $this->assertGreaterThanOrEqual(1, $r['json']['total']);

        $this->assertStatus(200, $this->api('GET', '/lists/'. self::ALICE_PRIVATE_LIST, null, $basic));
        $this->assertDenied($this->api('GET', '/lists/'. self::BOB_PRIVATE_LIST, null, $basic));

        $r = $this->api('POST', '/tasks', [
            'action' => 'newSimple',
            'list' => self::ALICE_PRIVATE_LIST,
            'title' => 'task via app password',
        ], $basic);
        $this->assertStatus(200, $r);
        $this->assertTrue($r['json']['ok'] ?? false);

        $r = $this->api('GET', '/lists', null, [
            'basic' => ['alice', 'wrong-app-password'],
            'token' => false,
            'cookies' => false,
        ]);
        $this->assertSame(401, $r['status'], $this->requestMessage($r));
    }


    /* Routing errors */

    public function testUnknownEndpointAndWrongMethod(): void
    {
        $this->login('alice', self::ALICE_PASSWORD);

        $r = $this->api('GET', '/unknown-endpoint');
        $this->assertStatus(404, $r);

        $r = $this->api('GET', '/session');
        $this->assertStatus(405, $r);

        $r = $this->api('DELETE', '/parseTitle');
        $this->assertStatus(404, $r);
    }



    /* HTTP helpers */

    private function bootstrapGuestSession(): void
    {
        $r = $this->page('/index.php?p=/login', ['token' => false, 'cookies' => false]);
        $this->assertStatus(200, $r);

        if (!preg_match('/"token"\s*:\s*"([^"]+)"/', $r['body'], $m)) {
            $this->fail('CSRF token was not found on the login page: '. $this->requestMessage($r));
        }
        $this->token = $m[1];
    }

    private function login(string $username, string $password): void
    {
        $this->bootstrapGuestSession();
        $r = $this->api('POST', '/login', [
            'username' => $username,
            'password' => $password,
        ]);
        $this->assertStatus(200, $r);
        $this->assertSame(1, $r['json']['logged'] ?? null, $this->requestMessage($r));
        $this->token = (string)($r['json']['token'] ?? $this->token);
    }

    /**
     * @param array<string, mixed>|null $body
     * @param array<string, mixed> $options
     * @return array{status: ?int, headers: array<int, string>, body: string, json: ?array}
     */
    private function api(string $method, string $path, ?array $body = null, array $options = []): array
    {
        $parts = explode('?', $path, 2);
        $url = $this->baseUrl. '/api.php?_path='. rawurlencode($parts[0]);
        if (isset($parts[1])) {
            $url .= '&'. $parts[1];
        }
        return $this->send($method, $url, $body, $options);
    }

    /**
     * @param array<string, mixed> $options
     * @return array{status: ?int, headers: array<int, string>, body: string, json: ?array}
     */
    private function page(string $path, array $options = []): array
    {
        return $this->send('GET', $this->baseUrl. $path, null, $options);
    }

    /**
     * @param array<string, mixed>|null $body
     * @param array<string, mixed> $options
     * @return array{status: ?int, headers: array<int, string>, body: string, json: ?array}
     */
    private function send(string $method, string $url, ?array $body, array $options): array
    {
        $headers = ['Accept: application/json'];
        $hasContentType = false;
        if (isset($options['headers']) && is_array($options['headers'])) {
            foreach ($options['headers'] as $header) {
                if (stripos((string)$header, 'Content-Type:') === 0) {
                    $hasContentType = true;
                }
            }
        }
        if (!$hasContentType && ($body !== null || strpos($url, '/api.php') !== false)) {
            $headers[] = 'Content-Type: application/json';
        }
        if (($options['token'] ?? true) && $this->token !== null) {
            $headers[] = 'MTT-Token: '. $this->token;
        }
        if (($options['cookies'] ?? true) && $this->cookies) {
            $pairs = [];
            foreach ($this->cookies as $name => $value) {
                $pairs[] = $name. '='. $value;
            }
            $headers[] = 'Cookie: '. implode('; ', $pairs);
        }
        if (isset($options['basic']) && is_array($options['basic'])) {
            $headers[] = 'Authorization: Basic '. base64_encode(
                (string)$options['basic'][0]. ':'. (string)$options['basic'][1]
            );
        }
        if (isset($options['headers']) && is_array($options['headers'])) {
            foreach ($options['headers'] as $header) {
                $headers[] = (string)$header;
            }
        }

        $content = ($body === null) ? '' : (string)json_encode($body, JSON_UNESCAPED_UNICODE);
        $headers[] = 'Content-Length: '. strlen($content);
        $headers[] = 'Connection: close';

        $parts = parse_url($url);
        if ($parts === false || !isset($parts['host'])) {
            $this->fail('Malformed URL: '. $url);
        }
        $host = $parts['host'];
        $port = (int)($parts['port'] ?? 80);
        $target = ($parts['path'] ?? '/'). (isset($parts['query']) ? '?'. $parts['query'] : '');

        $socket = @stream_socket_client(
            'tcp://'. $host. ':'. $port,
            $errno,
            $errstr,
            10
        );
        if (!is_resource($socket)) {
            $this->fail('HTTP connection failed: '. $method. ' '. $url. ' ('. $errstr. ")\n". $this->serverLog());
        }

        $request = $method. ' '. $target. " HTTP/1.1\r\n"
            . 'Host: '. $host. ':'. $port. "\r\n"
            . implode("\r\n", $headers). "\r\n\r\n"
            . $content;
        fwrite($socket, $request);
        $rawResponse = stream_get_contents($socket);
        fclose($socket);
        if ($rawResponse === false) {
            $this->fail('HTTP response read failed: '. $method. ' '. $url."\n". $this->serverLog());
        }

        $headerEnd = strpos($rawResponse, "\r\n\r\n");
        if ($headerEnd === false) {
            $this->fail('Malformed HTTP response: '. $method. ' '. $url."\n". $rawResponse);
        }
        $headersText = substr($rawResponse, 0, $headerEnd);
        $responseBody = substr($rawResponse, $headerEnd + 4);
        $responseHeaders = explode("\r\n", $headersText);

        $status = null;
        foreach ($responseHeaders as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d+)#', $header, $m)) {
                $status = (int)$m[1];
            }
            else if (stripos($header, 'Set-Cookie:') === 0) {
                $cookie = trim(substr($header, strlen('Set-Cookie:')));
                $semi = strpos($cookie, ';');
                if ($semi !== false) {
                    $cookie = substr($cookie, 0, $semi);
                }
                $eq = strpos($cookie, '=');
                if ($eq !== false) {
                    $name = trim(substr($cookie, 0, $eq));
                    $value = trim(substr($cookie, $eq + 1));
                    if ($value === '') {
                        unset($this->cookies[$name]);
                    }
                    else {
                        $this->cookies[$name] = $value;
                    }
                }
            }
        }

        if (preg_match('/^Transfer-Encoding:\s*chunked/im', $headersText)) {
            $responseBody = $this->decodeChunked($responseBody);
        }

        $json = json_decode($responseBody, true);
        return [
            'status' => $status,
            'headers' => $responseHeaders,
            'body' => $responseBody,
            'json' => is_array($json) ? $json : null,
        ];
    }

    private function decodeChunked(string $body): string
    {
        $result = '';
        $offset = 0;
        while (true) {
            $lineEnd = strpos($body, "\r\n", $offset);
            if ($lineEnd === false) {
                break;
            }
            $size = hexdec(trim(substr($body, $offset, $lineEnd - $offset)));
            $offset = $lineEnd + 2;
            if ($size === 0) {
                break;
            }
            $result .= substr($body, $offset, (int)$size);
            $offset += (int)$size + 2;
        }
        return $result;
    }



    /* Assertions */

    /**
     * @param array{status: ?int, headers: array<int, string>, body: string, json: ?array} $r
     */
    private function assertStatus(int $expected, array $r): void
    {
        $this->assertSame($expected, $r['status'], $this->requestMessage($r));
    }

    /**
     * @param array{status: ?int, headers: array<int, string>, body: string, json: ?array} $r
     */
    private function assertDenied(array $r): void
    {
        $this->assertStatus(403, $r);
        $this->assertSame(1, $r['json']['denied'] ?? null, $this->requestMessage($r));
        $this->assertSame(false, $r['json']['ok'] ?? null, $this->requestMessage($r));
    }

    /**
     * @param array{status: ?int, headers: array<int, string>, body: string, json: ?array} $r
     */
    private function requestMessage(array $r): string
    {
        $status = $r['status'] === null ? 'null' : (string)$r['status'];
        return 'HTTP status: '. $status. "\nResponse: ". $r['body']. "\nServer log:\n". $this->serverLog();
    }

    private function serverLog(): string
    {
        if (!is_file(self::$serverLog)) {
            return '';
        }
        $log = (string)file_get_contents(self::$serverLog);
        return substr($log, -4000);
    }

    private function waitForMail(): string
    {
        $deadline = microtime(true) + 3.0;
        do {
            $files = glob(self::$mailDir. '/*.eml') ?: [];
            if ($files) {
                return (string)file_get_contents($files[0]);
            }
            usleep(50000);
        } while (microtime(true) < $deadline);

        $this->fail('No mail was saved in '. self::$mailDir. "\n". $this->serverLog());
    }



    /* Server and database setup */

    private static function writeConfig(): void
    {
        $config = <<<'PHP'
<?php

define('MTT_DB_TYPE', 'sqlite');
define('MTT_DB_PREFIX', '');
define('MTT_SALT', 'TestSaltForApiHttpTests1234567890');
define('MTT_USE_REWRITE', false);
define('MTT_DEBUG', false);
define('MTT_MULTIUSER', 1);
define('MTT_DISABLE_EXT', 1);
define('MTT_MAILER_DSN', 'mail://localhost');
define('MTT_MAILER_WEAK_SSL', false);
PHP;

        file_put_contents(self::$configFile, $config. "\n");
    }

    private static function createSendmailScript(): void
    {
        $script = <<<'SH'
#!/bin/sh
dir="${MTT_TEST_MAIL_DIR:?}"
mkdir -p "$dir"
file=$(mktemp "$dir/mail-XXXXXX.eml") || exit 1
cat > "$file"
exit 0
SH;

        if (false === file_put_contents(self::$sendmailFile, $script. "\n")) {
            throw new RuntimeException('Failed to create the fake sendmail script');
        }
        chmod(self::$sendmailFile, 0755);
    }

    private static function createSeedDatabase(): void
    {
        $db = new SqliteDatabase(null);
        $db->connect(['filename' => self::$seedFile]);
        DBConnection::init($db);
        DBConnection::setTablePrefix('');
        MTTDBSetup::createSqliteTables($db);

        $aliceExtra = json_encode([
            'apppasswords' => [[
                'hash' => passwordHash(self::ALICE_APP_PASSWORD),
                'name' => 'test',
                'created' => time(),
                'uuid' => 'app-password-uuid-1',
            ]],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $db->ex(
            'INSERT INTO users (id,username,email,name,pwhash,pwtoken,last_visit,extra) '
            . 'VALUES (?,?,?,?,?,?,?,?)',
            [self::ALICE, 'alice', 'alice@example.com', 'Alice', passwordHash(self::ALICE_PASSWORD),
                'alice-pwtoken', null, $aliceExtra]
        );
        $db->ex(
            'INSERT INTO users (id,username,email,name,pwhash,pwtoken,last_visit,extra) '
            . 'VALUES (?,?,?,?,?,?,?,?)',
            [self::BOB, 'bob', 'bob@example.com', 'Bob', passwordHash('bob-password'),
                'bob-pwtoken', null, '{}']
        );

        $db->ex(
            'INSERT INTO settings (param_key,param_value) VALUES (?,?)',
            [
                'config.json',
                json_encode([
                    'mailFrom' => 'no-reply@example.test',
                    'mailFromName' => 'myTinyTodo Test',
                    'timezone' => 'UTC',
                    'lang' => 'en',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]
        );

        self::insertList($db, self::ALICE_PRIVATE_LIST, self::ALICE, 'Alice private', 0);
        self::insertList($db, self::ALICE_PUBLIC_LIST, self::ALICE, 'Alice published', 1);
        self::insertList($db, self::BOB_PRIVATE_LIST, self::BOB, 'Bob private', 0);
        self::insertList($db, self::BOB_PUBLIC_LIST, self::BOB, 'Bob published', 1);

        self::insertTask($db, self::ALICE_PRIVATE_TASK, self::ALICE_PRIVATE_LIST, 'Alice private task');
        self::insertTask($db, self::ALICE_PUBLIC_TASK, self::ALICE_PUBLIC_LIST, 'Alice public task');
        self::insertTask($db, self::BOB_PRIVATE_TASK, self::BOB_PRIVATE_LIST, 'Bob private task');
        self::insertTask($db, self::BOB_PUBLIC_TASK, self::BOB_PUBLIC_LIST, 'Bob public task');

        $db->ex('INSERT INTO tags (id,user_id,name) VALUES (?,?,?)', [1, self::ALICE, 'alice-tag']);
        $db->ex('INSERT INTO tag2task (tag_id,task_id,list_id) VALUES (?,?,?)',
            [1, self::ALICE_PUBLIC_TASK, self::ALICE_PUBLIC_LIST]);
        $db->ex('INSERT INTO tags (id,user_id,name) VALUES (?,?,?)', [2, self::BOB, 'bob-tag']);
        $db->ex('INSERT INTO tag2task (tag_id,task_id,list_id) VALUES (?,?,?)',
            [2, self::BOB_PUBLIC_TASK, self::BOB_PUBLIC_LIST]);
    }

    private static function insertList(AbstractDatabase $db, int $id, int $userId, string $name, int $published): void
    {
        $db->ex(
            'INSERT INTO lists (id,uuid,ow,name,user_id,d_created,d_edited,sorting,published,taskview,extra) '
            . 'VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            [$id, 'list-'. $id, $id * 10, $name, $userId, time(), time(), 0, $published, 0, '{}']
        );
    }

    private static function insertTask(AbstractDatabase $db, int $id, int $listId, string $title): void
    {
        $db->ex(
            'INSERT INTO todolist (id,uuid,list_id,d_created,title,compl,ow,prio,extra) '
            . 'VALUES (?,?,?,?,?,?,?,?,?)',
            [$id, 'task-'. $id, $listId, time(), $title, 0, $id * 10, 0, '{}']
        );
    }



    private static function startServer(): void
    {
        $env = getenv();
        if (!is_array($env)) {
            $env = [];
        }
        $env = array_merge($env, [
            'MTT_TEST_ROOT' => self::$root,
            'MTT_TEST_CONFIG_FILE' => self::$configFile,
            'MTT_TEST_SQLITE_FILE' => self::$seedFile,
            'MTT_TEST_MAIL_DIR' => self::$mailDir,
            'MTT_TEST_SENDMAIL' => self::$sendmailFile,
        ]);

        $descriptors = [
            1 => ['file', self::$serverLog, 'a'],
            2 => ['file', self::$serverLog, 'a'],
        ];
        $command = ['/bin/sh', dirname(__DIR__). '/serve-fakeroot.sh', (string)self::$port];
        self::$server = proc_open($command, $descriptors, $pipes, dirname(__DIR__), $env);
        if (!is_resource(self::$server)) {
            throw new RuntimeException('Failed to start the PHP built-in server');
        }

        self::waitForServer();
    }

    private static function waitForServer(): void
    {
        $deadline = microtime(true) + 10.0;
        do {
            if (self::probeServer()) {
                return;
            }
            usleep(100000);
        } while (microtime(true) < $deadline);

        $log = is_file(self::$serverLog) ? (string)file_get_contents(self::$serverLog) : '';
        throw new RuntimeException("PHP built-in server did not start.\n". $log);
    }

    private static function probeServer(): bool
    {
        $socket = @stream_socket_client('tcp://localhost:'. self::$port, $errno, $errstr, 2);
        if (!is_resource($socket)) {
            return false;
        }

        fwrite(
            $socket,
            "GET /index.php?p=/login HTTP/1.1\r\n"
            . "Host: localhost:". self::$port. "\r\n"
            . "Connection: close\r\n\r\n"
        );
        $line = fgets($socket);
        fclose($socket);
        return is_string($line) && (bool)preg_match('#^HTTP/\S+\s+200#', $line);
    }

    private static function findFreePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if (!is_resource($socket)) {
            throw new RuntimeException('Failed to allocate a free port: '. $errstr);
        }
        $name = stream_socket_get_name($socket, false);
        fclose($socket);
        if ($name === false) {
            throw new RuntimeException('Failed to read the allocated port');
        }
        $pos = strrpos($name, ':');
        if ($pos === false) {
            throw new RuntimeException('Unexpected socket name: '. $name);
        }
        return (int)substr($name, $pos + 1);
    }

    private static function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir. '/'. $item;
            if (is_link($path) || is_file($path)) {
                @unlink($path);
            }
            else {
                self::removeDir($path);
            }
        }
        @rmdir($dir);
    }
}

