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

/**
 * @interface AttributeConstraintInterface
 *
 * @package Yam\Entities
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
interface AssignableConstraintInterface
{
    public function getAssignableKeys();

    public function getImmutableKeys();

    public function isAssignable($key);

    public function isImmutable($key);
}
