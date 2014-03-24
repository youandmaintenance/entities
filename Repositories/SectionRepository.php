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

use \Carbon\Carbon;
use \Aura\Marshal\Manager;
use \Yam\Validators\ValidationRepository;
use \Yam\Utils\Traits\UuidGeneratorTrait;
use \Illuminate\Database\DatabaseManager;
use \Yam\Validators\Exception\ValidationException;
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
    public function __construct(Manager $manager, DatabaseManager $database, ValidationRepository $validator)
    {
        $this->db        = $database;
        $this->manager   = $manager;
        $this->validator = $validator;
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
        $this->validateSectionData($data);

        $section = $this->makeSection($data);

        $this->updateCount();

        return $section;
    }

    /**
     * update
     *
     * @param Section $section
     *
     * @access public
     * @return mixed
     */
    public function update($uuid, array $data)
    {
        $section = $this->find($uuid);

        $this->validateSectionData($data);

        return null;
    }

    /**
     * delete
     *
     * @param Section $section
     *
     * @access public
     * @return mixed
     */
    public function delete(Section $section)
    {
        $this->db->beginTransaction();

        try {
            $this->newSectionQuery()->delete($section->uuid);
            $this->manager->sections->deleteEntity($section->uuid);
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->db->commit();
    }

    /**
     * save
     *
     * @access public
     * @return void
     */
    public function save(Section $section)
    {
        $this->db->beginTransaction();

        $section->updated_at = (string)$this->newTimestamp();

        try {
            $this->newSectionQuery()->insert($section->getOwnAttributes());
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }

        // save fields:
        $this->saveFields($section->fields);

        $this->db->commit();
    }

    /**
     * saveFields
     *
     * @param Collection $fields
     *
     * @access protected
     * @return mixed
     */
    protected function saveFields(Collection $fields)
    {

    }

    /**
     * getSectionValidator
     *
     * @param array $data
     *
     * @access protected
     * @return mixed
     */
    protected function getSectionValidator(array $data = [])
    {
        $validator = $this->validator->get('yam.section');
        $validator->with($data);
        return $validator;
    }

    /**
     * getSectionValidator
     *
     * @param array $data
     *
     * @access protected
     * @return mixed
     */
    protected function getFieldValidator(array $data = [])
    {
        $validator = $this->validator->get('yam.field');
        $validator->with($data);
        return $validator;
    }

    protected function applyAttributes($resource, array $options)
    {
        if ($resource instanceof Collection) {
            foreach ($resource as $entity) {
                $this->applyAttributes($entity, $options);
            }
            return;
        }

        foreach ($options as $attribute) {
            $resource->getAttribute($attribute);
        }
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
    public function find($uuid, array $options = [])
    {
        if (!(bool)$uuid) {
            throw new \InvalidArgumentException();
        }

        if (null !== ($result = $this->getLoadedSectionsById($uuid))) {
            $this->applyAttributes($result, $options);
            return $result;
        }

        if ($this->findOnce === $uuid) {
            $this->findOnce = null;

            throw new EntityNotFoundException(
                sprintf('Section(s) with uuid %s not found', implode(', ', (array)$uuid))
            );
        }

        $callback = $this->prepareSection($uuid);

        $sections = $this->manager->sections->getCollection((array)$uuid);

        foreach ($sections as $section) {
            $section->addEagerLoadingConstraint('fields', $callback);
        }

        $this->findOnce &= $uuid;

        return $this->find($uuid, $options);
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
     * createFields
     *
     * @access protected
     * @return mixed
     */
    protected function createFields(array $fields, $uuid)
    {
        $data = [];
        foreach ($fields as $index => $field) {
            $this->addTimestamps($fields[$index], true);
            $fields[$index]['section_uuid'] = $uuid;

            $data[$index] = $this->getFieldData($fields[$index]);
        }

        return $data;
    }

    /**
     * getSectionData
     *
     * @param array $raw
     *
     * @access protected
     * @return array
     */
    protected function getSectionData(array $raw)
    {
        return $this->newSectionInstance($raw)->getData();
    }

    /**
     * getFieldData
     *
     * @param array $raw
     *
     * @access protected
     * @return array
     */
    protected function getFieldData(array $raw)
    {
        return $this->newFieldInstance($raw)->getData();
    }

    /**
     * newSectionInstance
     *
     * @param array $data
     *
     * @access protected
     * @return mixed
     */
    protected function newSectionInstance(array $data = [])
    {
        return $this->manager->sections->getEntityBuilder()->newInstance($data);
    }

    /**
     * newFieldInstance
     *
     * @param array $data
     *
     * @access protected
     * @return mixed
     */
    protected function newFieldInstance(array $data = [])
    {
        return $this->manager->fields->getEntityBuilder()->newInstance($data);
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
     *
     * @access protected
     * @return mixed
     */
    protected function newSectionQuery()
    {
        return $this->db->table('sections');
    }

    /**
     * newFieldQuery
     *
     * @access protected
     * @return mixed
     */
    protected function newFieldQuery()
    {
        return $this->db->table('fields');
    }

    /**
     * makeSlug
     *
     * @param mixed $value
     *
     * @access protected
     * @return mixed
     */
    protected function makeHandle($value)
    {
        return strtr(\Str::slug($value), ['-' => '_']);
    }

    /**
     * makeSection
     *
     * @access protected
     * @return Section
     */
    protected function makeSection(array $data, $exists = false)
    {
        $data['uuid'] = $uuid = (string)$this->makeUuid();

        $this->addTimestamps($data, true);

        $fields = $this->createFields($data['fields'], $uuid);

        unset($data['fields']);

        // transaction save section:
        $this->db->beginTransaction();

        try {
            $this->newSectionQuery()->insert($this->getSectionData($data));
        } catch (\Exception $e) {
            $this->db->rollback();
            throw new EntityCreateException($e->getMessage());
        }

        // transaction save fields:
        try {
            $this->newFieldQuery()->insert($fields);
        } catch (\Exception $e) {
            $this->db->rollback();
            throw new EntityCreateException($e->getMessage());
        }

        $this->db->commit();

        return $this->find($uuid);
    }

    /**
     * newTimestamps
     *
     * @access protected
     * @return void
     */
    protected function addTimestamps(array &$data, $new = true)
    {
        $data['created_at'] = (string)$this->newTimestamp();
        $data['updated_at'] = $new ? $data['created_at'] : (string)$this->newTimestamp();
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
            call_user_func_array([$query, is_array($uuid) ? 'whereIn' : 'where'], ['section_uuid', $uuid]);
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

    /**
     * newTimestamp
     *
     * @access protected
     * @return mixed
     */
	protected function newTimestamp()
	{
		return new Carbon;
	}

    /**
     * validateSectionData
     *
     * @param array $data
     *
     * @access protected
     * @return mixed
     */
    protected function validateSectionData(array $data)
    {
        $errored  = false;

        $validator = $this->getSectionValidator($data);

        if (!isset($data['fields']) || empty($data['fields'])) {

            $validator->fails(['fields' => ['empty fields']]);
            throw new ValidationException('validation failed', $validator->errors());
        }

        $messages = [];

        if(!$validator->validate($data)) {
            $messages['section'] = $validator->getErrors()->toArray();
        }

        $fieldValidator = $this->getFieldValidator();

        foreach ($data['fields'] as $key => $field) {
            if (!$fieldValidator->validate($field)) {
                $messages['fields'][] = $fieldValidator->getErrors()->toArray();
            }
        }

        if (!empty($messages)) {
            var_dump($messages);
            die;
            throw new ValidationException('validation failed', $messages);
        }
    }
}
