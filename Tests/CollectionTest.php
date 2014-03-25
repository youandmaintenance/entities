<?php

/**
 * This File is part of the Yam\Entities\Tests package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Yam\Entities\Tests;

use \Yam\Entities\Collection;

class CollectionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @test
     */
    public function itShouldBeInstantiable()
    {
        $collection = new Collection;

        $this->assertInstanceOf('Aura\Marshal\Collection\GenericCollection', $collection);
        $this->assertInstanceOf('Illuminate\Support\Contracts\JsonableInterface', $collection);
        $this->assertInstanceOf('Illuminate\Support\Contracts\ArrayableInterface', $collection);
    }

    /**
     * @test
     */
    public function itShouldColumnnizeCollectionAttributes()
    {
        $objA = new \StdClass;
        $objA->id = 1;
        $objB = new \StdClass;
        $objB->id = 2;
        $objC = new \StdClass;
        $objC->id = 3;

        $collection = new Collection([$objA, $objB, $objC]);
        $this->assertSame([1, 2, 3], $collection->pluck('id'));
    }

    /**
     * @test
     */
    public function itShouldBeArrayAble()
    {
        $objA = new \StdClass;
        $objA->id = 1;
        $objB = new \StdClass;
        $objB->id = 2;
        $objC = new \StdClass;
        $objC->id = 3;

        $collection = new Collection([$objA, $objB, $objC]);
        $this->assertSame([['id' => 1], ['id' => 2], ['id' => 3]], $collection->toArray());
    }
}

