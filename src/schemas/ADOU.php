<?php
/**
 * @link      https://github.com/chrmorandi/yii2-ldap for the source repository
 * @package   yii2-ldap
 * @author    Christopher Mota <chrmorandi@gmail.com>
 * @license   MIT License - view the LICENSE file that was distributed with this source code.
 */

namespace chrmorandi\ldap\schemas;

/**
 * Description of ADOUTrait
 *
 * @since 1.0.0
 */
class ADOU implements SchemaInterface {
    
    use SchemaTrait;
    
    /**
     * Contain the object class for make user in AD
     * User: This class is used to store information about an employee or contractor who works for an organization. 
     * It is also possible to apply this class to long term visitors.
     * Person: Contains personal information about a user.
     * OrganizationalPerson: This class is used for objects that contain organizational information about a user, such 
     * as the employee number, department, manager, title, office address, and so on.
     * Top: The top level class from which all classes are derived.
     * @link https://msdn.microsoft.com/en-us/library/ms680932(v=vs.85).aspx
     * @var array 
     */
    public static $objectClass = ['organizationalUnit', 'top'];
 
    /**
     * The name of the organizational unit.
     * @link https://msdn.microsoft.com/en-us/library/ms679096(v=vs.85).aspx
     * @var type 
     */
    public $ou;
    
    /**
     * Contains the description to display for an object. This value is restricted
     * as single-valued for backward compatibility in some cases but
     * is allowed to be multi-valued in others.
     * @link https://msdn.microsoft.com/en-us/library/ms675492(v=vs.85).aspx
     * @var string
     */
    public $description;
    
    /**
     * If TRUE, the object hosting this attribute must be replicated during installation of a new replica.
     * @link https://msdn.microsoft.com/en-us/library/ms676798(v=vs.85).aspx
     * @var Boolean 
     */
    public $isCriticalSystemObject;
            
    /**
     * The unique identifier for an object.
     * @link https://msdn.microsoft.com/en-us/library/ms679021(v=vs.85).aspx
     * @var type 
     */
    public $objectGuid;
    
    /**
     * The entry's created at attribute.
     * @link https://msdn.microsoft.com/en-us/library/ms680924(v=vs.85).aspx
     * @var DateTime
     */
    public $whenCreated;
    
    /**
     * The date when this object was last changed.
     * @link https://msdn.microsoft.com/en-us/library/ms680921(v=vs.85).aspx
     * @var DateTime
     */
    public $whenChanged;
    
    /**
     * The distinguished name of the user that is assigned to manage this object.
     * @link https://msdn.microsoft.com/en-us/library/ms676857(v=vs.85).aspx
     * @var Object(DS-DN)
     */
    public $managedBy;
}