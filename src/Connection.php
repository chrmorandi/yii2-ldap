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
use yii\caching\CacheInterface;
use yii\caching\Dependency;
use yii\caching\TagDependency;

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

    use LdapTrait;

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
     * @var int The LDAP port to use when connecting to the domain controllers.
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
    protected $bound = false;

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
        $password = "\"" . $password . "\"";
        $adpassword = mb_convert_encoding($password, "UTF-16LE", "UTF-8");
        return $adpassword;
    }

    /**
     * Returns the current query cache information.
     * This method is used internally by [[Command]].
     * @param integer $duration the preferred caching duration. If null, it will be ignored.
     * @param Dependency $dependency the preferred caching dependency. If null, it will be ignored.
     * @return array|null the current query cache information, or null if query cache is not enabled.
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
        if ($cache instanceof CacheInterface) {
            TagDependency::invalidate($cache, $tags);
        }
    }

    /**
     * Connects and Binds to the Domain Controller with a administrator credentials.
     * @return void
     */
    public function open($anonymous = false)
    {
        $token = 'Opening LDAP connection: ' . LdapHelper::recursiveImplode($this->dc, ' or ');
        Yii::info($token, __METHOD__);
        Yii::beginProfile($token, __METHOD__);
        // Connect to the LDAP server.
        $this->connect($this->dc, $this->port);
        Yii::endProfile($token, __METHOD__);

        //TODO se não logar não causa exeção e sim um retorno boleano
        try {
            if ($anonymous) {
                $this->bound = ldap_bind($this->resource);
            } else {
                $this->bound = ldap_bind($this->resource, $this->username, $this->password);
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
        $entry = $this->getFirstEntry($searchResult);
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
        if (isset($this->resource) && $this->resource !== false) {
            ldap_unbind($this->resource);
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
        $cookie = '';
        $errorCode = $dn = $errorMessage = $refs = null;

        $token = $function . ' - params: ' . LdapHelper::recursiveImplode($params, ';');
        Yii::info($token, 'chrmorandi\ldap\Connection::query');
        Yii::beginProfile($token, 'chrmorandi\ldap\Connection::query');

        do {
            if ($this->pageSize > 0) {
                $this->setOption(LDAP_OPT_SERVER_CONTROLS, [
                    LDAP_CONTROL_PAGEDRESULTS =>
                    [
                        'oid' => LDAP_CONTROL_PAGEDRESULTS,
                        'value' => ['size' => $this->pageSize, 'cookie' => $cookie]
                    ]
                ]);
            }

            // Run the search.
            if (!($result = call_user_func($function, $this->resource, ...$params))) {
                break;
            }

            if ($this->pageSize > 0) {
                ldap_parse_result($this->resource, $result, $errorCode, $dn, $errorMessage, $refs, $controls);
                $cookie = $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'];
            }

            //Collect each resource result
            $results[] = $result;
        } while (!empty($cookie));

        Yii::endProfile($token, 'chrmorandi\ldap\Connection::query');

        return new DataReader($this, $results);
    }

}
