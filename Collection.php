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

use \Yam\MarshalBridge\Collection\AbstractCollection as BaseCollection;

/**
 * @class Collection extends BaseCollection
 * @see BaseCollection
 *
 * @package Yam\Entities
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
class Collection extends BaseCollection
{
    protected function getItemAttributeValue($item, $attribute)
    {
        return $item->getAttribute($attribute);
    }
}
