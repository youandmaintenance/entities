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

use \Yam\Utils\Traits\Getter;
use \Aura\Marshal\Lazy\LazyInterface;
use \Aura\Marshal\Entity\GenericEntity;
use \Illuminate\Support\Contracts\JsonableInterface;
use \Illuminate\Support\Contracts\ArrayableInterface;

/**
 * @class AbstractEntity extends GenericEntity implements ArrayableInterface, JsonableInterface
 * @see ArrayableInterface
 * @see JsonableInterface
 * @see GenericEntity
 *
 * @package Yam\Entities
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
class AbstractEntity extends GenericEntity implements ArrayableInterface, JsonableInterface
{
    use Getter;

    /**
     * dirty
     *
     * @var boolean
     */
    protected $dirty = false;

    /**
     * original
     *
     * @var array
     */
    protected $original;


    /**
     * @param array $data
     *
     * @access public
     */
    public function __construct(array $data)
    {
        $this->original = $data;
        parent::__construct($data);
    }

    /**
     * we are dirty or not. lets play.
     *
     * @access public
     * @return bool
     */
    public function isDirty()
    {
        $o = $this->original;

        if (!$this->dirty) {
            foreach ($this->data as $key => $d) {
                if ($d === null ||
                    $d instanceof LazyInterface ||
                    (array_key_exists($key, $o) && $o[$key] === $d)
                ) {
                    continue;
                }
                $this->dirty = true;
                break;
            }
        }
        return (boolean)$this->dirty;
    }

    /**
     * setAttribute
     *
     * @param mixed $attr
     * @param mixed $value
     *
     * @access public
     * @return void
     */
    public function setAttribute($attr, $value = null)
    {
        return $this->offsetSet($attr, $value);
    }

    /**
     * getAttribute
     *
     * @param mixed $attr
     * @param mixed $default
     *
     * @access public
     * @return mixed
     */
    public function getAttribute($attr, $default = null)
    {
        return $this->offsetGet($attr);
    }

    /**
     * offsetGet
     *
     * @param mixed $attr
     *
     * @access public
     * @return mixed
     */
    public function offsetGet($attr)
    {
        if (null === $this->getDefault($this->data, $attr, null)) {
            return;
        }
        return parent::offsetGet($attr);
    }

    /**
     * fillAttributes
     *
     * @param array $attributes
     *
     * @access public
     * @return void
     */
    public function fillAttributes(array $attributes)
    {
        return $this->data = $attributes;
    }

    /**
     * toArray
     *
     * @access public
     * @return array
     */
    public function toArray()
    {
        return $this->dataGetClean();
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
     * dataGetClean
     *
     * @access protected
     * @return mixed
     */
    protected function dataGetClean()
    {
        $clean = [];

        foreach ($this->data as $key => $data) {
            if ($data instanceof LazyInterface) {
                $value = null;
            } elseif ($data instanceof ArrayableInterface) {
                $value = $data->toArray();
            } else {
                $value = $data;
            }
            $clean[$key] = $value;
        }
        return $clean;
    }

    /**
     * setDirty
     *
     * @param mixed $dirty
     *
     * @access protected
     * @return void
     */
    protected function setDirty($dirty)
    {
        $this->dirty = (boolean)$dirty;
    }

}
