<?php

/**
 * This File is part of the Yam\Entities package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Yam\Entities\Tests;

use \Mockery as m;
use \Aura\Marshal\Lazy\LazyInterface;
use \Yam\Entities\AbstractEntity;
use \Yam\Entities\Tests\Stubs\EntityStub;
use \Illuminate\Support\Contracts\ArrayableInterface;


/**
 * @class EntityTest extends \PHPUnit_Framework_TestCase
 * @see \PHPUnit_Framework_TestCase
 *
 * @package Yam\Entities
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
class EntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClassName
     */
    protected $subject;

    /**
     * @access protected
     */
    protected function tearDown()
    {
        m::close();
    }

    /**
     * @test
     */
    public function itShouldBeInstatiable()
    {
        $entity = $this->getEntity();
        $this->assertInstanceOf('Yam\Entities\AbstractEntity', $entity);
    }

    /**
     * @test
     */
    public function itShouldShouldThrowAnEceptionWhenInitializedWithoutData()
    {
        try {
            $entity = $this->getEntity(null);
        } catch (\Exception $e) {
            $this->assertInstanceof('Exception', $e);
            return;
        }
        $this->fail();
    }

    /**
     * @test
     */
    public function itShouldBeDirtyIfaValueGotChanged()
    {
        $entity = $this->getEntity([
            'foo' => 'bar',
        ]);

        $this->assertFalse(
            $entity->isDirty(),
            '->isDirty() should report clean if no generic value has changed'
        );
        $entity->foo = 'baz';

        $this->assertTrue(
            $entity->isDirty(),
            '->isDirty() should report dirty if at least one generic value has changed'
        );

        $entity = $this->getEntity([
            'foo' => 'bar'
        ]);

        $entity->relation = m::mock('\Aura\Marshal\Lazy\LazyInterface');

        $this->assertFalse(
            $entity->isDirty(),
            '->isDirty() should report clean if a changed value is an unloaded relation'
        );
        $entity->relation = new \StdClass;
        $this->assertTrue(
            $entity->isDirty(),
            '->isDirty() should report dirty if at least one unloaded relation was loaded'
        );
    }

    /**
     * @test
     */
    public function toArrayShouldBeCalledRecursivelyOnRelationsAttributes()
    {
        $rel2 = $this->getEntity(['foo' => 'bar']);
        $rel = $this->getEntity(['relation' => $rel2]);

        $entity = $this->getEntity(['relation' => $rel]);

        $this->assertEquals(['relation' => ['relation' => ['foo' => 'bar']]], $entity->toArray());
    }

    /**
     * getEntity
     *
     * @param mixed $data
     *
     * @access protected
     * @return EntityStub
     */
    protected function getEntity($data = [])
    {
        return new EntityStub($data);
    }
}
