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
use \Carbon\Carbon;
use \Faker\Factory as Faker;
use \Illuminate\Support\Str;
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

    protected $letSectionValidatorPass = true;

    protected $letFieldValidatorPass = true;

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
        parent::setUp();

        $this->mocks = [];
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
        $repo = $this->getTheRealThing();

        $section = $repo->create([
            'name'        => 'FooSection',
            'handle'      => 'foo_section',
            'versionable' => false,
            'fields' => $this->getFieldData(4)
        ]);

        $this->assertInstanceof('Yam\Entities\Section', $section);
    }

    /**
     * @test
     */
    public function aSectionSouldBeInTheSystemAfterItsBeenCreated()
    {
        $repo = $this->getTheRealThing();

        $section = $repo->create([
            'name'        => 'FooSection',
            'handle'      => 'foo_section',
            'versionable' => false,
            'fields' => $this->getFieldData(2)
        ]);

        $this->assertInstanceof('Yam\Entities\Section', $repo->find($section->uuid));
    }

    /**
     * @test
     */
    public function fieldsSouldBeLoadedAfterCreatingASection()
    {
        $repo = $this->getTheRealThing();

        $countFields = 4;

        $section = $repo->create([
            'name'        => 'FooSection',
            'handle'      => 'foo_section',
            'versionable' => false,
            'fields' => $this->getFieldData($countFields)
        ]);

        $this->assertInstanceof('Yam\Entities\Collection', $section->fields);

        $this->assertEquals(
            $countFields,
            count($section->fields),
            sprintf('Collection should have %d fields', $countFields)
        );
    }

    /**
     * @test
     */
    public function itShouldFailCreatingIfFieldsAreMissing()
    {
        $repo = $this->getTheRealThing();

        try {
            $section = $repo->create([]);
        } catch (\Yam\Validators\Exception\ValidationException $e) {
            $this->assertInstanceof('Yam\Validators\Exception\ValidationException', $e);
            return;
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
            return;
        }

        $this->fail('A validation exception should be thrown');
    }

    /**
     * @test
     */
    public function itShouldFindSectionsByTheirUUID()
    {
        $ids = $this->seedSectionsAndReturnUuids(2, 2);

        $repo = $this->getTheRealThing();
        $this->assertInstanceof('Yam\Entities\Section', $repo->find($ids[0]));

        $repo = $this->getTheRealThing();
        $this->assertInstanceof('Yam\Entities\Collection', $collection = $repo->find($ids));

        $this->assertEquals(
            2,
            count($collection[1]->fields),
            sprintf('Collection should have %d fields', 2)
        );
    }

    /**
     * @test
     */
    public function itShouldSuccessfullyUpdateAsection()
    {
        $ids = $this->seedSectionsAndReturnUuids(1, 2);

        $repo = $this->getTheRealThing();

        $name = Faker::create()->name;

        $section = $repo->find($ids[0]);

        if ($name === $section->name) {
            $name =  $name.rand();
        }
        $section->name = $name;

        $repo->save($section);
        $this->assertSame($name, $section->name);

        $repo = $this->getTheRealThing();
        $section = $repo->find($ids[0]);

        $this->assertSame($name, $section->name);
    }

    /**
     * @test
     */
    public function itShouldSuccessfullyUpdateNewData()
    {
        $ids = $this->seedSectionsAndReturnUuids(1, 1);

        $repo = $this->getTheRealThing();

        $section = $repo->find($ids[0], ['fields']);

        $data = $section->toArray();

        $fields = $this->getFieldData(5);

        foreach ($fields as $f) {
            $data['fields'][] = $f;
        }

        $repo->update($section->uuid, $data);

        foreach ($section->fields as $f) {
            if ($f->section_uuid !== $ids[0]) {
                $this->fail('new fields should be created with the same parent uuid');
            }
        }

        $this->assertEquals(6, count($section->fields));
    }

    /**
     * Create the Repository with its realy dependancies,
     *
     * @access protected
     * @return SectionRepository
     */
    protected function getTheRealThing()
    {
        $manager = new \Aura\Marshal\Manager(
            new \Yam\MarshalBridge\Type\Builder,
            new \Aura\Marshal\Relation\Builder
        );

        $this->setUpDataManager($manager);

        $repo = new SectionRepository($manager, $this->db, $this->getValidatorMock());

        return $repo;
    }

    /**
     * Create some fake section and field data and seed the database.
     *
     * Will return and array of fake uuids that have been seeded.
     *
     * @param int $countSections
     * @param int $countFields
     *
     * @access protected
     * @return array
     */
    protected function seedSectionsAndReturnUuids($countSections = 1, $countFields = 1)
    {
        $fields = [];
        $sections = [];
        $uuids = [];

        $faker   = Faker::create();
        $time = (string)(new Carbon);

        while ($countSections > 0) {
            $uudis[] = $uuid  = $faker->uuid;
            $name    = $faker->name;
            $handle  = strtr(Str::slug($name), ['-', '_']);

            $f = $this->getFieldData($countFields);

            foreach ($f as &$fd) {
                $fd['settings'] = json_encode($fd['settings']);
                $fd['created_at'] = $time;
                $fd['updated_at'] = $time;
                $fd['section_uuid'] = $uuid;
            }

            $fields[] = $f;

            $sec = [
                'name'   => $name,
                'handle' => $handle,
                'versionable' => false,
                'created_at' => $time,
                'updated_at' => $time,
                'uuid'       => $uuid
            ];

            $sections[] = $sec;
            $countSections--;
        }

        $this->db->table('sections')->insert($sections);

        foreach ($fields as $fieldData) {
            $this->db->table('fields')->insert($fieldData);
        }

        return $uudis;
    }

    /**
     * Generate some fake field data.
     *
     * @param int $count
     *
     * @access protected
     * @return array
     */
    protected function getFieldData($count = 1)
    {
        $faker   = Faker::create();
        $fields = [];
        while ($count > 0) {
            $name    = $faker->name;
            $handle  = strtr(Str::slug($name), ['-', '_']);
            $fields[] = [
                'label' => $name,
                'handle' => $handle,
                'type_id' => 1,
                'sorting' => $count + 1,
                'position' => $count % 2 === 0 ? 'left' : 'right',
                'settings' => []
            ];
            $count--;
        }

        return $fields;
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
        $validatorA = m::mock('Some\Validator');
        $validatorA->shouldReceive('with');

        $validatorA->shouldReceive('validate')->andReturnUsing(function () {
            return $this->letSectionValidatorPass;
        });

        $validatorA->shouldReceive('fails')->andReturnUsing(function () {
            return !$this->letSectionValidatorPass;
        });

        $validatorB = m::mock('Some\Validator');
        $validatorB->shouldReceive('with');

        $validatorB->shouldReceive('validate')->andReturnUsing(function () {
            return $this->letFieldValidatorPass;
        });

        $validatorB->shouldReceive('fails')->andReturnUsing(function () {
            return !$this->letFieldValidatorPass;
        });

        $validatorA->shouldReceive('getErrors')->andReturn([]);
        $validatorB->shouldReceive('getErrors')->andReturn([]);

        $repo =  m::mock('Yam\Validators\ValidationRepository');

        $repo->shouldReceive('get')->with('yam.section')->andReturn($validatorA);
        $repo->shouldReceive('get')->with('yam.field')->andReturn($validatorB);

        return $repo;
    }

    /**
     * getRepo
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
     * Migrate the databse with initial tables.
     *
     * This will run before each test.
     *
     * @param SchemaBuilder $schema
     *
     * @access protected
     * @return void
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
            $table->enum('position', ['left', 'right']);
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
        $collectionBuilder = new \Yam\Entities\Builders\CollectionBuilder;

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

    protected function seed($db)
    {
        $db->table('field_types')->insert($this->getFieldTypes());
    }

    protected function getFieldTypes()
    {
        $fieldTypes = [
            [
                'name'      => 'Input',
                'namespace' => 'Yam\Entities\FieldTypes',
                'type'      => 'string',
                'defaults'  => '{"required":false}'
            ],
            [
                'name'      => 'Markdown',
                'namespace' => 'Yam\Entities\FieldTypes',
                'type'      => 'text',
                'defaults'  => '{"required":false}'
            ],
            [
                'name'      => 'Textbox',
                'namespace' => 'Yam\Entities\FieldTypes',
                'type'      => 'text',
                'defaults'  => '{"required":false}'
            ],
            [
                'name'      => 'Checkbox',
                'namespace' => 'Yam\Entities\FieldTypes',
                'type'      => 'boolean',
                'defaults'  => '{"required":false}'
            ],
            [
                'name'      => 'Number',
                'namespace' => 'Yam\Entities\FieldTypes',
                'type'      => 'string',
                'defaults'  => '{"required":false}'
            ],
            [
                'name'      => 'Date',
                'namespace' => 'Yam\Entities\FieldTypes',
                'type'      => 'datetime',
                'defaults'  => '{"required":false}'
            ]
        ];

        $time = new Carbon();

        foreach ($fieldTypes as &$ft) {
            $ft['created_at'] = (string)$time;
            $ft['updated_at'] = (string)$time;
        }

        return $fieldTypes;
    }
}
