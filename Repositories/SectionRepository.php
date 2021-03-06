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
use \Yam\Entities\Section;
use \Yam\Entities\Collection;
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
class SectionRepository implements RepositoryInterface, SaveableSectionInterface, DeletableSectionInterface
{
    use UuidGeneratorTrait {
        UuidGeneratorTrait::createUuid as private makeUuid;
    }

    /**
     * @var string
     */
    const TABLE_SECTIONS = 'sections';

    /**
     * @var string
     */
    const TABLE_FIELDS = 'fields';

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
     * @param \Aura\Marshal\Manager                $manager   the datamapper
     * @param \Illuminate\Database\DatabaseManager $database  the database
     * @param \Yam\Validators\ValidationRepository $validator the validator
     *
     * @access public
     * @return mixed
     */
    public function __construct(Manager $manager, DatabaseManager $database, ValidationRepository $validator)
    {
        $this->db        = $database;
        $this->manager   = $manager;
        $this->validator = $validator;
    }

    /**
     * Find a section by its id.
     *
     * @param string|array $uuid unique id or a list of ids
     * @param array        $options related fields to load into the result
     *
     * @throws \Yam\Entities\Exception\EntityNotFoundException
     * @access public
     * @return \Yam\Entities\Section | \Yam\Entities\Collection
     * either a Section or a SectionCollection
     */
    public function find($uuid, array $options = [])
    {
        if (!(bool)$uuid) {
            throw new \InvalidArgumentException();
        }

        // will throw an exception if uuids are not present on the collection:
        try {
            if (null !== ($result = $this->getLoadedSectionsById($uuid))) {
                $this->applyAttributes($result, $options);
                return $result;
            }
        } catch (\Exception $e) {}

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
     * Find all available sections in the system.
     *
     * @param array $options related fields to load into the result
     *
     * @throws \Yam\Entities\Exception\EntityNotFoundException
     * @access public
     * @return \Yam\Entities\Collection
     */
    public function findAll(array $options = [])
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

        if ($this->count === 0) {
            throw new EntityNotFoundException('No sections found.');
        }

        $this->applyAttributes($collection = $this->makeCollection($sections), $options);

        return $collection;
    }

    /**
     * Creates a new section from an input data array
     *
     * @param array $data the input data.
     *
     * @throws \Yam\Validators\Exception\ValidationException
     * @throws \Yam\Entities\Exception\EntityCreateException
     * @access public
     * @return \Yam\Entities\Section instance of Section
     */
    public function create(array $data)
    {
        $this->validateSectionData($data);

        $section = $this->makeSection($data, false);

        $this->updateCount();

        return $section;
    }

    /**
     * Updates an existing section from an input data array and a given uuid.
     *
     * @param string $uuid the section uuid that is going ot be updated.
     * @param array  $data the input data.
     *
     * @throws \Yam\Validators\Exception\ValidationException
     * @access public
     * @return \Yam\Entities\Section instance of Section
     */
    public function update($uuid, array $data)
    {
        $section = $this->find($uuid, ['fields']);

        $data = array_merge($section->getData(), $data);

        $this->validateSectionData($data);

        $fields = isset($data['fields']) ? $data['fields'] : false;
        unset($data['fields']);

        $section->fill($data);

        if ($section->isDirty()) {
            $section->updated_at = (string)$this->newTimestamp();
            $this->persistsSection($section);
        }

        if ($fields) {
            $this->updateFields($section->fields, $fields, $section->uuid);
        }

        return $section;
    }

    /**
     * updateFields
     *
     * @param Collection $fields
     * @param array $fieldData
     * @param mixed $uuid
     *
     * @access protected
     * @return mixed
     */
    protected function updateFields(Collection $fields, array $fieldData, $uuid)
    {
        $validateData = [];

        foreach ($fieldData as $fData) {

            if (isset($fData['id'])) {
                $field = $this->manager->fields->getEntity($fData['id']);
                $validateData[] = array_merge($field->getData(), $fData);
            } else {
                $validateData[] = $fData;
            }
        }

        $this->validateFieldData($validateData, true);
        $diff = $this->getFieldDiff($fields, $validateData, $uuid);

        $this->db->beginTransaction();

        // updated fields:
        try {
            foreach ($diff['updated'] as $updated) {
                $this->newFieldQuery()
                    ->where('id', $updated['id'])
                    ->update($updated);
            }
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }

        // deleted fields:
        if ($deleted = (bool)$diff['deleted']) {
            try {
                $this->newFieldQuery()
                    ->whereIn('id', $diff['deleted'])
                    ->delete();
            } catch (\Exception $e) {
                $this->db->rollback();
                throw $e;
            }
        }

        // new fields:
        if ($new = (bool)$diff['new']) {
            try {
                $this->newFieldQuery()->insert($diff['new']);
            } catch (\Exception $e) {
                $this->db->rollback();
                throw $e;
            }
        }

        $this->db->commit();

        // update new fields on the collection.
        if ($new || $deleted) {
            $result = $this->execQuery($this->getFieldsQuery($uuid));
            $this->manager->fields->load($result);
            $fields->replace($this->manager->fields->getCollection(array_pluck($result, 'id')));
        }

        // update removed fields on the collection.
        if ($deleted) {
            foreach($diff['deleted'] as $id) {
                $this->manager->fields->removeEntity($id);
            }
        }
    }

    /**
     * persistsSection
     *
     * @param Section $section
     *
     * @access protected
     * @return mixed
     */
    protected function persistsSection(Section $section)
    {
        $this->db->beginTransaction();

        $update = (bool)$section->id;
        $q = $this->newSectionQuery();

        try {
            if ($update) {
                $q->where('uuid', $section->uuid)
                ->update($section->getData());
            } else {
                $q->insert($section->getData());
            }

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->db->commit();
    }

    /**
     * Delete a Section entity from the system.
     *
     * @param \Yam\Entities\Section $section
     *
     * @access public
     * @return void
     */
    public function delete($uuid)
    {
        $section = $this->find($uuid);
        $fields = $section->fields;

        $this->db->beginTransaction();

        try {
            $this->newSectionQuery()
                ->where('uuid', $uuid)
                ->delete();
            $this->manager->sections->removeEntity($uuid);
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }

        try {
            $this->newFieldQuery()
                ->where('section_uuid', $uuid)
                ->delete();

            foreach ($fields->pluck('id') as $id) {
                $this->manager->fields->removeEntity($id);
            }

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->db->commit();
    }

    /**
     * Updates an existing Section entity and persits its data.
     *
     * Sections must be derieved from the sectionrepository.
     *
     * @param Section $section
     *
     * @throws \Yam\Validators\Exception\ValidationException if $section wasn't
     * derived from this repository.
     * @access public
     * @return void
     */
    public function save(Section $section)
    {
        if ($section !== $this->find($section->uuid)) {
            throw new \InvalidArgumentException(
                sprintf('Sections must be derived from %s', get_class($this))
            );
        }

        $section->fields;
        $this->update($section->uuid, $section->toArray());
    }

    /**
     * deleteSection
     *
     * @param \Yam\Entities\Section $section
     *
     * @access public
     * @return mixed
     */
    public function deleteSection(Section $section)
    {
        return $this->delete($section->uuid);
    }

    /**
     * getSectionValidator
     *
     * @param array $data
     *
     * @access protected
     * @return Validator
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
     * @return Validator
     */
    protected function getFieldValidator(array $data = [])
    {
        $validator = $this->validator->get('yam.field');
        $validator->with($data);
        return $validator;
    }

    /**
     * applyAttributes
     *
     * @param mixed $resource
     * @param array $options
     *
     * @access protected
     * @return mixed
     */
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
     * Initialize a new query on the sections table.
     *
     * @access protected
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newSectionQuery()
    {
        return $this->db->table(static::TABLE_SECTIONS);
    }

    /**
     * Initialize a new query on the fields table.
     *
     * @access protected
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newFieldQuery()
    {
        return $this->db->table(static::TABLE_FIELDS);
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
     * @return \Yam\Entities\Section
     */
    protected function makeSection(array $data, $exists = false)
    {
        $this->addTimestamps($data, true);

        $uuid = (string)$this->makeUuid();

        $fields = $this->createFields($data['fields'], $uuid);

        unset($data['fields']);

        $data = $this->getSectionData($data);

        $data['uuid'] = $uuid;

        $this->saveSectionData($data, $fields);

        return $this->find($uuid);
    }

    /**
     * updateFields
     *
     * @param array $fields
     * @param mixed $uuid
     *
     * @access protected
     * @return array
     */
    protected function getFieldDiff(Collection $existingFields, array $fields, $uuid)
    {
        $keys    = [];
        $new     = [];
        $updated = [];

        $existsingFieldKeys = $existingFields->pluck('id');

        //separating new fields and dirty fields;

        foreach ($fields as $fieldData) {

            if (isset($fieldData['id'])) {

                $keys[] = $fieldData['id'];

                if ($field = $this->manager->fields->getEntity($fieldData['id'])) {

                    $field->fill($fieldData);

                    if ($field->isDirty()) {
                        $field->updated_at = (string)$this->newTimestamp();
                        $updated[] = $field->getData();
                    }
                }

                continue;
            }

            $fieldData['section_uuid'] = $uuid;
            $this->addTimestamps($fieldData);
            $new[] = $this->newFieldInstance($fieldData)->getData();
        }

        $updatedKeys = array_pluck($updated, 'id');

        //foreach ($existingFields as $field) {
        //    if (!in_array($field->id, $updatedKeys) && $field->isDirty()) {
        //        $updated[] = $field->getData();
        //    }
        //}

        $deleted = array_diff($existsingFieldKeys, $keys);

        return compact('new', 'updated', 'deleted');
    }

    /**
     * saveSection
     *
     * @param array $sectionData
     * @param array $fieldData
     *
     * @access protected
     * @return mixed
     */
    protected function saveSectionData(array $sectionData, array $fieldData)
    {
        // transaction save section:
        $this->db->beginTransaction();

        try {
            $this->newSectionQuery()->insert($sectionData);
        } catch (\Exception $e) {
            $this->db->rollback();
            throw new EntityCreateException($e->getMessage());
        }

        // transaction save fields:
        try {
            $this->newFieldQuery()->insert($fieldData);
        } catch (\Exception $e) {
            $this->db->rollback();
            throw new EntityCreateException($e->getMessage());
        }

        $this->db->commit();
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
     * updateTimestamps
     *
     * @param array $data
     * @param mixed $new
     *
     * @access protected
     * @return void
     */
    protected function updateTimestamp(array &$data)
    {
        $data['updated_at'] = (string)$this->newTimestamp();
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
    protected function validateSectionData(array $data, $withFields = true, &$messages = [])
    {
        $errored  = false;

        $validator = $this->getSectionValidator($data);

        if ($withFields && !isset($data['fields']) || empty($data['fields'])) {

            $validator->fails(['fields' => ['empty fields']]);
            throw new ValidationException('validation failed', $validator->getErrors());
        }


        if(!$validator->validate($data)) {
            $messages['section'] = $validator->getErrors()->toArray();
        }

        if ($withFields) {
            $this->validateFieldData($data['fields'], false, $messages);
        }

        if (!empty($messages)) {
            throw new ValidationException('validation failed', $messages);
        }
    }

    /**
     * validateFieldData
     *
     * @param array $fields
     * @param mixed $thow
     * @param array $messages
     *
     * @access protected
     * @return mixed
     */
    protected function validateFieldData(array $fields, $throw = true, array &$messages = [])
    {
        $fieldValidator = $this->getFieldValidator();

        foreach ($fields as $key => $field) {
            if (!$fieldValidator->validate($field)) {
                $messages['fields'][] = $fieldValidator->getErrors()->toArray();
            }
        }

        if ($throw && !empty($messages)) {
            throw new ValidationException('validation failed', $messages);
        }
    }
}
