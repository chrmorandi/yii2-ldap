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
use ReflectionClass;
use ReflectionProperty;
use Yii;
use yii\db\BaseActiveRecord;

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
     * The schema class with implement SchemaInterface
     * @var string
     */
    public $schemaClass;
    
    /**
     * Instance of Schema class
     */
    private $schema;
    
    /**
     * Returns the LDAP connection used by this AR class.
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
        return 'dn';
    }
    
    /**
     * Initializes the object.
     * This method is called at the end of the constructor.
     * The default implementation will trigger an [[EVENT_INIT]] event.
     * If you override this method, make sure you call the parent implementation at the end
     * to ensure triggering of the event.
     */
    public function init()
    {
        $this->schema = new Schema();
        
        if(!is_null($this->schemaClass)){
            if(class_exists($this->schemaClass)){
                Schema::set(new $this->schemaClass);
            } else {
                throw new InvalidConfigException('"' . $this->schemaClass . '" does not exist.');
            }
        }

        parent::init();        
    }
    
        
    /**
     * Returns the list of attribute names.
     * By default, this method returns all public non-static properties of the class.
     * You may override this method to change the default behavior.
     * @return array list of attribute names.
     */
    public function attributes()
    {
        $class = new ReflectionClass($this->schema->get());
        $names = [];
        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $names[] = $property->getName();
            }
        }

        return $names;
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
     * @param boolean $runValidation whether to perform validation (calling [[validate()]])
     * before saving the record. Defaults to `true`. If the validation fails, the record
     * will not be saved to the database and this method will return `false`.
     * @param array $attributes list of attributes that need to be saved. Defaults to null,
     * meaning all attributes that are loaded from DB will be saved.
     * @return boolean whether the attributes are valid and the record is inserted successfully.
     * @throws \Exception in case insert failed.
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
     * @param array $attributes list of attributes that need to be saved. Defaults to null,
     * meaning all attributes that are loaded will be saved.
     * @return boolean whether the record is inserted successfully.
     */
    protected function insertInternal($attributes = null)
    {
        if (!$this->beforeSave(true)) {
            return false;
        }
        
        $values = $this->getDirtyAttributes($attributes);
        $dn = $values[self::primaryKey()];
        unset($values[self::primaryKey()]);
        
        if (($primaryKeys = static::getDb()->execute('ldap_add', [$dn,$values])) === false) {
            return false;
        }
        $this->setAttribute(self::primaryKey(), $dn);
        $values[self::primaryKey()] = $dn;

        $changedAttributes = array_fill_keys(array_keys($values), null);
        $this->setOldAttributes($values);
        $this->afterSave(true, $changedAttributes);

        return true;
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
     * @param string|array $condition the conditions that will be put in the WHERE part of the DELETE SQL.
     * Please refer to [[Query::where()]] on how to specify this parameter.
     * @return integer the number of rows deleted
     */
    public static function deleteAll($condition = null)
    {
        $entries = (new Query())->select(self::primaryKey())->where($condition);
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
