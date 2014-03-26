<?php

/**
 * This File is part of the Yam\Entities\Repositories package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Yam\Entities\Repositories;

use \Yam\Entities\AbstractEntity;

/**
 * @class RepositoryInterface
 * @package Yam\Entities\Repositories
 * @version $Id$
 */
interface RepositoryInterface
{
    /**
     * create
     *
     * @param array $data
     *
     * @access public
     * @return mixed
     */
    public function find($id, array $options = []);

    /**
     * findAll
     *
     * @param mixed $options
     *
     * @access public
     * @return mixed
     */
    public function findAll(array $options = []);

    /**
     * create
     *
     * @param array $data
     *
     * @access public
     * @return mixed
     */
    public function create(array $data);

    /**
     * update
     *
     * @param mixed $id
     * @param mixed $data
     *
     * @access public
     * @return mixed
     */
    public function update($id, array $data);

    /**
     * delete
     *
     * @param mixed $id
     *
     * @access public
     * @return mixed
     */
    public function delete($id);
}
