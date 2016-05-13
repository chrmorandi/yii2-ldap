<?php
/**
 * @package   yii2-ldap
 * @author    @author Christopher Mota <chrmorandi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace chrmorandi\ldap;

use chrmorandi\ldap\exceptions\AdldapException;
use chrmorandi\ldap\exceptions\BindException;
use chrmorandi\ldap\exceptions\ConnectionException;
use chrmorandi\ldap\exceptions\InvalidArgumentException;
use chrmorandi\ldap\interfaces\SchemaInterface;
use chrmorandi\ldap\operation\OperationInterface;
use Yii;
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
     * Stores the bool whether or not
     * the current connection is bound.
     *
     * @var bool
     */
    protected $bound = false;

    /**
     * @var Configuration
     */
    protected $config;
    
    
    /**
     * @var SchemaInterface
     */
    protected $schema;
    
    /**
     * @var array|null options for configuration.
     */  
    public $options;
    
    
    public function init() {
        $this->setConfiguration($this->options);
        parent::init();
    }
    
    /**
     * {@inheritdoc}
     */
    public function __destruct()
    {
        if ($this->conn instanceof ConnectionInterface && $this->conn->isBound()) {
            $this->conn->close();
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function setConfiguration($configuration = [])
    {
        if (is_array($configuration)) {
            // Construct a configuration instance if an array is given.
            $configuration = new Configuration($configuration);
        } elseif (!$configuration instanceof Configuration) {
            $class = Configuration::class;

            throw new InvalidArgumentException("Configuration must be either an array or instance of $class");
        }

        $this->config = $configuration;
    }
    
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
    public function getConfiguration()
    {
        return $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function getSchema()
    {
        return $this->schema;
    }
    
    /**
     * Connects and Binds to the Domain Controller.
     *
     * If no username or password is specified, then the
     * configured administrator credentials are used.
     *
     * @param string|null $username
     * @param string|null $password
     *
     * @throws ConnectionException
     * @throws BindException
     *
     */
    public function open($username = null, $password = null)
    {
        // Retrieve the domain controllers.
        $controllers = $this->config->getDomainControllers();

        // Retrieve the port we'll be connecting to.
        $port = $this->config->getPort();

        // Connect to the LDAP server.
        if ($this->connect($controllers, $port)) {

            if (is_null($username) && is_null($password)) {
                // If both the username and password are null, we'll connect to the server
                // using the configured administrator username and password.
                $this->bindAsAdministrator();
            } else {
                // Bind to the server with the specified username and password otherwise.
                $this->bind($username, $password);
            }
        } else {
            throw new ConnectionException('Unable to connect to LDAP server.');
        }
    }

    /**
     * Binds to the current LDAP server using the
     * configuration administrator credentials.
     *
     * @throws \Adldap\exceptions\Auth\BindException
     */
    public function bindAsAdministrator()
    {
        $credentials = $this->config->getAdminCredentials();

        list($username, $password, $suffix) = array_pad($credentials, 3, null);

        if (empty($suffix)) {
            // Use the user account suffix if no administrator account suffix is given.
            $suffix = $this->config->getAccountSuffix();
        }

        $this->bind($username, $password, '', $suffix);
    }
    
    /**
     * Binds to LDAP with the supplied credentials or anonymously if specified.
     *
     * @param string $username The username to bind with.
     * @param string $password The password to bind with.
     * @param string $prefix
     * @param string $suffix
     * @param bool $anonymous Whether this is an anonymous bind attempt.
     * @throws BindException
     */
    protected function bind($username, $password, $prefix = null, $suffix = null, $anonymous = false)
    {
        if ($anonymous) {
            $this->isBound = @ldap_bind($this->conn);
        } else {
            // If the username isn't empty, we'll append the configured
            // account prefix and suffix to bind to the LDAP server.
            if (is_null($prefix)) {
                $prefix = $this->config->getAccountPrefix();
            }

            if (is_null($suffix)) {
                $suffix = $this->config->getAccountSuffix();
            }

            $username = $prefix.$username.$suffix;
            
            $this->isBound = @ldap_bind($this->conn, $username, $password);
        }

        if (!$this->isBound) {
            throw new BindException(
                sprintf('Unable to bind to LDAP: %s', $this->lastError),
                $this->errNo
            );
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
    public function canChangePasswords()
    {
        if (!$this->config->isUsingTLS()) {
            return false;
        }

        return true;
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
        $this->conn = ldap_connect($hostname, $port);
        
        if(!$this->conn){            
            return false;          
        }
        
        $followReferrals = $this->config->getFollowReferrals();

        // Set the LDAP options.
        $this->setOption(LDAP_OPT_PROTOCOL_VERSION, 3);
        $this->setOption(LDAP_OPT_REFERRALS, $followReferrals);

        if ($this->config->getUseTls() && !$this->startTLS()) {
            throw new ConnectionException(
                sprintf("Failed to start TLS: %s", $this->lastError),
                $this->getErrNo()
            );
        }
        
        return true;
    }    

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if (is_resource($this->conn)) {
            ldap_close($this->conn);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function controlPagedResult($pageSize = 1000, $isCritical = false, $cookie = '')
    {
        if ($this->isPagingSupported()) {
            return ldap_control_paged_result($this->conn, $pageSize, $isCritical, $cookie);
        }

        $message = 'LDAP Pagination is not supported on your current PHP installation.';

        throw new AdldapException($message);
    }

    /**
     * {@inheritdoc}
     */
    public function controlPagedResultResponse($result, &$cookie)
    {
        if ($this->isPagingSupported()) {
            return ldap_control_paged_result_response($this->conn, $result, $cookie);
        }

        $message = 'LDAP Pagination is not supported on your current PHP installation.';

        throw new AdldapException($message);
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
     * @param OperationInterface $operation
     * @return mixed
     * @throws ConnectionException
     */
    public function execute(OperationInterface $operation)
    {        
        $result = true;
        $this->open();
        $token = $operation->name;
        
        try {           
            Yii::beginProfile($token, 'chrmorandi\ldap\Connection::execute');
            return $operation->execute();
        } catch (\Exception $e) {
            $this->logExceptionAndThrow($e, $log);
        } finally {
            Yii::endProfile($token, 'chrmorandi\ldap\Connection::execute');
            $this->resetLdapControls($operation);
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