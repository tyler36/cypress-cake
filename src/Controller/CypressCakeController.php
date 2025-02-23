<?php
declare(strict_types=1);

namespace Tyler36\CypressCake\Controller;

use App\Controller\AppController;
use Cake\Datasource\ConnectionManager;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use InvalidArgumentException;
use Tyler36\CypressCake\DatabaseHelperTrait;

/**
 * Class CypressController.
 */
class CypressCakeController extends AppController
{
    use DatabaseHelperTrait;
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        // Disable components for API access.
        $this->noFormProtectionComponent = true;
        $this->noAuthenticationComponent = true;

        parent::initialize();
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event An Event instance
     * @return \Cake\Http\Response|null|void
     * @link https://book.cakephp.org/5/en/controllers.html#request-life-cycle-callbacks
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        // Disable default authentication.
        if (isset($this->Authentication)) {
            $this->Authentication->allowUnauthenticated([
                'importDatabase', 'clearDatabase', 'csrfToken', 'add', 'cake',
            ]);
        }

        // Do NOT try to autoload a view.
        $this->autoRender = false;
    }

    /**
     * Clear all data from database.
     *
     * @return \Cake\Http\Response
     */
    public function clearDatabase(): Response
    {
        self::truncateAllTables();

        return $this->response
            ->withType('application/json')
            ->withStringBody($this->encodeBody(['data' => true]));
    }

    /**
     * Import database from 'filename' or env('SQL_TESTING_BASE_DUMP')
     * Paths are relative to project root.
     *
     * @return \Cake\Http\Response
     */
    public function importDatabase(): Response
    {
        $filename = strval($this->getRequest()->getData('filename') ?? env('SQL_TESTING_BASE_DUMP'));
        if (!$filename) {
            throw new InvalidArgumentException("DB filename is invalid: '$filename'");
        }

        if (!file_exists($filename)) {
            throw new InvalidArgumentException("DB backup file not found: '{$filename}'");
        }

        $sql = file_get_contents($filename);
        if (!$sql) {
            return $this->response
                ->withType('application/json')
                ->withStringBody($this->encodeBody(['data' => true]));
        }

        /** @var \Cake\Database\Connection $connection */
        $connection = ConnectionManager::get('default');
        if (self::isPostgres($connection)) {
            $db = $connection->config()['host'];
            `psql $db < $filename`;
        }

        if (!self::isPostgres($connection)) {
            $connection->execute($sql);
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody($this->encodeBody(['data' => true]));
    }

    /**
     * Create a new model based on an existing factory.
     *
     * @return \Cake\Http\Response
     */
    public function create(): Response
    {
        $data = $this->getRequest()->getData();

        $factory = '\App\Test\Factory\\' . $data['factory'] . 'Factory';
        if (!class_exists($factory)) {
            return $this->response
                ->withStatus(400)
                ->withType('application/json')
                ->withStringBody($this->encodeBody(['error' => "Factory does NOT exist: $factory"]));
        }

        $model = $factory::make($data['attributes'] ?? [])->disablePrimaryKeyOffset()->persist();

        return $this->response
            ->withType('application/json')
            ->withStringBody($this->encodeBody(['data' => $model]));
    }

    /**
     * Get a valid CSRF token.
     *
     * @return \Cake\Http\Response
     */
    public function csrfToken(): Response
    {
        $token = $this->request->getAttribute('csrfToken');

        return $this->response
            ->withType('application/json')
            ->withStringBody($this->encodeBody(['csrfToken' => $token]));
    }

    /**
     * Run arbitrary 'cake' commands.
     *
     * @return \Cake\Http\Response
     */
    public function cake(): Response
    {
        $data = $this->getRequest()->getData();

        $output = shell_exec(ROOT . '/bin/cake ' . $data['command']);

        return $this->response
            ->withType('application/json')
            ->withStringBody($this->encodeBody(['data' => $output]));
    }

    /**
     * Encode data array for response.
     *
     * @param non-empty-array<mixed> $body
     * @return string
     */
    protected function encodeBody(array $body): string
    {
        return json_encode($body) ?: '';
    }
}
