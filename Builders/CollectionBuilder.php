<?php

/**
 * This File is part of the Yam\Entities\Builders package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Yam\Entities\Builders;

use \Yam\Entities\Collection;
use \Aura\Marshal\Collection\BuilderInterface;

/**
 * @class SectionCollectionBuilder implements CollectionInterface
 * @see CollectionInterface
 *
 * @package Yam\Entities\Builders
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
class CollectionBuilder implements BuilderInterface
{
    /**
     * newInstance
     *
     * @param array $data
     *
     * @access public
     * @return \Yam\Entities\SectionCollection
     */
    public function newInstance(array $data)
    {
        return new Collection($data);
    }
}
