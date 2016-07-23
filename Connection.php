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
 * @property string $errNo Error number of the last command
 * @property string $lastError Error message of the last command
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
    protected $bound = false;

    /**
     * @var resource
     */
    protected $resource;

    /**
     * Get the current resource of connection.
     * @return mixed
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Connects and Binds to the Domain Controller with a administrator credentials.
     * @throws LdapException
     */
    public function open($anonymous = false)
    {
        // Connect to the LDAP server.
        if ($this->connect($this->dc, $this->port)) {
            if ($anonymous) {
                $this->isBound = ldap_bind($this->connection);
            } else {
                $this->bound = ldap_bind($this->resource, $this->username, $this->password);
            }
        } else {
            throw new LdapException(sprintf('Unable to connect to server: %s', $this->lastError), $this->errNo);
        }
    }

    /**
     * Returns true/false if the current connection is bound.
     * @return bool
     */
    public function isBound()
    {
        return $this->bound;
    }

    /**
     * Connection.
     * @param string $hostname
     * @param type $port
     * @return boolean
     * @throws LdapException
     */
    public function connect($hostname = [], $port = '389')
    {
        $protocol = $this::PROTOCOL;

        if (is_array($hostname)) {
            $hostname = $protocol . implode(' ' . $protocol, $hostname);
        }
        $this->resource = ldap_connect($hostname, $port);

        if (!$this->resource) {
            return false;
        }

        // Set the LDAP options.
        $this->setOption(LDAP_OPT_PROTOCOL_VERSION, 3);
        $this->setOption(LDAP_OPT_REFERRALS, $this->followReferrals);

        if ($this->useTLS && !$this->startTLS()) {
            throw new LdapException($this->lastError, $this->getErrNo());
        }

        $this->trigger(self::EVENT_AFTER_OPEN);

        return is_resource($this->resource);
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
     * @throws LdapException
     */
    public function execute($function, $params)
    {
        $this->open();

        $result = call_user_func($function, $this->resource, ...$params);
        if (!$result) {
            throw new LdapException($this->getLastError(), $this->getErrNo());
        }

        if (is_resource($result)) {
            return new DataReader($this, $result);
        }

        return $result;
    }

    /**
     * Close the connection before serializing.
     * @return array
     */
    public function __sleep()
    {
        $this->close();
        return array_keys((array) $this);
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
     * @return mixed
     */
    public function modAdd($dn, array $entry)
    {
        return ldap_mod_add($this->resource, $dn, $entry);
    }

    /**
     * Replaces attribute values with new ones.
     * @param string $dn
     * @param array  $entry
     * @return mixed
     */
    public function modReplace($dn, array $entry)
    {
        return ldap_mod_replace($this->resource, $dn, $entry);
    }

    /**
     * Delete attribute values from current attributes.
     * @param string $dn
     * @param array  $entry
     * @return mixed
     */
    public function modDelete($dn, array $entry)
    {
        return ldap_mod_del($this->resource, $dn, $entry);
    }
    
    /**
     * Retrieve the entries from a search result.
     * @param $searchResult
     * @return mixed
     */
    public function getEntries($searchResult)
    {
        return ldap_get_entries($this->resource, $searchResult);
    }
    
    /**
     * Returns the number of entries from a search result.
     * @param $searchResult
     * @return int
     */
    public function countEntries($searchResult)
    {
        return ldap_count_entries($this->resource, $searchResult);
    }

    /**
     * Retrieves the first entry from a search result.
     * @param $searchResult
     * @return mixed
     */
    public function getFirstEntry($searchResult)
    {
        return ldap_first_entry($this->resource, $searchResult);
    }

    /**
     * Retrieves the next entry from a search result.
     * @param $entry
     * @return mixed
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
     * @return mixed
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
     * @return mixed
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
