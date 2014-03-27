<?php

/**
 * This File is part of the Yam\Entities package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Yam\Entities;

use \Aura\Marshal\Collection\GenericCollection;
use \Illuminate\Support\Contracts\JsonableInterface;
use \Illuminate\Support\Contracts\ArrayableInterface;

/**
 * @class AbstractCollection
 * @package Yam\Entities
 * @version $Id$
 */
class AbstractCollection extends GenericCollection implements JsonableInterface, ArrayableInterface
{
    protected $entityKeyAttribute;


    /**
     * pluck
     *
     * @param mixed $attribute
     *
     * @access public
     * @return mixed
     */
    public function pluck($attribute)
    {
        return array_pluck($this->data, $attribute);
    }

    /**
     * merge
     *
     * @param GenericCollection $collection
     *
     * @access public
     * @return mixed
     */
    public function merge(GenericCollection $collection)
    {
        foreach ($collection as $item) {
            if (!in_array($item, $this->data)) {
                $this->data[] = &$item;
            }
        }
    }

    /**
     * replace
     *
     * @param GenericCollection $collection
     *
     * @access public
     * @return void
     */
    public function replace(GenericCollection $collection)
    {
        $this->data = $collection->getData();
    }

    /**
     * getData
     *
     *
     * @access public
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * find
     *
     * @param mixed $key
     *
     * @access public
     * @return mixed
     */
    public function findByAttribute($key, $attribute = 'id')
    {
        $attribute = $this->getEntityKeyAttribute();

        $found = array_filter($this->data, function ($entity) use ($key, $attribute) {
            return $key === $entity->{$attribute};
        });

        return array_head($found);
    }

    /**
     * toArray
     *
     * @access public
     * @return array
     */
    public function toArray()
    {
        $data = [];

        foreach ($this->data as $key => $entity) {
            if ($entity instanceof ArrayableInterface) {
                $value = $entity->toArray();
            } elseif (is_object($entity)) {
                $value = (array)$entity;
            } else {
                $value = $entity;
            }
            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * toJson
     *
     * @param int $options
     *
     * @access public
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * getEntityKeyAttribute
     *
     * @access protected
     * @return string
     */
    protected function getEntityKeyAttribute()
    {
        return $this->entityKeyAttribute;
    }
}
