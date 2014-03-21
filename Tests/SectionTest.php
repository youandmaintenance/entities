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

use \Yam\Entities\Section;
use \Yam\Entities\Tests\Stubs\EntityStub;

/**
 * @class SectionTest
 * @package Yam\Entities\Tests
 * @version $Id$
 */
class SectionTest extends EntityTest
{
    /**
     * @test
     */
    public function itShouldBeInstatiable()
    {
        parent::itShouldBeInstatiable();
        $entity = $this->getEntity();
        $this->assertInstanceOf('Yam\Entities\Section', $entity);
        $this->assertInstanceOf('Yam\Entities\AssignableConstraintInterface', $entity);
    }

    /**
     * @test
     */
    public function callingAttribuesShouldReturnPredefinedValues()
    {
        $entity = $this->getEntity();
        $this->assertTrue(in_array('id', $entity->getAssignableKeys()));
        $this->assertTrue(in_array('uuid', $entity->getAssignableKeys()));
        $this->assertTrue(in_array('versionable', $entity->getAssignableKeys()));
        $this->assertTrue(in_array('fields', $entity->getAssignableKeys()));
        $this->assertTrue(in_array('entries', $entity->getAssignableKeys()));
    }

    /**
     * @test
     */
    public function valuesShouldBeAssignableOrNot()
    {
        $entity = $this->getEntity();
        $this->assertTrue($entity->isAssignable('id'));
        $this->assertFalse($entity->isAssignable('foo'));

        $entity->uuid = '249fcb61-4253-47d5-80ab-e012e19e7727';

        $this->assertFalse($entity->isAssignable('id'));
    }

    /**
     * @test
     */
    public function noneAssignableAttributesShouldNotGetSet()
    {
        $entity = $this->getEntity();
        $entity->foo = 'bar';
        $this->assertNull($entity->foo);
    }

    /**
     * @test
     */
    public function itShouldBeDirtyIfaValueGotChanged()
    {
        $entity = $this->getEntity([
            'id'          => 1,
            'uuid'        => '249fcb61-4253-47d5-80ab-e012e19e7727',
            'name'        => 'Some Name',
            'handle'      => 'some_name',
            'versionable' => false
        ]);

        $this->assertFalse(
            $entity->isDirty(),
            '->isDirty() should report clean if no generic value has changed'
        );

        $entity->name = 'some other name';

        $this->assertTrue(
            $entity->isDirty(),
            '->isDirty() should report dirty if at least one generic value has changed'
        );
    }

    /**
     * @test
     */
    public function toArrayShouldBeCalledRecursivelyOnRelationsAttributes()
    {
        $rel2 = $this->getEntity(['foo' => 'bar']);
        $rel = $this->getEntity(['relation' => $rel2]);

        $entity = $this->getEntity(['fields' => $rel]);

        $this->assertEquals(['fields' => ['relation' => ['foo' => 'bar']]], $entity->toArray());
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
        return new Section($data);
    }
}
