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
 * @class ImmutableConstraints
 * @package Yam\Entities
 * @version $Id$
 */
interface ImmutableConstraintsInterface
{
    /**
     * isImmutable
     *
     * @param mixed $key
     *
     * @access public
     * @return key
     */
    public function isImmutable($key);
}
