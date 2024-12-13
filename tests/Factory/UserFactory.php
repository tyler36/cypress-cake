<?php
declare(strict_types=1);

namespace Tyler36\CypressCake\Test\Factory;

use CakephpFixtureFactories\Factory\BaseFactory as CakephpBaseFactory;
use Faker\Generator;

/**
 * UserFactory
 *
 * @method \App\Model\Entity\User getEntity()
 * @method array<\App\Model\Entity\User> getEntities()
 * @method \App\Model\Entity\User|array<\App\Model\Entity\User> persist()
 * @method static \App\Model\Entity\User get(mixed $primaryKey, array $options = [])
 */
class UserFactory extends CakephpBaseFactory
{
    /**
     * Defines the Table Registry used to generate entities with
     *
     * @return string
     */
    protected function getRootTableRegistryName(): string
    {
        return 'Users';
    }

    /**
     * Defines the factory's default values. This is useful for
     * not nullable fields. You may use methods of the present factory here too.
     *
     * @return void
     */
    protected function setDefaultTemplate(): void
    {
        $this->setDefaultData(function (Generator $faker) {
            // Generate new "random" seed.
            $faker->seed(microtime(true) * 10000);

            return [
                'name' => $faker->name(),
                'email' => $faker->safeEmail(),
                // Password is automatically hashed by 'UserEntity::_setPassword'
                'password' => 'password',
                'created' => $faker->dateTimeThisYear(),
                'modified' => $faker->dateTimeThisYear(),
            ];
        });
    }
}
