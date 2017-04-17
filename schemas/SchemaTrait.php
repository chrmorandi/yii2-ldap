<?php
/**
 * @link      https://github.com/chrmorandi/yii2-ldap for the canonical source repository
 * @package   yii2-ldap
 * @author    Christopher Mota <chrmorandi@gmail.com>
 * @license   MIT License - view the LICENSE file that was distributed with this source code.
 */

namespace chrmorandi\ldap\schemas;

use ReflectionClass;
use ReflectionProperty;

/**
 *
 * @since 1.0.0
 */
trait SchemaTrait {
    
    /**
     * The LDAP API references an LDAP object by its distinguished name (DN).
     * A DN is a sequence of relative distinguished names (RDN) connected by commas.
     *
     * @link https://msdn.microsoft.com/en-us/library/aa366101(v=vs.85).aspx
     * @var  string
     */
    public $dn;
    
     /**
     * Returns the list of attribute names.
     * By default, this method returns all public properties of the class.
     * @return array list of attribute names.
     */
    public function getAttributes() {
        $class = new ReflectionClass(self::class);
        $names = [];
        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $names[] = $property->getName();
        }
        
        return $names;
    }
}
