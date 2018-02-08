<?php
/**
 * Created by PhpStorm.
 * User: justin
 * Date: 2/8/18
 * Time: 2:02 PM
 */

namespace Vallarj\JsonApi\Schema;


class WriteOnlyDateAttribute extends DateAttribute
{
    /**
     * Overrides the isReadable function to always return false
     * @inheritdoc
     */
    public function isReadable(): bool
    {
        return parent::isReadable(); // TODO: Change the autogenerated stub
    }

    /**
     * Overridse the isWritable function to always return true
     * @inheritdoc
     */
    public function isWritable(): bool
    {
        return parent::isWritable(); // TODO: Change the autogenerated stub
    }
}