<?php
/**
 *  Copyright 2017 Justin Dane D. Vallar
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 *
 */

namespace Vallarj\JsonApi\Exception;


class InvalidArgumentException extends \InvalidArgumentException
{
    public static function fromAbstractDocumentAddRelationship()
    {
        return new self("Argument must be an instance of ResponseSchema or an array compatible " .
            "with ResponseSchema builder specifications");
    }

    /**
     * Thrown when adding an invalid type of argument to the addAttribute method
     * of ResponseSchema class
     * @return InvalidArgumentException
     */
    public static function fromResourceSchemaAddAttribute()
    {
        return new self("Argument must be an instance of ResponseSchemaAttribute or an array compatible " .
            "with schema attribute builder specifications");
    }

    /**
     * Thrown when adding an invalid type of argument to the addRelationship method
     * of ResponseSchema class
     * @return InvalidArgumentException
     */
    public static function fromResourceSchemaAddRelationship()
    {
        return new self("Argument must be an instance of ResponseSchemaRelationship or an array " .
            "compatible with schema relationship builder specifications");
    }

    public static function fromSchemaRelationshipConstructor()
    {
        return new self("Argument 'cardinality' must be one of" .
            "ResponseSchemaRelationship::TO_ONE or ResponseSchemaRelationship::TO_MANY");
    }

    public static function fromSchemaRelationshipAddSchema()
    {
        return new self("Argument must be an instance of ResponseSchema or an array compatible " .
            "with ResponseSchema builder specifications");
    }

    public static function fromSingleResourceResponseDocumentBind()
    {
        return new self("Argument must be an object.");
    }
}