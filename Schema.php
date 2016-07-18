<?php
/**
 * @link      https://github.com/chrmorandi/yii2-ldap for the canonical source repository
 * @package   yii2-ldap
 * @author    Christopher Mota <chrmorandi@gmail.com>
 * @license   MIT License - view the LICENSE file that was distributed with this source code.
 */

namespace chrmorandi\ldap;

use chrmorandi\ldap\schemas\ActiveDirectory;
use yii\base\Object;

class Schema extends Object
{
    /**
     * The current LDAP attribute schema.
     *
     * @var SchemaInterface
     */
    protected $current;

    /**
     * Returns the current LDAP attribute schema.
     *
     * @return SchemaInterface
     */
    public function get()
    {
        if (!$this->current instanceof SchemaInterface) {
            $this->set(static::getDefault());
        }

        return $this->current;
    }

    /**
     * Sets the current LDAP attribute schema.
     *
     * @param SchemaInterface $schema
     */
    public function set(SchemaInterface $schema)
    {
        $this->current = $schema;
    }

    /**
     * Returns a new instance of the default schema.
     *
     * @return SchemaInterface
     */
    public static function getDefault()
    {
        return new ActiveDirectory();
    }
}
