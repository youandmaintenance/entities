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
use \Aura\Marshal\Manager;
use \Yam\Validators\ValidationRepository;
use \Illuminate\Database\DatabaseManager;
use \Yam\Entities\Repositories\SectionRepository;
use \Illuminate\Database\Schema\Builder as SchemaBuilder;

use Illuminate\Database\Connectors\ConnectionFactory;

/**
 * @class SectionRepositoryTest extends AbstractRepositoryTest
 * @see AbstractRepositoryTest
 *
 * @package Yam\Entities\Tests\Repositories
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
class SectionRepositoryTest extends AbstractRepositoryTest
{
    /**
     * mocks
     *
     * @var mixed
     */
    protected $mocks;

    /**
     * repository
     *
     * @var mixed
     */
    protected $repository;

    /**
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->mocks = null;
        m::close();
    }

    /**
     * @access protected
     * @return mixed
     */
    protected function setUp()
    {
        $this->mocks = [];
        $this->manager = new \Aura\Marshal\Manager();
    }

    /**
     * @test
     */
    public function itShouldBeInstatiable()
    {
        $repo = new SectionRepository(
            $this->getManagetMock(),
            $this->getDBMock(),
            $this->getValidatorMock()
        );

        $this->assertInstanceof('Yam\Entities\Repositories\SectionRepository', $repo);
    }

    /**
     * @test
     */
    public function itShouldCreateANewSection()
    {
        $this->prepareDatabase();
    }

    /**
     * getMockFrom
     *
     * @param mixed $type
     *
     * @access protected
     * @return mixed
     */
    protected function getMockFrom($type)
    {
        return $this->mocks[$type];
    }

    /**
     * getDBMock
     *
     * @access protected
     * @return mixed
     */
    protected function getDBMock()
    {
        return m::mock('Illuminate\Database\DatabaseManager');
    }

    /**
     * getManagetMock
     *
     * @access protected
     * @return mixed
     */
    protected function getManagetMock()
    {
        return m::mock('Aura\Marshal\Manager');
    }

    /**
     * getValidatorMock
     *
     * @access protected
     * @return mixed
     */
    protected function getValidatorMock()
    {
        return m::mock('Yam\Validators\ValidationRepository');
    }

    /**
     * getRepo
     *
     *
     * @access protected
     * @return mixed
     */
    protected function getRepo()
    {
        $this->mocks['validator']  = $validator = $this->getValidatorMock();
        $this->mocks['manager']    = $manager   = $this->getManagetMock();
        $this->mocks['db']         = $db        = $this->getDBMock();

        return new SectionRepository($manager, $db, $validator);
    }

    /**
     * migrate
     *
     * @param SchemaBuilder $schema
     *
     * @access protected
     * @return mixed
     */
    protected function migrate(SchemaBuilder $schema)
    {
        $schema->create('sections', function ($table) {
            $table->increments('id');
            $table->string('name', 100);
            $table->string('handle', 100);
            $table->boolean('versionable');
            $table->timestamps();
            $table->char('uuid', 36);
            $table->index('id');
        });

        $schema->create('fields', function ($table) {
            $table->increments('id');
            $table->char('section_uuid', 36);
            $table->integer('type_id');
            $table->integer('sorting');
            $table->string('label', 100);
            $table->string('handle', 100);
            $table->string('position', 100);
            $table->text('settings');
            $table->timestamps();
        });

        $schema->create('field_types', function ($table) {
            $table->increments('id');
            $table->string('name', 100);
            $table->string('namespace', 100);
            $table->string('type', 100);
            $table->text('defaults');
            $table->timestamps();
        });
    }

    /**
     * setUpDataManager
     *
     * @param mixed $manager
     *
     * @access protected
     * @return mixed
     */
    protected function setUpDataManager($manager)
    {
        $manager->setType(
            'sections',
            [
                'identity_field'     => 'uuid',
                'entity_builder'     => new \Yam\Entities\Builders\SectionBuilder,
                'collection_builder' => $collectionBuilder
            ]
        );

        $manager->setType(
            'fields',
            [
                'identity_field' => 'id',
                'entity_builder'     => new \Yam\Entities\Builders\SectionFieldsBuilder,
                'collection_builder' => $collectionBuilder
            ]
        );

        $manager->setRelation('sections', 'fields', [
            'relationship'  => 'has_many',
            'native_field'  => 'uuid',
            'foreign_field' => 'section_uuid'
        ]);

        $manager->setRelation('fields', 'section', [
            'relationship'  => 'belongs_to',
            'foreign_type'  => 'sections',
            'native_field'  => 'section_uuid',
            'foreign_field' => 'uuid'
        ]);
    }
}
