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
class FieldValidator extends AbstractValidationService
{
    /**
     * rules
     *
     * @var mixed
     */
    protected static $rules = [
        'section_uuid' => 'required_if:fields,id',
        'label'        => 'required',
        'handle'       => 'handle:required_if:fields,id',
        'type_id'      => 'required:exists:field_types,id',
        //'required'     => 'required',
        'sorting'      => 'integer',
        'position'     => 'in:left,right',
    ];
}
