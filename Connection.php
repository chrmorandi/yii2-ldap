<?php
/**
 * @link      https://github.com/chrmorandi/yii2-ldap for the canonical source repository
 * @package   yii2-ldap
 * @author    Christopher Mota <chrmorandi@gmail.com>
 * @license   MIT License - view the LICENSE file that was distributed with this source code.
 */

namespace chrmorandi\ldap;

use yii\base\Component;

/**
 * @property resource $resource
 * @property boolean  $bount
 * @property int      $errNo Error number of the last command
 * @property string   $lastError Error message of the last command
 *
 * @author Christopher Mota <chrmorandi@gmail.com>
 * @since 1.0
 */
class Connection extends Component
{
    /**
     * LDAP protocol string.
     * @var string
     */
    const PROTOCOL = 'ldap://';

    /**
     * LDAP port number.
     * @var string
     */
    const PORT = '389';

    /**
     * @event Event an event that is triggered after a DB connection is established
     */
    const EVENT_AFTER_OPEN = 'afterOpen';

    /**
     * @var string the LDAP base dn.
     */
    public $baseDn;

    /**
     * https://msdn.microsoft.com/en-us/library/ms677913(v=vs.85).aspx
     * @var bool the integer to instruct the LDAP connection whether or not to follow referrals.
     */
    public $followReferrals = false;

    /**
     * @var string The LDAP port to use when connecting to the domain controllers.
     */
    public $port = self::PORT;

    /**
     * @var bool Determines whether or not to use TLS with the current LDAP connection.
     */
    public $useTLS = false;

    /**
     * @var array the domain controllers to connect to.
     */
    public $dc = [];

    /**
     * @var string the LDAP account suffix.
     */
    protected $accountSuffix;

    /**
     * @var string the LDAP account prefix.
     */
    protected $accountPrefix;

    /**
     * @var string the username for establishing LDAP connection. Defaults to `null` meaning no username to use.
     */
    public $username;

    /**
     * @var string the password for establishing DB connection. Defaults to `null` meaning no password to use.
     */
    public $password;

    /**
     * @var bool stores the bool whether or not the current connection is bound.
     */
    protected $_bound = false;

    /**
     * @var resource|false
     */
    protected $resource;

    /**
     * Connects and Binds to the Domain Controller with a administrator credentials.
     * @return void
     */
    protected function open($anonymous = false)
    {
        // Connect to the LDAP server.
        $this->connect($this->dc, $this->port);

        if ($anonymous) {               
            $this->_bound = ldap_bind($this->resource);
        } else {
            $this->_bound = ldap_bind($this->resource, $this->username, $this->password);
        }
    }

    /**
     * Connection.
     * @param string|array $hostname
     * @param type $port
     * @return void
     */
    public function connect($hostname = [], $port = '389')
    {
        if (is_array($hostname)) {
            $hostname = self::PROTOCOL.implode(' '.self::PROTOCOL, $hostname);
        }

        $this->resource = ldap_connect($hostname, $port);

        // Set the LDAP options.     
        $this->setOption(LDAP_OPT_PROTOCOL_VERSION, 3);
        $this->setOption(LDAP_OPT_REFERRALS, $this->followReferrals);
        if ($this->useTLS) {
            $this->startTLS();
        }

        $this->trigger(self::EVENT_AFTER_OPEN);
    }
    
    /**
     * Closes the current connection.
     *
     * @return boolean
     */
    public function close()
    {
        if (is_resource($this->resource)) {
            ldap_close($this->resource);
        }
        return true;
    }

    /**
     * Execute ldap functions like.
     *
     * http://php.net/manual/en/ref.ldap.php
     *
     * @param  string $function php LDAP function
     * @param  array $params params for execute ldap function
     * @return bool|DataReader
     */
    public function execute($function, $params)
    {
        $this->open();

        $result = call_user_func($function, $this->resource, ...$params);

        if (is_resource($result)) {
            return new DataReader($this, $result);
        }

        return $result;
    }
    
    /**
     * Returns true/false if the current connection is bound.
     * @return bool
     */
    public function getBound()
    {
        return $this->_bound;
    }
    
    /**
     * Get the current resource of connection.
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }
    
    /**
     * Sorts an AD search result by the specified attribute.
     * @param resource $result
     * @param string   $attribute
     * @return bool
     */
    public function sort($result, $attribute)
    {
        return ldap_sort($this->resource, $result, $attribute);
    }

    /**
     * Adds an entry to the current connection.
     * @param string $dn
     * @param array  $entry
     * @return bool
     */
    public function add($dn, array $entry)
    {
        return ldap_add($this->resource, $dn, $entry);
    }

    /**
     * Deletes an entry on the current connection.
     * @param string $dn
     * @return bool
     */
    public function delete($dn)
    {
        return ldap_delete($this->resource, $dn);
    }

    /**
     * Modify the name of an entry on the current connection.
     *
     * @param string $dn
     * @param string $newRdn
     * @param string $newParent
     * @param bool   $deleteOldRdn
     * @return bool
     */
    public function rename($dn, $newRdn, $newParent, $deleteOldRdn = false)
    {
        return ldap_rename($this->resource, $dn, $newRdn, $newParent, $deleteOldRdn);
    }

    /**
     * Modifies an existing entry on the
     * current connection.
     * @param string $dn
     * @param array  $entry
     * @return bool
     */
    public function modify($dn, array $entry)
    {
        return ldap_modify($this->resource, $dn, $entry);
    }

    /**
     * Batch modifies an existing entry on the current connection.
     * @param string $dn
     * @param array  $values
     * @return mixed
     */
    public function modifyBatch($dn, array $values)
    {
        return ldap_modify_batch($this->resource, $dn, $values);
    }

    /**
     * Add attribute values to current attributes.
     * @param string $dn
     * @param array  $entry
     * @return boolean
     */
    public function modAdd($dn, array $entry)
    {
        return ldap_mod_add($this->resource, $dn, $entry);
    }

    /**
     * Replaces attribute values with new ones.
     * @param string $dn
     * @param array  $entry
     * @return boolean
     */
    public function modReplace($dn, array $entry)
    {
        return ldap_mod_replace($this->resource, $dn, $entry);
    }

    /**
     * Delete attribute values from current attributes.
     * @param string $dn
     * @param array  $entry
     * @return boolean
     */
    public function modDelete($dn, array $entry)
    {
        return ldap_mod_del($this->resource, $dn, $entry);
    }
    
    /**
     * Retrieve the entries from a search result.
     * @param resource $searchResult
     * @return array|boolean
     */
    public function getEntries($searchResult)
    {
        return ldap_get_entries($this->resource, $searchResult);
    }
    
    /**
     * Returns the number of entries from a search result.
     * @param resource $searchResult
     * @return int
     */
    public function countEntries($searchResult)
    {
        return ldap_count_entries($this->resource, $searchResult);
    }

    /**
     * Retrieves the first entry from a search result.
     * @param resource $searchResult
     * @return resource
     */
    public function getFirstEntry($searchResult)
    {
        return ldap_first_entry($this->resource, $searchResult);
    }

    /**
     * Retrieves the next entry from a search result.
     * @param $entry
     * @return resource
     */
    public function getNextEntry($entry)
    {
        return ldap_next_entry($this->resource, $entry);
    }

    /**
     * Retrieves the ldap entry's attributes.
     * @param $entry
     * @return mixed
     */
    public function getAttributes($entry)
    {
        return ldap_get_attributes($this->resource, $entry);
    }

    /**
     * Sets an option on the current connection.
     * @param int   $option
     * @param mixed $value
     * @return boolean
     */
    public function setOption($option, $value)
    {
        return ldap_set_option($this->resource, $option, $value);
    }
    
    /**
     * Starts a connection using TLS.
     * @return bool
     */
    public function startTLS()
    {
        return ldap_start_tls($this->resource);
    }
       
    /**
     * Retrieve the last error on the current connection.
     * @return string
     */
    public function getLastError()
    {
        return ldap_error($this->resource);
    }
    
    /**
     * Returns the number of the last error on the current connection.
     * @return int
     */
    public function getErrNo()
    {
        return ldap_errno($this->resource);
    }

    /**
     * Returns the error string of the specified error number.
     * @param int $number
     * @return string
     */
    public function err2Str($number)
    {
        return ldap_err2str($number);
    }
}
