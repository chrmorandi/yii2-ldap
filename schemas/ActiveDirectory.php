<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace chrmorandi\ldap\schemas;

use chrmorandi\ldap\SchemaInterface;
use yii\base\Object;

/**
 * Class ActiveDirectory.
 *
 * The active directory attribute schema.
 */
class ActiveDirectory extends Object implements SchemaInterface
{
    /**
     * The date when the account expires. This value represents the number of 100-nanosecond
     * intervals since January 1, 1601 (UTC). A value of 0 or 0x7FFFFFFFFFFFFFFF
     * (9223372036854775807) indicates that the account never expires.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms675098(v=vs.85).aspx
     *
     * @var string
     */
    public $accountexpires = 9223372036854775807;

    /**
     * The logon name used to support clients and servers running earlier versions of the
     * operating system, such as Windows NT 4.0, Windows 95, Windows 98,
     * and LAN Manager. This attribute must be 20 characters or
     * less to support earlier clients.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms679635(v=vs.85).aspx
     *
     * @var string
     */
    public $samaccountname;

    /**
     * This attribute contains information about every account type object.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms679637(v=vs.85).aspx
     *
     * @var string
     */
    public $samaccounttype;

    /**
     * The name to be displayed on admin screens.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms675214(v=vs.85).aspx
     *
     * @var string
     */
    public $admindisplayname;

    /**
     * Ambiguous name resolution attribute to be used when choosing between objects.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms675223(v=vs.85).aspx
     *
     * @var string
     */
    public $anr;

    /**
     * The number of times the user tried to log on to the account using
     * an incorrect password. A value of 0 indicates that the
     * value is unknown.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms675244(v=vs.85).aspx
     *
     * @var string
     */
    public $badpwdcount;

    /**
     * The last time and date that an attempt to log on to this
     * account was made with a password that is not valid.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms675243(v=vs.85).aspx
     *
     * @var string
     */
    public $badpasswordtime;

     /**
     * The name that represents an object.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms675449(v=vs.85).aspx
     *
     * @var string
     */
    public $cn;

    /**
     * The user's company name.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms675457(v=vs.85).aspx
     *
     * @var string
     */
    public $company;

    /**
     * The object class computer string.
     *
     * Used when constructing new Computer models.
     *
     * @var string
     */
    public  $computer;

    /**
     * DN enterprise configuration naming context.
     *
     * @link https://support.microsoft.com/en-us/kb/219005
     *
     * @var string
     */
    public $configurationnamingcontext;

    /**
     * The object class contact string.
     *
     * Used when constructing new User models.
     *
     * @var string
     */
    public $contact;

     /**
     * The entry's country attribute.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms675432(v=vs.85).aspx
     *
     * @var string
     */
    public $c;

    /**
     * The entry's created at attribute.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms680924(v=vs.85).aspx
     *
     * @var string
     */
    public $whencreated;

    /**
     * This is the default NC for a particular server.
     *
     * By default, the DN for the domain of which this directory server is a member.
     *
     * @link https://support.microsoft.com/en-us/kb/219005
     *
     * @var string
     */
    public $defaultnamingcontext;

     /**
     * Contains the name for the department in which the user works.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms675490(v=vs.85).aspx
     *
     * @var string
     */
    public $department;

    /**
     * Contains the description to display for an object. This value is restricted
     * as single-valued for backward compatibility in some cases but
     * is allowed to be multi-valued in others.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms675492(v=vs.85).aspx
     *
     * @var string
     */
    public $description;

    /**
     * The display name for an object. This is usually the combination
     * of the users first name, middle initial, and last name.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms675514(v=vs.85).aspx
     *
     * @var string
     */
    public $displayname;

    /**
     * The LDAP API references an LDAP object by its distinguished name (DN).
     *
     * A DN is a sequence of relative distinguished names (RDN) connected by commas.
     *
     * @link https://msdn.microsoft.com/en-us/library/aa366101(v=vs.85).aspx
     *
     * @var string
     */
    public $dn;

    /**
     * Name of computer as registered in DNS.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms675524(v=vs.85).aspx
     *
     * @var string
     */
    public $dnshostname;

    /**
     * Domain Component located inside an RDN.
     *
     * @link https://msdn.microsoft.com/en-us/library/aa366101(v=vs.85).aspx
     *
     * @var string
     */
    public $dc;

    /**
     * The device driver name.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms675652(v=vs.85).aspx
     *
     * @var string
     */
    public $drivername;

    /**
     * The Version number of device driver.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms675653(v=vs.85).aspx
     *
     * @var string
     */
    public $driverversion;

    /**
     * The list of email addresses for a contact.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms676855(v=vs.85).aspx
     *
     * @var string
     */
    public $mail;

    /**
     * The email nickname for the user.
     *
     * @var string
     */
    public $mailnickname;

    /**
     * The ID of an employee.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms675662(v=vs.85).aspx
     *
     * @var string
     */
    public $employeeid;

    /**
     * The number assigned to an employee other than the ID.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms675663(v=vs.85).aspx
     *
     * @var string
     */
    public $employeenumber;

    /**
     * The job category for an employee.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms675664(v=vs.85).aspx
     *
     * @var string
     */
    public $employeetype;

    /**
     * The AD false bool in string form for conversion.
     *
     * @var string
     */
    public $FALSE;

    /**
     * Contains the given name (first name) of the user.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms675719(v=vs.85).aspx
     *
     * @var string
     */
    public $givenname;

    /**
     * Contains a set of flags that define the type and scope of a group object.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms675935(v=vs.85).aspx
     *
     * @var string
     */
    public $grouptype;

    /**
     * A user's home address.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms676193(v=vs.85).aspx
     *
     * @var string
     */
    public $homepostaladdress;

    /**
     * The users mailbox database location.
     *
     * @var string
     */
    public $homemdb;

    /**
     * {@inheritdoc}
     */
    public $info;

    /**
     * {@inheritdoc}
     */
    public $initials;

    /**
     * {@inheritdoc}
     */
    public $instancetype;

    /**
     * {@inheritdoc}
     */
    public $iscriticalsystemobject;

    /**
     * {@inheritdoc}
     */
    public $lastlogoff;

    /**
     * {@inheritdoc}
     */
    public $lastlogon;

    /**
     * {@inheritdoc}
     */
    public $lastlogontimestamp;

    /**
     * {@inheritdoc}
     */
    public $sn;

    /**
     * {@inheritdoc}
     */
    public $legacyexchangedn;

    /**
     * {@inheritdoc}
     */
    public $l;

    /**
     * {@inheritdoc}
     */
    public $location;

    /**
     * {@inheritdoc}
     */
    public $lockouttime;

    /**
     * {@inheritdoc}
     */
    public $manager;

    /**
     * {@inheritdoc}
     */
    public $maxpwdage;

    /**
     * {@inheritdoc}
     */
    public $member;

    /**
     * {@inheritdoc}
     */
    public $memberof;

    /**
     * {@inheritdoc}
     */
    public $messagetrackingenabled;

    /**
     * {@inheritdoc}
     */
    //public $ms-exch-exchange-server;

    /**
     * {@inheritdoc}
     */
    public $name;

    /**
     * {@inheritdoc}
     */
    public $objectcategory;

    /**
     * {@inheritdoc}
     */
    public $container;

    /**
     * {@inheritdoc}
     */
    public $msexchprivatemdb;

    /**
     * {@inheritdoc}
     */
    public $msExchExchangeServer;

    /**
     * {@inheritdoc}
     */
    public $msExchStorageGroup;

    /**
     * {@inheritdoc}
     */
    public $group;

    /**
     * {@inheritdoc}
     */
    //public $organizational-unit;

    /**
     * {@inheritdoc}
     */
    //public $print-queue;

    /**
     * {@inheritdoc}
     */
    public $objectclass;

    /**
     * {@inheritdoc}
     */
    public $person;

    /**
     * {@inheritdoc}
     */
    public $user;

    /**
     * {@inheritdoc}
     */
    public $printqueue;

    /**
     * {@inheritdoc}
     */
    public $objectguid;

    /**
     * {@inheritdoc}
     */
    public $objectsid;

    /**
     * {@inheritdoc}
     */
    public $operatingsystem;

    /**
     * {@inheritdoc}
     */
    public $operatingsystemservicepack;

    /**
     * {@inheritdoc}
     */
    public $operatingsystemversion;

    /**
     * {@inheritdoc}
     */
    public $organizationalperson;

    /**
     * {@inheritdoc}
     */
    public $organizationalunit;

    /**
     * {@inheritdoc}
     */
    public $ou;

    /**
     * {@inheritdoc}
     */
    public $othermailbox;

    /**
     * {@inheritdoc}
     */
    public $pwdlastset;

    /**
     * {@inheritdoc}
     */
    public $personaltitle;

    /**
     * {@inheritdoc}
     */
    public $physicaldeliveryofficename;

    /**
     * {@inheritdoc}
     */
    public $portname;

    /**
     * {@inheritdoc}
     */
    public $postalcode;

    /**
     * {@inheritdoc}
     */
    public $primarygroupid;

    /**
     * {@inheritdoc}
     */
    public $printbinnames;

    /**
     * {@inheritdoc}
     */
    public $printcolor;

    /**
     * {@inheritdoc}
     */
    public $printduplexsupported;

    /**
     * {@inheritdoc}
     */
    public $printendtime;

    /**
     * {@inheritdoc}
     */
    public $printmaxresolutionsupported;

    /**
     * {@inheritdoc}
     */
    public $printmediasupported;

    /**
     * {@inheritdoc}
     */
    public $printmemory;

    /**
     * {@inheritdoc}
     */
    public $printername;

    /**
     * {@inheritdoc}
     */
    public $printorientationssupported;

    /**
     * {@inheritdoc}
     */
    public $printrate;

    /**
     * {@inheritdoc}
     */
    public $printrateunit;

    /**
     * {@inheritdoc}
     */
    public $printsharename;

    /**
     * {@inheritdoc}
     */
    public $printstaplingsupported;

    /**
     * {@inheritdoc}
     */
    public $printstarttime;

    /**
     * {@inheritdoc}
     */
    public $priority;

    /**
     * {@inheritdoc}
     */
    public $profilepath;

    /**
     * {@inheritdoc}
     */
    public $proxyaddresses;

    /**
     * {@inheritdoc}
     */
    public $scriptpath;

    /**
     * {@inheritdoc}
     */
    public $serialnumber;

    /**
     * {@inheritdoc}
     */
    public $servername;

    /**
     * {@inheritdoc}
     */
    public $showinaddressbook;

    /**
     * {@inheritdoc}
     */
    public $street;

    /**
     * {@inheritdoc}
     */
    public $streetaddress;

    /**
     * An integer value that contains flags that define additional properties of the class.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms680022(v=vs.85).aspx
     *
     * @return string
     */
    public $systemflags;

    /**
     * The primary telephone number.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms680027(v=vs.85).aspx
     *
     * @return string
     */
    public $telephonenumber;

   /**
     * The users thumbnail photo path.
     *
     * @return string
     */
    public $thumbnailphoto;

    /**
     * Contains the user's job title.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms680037(v=vs.85).aspx
     *
     * @return string
     */
    public $title;

    /**
     * The top level class from which all classes are derived.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms683975(v=vs.85).aspx
     *
     * @return string
     */
    public $top;

    /**
     * The AD true bool in string form for conversion.
     *
     * @return string
     */
    public $TRUE;

    /**
     * The password of the user in Windows NT one-way format (OWF). Windows 2000 uses the Windows NT OWF.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms680513(v=vs.85).aspx
     *
     * @return string
     */
    public $unicodepwd;

    /**
     * The date when this object was last changed.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms680921(v=vs.85).aspx
     *
     * @return string
     */
    public $whenchanged;

    /**
     * The entry's URL attribute.
     *
     * @return string
     */
    public $url;

    /**
     * Flags that control the behavior of the user account.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms680832(v=vs.85).aspx
     *
     * @return string
     */
    public $useraccountcontrol;

    /**
     * his attribute contains the UPN that is an Internet-style login name for
     * a user based on the Internet standard RFC 822.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms680857(v=vs.85).aspx
     *
     * @return string
     */
    public $userprincipalname;

    /**
     * A general purpose version number.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms680897(v=vs.85).aspx
     *
     * @return string
     */
    public $versionnumber;
}
