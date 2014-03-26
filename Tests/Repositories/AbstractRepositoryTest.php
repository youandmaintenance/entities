<?php

/**
 * This File is part of the Yam\Entities\Tests\Repositories package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Yam\Entities\Tests\Repositories;

use \Mockery as m;
use \PDO;
use \Illuminate\Container\Container;
use \Illuminate\Database\DatabaseManager;
use \Illuminate\Database\Connectors\ConnectionFactory;
use \Illuminate\Database\Schema\Builder as SchemaBuilder;

abstract class AbstractRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * repository
     *
     * @var mixed
     */
    protected $repository;

    /**
     * db
     *
     * @var mixed
     */
    protected $db;

    /**
     * schema
     *
     * @var mixed
     */
    protected $schema;

    /**
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        m::close();
    }

    protected function setUp()
    {
        $this->prepareDatabase();
        $this->migrate($this->schema);
    }

    /**
     * prepareDatabase
     *
     * @access protected
     * @return mixed
     */
    protected function prepareDatabase()
    {
        $config = [
            'database.fetch' => PDO::FETCH_CLASS,
            'database.default' => 'sqlite',
            'database.connections' => [
                'sqlite' => [
                    'driver'   => 'sqlite',
                    'database' => ':memory:',
                    'prefix'   => '',
                ]
            ]
        ];

        $container = m::mock('Illuminate\Container\Container');
        $container->shouldReceive('bound')->andReturn(false);

        $container->shouldReceive('offsetGet')->with('config')->andReturn($config);

        $db = new DatabaseManager(
            $container,
            new ConnectionFactory($container)
        );

        $this->db = $db;
        $this->schema = new SchemaBuilder($this->db->connection('sqlite'));
    }

    abstract protected function migrate(SchemaBuilder $schema);
}
