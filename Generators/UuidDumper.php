<?php

/**
 * This File is part of the lib\yam\src\Yam\Entities\Generator package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Yam\Entities\Generators;

use \Rhumsaa\Uuid\Uuid;
use \Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;
use \Yam\Entities\Exception\GeneratorException;

/**
 * @class UiidShareObject
 *
 * @package lib\yam\src\Yam\Entities\Generators
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
class UuidDumper
{
    /**
     * dirty
     *
     * @var boolean
     */
    protected $dirty;

    /**
     * create a new UuidDumper
     */
    public function __construct()
    {
        $this->dirty = false;
    }

    /**
     * createNew
     *
     * @access public
     * @return mixed
     */
    public function createNew()
    {
        return new static;
    }

    /**
     * __toString
     *
     * @access public
     * @return mixed
     */
    public function __toString()
    {
        return (string)$this->generateUuid();
    }

    /**
     * generateUid
     *
     *
     * @throws UnsatisfiedDependencyException
     * @throws GeneratorException
     * @access protected
     * @return string
     */
    protected function generateUuid()
    {
        if ($this->dirty) {
            return $this->uuid;
        }

        try {
            if ($uuid = Uuid::uuid4()) {
                $this->dirty = true;
                return $uuid;
            }
        } catch (UnsatisfiedDependencyException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GeneratorException(sprintf('UUID creation failed with message: %s', $e->getMessage()));
        }
    }
}
