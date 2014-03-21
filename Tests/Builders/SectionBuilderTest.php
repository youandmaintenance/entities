<?php

/**
 * This File is part of the Yam\Entities package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Yam\Entities\Tests\Builders;

use Yam\Entities\Section;
use Yam\Entities\Builders\SectionBuilder;

/**
 * @class CollectiomBuilderTest extends \PHPUnit_Framework_TestCase
 * @see \PHPUnit_Framework_TestCase
 *
 * @package Yam\Entities
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
class SectionBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function itShouldInstantiateANewSection()
    {
        $builder = new SectionBuilder;
        $this->assertInstanceOf('Yam\Entities\Section', $builder->newInstance([[]]));
    }
}
