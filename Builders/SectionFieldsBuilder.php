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

use \Yam\Entities\Field;
use \Yam\Entities\Section;
use \Aura\Marshal\Entity\BuilderInterface;

/**
 * @class SectionBuilder implements BuilderInterface
 * @see BuilderInterface
 *
 * @package Yam\Entities\Builders
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
class SectionFieldsBuilder implements BuilderInterface
{
    /**
     * newInstance
     *
     * @param array $data
     *
     * @access public
     * @return mixed
     */
    public function newInstance(array $data)
    {
        return new Field($data);
    }
}
