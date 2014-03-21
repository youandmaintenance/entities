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

use Yam\Entities\Collection;
use Yam\Entities\Builders\CollectionBuilder;
use \Aura\Marshal\Collection\GenericCollection;
use \Aura\Marshal\Collection\CollectionInterface;

/**
 * @class CollectiomBuilderTest extends \PHPUnit_Framework_TestCase
 * @see \PHPUnit_Framework_TestCase
 *
 * @package Yam\Entities
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
class CollectiomBuilderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @test
     */
    public function itShouldInstantiateANewCollection()
    {
        $builder = new CollectionBuilder;
        $this->assertInstanceOf('Yam\Entities\Collection', $builder->newInstance([[]]));
        $this->assertInstanceOf('Aura\Marshal\Collection\GenericCollection', $builder->newInstance([[]]));
    }
}
