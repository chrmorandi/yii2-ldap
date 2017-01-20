<?php
/**
 * @link      https://github.com/chrmorandi/yii2-ldap for the canonical source repository
 * @package   yii2-ldap
 * @author    Christopher Mota <chrmorandi@gmail.com>
 * @license   MIT License - view the LICENSE file that was distributed with this source code.
 */

namespace chrmorandi\ldap;

use chrmorandi\ldap\ActiveQuery;
use chrmorandi\ldap\Connection;
use Exception;
use Yii;
use yii\db\BaseActiveRecord;

/**
 * ActiveRecord is the base class for classes representing relational data in terms of objects.
 *
 * This class implements the ActiveRecord pattern for the [ldap] protocol.
 *
 * For defining a record a subclass should at least implement the [[attributes()]] method to define
 * attributes or use some prepared traits for specific objects. A primary key can be defined via [[primaryKey()]] which defaults to `cn` if not specified.
 *
 * The following is an example model called `User`:
 *
 * ```php
 * class User extends \chrmorandi\ldap\ActiveRecord
 * {
 *     public function attributes()
 *     {
 *         return ['objectClass', 'cn', 'name'];
 *     }
 * }
 * ```
 * Or
 *
 * ```php
 * public function attributes() {
 *     return \chrmorandi\ldap\schemas\ADUser::getAttributes();
 * }
 * ```
 *
 * @since 1.0.0
 */
class ActiveRecord extends BaseActiveRecord
{    
    /**
     * Returns the LDAP connection used by this AR class.
     *
     * @return Connection the LDAP connection used by this AR class.
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
     * @return string[] the primary keys of this record.
     */
    public static function primaryKey()
    {
        return ['dn'];
    }
    
    /**
     * Returns the list of attribute names.
     * You must override this method to define avaliable attributes.
     * @return array list of attribute names.
     */
    public function attributes()
    {
        throw new InvalidConfigException('The attributes() method of ldap ActiveRecord has to be implemented by child classes.');
    }
    
    /**
     * Inserts a row into the associated database table using the attribute values of this record.
     *
     * This method performs the following steps in order:
     *
     * 1. call [[beforeValidate()]] when `$runValidation` is true. If [[beforeValidate()]]
     *    returns `false`, the rest of the steps will be skipped;
     * 2. call [[afterValidate()]] when `$runValidation` is true. If validation
     *    failed, the rest of the steps will be skipped;
     * 3. call [[beforeSave()]]. If [[beforeSave()]] returns `false`,
     *    the rest of the steps will be skipped;
     * 4. insert the record into database. If this fails, it will skip the rest of the steps;
     * 5. call [[afterSave()]];
     *
     * In the above step 1, 2, 3 and 5, events [[EVENT_BEFORE_VALIDATE]],
     * [[EVENT_AFTER_VALIDATE]], [[EVENT_BEFORE_INSERT]], and [[EVENT_AFTER_INSERT]]
     * will be raised by the corresponding methods.
     *
     * Only the [[dirtyAttributes|changed attribute values]] will be inserted into database.
     *
     * If the table's primary key is auto-incremental and is null during insertion,
     * it will be populated with the actual value after insertion.
     *
     * For example, to insert a customer record:
     *
     * ```php
     * $customer = new Customer;
     * $customer->name = $name;
     * $customer->email = $email;
     * $customer->insert();
     * ```
     *
     * @param  boolean $runValidation whether to perform validation (calling [[validate()]])
     * before saving the record. Defaults to `true`. If the validation fails, the record
     * will not be saved to the database and this method will return `false`.
     * @param  string[]|null $attributes    list of attributes that need to be saved. Defaults to null, meaning all attributes that are loaded from DB will be saved. meaning all attributes that are loaded from DB will be saved.
     * meaning all attributes that are loaded from DB will be saved.
     * @return boolean whether the attributes are valid and the record is inserted successfully.
     * @throws Exception in case insert failed.
     */
    public function insert($runValidation = true, $attributes = null)
    {
        if ($runValidation && !$this->validate($attributes)) {
            Yii::info('Model not inserted due to validation error.', __METHOD__);
            return false;
        }

        return $this->insertInternal($attributes);
    }
    
    /**
     * Inserts an ActiveRecord into LDAP without.
     *
     * @param  string[]|null $attributes list of attributes that need to be saved. Defaults to null,
     * meaning all attributes that are loaded will be saved.
     * @return boolean whether the record is inserted successfully.
     */
    protected function insertInternal($attributes = null)
    {
        if (!$this->beforeSave(true)) {
            return false;
        }
        
        $primaryKey = static::primaryKey();
        $values = $this->getDirtyAttributes($attributes);
        $dn = $values[$primaryKey[0]];
        unset($values[$primaryKey[0]]);
        
        static::getDb()->open();

        if (static::getDb()->add($dn, $values) === false) {
            return false;
        }
        $this->setAttribute($primaryKey[0], $dn);
        $values[$primaryKey[0]] = $dn;

        $changedAttributes = array_fill_keys(array_keys($values), null);
        $this->setOldAttributes($values);
        $this->afterSave(true, $changedAttributes);
        
        static::getDb()->close();

        return true;
    }
    
    /**
     * @see update()
     * @param array $attributes attributes to update
     * @return integer number of rows updated
     * @throws StaleObjectException
     */
    protected function updateInternal($attributes = null)
    {
        if (!$this->beforeSave(false)) {
            return false;
        }
        $values = $this->getDirtyAttributes($attributes);
        if (empty($values)) {
            $this->afterSave(false, $values);
            return 0;
        }

        if(($condition = $this->getOldPrimaryKey(true)) !== $this->getPrimaryKey(true)) {
            // TODO Change DN
//            static::getDb()->rename($condition, $newRdn, $newParent, true);
//            if (!$this->refresh()){
//                Yii::info('Model not refresh.', __METHOD__);
//                return false;
//            }
        }
        
        foreach ($values as $key => $value) {
            if($key == 'dn'){
                continue;
            }
            if(empty ($this->getOldAttribute($key)) && $value === ''){
                unset($values[$key]);
            } else if($value === ''){
                $attributes[] = ['attrib'  => $key, 'modtype' => LDAP_MODIFY_BATCH_REMOVE];
            } else if (empty ($this->getOldAttribute($key))) {
                $attributes[] = ['attrib'  => $key, 'modtype' => LDAP_MODIFY_BATCH_ADD, 'values' => [$value]];
            } else {
                $attributes[] = ['attrib'  => $key, 'modtype' => LDAP_MODIFY_BATCH_REPLACE, 'values' => [$value]];
            }
        }
        
        if (empty($attributes)) {
            $this->afterSave(false, $attributes);
            return 0;
        }

        // We do not check the return value of updateAll() because it's possible
        // that the UPDATE statement doesn't change anything and thus returns 0.
        $rows = static::updateAll($attributes, $condition);

//        $changedAttributes = [];
//        foreach ($values as $key => $value) {
//            $changedAttributes[$key] = empty($this->getOldAttributes($key)) ? $this->getOldAttributes($key) : null;
//            $this->setOldAttributes([$key=>$value]);
//        }
//        $this->afterSave(false, $changedAttributes);

        return $rows;
    }
    
    /**
     * Updates the whole table using the provided attribute values and conditions.
     * For example, to change the status to be 1 for all customers whose status is 2:
     *
     * ```php
     * Customer::updateAll(['status' => 1], 'status = 2');
     * ```
     *
     * @param array $attributes attribute values (name-value pairs) to be saved into the table
     * @param string|array $condition the conditions that will be put in the WHERE part of the UPDATE SQL.
     * Please refer to [[Query::where()]] on how to specify this parameter.
     * @return integer the number of rows updated
     */
    public static function updateAll($attributes, $condition = '')
    {
        if(is_array($condition)){
            $condition = $condition['dn'];
        }        
        
        static::getDb()->open();
        $teste = static::getDb()->modify($condition, $attributes);
        static::getDb()->close();
        
        return count($attributes);
    }
    
    /**
     * Deletes rows in the table using the provided conditions.
     * WARNING: If you do not specify any condition, this method will delete ALL rows in the ldap directory.
     *
     * For example, to delete all customers whose status is 3:
     *
     * ```php
     * Customer::deleteAll('status = 3');
     * ```
     *
     * @param  string|array $condition the conditions that will be put in the WHERE part of the DELETE SQL.
     * Please refer to [[Query::where()]] on how to specify this parameter.
     * @return integer the number of rows deleted
     */
    public static function deleteAll($condition = '')
    {
        $entries = (new Query())->select(self::primaryKey())->where($condition)->execute()->toArray();
        $count = 0;
        
        foreach ($entries as $entry) {
            $params = [
                $entry[self::primaryKey()]
            ];
            static::getDb()->execute('ldap_delete', $params);
            $count++;
        }
        return $count;
    }
}
