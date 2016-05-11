<?php

namespace chrmorandi\ldap\Models;

use chrmorandi\ldap\models\traits\HasCriticalSystemObjectTrait;
use chrmorandi\ldap\models\traits\HasDescriptionTrait;

class Container extends Entry
{
    use HasDescriptionTrait, HasCriticalSystemObjectTrait;

    /**
     * Returns the containers system flags integer.
     *
     * https://msdn.microsoft.com/en-us/library/ms680022(v=vs.85).aspx
     *
     * @return string
     */
    public function getSystemFlags()
    {
        return $this->getAttribute($this->schema->systemFlags(), 0);
    }
}
