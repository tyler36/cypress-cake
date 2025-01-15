<?php
declare(strict_types=1);

namespace Tyler36\CypressCake\Tests\Controller;

use App\Test\Factory\UserFactory;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Tyler36\CypressCake\DatabaseHelperTrait;

/**
 * Class CypressControllerTest.
 */
class CypressControllerTest extends TestCase
{
    use DatabaseHelperTrait;
    use IntegrationTestTrait;

    /**
     * Test setup
     */
    protected function setUp(): void
    {
        parent::setUp();

        self::truncateAllTables();
    }

    public function test_it_clears_the_database(): void
    {
      // Make sure we have at least 1 entity in the database.
        UserFactory::make()->persist();
        $users = TableRegistry::getTableLocator()->get('Users');
        $this->assertCount(1, $users->find());

      // Clear the database.
        $this->get('/cypress/clear-database');
        $this->assertResponseOk();
        $this->assertCount(0, $users->find());

        $response = json_decode($this->_getBodyAsString());
        $this->assertTrue($response->data);
    }

    public function test_it_restores_database_from_backup(): void
    {
        $email = 'dave123@example.com';

        // Make sure the default ENV value is set and populated.
        $_ENV['SQL_TESTING_BASE_DUMP'] = '/tmp/test-base.sql';
        $sql = <<<SQL
INSERT INTO users VALUES (1234,'{$email}','invalid','2024-02-25 09:31:03','2024-06-27 06:34:41');
-- Example comment line;
INSERT INTO users VALUES (5678,'rob{$email}','invalid','2024-02-25 09:31:03','2024-06-27 06:34:41');
SQL;
        file_put_contents($_ENV['SQL_TESTING_BASE_DUMP'], $sql);

        $users = TableRegistry::getTableLocator()->get('Users');
        $this->assertCount(0, $users->find()->where(['email' => $email]));

        $this->enableCsrfToken();
        $this->post('/cypress/import-database');
        $this->assertResponseOk();
        $this->assertCount(2, $users = $users->find());
        $this->assertEquals($email, $users->first()->email);

        // ASSERT response contains model.
        $response = json_decode($this->_getBodyAsString());
        $this->assertTrue($response->data);
    }

    public function test_it_restores_database_from_named_backup(): void
    {
        $email = 'bob@example.com';
        $sql = <<<SQL
INSERT INTO users VALUES (1234,'{$email}','invalid','2024-02-25 09:31:03','2024-06-27 06:34:41');
-- Example comment line;
INSERT INTO users VALUES (5678,'rob{$email}','invalid','2024-02-25 09:31:03','2024-06-27 06:34:41');
SQL;
        $file = '/tmp/fake.sql';
        file_put_contents($file, $sql);

        $users = TableRegistry::getTableLocator()->get('Users');
        $this->assertCount(0, $users->find()->where(['email' => $email]));

        $this->enableCsrfToken();
        $this->post('/cypress/import-database', ['filename' => $file]);
        $this->assertResponseOk();
        $this->assertCount(2, $users = $users->find());
        $this->assertEquals($email, $users->first()->email);

        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function test_it_throws_error_if_database_filename_is_missing(): void
    {
        $this->disableErrorHandlerMiddleware();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("DB filename is invalid: ''");

        $this->enableCsrfToken();
        $this->post('/cypress/import-database', ['filename' => '']);
    }

    public function test_it_throws_an_error_if_database_file_is_missing(): void
    {
        $this->disableErrorHandlerMiddleware();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("DB backup file not found: 'invalid'");

        $this->enableCsrfToken();
        $this->post('/cypress/import-database', ['filename' => 'invalid']);
    }

    public function test_it_can_create_a_entity(): void
    {
        $users = TableRegistry::getTableLocator()->get('Users');
        $this->assertCount(0, $users->find());

        $this->enableCsrfToken();
        $this->post('/cypress/create', [
          'factory' => 'User',
        ]);
        $this->assertResponseOk();

        $this->assertCount(1, $users->find());
        $user = json_decode($this->_getBodyAsString())->data;
        $this->assertNotNull($user->email);
        $this->assertNotNull($user->id);
    }

    public function test_sql_autogenerates_primary_keys(): void
    {
        $users = TableRegistry::getTableLocator()->get('Users');
        $this->assertCount(0, $users->find());

        $this->enableCsrfToken();
        $this->post('/cypress/create', [
          'factory' => 'User',
        ]);
        $this->assertResponseOk();

        $this->assertCount(1, $users->find());
        $user = json_decode($this->_getBodyAsString())->data;
        $this->assertEquals(1, $user->id);
    }

    public function test_it_can_create_a_new_user_with_attributes(): void
    {
        $users = TableRegistry::getTableLocator()->get('Users');
        $userCount = $users->find()->count();

        $this->enableCsrfToken();
        $this->post('/cypress/create', [
          'factory' => 'User',
          'attributes' => ['email' => $email = 'foobar@example.com'],
        ]);

        // ASSERT response contains entity.
        $response = json_decode($this->_getBodyAsString());
        $this->assertEquals($response->data->email, $email);

        // ASSERT an entity was created.
        $users = $users->find();
        $this->assertCount($userCount + 1, $users);
        $this->assertEquals($email, $users->first()->email);
    }

    public function test_it_throws_an_exception_if_the_factory_does_not_exist(): void
    {
        $this->enableCsrfToken();
        $this->post('/cypress/create', [
          'factory' => 'FooBar',
        ]);
        $this->assertResponseCode(400);
        $response = json_decode($this->_getBodyAsString());
        $this->assertStringContainsString("Factory does NOT exist: \App\Test\Factory\FooBarFactory", $response->error);
    }

    public function test_it_can_run_arbitrary_cake_commands(): void
    {
        $this->enableCsrfToken();
        $this->post('/cypress/cake', ['command' => 'routes']);
        $this->assertResponseOk();

        $response = $this->_getBodyAsString();
        $this->assertStringContainsString('Route name', $response);
        $this->assertStringContainsString('cypress-cake.cake', $response);
    }

    public function test_it_can_return_a_valid_csrf_token(): void
    {
        // $this->enableCsrfToken();
        $this->get('/cypress/csrf-token');
        $this->assertResponseOk();

        $this->assertResponseCode(200);
        $response = json_decode($this->_getBodyAsString());
        $this->assertNotNull($response->csrfToken);
    }
}
