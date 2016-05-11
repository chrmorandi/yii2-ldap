<?php

namespace chrmorandi\ldap\models\traits;

trait HasCriticalSystemObjectTrait
{
    use ModelTrait;

    /**
     * Returns true / false if the entry is a critical system object.
     *
     * @return null|bool
     */
    public function isCriticalSystemObject()
    {
        $attribute = $this->getAttribute($this->getSchema()->isCriticalSystemObject(), 0);

        return $this->convertStringToBool($attribute);
    }
}
