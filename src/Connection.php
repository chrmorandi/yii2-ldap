<?php
/**
 * @link      https://github.com/chrmorandi/yii2-ldap for the source repository
 * @package   yii2-ldap
 * @author    Christopher Mota <chrmorandi@gmail.com>
 * @license   MIT License - view the LICENSE file that was distributed with this source code.
 * @since     1.0.0
 */

namespace chrmorandi\ldap;

use Yii;
use yii\base\Component;
use yii\caching\Cache;

/**
 * @property resource $resource
 * @property bool     $bount
 * @property int      $errNo Error number of the last command
 * @property string   $lastError Error message of the last command
 *
 * @author Christopher Mota <chrmorandi@gmail.com>
 * @since  1.0
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
     * @var int
     */
    const PORT = 389;

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
    public $useTLS = true;

    /**
     * @var array the domain controllers to connect to.
     */
    public $dc = [];

    /**
     * @var string the username for establishing LDAP connection. Defaults to `null` meaning no username to use.
     */
    public $username;

    /**
     * @var string the password for establishing DB connection. Defaults to `null` meaning no password to use.
     */
    public $password;

    /**
     * @var int The page size for the paging operation.
     */
    public $pageSize = -1;

    /**
     * @var integer zero-based offset from where the records are to be returned. If not set or
     * less than 1, it means not filter values.
     */
    public $offset = -1;

    /**
     * @var bool whether to enable caching.
     * Note that in order to enable query caching, a valid cache component as specified
     * by [[cache]] must be enabled and [[enableCache]] must be set true.
     * Also, only the results of the queries enclosed within [[cache()]] will be cached.
     * @see cacheDuration
     * @see cache
     */
    public $enableCache = true;

    /**
     * @var integer number of seconds that table metadata can remain valid in cache.
     * Use 0 to indicate that the cached data will never expire.
     * @see enableCache
     */
    public $cacheDuration = 3600;

    /**
     * @var string the cache the ID of the cache application component that
     * is used to cache result query.
     * @see enableCache
     */
    public $cache = 'cache';

    /**
     * @var string the attribute for authentication
     */
    public $loginAttribute = "sAMAccountName";

    /**
     * @var bool stores the bool whether or not the current connection is bound.
     */
    protected $_bound = false;

    /**
     * @var resource|false
     */
    protected $resource;

    /**
     *
     * @var string
     */
    protected $userDN;

    /**
     * Create AD password (Microsoft Active Directory password format)
     * @param string $password
     * @return string
     */
    protected static function encodePassword($password)
    {
        $password   = "\"" . $password . "\"";
        $adpassword = mb_convert_encoding($password, "UTF-16LE", "UTF-8");
        return $adpassword;
    }

    /**
     * Returns the current query cache information.
     * This method is used internally by [[Command]].
     * @param integer $duration the preferred caching duration. If null, it will be ignored.
     * @param \yii\caching\Dependency $dependency the preferred caching dependency. If null, it will be ignored.
     * @return array the current query cache information, or null if query cache is not enabled.
     * @internal
     */
    public function getCacheInfo($duration = 3600, $dependency = null)
    {
        if (!$this->enableCache) {
            return null;
        }

        if (($duration === 0 || $duration > 0) && Yii::$app) {
            $cache = Yii::$app->get($this->cache, false);
            if ($cache instanceof Cache) {
                return [$cache, $duration, $dependency];
            }
        }

        return null;
    }

    /**
     * Invalidates the cached data that are associated with any of the specified [[tags]] in this connection.
     * @param string|array $tags
     */
    public function clearCache($tags)
    {
        $cache = Yii::$app->get($this->cache, false);
        \yii\caching\TagDependency::invalidate($cache, $tags);
    }

    /**
     * Connects and Binds to the Domain Controller with a administrator credentials.
     * @return void
     */
    public function open($anonymous = false)
    {
        $token = 'Opening LDAP connection: ' . LdapHelper::recursive_implode($this->dc, ' or ');
        Yii::info($token, __METHOD__);
        Yii::beginProfile($token, __METHOD__);
        // Connect to the LDAP server.
        $this->connect($this->dc, $this->port);
        Yii::endProfile($token, __METHOD__);

        try {
            if ($anonymous) {
                $this->_bound = ldap_bind($this->resource);
            } else {
                $this->_bound = ldap_bind($this->resource, $this->username, $this->password);
            }
        } catch (\Exception $e) {
            throw new \Exception('Invalid credential for user manager in ldap.', 0);
        }
    }

    /**
     * Connection.
     * @param string|array $hostname
     * @param int $port
     * @return void
     */
    protected function connect($hostname = [], $port = 389)
    {
        if (is_array($hostname)) {
            $hostname = self::PROTOCOL . implode(' ' . self::PROTOCOL, $hostname);
        }

        $this->close();
        $this->resource = ldap_connect($hostname, $port);

        // Set the LDAP options.
        $this->setOption(LDAP_OPT_PROTOCOL_VERSION, 3);
        $this->setOption(LDAP_OPT_REFERRALS, $this->followReferrals);
        $this->setOption(LDAP_OPT_NETWORK_TIMEOUT, 2);

        if ($this->useTLS) {
            $this->startTLS();
        }

        $this->trigger(self::EVENT_AFTER_OPEN);
    }

    /**
     * Authenticate user
     * @param string $username
     * @param string $password
     * @return bool indicate occurrence of error.
     */
    public function auth($username, $password)
    {
        // Open connection with manager
        $this->open();

        # Search for user and get user DN
        $searchResult = ldap_search($this->resource, $this->baseDn, "(&(objectClass=person)($this->loginAttribute=$username))", [$this->loginAttribute]);
        $entry        = $this->getFirstEntry($searchResult);
        if ($entry) {
            $this->userDN = $this->getDn($entry);
        } else {
            // User not found.
            return false;
        }

        // Connect to the LDAP server.
        $this->connect($this->dc, $this->port);

        // Try to authenticate user, but ignore any PHP warnings.
        return @ldap_bind($this->resource, $this->userDN, $password);
    }

    /**
     * Change the password of the current user. This must be performed over TLS.
     * @param string $username User for change password
     * @param string $oldPassword The old password
     * @param string $newPassword The new password
     * @return bool return true if change password is success
     * @throws \Exception
     */
    public function changePasswordAsUser($username, $oldPassword, $newPassword)
    {
        if (!$this->useTLS) {
            $message = 'TLS must be configured on your web server and enabled to change passwords.';
            throw new \Exception($message);
        }

        // Open connection with user
        if (!$this->auth($username, $oldPassword)) {
            return false;
        }

        return $this->changePasswordAsManager($this->userDN, $newPassword);
    }

    /**
     * Change the password of the user as manager. This must be performed over TLS.
     * @param string $userDN User Distinguished Names (DN) for change password. Ex.: cn=admin,dc=example,dc=com
     * @param string $newPassword The new password
     * @return bool return true if change password is success
     * @throws \Exception
     */
    public function changePasswordAsManager($userDN, $newPassword)
    {
        if (!$this->useTLS) {
            $message = 'TLS must be configured on your web server and enabled to change passwords.';
            throw new \Exception($message);
        }

        // Open connection with manager
        $this->open();

        // Replace passowrd attribute for AD
        // The AD password change procedure is modifying the attribute unicodePwd
        $modifications['unicodePwd'] = self::encodePassword($newPassword);
        return ldap_mod_replace($this->resource, $userDN, $modifications);
    }

    /**
     * Closes the current connection.
     *
     * @return bool
     */
    public function close()
    {
        if (is_resource($this->resource)) {
            ldap_close($this->resource);
        }
        return true;
    }

    /**
     * Execute ldap search like.
     *
     * @link http://php.net/manual/en/ref.ldap.php
     *
     * @param  string $function php LDAP function
     * @param  array $params params for execute ldap function
     * @return bool|DataReader
     */
    public function executeQuery($function, $params)
    {
        $this->open();
        $results = [];
        $cookie  = '';
        $token   = $function . ' - params: ' . LdapHelper::recursive_implode($params, ';');

        Yii::info($token, 'chrmorandi\ldap\Connection::query');

        Yii::beginProfile($token, 'chrmorandi\ldap\Connection::query');
        do {
            if ($this->pageSize > 0) {
                $this->setControlPagedResult($cookie);
            }

            // Run the search.
            $result = call_user_func($function, $this->resource, ...$params);

            if ($this->pageSize > 0) {
                $this->setControlPagedResultResponse($result, $cookie);
            }

            //Collect each resource result
            $results[] = $result;
        } while (!is_null($cookie) && !empty($cookie));
        Yii::endProfile($token, 'chrmorandi\ldap\Connection::query');

        return new DataReader($this, $results);
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
     * Batch modifies an existing entry on the current connection.
     * The types of modifications:
     *      LDAP_MODIFY_BATCH_ADD - Each value specified through values is added.
     *      LDAP_MODIFY_BATCH_REMOVE - Each value specified through values is removed.
     *          Any value of the attribute not contained in the values array will remain untouched.
     *      LDAP_MODIFY_BATCH_REMOVE_ALL - All values are removed from the attribute named by attrib.
     *      LDAP_MODIFY_BATCH_REPLACE - All current values are replaced by new one.
     * @param string $dn
     * @param array  $values array associative with three keys: "attrib", "modtype" and "values".
     * ```php
     * [
     *     "attrib"  => "attribute",
     *     "modtype" => LDAP_MODIFY_BATCH_ADD,
     *     "values"  => ["attribute value one"],
     * ],
     * ```
     * @return mixed
     */
    public function modify($dn, array $values)
    {
        return ldap_modify_batch($this->resource, $dn, $values);
    }

    /**
     * Retrieve the entries from a search result.
     * @param resource $searchResult
     * @return array|bool
     */
    public function getEntries($searchResult)
    {
        return ldap_get_entries($this->resource, $searchResult);
    }

    /**
     * Retrieves the number of entries from a search result.
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
     * @return resource link identifier
     */
    public function getFirstEntry($searchResult)
    {
        return ldap_first_entry($this->resource, $searchResult);
    }

    /**
     * Retrieves the next entry from a search result.
     * @param resource $entry link identifier
     * @return resource
     */
    public function getNextEntry($entry)
    {
        return ldap_next_entry($this->resource, $entry);
    }

    /**
     * Retrieves the ldap first entry attribute.
     * @param resource $entry
     * @return string
     */
    public function getFirstAttribute($entry)
    {
        return ldap_first_attribute($this->resource, $entry);
    }

    /**
     * Retrieves the ldap next entry attribute.
     * @param resource $entry
     * @return string
     */
    public function getNextAttribute($entry)
    {
        return ldap_next_attribute($this->resource, $entry);
    }

    /**
     * Retrieves the ldap entry's attributes.
     * @param resource $entry
     * @return array
     */
    public function getAttributes($entry)
    {
        return ldap_get_attributes($this->resource, $entry);
    }

    /**
     * Retrieves all binary values from a result entry. Individual values are accessed by integer index in the array.
     * The first index is 0. The number of values can be found by indexing "count" in the resultant array.
     *
     * @link https://www.php.net/manual/en/function.ldap-get-values-len.php
     *
     * @param resource $entry Link identifier
     * @param string $attribute Name of attribute
     * @return array Returns an array of values for the attribute on success and empty array on error.
     */
    public function getValuesLen($entry, $attribute)
    {
        $result = ldap_get_values_len($this->resource, $entry, $attribute);
        return is_array($result) ? $result : [];
    }

    /**
     * Retrieves the DN of a result entry.
     *
     * @link https://www.php.net/manual/en/function.ldap-get-dn.php
     *
     * @param resource $entry
     * @return string
     */
    public function getDn($entry)
    {
        return ldap_get_dn($this->resource, $entry);
    }

    /**
     * Free result memory.
     *
     * @link https://www.php.net/manual/en/function.ldap-free-result.php
     *
     * @param resource $searchResult
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function freeResult($searchResult)
    {
        return ldap_free_result($searchResult);
    }

    /**
     * Sets an option on the current connection.
     *
     * @link https://www.php.net/manual/en/function.ldap-set-option.php
     *
     * @param int   $option The parameter.
     * @param mixed $value The new value for the specified option.
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function setOption($option, $value)
    {
        return ldap_set_option($this->resource, $option, $value);
    }

    /**
     * Starts a connection using TLS.
     *
     * @link https://www.php.net/manual/en/function.ldap-start-tls.php
     *
     * @return bool
     */
    public function startTLS()
    {
        return ldap_start_tls($this->resource);
    }

    /**
     * Send LDAP pagination control.
     *
     * @link http://php.net/manual/en/function.ldap-control-paged-result.php
     *
     * @param string $cookie An opaque structure sent by the server
     * @param bool   $isCritical Indicates whether the pagination is critical or not. If true and if the server doesn't support pagination, the search will return no result.
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function setControlPagedResult($cookie = '', $isCritical = false)
    {
        return ldap_control_paged_result($this->resource, $this->pageSize, $isCritical, $cookie);
    }

    /**
     * Retrieve a paginated result response.
     *
     * @link https://www.php.net/manual/en/function.ldap-control-paged-result-response.php
     *
     * @param resource $result
     * @param string $cookie An opaque structure sent by the server
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function setControlPagedResultResponse($result, &$cookie)
    {
        return ldap_control_paged_result_response($this->resource, $result, $cookie);
    }

    /**
     * Return the LDAP error message of the last LDAP command.
     *
     * @link https://www.php.net/manual/en/function.ldap-error.php
     *
     * @return string Error message.
     */
    public function getLastError()
    {
        return ldap_error($this->resource);
    }

    /**
     * Returns the number of the last error on the current connection.
     *
     * @link https://www.php.net/manual/en/function.ldap-errno.php
     *
     * @return int Error number
     */
    public function getErrNo()
    {
        return ldap_errno($this->resource);
    }

    /**
     * Returns the error string of the specified error number.
     *
     * @link https://www.php.net/manual/en/function.ldap-err2str.php
     *
     * @param int $number The error number.
     * @return string  Error message.
     */
    public function err2Str($number)
    {
        return ldap_err2str($number);
    }

}
