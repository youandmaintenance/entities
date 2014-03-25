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
}
