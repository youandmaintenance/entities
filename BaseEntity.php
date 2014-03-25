<?php

/**
 * This File is part of the lib\yam\src\Yam\Entities package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Yam\Entities;

use \Aura\Marshal\Lazy\LazyInterface;

/**
 * @abstract class BaseEntity extends AbstractEntity implements AssignableConstraintInterface
 * @see AssignableConstraintInterface
 * @see AbstractEntity
 * @abstract
 *
 * @package lib\yam\src\Yam\Entities
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
abstract class BaseEntity extends AbstractEntity implements AssignableConstraintInterface
{

    /**
     * assignable
     *
     * @var array
     */
    protected $assignable;

    /**
     * immutable
     *
     * @var array
     */
    protected $immutable;

    /**
     * eglConstraints
     *
     * @var array
     */
    protected $eglConstraints;

    /**
     * @param mixed $data
     *
     * @access public
     * @return mixed
     */
    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    /**
     * attributes
     *
     * @access public
     * @return array
     */
    public function getAssignableKeys()
    {
        if (null === $this->assignable) {
            $this->assignable = $this->getAssignableAttributeKeys();
        }

        return $this->assignable;
    }

    /**
     * getImmutableKeys
     *
     * @access public
     * @return mixed
     */
    public function getImmutableKeys()
    {
        if (null === $this->immutable) {
            $this->immutable = $this->getImmutableAttributeKeys();
        }

        return $this->immutable;
    }

    /**
     * offsetSet
     *
     * @param mixed $attr
     * @param mixed $value
     *
     * @access public
     * @return void
     */
    public function offsetSet($attr, $value)
    {
        if ($this->isAssignable($attr = strtolower($attr))) {
            return parent::offsetSet($attr, $this->setAttributeValue($attr, $value));
        }
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
        if (
            ($value = $this->getAttributeValue($attr, $this->getDefault($this->data, $attr, null)))
            instanceof LazyInterface
        ) {

            if ($this->hasEglConstraint($attr)) {
                $this->loadEglConstraint($attr);
            }

            $value = $value->get($this);

            $this->offsetSet($attr, $value);
        }

        return $value;
    }

    /**
     * getIdAttributeValue
     *
     * @param mixed $value
     *
     * @access public
     * @return mixed
     */
    public function getIdAttributeValue($value)
    {
        return (int)$value;
    }

    /**
     * addEagerLoadingConstraint
     *
     * @param mixed $constraint
     * @param callable $callable
     *
     * @access public
     * @return void
     */
    public function addEagerLoadingConstraint($constraint, callable $callable)
    {
        $this->eglConstraints[$constraint] = $callable;
    }

    /**
     * hasEglConstraint
     *
     * @param mixed $attr
     *
     * @access protected
     * @return boolean
     */
    protected function hasEglConstraint($attr)
    {
        return (bool)$this->getDefault($this->eglConstraints, $attr, false);
    }

    /**
     * loadEglConstraint
     *
     * @param mixed $attr
     *
     * @access protected
     * @return void
     */
    protected function loadEglConstraint($attr)
    {
        if ($callback = $this->getDefault($this->eglConstraints, $attr, false)) {
            call_user_func($callback);
            unset($this->eglConstraints[$attr]);
        }
    }

    /**
     * assignable
     *
     * @param mixed $key
     *
     * @access public
     * @return bool
     */
    public function isAssignable($key)
    {
        return !$this->isImmutable($key) && in_array($key, $this->getAssignableKeys());
    }

    /**
     * isImmutable
     *
     * @param mixed $key
     *
     * @access public
     * @return boolean
     */
    public function isImmutable($key)
    {
        return !$this->isNew() && in_array($key, $this->getImmutableKeys());
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * isNew
     *
     * @access protected
     * @return mixed
     */
    protected function isNew()
    {
        return !(bool)$this->getDefault($this->data, 'uuid', false);
    }

    /**
     * getAssignableAttributes
     *
     * @access protected
     * @return array
     */
    abstract protected function getAssignableAttributeKeys();

    /**
     * getImmutableKeys
     *
     *
     * @access protected
     * @return array
     */
    abstract protected function getImmutableAttributeKeys();
}
