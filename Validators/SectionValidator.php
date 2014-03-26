<?php

/**
 * This File is part of the Yam\Entities\Validators package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Yam\Entities\Validators;

use \Yam\Validators\AbstractValidationService;

/**
 * @class SectionValidator
 * @package Yam\Entities\Validators
 * @version $Id$
 */
class SectionValidator extends AbstractValidationService
{
    /**
     * rules
     *
     * @var mixed
     */
    protected static $rules = [
        'name'    => 'required:unique:sections,name',
        'handle'  => 'required:handle:unique:sections,handle'
    ];
}
