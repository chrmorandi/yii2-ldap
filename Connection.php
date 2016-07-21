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
 *
 *
 * @author Christopher Mota <chrmorandi@gmail.com>
 * @since 1.0
 */
class Connection extends Component
{
    use LdapFunctionTrait;

    /**
     * LDAP protocol string.
     *
     * @var string
     */
    const PROTOCOL = 'ldap://';

    /**
     * LDAP port number.
     *
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
     *
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
     * @var Connection
     */
    protected $resource;
    
    /**
     * Get the current resource of connection.
     *
     * @return Connection
     */
    public function getResource()
    {
        return $this->resource;
    }
    
    /**
     * Connects and Binds to the Domain Controller with a administrator credentials.
     *
     * @throws LdapException
     */
    public function open($anonymous = false)
    {
        // Connect to the LDAP server.
        if ($this->connect($this->dc, $this->port)) {
            if ($anonymous) {
                $this->bound = ($this->resource);
            } else {
                $this->bound = ldap_bind($this->resource, $this->username, $this->password);
            }
        } else {
            throw new LdapException(sprintf('Unable to connect to server: %s', $this->lastError), $this->errNo);
        }
    }

    /**
     * Returns true / false if the current
     * connection is bound.
     *
     * @return bool
     */
    public function isBound()
    {
        return $this->bound;
    }

    /**
     * Retrieve the last error on the current
     * connection.
     *
     * @return boolean
     */
    public function connect($hostname = [], $port = '389')
    {
        $protocol = $this::PROTOCOL;

        if (is_array($hostname)) {
            $hostname = $protocol.implode(' '.$protocol, $hostname);
        }
        $this->resource = ldap_connect($hostname, $port);
        
        if (!$this->resource) {
            return false;
        }
        
        $followReferrals = $this->followReferrals;

        // Set the LDAP options.
        $this->setOption(LDAP_OPT_PROTOCOL_VERSION, 3);
        $this->setOption(LDAP_OPT_REFERRALS, $followReferrals);

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
}
