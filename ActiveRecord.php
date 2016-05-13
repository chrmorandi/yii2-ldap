<?php

namespace chrmorandi\ldap;

use chrmorandi\ldap\ActiveQuery;
use chrmorandi\ldap\Connection;
use chrmorandi\ldap\exceptions\InvalidArgumentException;
use chrmorandi\ldap\Object\LdapObject;
use chrmorandi\ldap\operation\DeleteOperation;
use yii\base\InvalidConfigException;
use yii\db\BaseActiveRecord;
use Yii;

/**
 * ActiveRecord is the base class for classes representing relational data in terms of objects.
 *
 * This class implements the ActiveRecord pattern for the [ldap] protocol.
 *
 * For defining a record a subclass should at least implement the [[attributes()]] method to define
 * attributes. A primary key can be defined via [[primaryKey()]] which defaults to `cn` if not specified.
 *
 * The following is an example model called `User`:
 *
 * ```php
 * class User extends \yii\redis\ActiveRecord
 * {
 *     public function attributes()
 *     {
 *         return ['cn', 'name', 'email'];
 *     }
 * }
 * ```
 *
 * @author Christopher mota
 * @since 1.0.0
 */
class ActiveRecord extends BaseActiveRecord
{
    /**
     * Returns the database connection used by this AR class.
     * @return Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('ldap');
    }

    /**
     * @inheritdoc
     * @return ActiveQuery the newly created [[ActiveQuery]] instance.
     */
    public static function find()
    {
        return Yii::createObject(ActiveQuery::class, [get_called_class()]);
    }

    /**
     * Returns the primary key name(s) for this AR class.
     * This method should be overridden by child classes to define the primary key.
     *
     * Note that an array should be returned even when it is a single primary key.
     *
     * @return string[] the primary keys of this record.
     */
    public static function primaryKey()
    {
        return ['id'];
    }
    
    /**
     * Returns the list of all attribute names of the model.
     * This method must be overridden by child classes to define available attributes.
     * @return array list of attribute names.
     */
    public function attributes()
    {
        throw new InvalidConfigException('The attributes() method of ldap ActiveRecord has to be implemented by child classes.');
    }
    
    /**
     * @inheritdoc
     */
    public function insert($runValidation = true, $attributes = null)
    {
        // TODO
    }
    
    /**
     * Delete an object from LDAP. Optionally you can set the second argument to true which sends a control to LDAP to
     * perform a recursive deletion. This is helpful in the case of deleting an OU with with objects underneath it. By
     * setting the second parameter to true the OU and all objects below it would be deleted. Use with care!
     *
     * If recursive deletion does not work, first check that 'accidental deletion' is not enabled on the object (AD).
     *
     * @param LdapObject $ldapObject
     * @param bool $recursively
     * @return $this
     */
    public static function deleteAll($condition = null, $recursively = false)
    {
        $db = static::getDb();
        
        $this->validateObject($ldapObject);        
        $operation = new DeleteOperation($ldapObject->get('dn'));
        if ($recursively) {
            //$operation->addControl((new LdapControl(LdapControlType::SUB_TREE_DELETE))->setCriticality(true));
        }
        $result = $db->execute($operation);
        return end($result);
    }
    
    /**
     * The DN attribute must be present to perform LDAP operations.
     *
     * @param LdapObject $ldapObject
     */
    protected function validateObject(LdapObject $ldapObject)
    {
        if (!$ldapObject->has('dn')) {
            throw new InvalidArgumentException('To persist/delete/move/restore a LDAP object it must have the DN attribute.');
        }
    }
}
