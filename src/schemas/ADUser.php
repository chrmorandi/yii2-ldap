<?php
/**
 * @link      https://github.com/chrmorandi/yii2-ldap for the source repository
 * @package   yii2-ldap
 * @author    Christopher Mota <chrmorandi@gmail.com>
 * @license   MIT License - view the LICENSE file that was distributed with this source code.
 * @since     1.0.0
 */

namespace chrmorandi\ldap\schemas;

use DateTime;

/**
 *
 */
class ADUser implements SchemaInterface
{
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
    public static $objectClass = ['user', 'person', 'organizationalPerson', 'top'];

    /**
     * The date when the account expires. This value represents the number of 100-nanosecond
     * intervals since January 1, 1601 (UTC). A value of 0 or 0x7FFFFFFFFFFFFFFF
     * (9223372036854775807) indicates that the account never expires.
     * @link https://msdn.microsoft.com/en-us/library/ms675098(v=vs.85).aspx
     * @var DateTime|false
     */
    public $accountExpires = 9223372036854775807;

    /**
     * The number of times the user tried to log on to the account using
     * an incorrect password. A value of 0 indicates that the
     * value is unknown.
     * @link https://msdn.microsoft.com/en-us/library/ms675244(v=vs.85).aspx
     * @var int
     */
    public $badPwdCount;

    /**
     * The user's company name.
     * @link https://msdn.microsoft.com/en-us/library/ms675457(v=vs.85).aspx
     * @var string
     */
    public $company;

    /**
     * The entry's country attribute.
     * @link https://msdn.microsoft.com/en-us/library/ms675432(v=vs.85).aspx
     * @var string
     */
    public $c;

    /**
     * The name that represents an object. Used to perform searches.
     * @link https://msdn.microsoft.com/en-us/library/ms675449(v=vs.85).aspx
     * @var string
     */
    public $cn;

    /**
     * Contains the name for the department in which the user works.
     * @link https://msdn.microsoft.com/en-us/library/ms675490(v=vs.85).aspx
     * @var string
     */
    public $department;

    /**
     * Contains the description to display for an object. This value is restricted
     * as single-valued for backward compatibility in some cases but
     * is allowed to be multi-valued in others.
     * @link https://msdn.microsoft.com/en-us/library/ms675492(v=vs.85).aspx
     * @var string
     */
    public $description;

    /**
     * The display name for an object. This is usually the combination
     * of the users first name, middle initial, and last name.
     * @link https://msdn.microsoft.com/en-us/library/ms675514(v=vs.85).aspx
     * @var string
     */
    public $displayName;

    /**
     * The user's division.
     * @link https://msdn.microsoft.com/en-us/library/ms675518(v=vs.85).aspx
     * @var string
     */
    public $division;

    /**
     * The ID of an employee.
     * @link https://msdn.microsoft.com/en-us/library/ms675662(v=vs.85).aspx
     * @var string
     */
    public $employeeID;

    /**
     * The number assigned to an employee other than the ID.
     * @link https://msdn.microsoft.com/en-us/library/ms675663(v=vs.85).aspx
     * @var string
     */
    public $employeeNumber;

    /**
     * The job category for an employee.
     * @link https://msdn.microsoft.com/en-us/library/ms675664(v=vs.85).aspx
     * @var string
     */
    public $employeeType;

    /**
     * Contains the given name (first name) of the user.
     * @link https://msdn.microsoft.com/en-us/library/ms675719(v=vs.85).aspx
     * @var string
     */
    public $givenName;

    /**
     * The user's main home phone number.
     * @link https://msdn.microsoft.com/en-us/library/ms676192(v=vs.85).aspx
     * @var string
     */
    public $homePhone;

    /**
     * Contains the initials for parts of the user's full name. This may be used as
     * the middle initial in the Windows Address Book.
     * @link https://msdn.microsoft.com/en-us/library/ms676202(v=vs.85).aspx
     * @var string
     */
    public $initials;

    /**
     * The TCP/IP address for the phone. Used by Telephony.
     * @link https://msdn.microsoft.com/en-us/library/ms676213(v=vs.85).aspx
     * @var string
     */
    public $ipPhone;

    /**
     * The date and time (UTC) that this account was locked out. This value is stored
     * as a large integer that represents the number of 100-nanosecond intervals since
     * January 1, 1601 (UTC). A value of zero means that the account is not currently
     * locked out.
     * @link https://msdn.microsoft.com/en-us/library/ms676843(v=vs.85).aspx
     * @var DateTime|false
     */
    public $lockoutTime;

    /**
     * The list of email addresses for a contact.
     * @link https://msdn.microsoft.com/en-us/library/ms676855(v=vs.85).aspx
     * @var string
     */
    public $mail;

    /**
     * Contains the distinguished name of the user who is the user's manager.
     * The manager's user object contains a directReports property that contains
     * references to all user objects that have their manager properties set to this
     * distinguished name.
     * @link https://msdn.microsoft.com/en-us/library/ms676859(v=vs.85).aspx
     * @var string
     */
    public $manager;

    /**
     * The distinguished name of the groups to which this object belongs.
     * @link https://msdn.microsoft.com/en-us/library/ms677099(v=vs.85).aspx
     * @var array
     */
    public $memberOf;

    /**
     * The primary mobile phone number.
     * @link https://msdn.microsoft.com/en-us/library/ms677119(v=vs.85).aspx
     * @var string
     */
    public $mobile;

    /**
     * The name of the company or organization.
     * @link https://msdn.microsoft.com/en-us/library/ms679009(v=vs.85).aspx
     * @var string
     */
    public $o;

    /**
     * The unique identifier for an object.
     * @link https://msdn.microsoft.com/en-us/library/ms679021(v=vs.85).aspx
     * @var type
     */
    public $objectGuid;

    /**
     * A binary value that specifies the security identifier (SID) of the user. The SID is a unique value used to
     * identify the user as a security principal.
     * @link https://msdn.microsoft.com/en-us/library/ms679024(v=vs.85).aspx
     * @var string
     */
    public $objectSid;

    /**
     * The date and time that the password for this account was last changed. This value is stored as a large
     * integer that represents the number of 100 nanosecond intervals since January 1, 1601 (UTC). If this value
     * is set to 0 and the User-Account-Control attribute does not contain the UF_DONT_EXPIRE_PASSWD
     * flag, then the user must set the password at the next logon.
     * @link https://msdn.microsoft.com/en-us/library/ms679430(v=vs.85).aspx
     * @var type
     */
    public $pwdLastSet;

    /**
     * The logon name used to support clients and servers running earlier versions of the operating system, such
     * as Windows NT 4.0, Windows 95, Windows 98, and LAN Manager.
     * @link https://msdn.microsoft.com/en-us/library/ms679635(v=vs.85).aspx
     * @var type
     */
    public $sAMAccountName;

    /**
     * The name of a user's state or province.
     * @link https://msdn.microsoft.com/en-us/library/ms679880(v=vs.85).aspx
     * @var string
     */
    public $st;

    /**
     * The street address.
     * @link https://msdn.microsoft.com/en-us/library/ms679882(v=vs.85).aspx
     * @var string
     */
    public $streetAddress;

    /**
     * This attribute contains the family or last name for a user.
     * @link https://msdn.microsoft.com/en-us/library/ms679872(v=vs.85).aspx
     * @var string
     */
    public $sn;

    /**
     * The primary telephone number.
     * @link https://msdn.microsoft.com/en-us/library/ms680027(v=vs.85).aspx
     * @return string
     */
    public $telephoneNumber;

    /**
     * Contains the user's job title. This property is commonly used to indicate the formal job title, such as Senior
     * Programmer, rather than occupational class, such as programmer. It is not typically used for suffix titles
     * such as Esq. or DDS.
     * @link https://msdn.microsoft.com/en-us/library/ms680037(v=vs.85).aspx
     * @var type
     */
    public $title;

    /**
     * Flags that control the behavior of the user account.
     * @link https://msdn.microsoft.com/en-us/library/ms680832(v=vs.85).aspx
     * @return string
     */
    public $userAccountControl;

    /**
     * his attribute contains the UPN that is an Internet-style login name for
     * a user based on the Internet standard RFC 822.
     * @link https://msdn.microsoft.com/en-us/library/ms680857(v=vs.85).aspx
     * @return string
     */
    public $userPrincipalName;

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
     * A web page that is the primary landing page of a website.
     * @link https://msdn.microsoft.com/en-us/library/ms680927(v=vs.85).aspx
     * @var string
     */
    public $wWWHomePage;

}
