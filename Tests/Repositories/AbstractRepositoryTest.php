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
use \Yam\Utils\Tests\Traits\DatabaseAwareTestTrait;
use \Illuminate\Database\Connectors\ConnectionFactory;
use \Illuminate\Database\Schema\Builder as SchemaBuilder;

abstract class AbstractRepositoryTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseAwareTestTrait;

    /**
     * repository
     *
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        $this->prepareDatabase();
        $this->migrate($this->schema);
        $this->seed($this->db);
    }

    /**
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        m::close();
    }

    /**
     * Run DB migration before each test.
     *
     * @param SchemaBuilder $schema
     *
     * @access protected
     * @abstract
     * @return void
     */
    abstract protected function migrate(SchemaBuilder $schema);

    /**
     * Run DB seeds before each test.
     *
     * @param DatabaseManager $db
     *
     * @access protected
     * @abstract
     * @return void
     */
    abstract protected function seed(DatabaseManager $db);
}
