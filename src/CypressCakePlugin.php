<?php
declare(strict_types=1);

namespace Tyler36\CypressCake;

use Cake\Core\BasePlugin;
use Cake\Core\ContainerInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;

/**
 * Plugin for CypressCake
 */
class CypressCakePlugin extends BasePlugin
{
    /**
     * Load all the plugin configuration and bootstrap logic.
     *
     * The host application is provided as an argument. This allows you to load
     * additional plugin dependencies, or attach events.
     *
     * @param \Cake\Core\PluginApplicationInterface $app The host application
     * @return void
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
    }

    /**
     * Add routes for the plugin.
     *
     * If your plugin has many routes and you would like to isolate them into a separate file,
     * you can create `$plugin/config/routes.php` and delete this method.
     *
     * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
     * @return void
     */
    public function routes(RouteBuilder $routes): void
    {
        $routes->plugin(
            'Tyler36/CypressCake',
            ['path' => '/cypress'],
            function (RouteBuilder $builder): void {
                $builder->post(
                    '/clear-database',
                    ['controller' => 'CypressCake','action' => 'clearDatabase'],
                    'cypress-cake.clear-database'
                );
                $builder->post(
                    '/import-database',
                    ['controller' => 'CypressCake', 'action' => 'importDatabase'],
                    'cypress-cake.import-database'
                );
                $builder->get(
                    '/csrf-token',
                    ['controller' => 'CypressCake', 'action' => 'csrfToken'],
                    'cypress-cake.csrf-token',
                );
                $builder->post('/create', ['controller' => 'CypressCake', 'action' => 'create'], 'cypress-cake.create');
                $builder->post('/cake', ['controller' => 'CypressCake', 'action' => 'cake'], 'cypress-cake.cake');

                $builder->fallbacks();
            }
        );
    }

    /**
     * Add middleware for the plugin.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to update.
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        // Add your middlewares here

        return $middlewareQueue;
    }

    /**
     * Register application container services.
     *
     * @param \Cake\Core\ContainerInterface $container The Container to update.
     * @return void
     * @link https://book.cakephp.org/4/en/development/dependency-injection.html#dependency-injection
     */
    public function services(ContainerInterface $container): void
    {
        // Add your services here
    }
}
