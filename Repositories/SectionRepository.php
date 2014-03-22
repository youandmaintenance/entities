<?php

/**
 * This File is part of the Yam\Entities\Repositories package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Yam\Entities\Repositories;

use \Aura\Marshal\Manager;
use \Yam\Utilities\Traits\UuidGeneratorTrait;
use \Illuminate\Database\DatabaseManager;
use \Yam\Entities\Exception\EntityCreateException;

/**
 * @class SectionRepository
 *
 * @package Yam\Entities\Repositories
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
class SectionRepository
{
    use UuidGeneratorTrait {
        UuidGeneratorTrait::createUuid as private makeUuid;
    }

    /**
     * manager
     *
     * @var \Aura\Marshal\Manager
     */
    protected $manager;

    /**
     * connection
     *
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $db;

    /**
     * count
     *
     * @var mixed
     */
    protected $count;

    /**
     * findOnce
     *
     * @var mixed
     */
    protected $findOnce;

    /**
     * @param Manager $manager
     *
     * @access public
     */
    public function __construct(Manager $manager, DatabaseManager $database)
    {
        $this->db      = $database;
        $this->manager = $manager;
    }

    /**
     * create
     *
     * @param array $data
     *
     * @access public
     * @return mixed
     */
    public function create(array $data)
    {
        $section = $this->makeSection($data);

        $this->db->beginTransaction();

        $section->setAttribute('uuid', $this->makeUuid());

        try {
            $this->newSectionQuery()->insert($section->getOwnAttributes());
        } catch (\Exception $e) {
            $this->db->rollback();
            throw new EntityCreateException($e->getMessage());
        }

        $this->db->commit();
        $this->updateCount();

        return $section;
    }

    /**
     * fetch
     *
     * @param string|array $uuid unique id or a list of ids
     *
     * @throws \InvalidArgumentException
     * @access public
     * @return mixed either a Section or a SectionCollection
     */
    public function find($uuid)
    {
        if (!(bool)$uuid) {
            throw new \InvalidArgumentException();
        }

        if (null !== ($result = $this->getLoadedSectionsById($uuid))) {
            return $result;
        }

        if ($this->findOnce === $uuid) {
            $this->findOnce = null;
            return null;
        }

        $callback = $this->prepareSection($uuid);

        $sections = $this->manager->sections->getCollection((array)$uuid);

        foreach ($sections as $section) {
            $section->addEagerLoadingConstraint('fields', $callback);
        }

        $this->findOnce &= $uuid;

        return $this->find($uuid);
    }

    /**
     * fetchAll
     *
     * @access public
     * @return Collection
     */
    public function findAll()
    {
        if (!$this->count) {

            $callback = $this->prepareSection();
            $sections = $this->manager->sections->getAllEntities();

            foreach ($sections as $section) {
                $section->addEagerLoadingConstraint('fields', $callback);
            }

        } else {
            $sections = $this->manager->sections->getAllEntities();
        }

        $this->count = $this->manager->sections->count();
        return $this->makeCollection($sections);
    }

    /**
     * makeCollection
     *
     * @param array $data
     *
     * @access protected
     * @return Collection
     */
    protected function makeCollection(array $data)
    {
        return $this->manager->sections->getCollectionBuilder()->newInstance($data);
    }

    /**
     * makeSection
     *
     * @access protected
     * @return Section
     */
    protected function makeSection(array $data)
    {
        return $this->manager->sections->getEntityBuilder()->newInstance($data);
    }

    /**
     * findByHandle
     *
     * @param mixed $handle
     *
     * @access public
     * @return mixed
     */
    public function findByHandle($value)
    {
        if ($this->count !== $this->manager->sections->count()) {
            $this->findAll();
        }

        return $this->manager->sections->getEntityByField('handle', $value);
    }

    /**
     * newSectionQuery
     *
     * @access public
     * @return mixed
     */
    public function newSectionQuery()
    {
        return $this->db->table('sections');
    }

    /**
     * newFieldQuery
     *
     * @access public
     * @return mixed
     */
    public function newFieldQuery()
    {
        return $this->db->table('fields');
    }

    /**
     * getLoadedSectionsById
     *
     * @param mixed $uuid
     *
     * @access protected
     * @return mixed
     */
    protected function getLoadedSectionsById($uuid)
    {
        if (is_array($uuid)) {
            return $this->manager->sections->getCollection($uuid);
        }

        return $this->manager->sections->getEntity($uuid);
    }

    /**
     * createEglCallback
     *
     * @param mixed $query
     *
     * @access protected
     * @return mixed
     */
    protected function createEglCallback($query)
    {
        return function () use ($query) {
            static $called;
            if ($called) {
                return;
            }
            $called = true;
            $result = $this->execQuery($query);
            $this->manager->fields->load($result);
        };
    }

    /**
     * resultToArray
     *
     * @param mixed $data
     *
     * @access protected
     * @return mixed
     */
    protected function resultToArray($data)
    {
        return json_decode(json_encode($data), true);
    }


    /**
     * getQueries
     *
     * @param mixed $id
     *
     * @access protected
     * @return array
     */
    protected function getQueries($id = null)
    {
        return [$this->getSectionQuery($id), $this->getFieldsQuery($id)];
    }

    /**
     * prepareSection
     *
     * @param mixed $uuid
     *
     * @access protected
     * @return mixed
     */
    protected function prepareSection($uuid = null)
    {
        list($sq, $fq) = $this->getQueries($uuid);

        $this->manager->sections->load($this->execQuery($sq));

        return $this->createEglCallback($fq);
    }

    /**
     * getSectionQuery
     *
     * @param mixed $uuid
     *
     * @access protected
     * @return mixed
     */
    protected function getSectionQuery($uuid = null)
    {
        $query = $this->newSectionQuery();

        if (null !== $uuid) {
            $where = is_array($uuid) ? 'whereIn' : 'where';
            $query->{$where}('uuid', $uuid);
        }

        return $query;
    }

    /**
     * getFieldsQueiry
     *
     * @param mixed $sectionUuid
     *
     * @access protected
     * @return mixed
     */
    protected function getFieldsQuery($uuid = null)
    {
        $query = $this->newFieldQuery();

        if (null !== $uuid) {
            $where = is_array($uuid) ? 'whereIn' : 'where';
            $query->{$where}('section_uuid', $uuid);
        }

        return $query;
    }

    /**
     * execQuery
     *
     * @param mixed $query
     *
     * @access protected
     * @return array
     */
    protected function execQuery($query)
    {
        if ($result = $query->get()) {
            return $this->resultToArray($result);
        }
        return [];
    }

    /**
     * updateCount
     *
     * @access protected
     * @return void
     */
    protected function updateCount()
    {
        $this->count = $this->manager->sections->count();
    }
}
