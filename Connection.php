<?php
/**
 * @link      https://github.com/chrmorandi/yii2-ldap for the canonical source repository
 * @package   yii2-ldap
 * @author    Christopher Mota <chrmorandi@gmail.com>
 * @license   MIT License - view the LICENSE file that was distributed with this source code.
 */

namespace chrmorandi\ldap;

use InvalidArgumentException;
use yii\base\Component;
use yii\db\sqlite\Schema;

/**
 * 
 *
 * @author Christopher Mota <chrmorandi@gmail.com>
 * @since 1.0
 */
class Connection extends Component implements ConnectionInterface
{
    use LdapFunctionSupportTrait;
    use LdapFunctionTrait;

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
     * @var array|object
     */
    protected $schema;
    
    /**
     * {@inheritdoc}
     */
    public function setSchema($schema = null)
    {
        if (is_null($schema)) {
            // Retrieve the default schema if one isn't given.
            $schema = Schema::getDefault();
        } elseif (!$schema instanceof SchemaInterface) {
            $class = SchemaInterface::class;

            throw new InvalidArgumentException("Schema must be an instance of $class");
        }

        $this->schema = $schema;
    }

    /**
     * {@inheritdoc}
     */
    public function getSchema()
    {
        return $this->schema;
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
     * {@inheritdoc}
     */
    public function isBound()
    {
        return $this->bound;
    }

    /**
     * {@inheritdoc}
     */
    public function connect($hostname = [], $port = '389')
    {
        $protocol = $this::PROTOCOL;

        if (is_array($hostname)) {
            $hostname = $protocol.implode(' '.$protocol, $hostname);
        }
        $this->resource = ldap_connect($hostname, $port);
        
        if(!$this->resource){            
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
     * {@inheritdoc}
     */
    public function close()
    {
        if (is_resource($this->resource)) {
            ldap_close($this->resource);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function controlPagedResult($pageSize = 1000, $isCritical = false, $cookie = '')
    {
        if ($this->isPagingSupported()) {
            return ldap_control_paged_result($this->resource, $pageSize, $isCritical, $cookie);
        }

        $message = 'LDAP Pagination is not supported on your current PHP installation.';

        throw new LdapException($message);
    }

    /**
     * {@inheritdoc}
     */
    public function controlPagedResultResponse($result, &$cookie)
    {
        if ($this->isPagingSupported()) {
            return ldap_control_paged_result_response($this->resource, $result, $cookie);
        }

        $message = 'LDAP Pagination is not supported on your current PHP installation.';

        throw new LdapException($message);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getExtendedError()
    {
        return $this->getDiagnosticMessage();
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedErrorCode()
    {
        return $this->extractDiagnosticCode($this->getExtendedError());
    }

    /**
     * {@inheritdoc}
     */
    public function extractDiagnosticCode($message)
    {
        preg_match('/^([\da-fA-F]+):/', $message, $matches);

        if (!isset($matches[1])) {
            return false;
        }

        return $matches[1];
    }
    
    /**
     * Execute ldap functions like.
     * 
     * http://php.net/manual/en/ref.ldap.php
     * 
     * @param  string $function php LDAP function
     * @param  array $params params for execute ldap function
     * @return bool|resource
     * @throws LdapException
     */
    public function execute($function, $params)
    {
        $this->open();

        $result = call_user_func($function, $this->resource, ...$params);
        if (!$result) {
            throw new LdapException($this->getLastError(), $this->getErrNo());
        }
        
        if(is_resource($result)){
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