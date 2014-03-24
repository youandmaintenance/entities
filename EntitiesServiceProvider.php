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

use \Aura\Marshal\Manager;
use \Illuminate\Support\ServiceProvider;
use \Yam\Entities\Repositories\SectionRepository;

/**
 * @class EntitiesServiceProvider extends ServiceProvider
 * @see ServiceProvider
 *
 * @package Yam\Entities
 * @version $Id$
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
class EntitiesServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('yam.entities.collectionbuilder', 'Yam\Entities\Builders\CollectionBuilder');
        $manager = $this->app->make('marshal.manager');

        $this->setRepositories($manager);
        $this->setEntities($manager);
        $this->setEntityRelations($manager);
    }

    protected function setEntities(Manager $manager)
    {
        $collectionBuilder = $this->app->make('yam.entities.collectionbuilder');

        $manager->setType(
            'sections',
            [
                'identity_field'     => 'uuid',
                'entity_builder'     => new \Yam\Entities\Builders\SectionBuilder,
                'collection_builder' => $collectionBuilder
            ]
        );

        $manager->setType(
            'entries',
            [
                'identity_field' => 'uuid'
            ]
        );

        $manager->setType(
            'fields',
            [
                'identity_field' => 'id',
                'entity_builder'     => new \Yam\Entities\Builders\SectionFieldsBuilder,
                'collection_builder' => $collectionBuilder
            ]
        );
    }

    /**
     * setEntityRelations
     *
     * @param Manager $manager
     *
     * @access protected
     * @return void
     */
    protected function setEntityRelations(Manager $manager)
    {
        $manager->setRelation('sections', 'fields', [
            'relationship'  => 'has_many',
            'native_field'  => 'uuid',
            'foreign_field' => 'section_uuid'
        ]);

        $manager->setRelation('fields', 'section', [
            'relationship'  => 'belongs_to',
            'foreign_type'  => 'sections',
            'native_field'  => 'section_uuid',
            'foreign_field' => 'uuid'
        ]);

        //$manager->setRelation('sections', 'entries', [
        //    'relationship'  => 'has_many',
        //    'native_field'  => 'uuid',
        //    'foreign_field' => 'section_uuid'
        //]);

        //$manager->setRelation('entries', 'section', [
        //    'relationship'  => 'belongs_to',
        //    'foreign_type'  => 'sections',
        //    'native_field'  => 'section_uuid',
        //    'foreign_field' => 'uuid'
        //]);
    }

    public function boot()
    {
        $this->registerValidators();
    }

    /**
     * setRepositories
     *
     * @param Managet $manager
     *
     * @access protected
     * @return void
     */
    protected function setRepositories(Manager $manager)
    {
        $this->app->bindShared('yam.sectionrepository', function () use ($manager) {
            return new SectionRepository($manager, $this->app['db'], $this->app['yam.validators']);
        });
    }

    protected function registerValidators()
    {
        $validators = $this->app['yam.validators'];

        foreach (include __DIR__.'/rules/rules.php' as $name => $data) {
            $validators->register($name, $data['class'], $data['rules']);
        }
    }
}
