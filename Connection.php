<?php
/**
 * @package   yii2-ldap
 * @author    @author Christopher Mota <chrmorandi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace chrmorandi\ldap;

use chrmorandi\ldap\Exception\LdapConnectionException;
use chrmorandi\ldap\exceptions\AdldapException;
use chrmorandi\ldap\exceptions\BindException;
use chrmorandi\ldap\exceptions\ConnectionException;
use chrmorandi\ldap\exceptions\InvalidArgumentException;
use chrmorandi\ldap\interfaces\ConnectionInterface;
use chrmorandi\ldap\interfaces\GuardInterface;
use chrmorandi\ldap\interfaces\SchemaInterface;
use chrmorandi\ldap\operation\AuthenticationOperation;
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

    /**
     * @event Event an event that is triggered after a DB connection is established
     */
    const EVENT_AFTER_OPEN = 'afterOpen';
    
    /**
     * Stores the bool to tell the connection
     * whether or not to use SSL.
     *
     * To use SSL, your server must support LDAP over SSL.
     * http://adldap.sourceforge.net/wiki/doku.php?id=ldap_over_ssl
     *
     * @var bool
     */
    protected $useSSL = false;

    /**
     * Stores the bool to tell the connection
     * whether or not to use TLS.
     *
     * If you wish to use TLS you should ensure that $useSSL is set to false and vice-versa
     *
     * @var bool
     */
    protected $useTLS = false;

    /**
     * Stores the bool whether or not
     * the current connection is bound.
     *
     * @var bool
     */
    protected $bound = false;
    
    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var Configuration
     */
    protected $configuration;
    
    /**
     * @var string|null The LDAP server that we are currently connected to.
     */  
    protected $server;
    
    /**
     * @var SchemaInterface
     */
    protected $schema;

    /**
     * @var GuardInterface
     */
    protected $guard;
    
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
        if ($this->connection instanceof ConnectionInterface && $this->connection->isBound()) {
            $this->connection->close();
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

        $this->configuration = $configuration;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return $this->configuration;
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
    public function getSchema()
    {
        return $this->schema;
    }
    
    /**
     * {@inheritdoc}
     */
    public function setGuard(GuardInterface $guard)
    {
        $this->guard = $guard;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getGuard()
    {
        if (!$this->guard instanceof GuardInterface) {
            $this->guard = new Guard($this, $this->configuration);
        }

        return $this->guard;
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
        // Prepare the connection.
        $this->prepareConnection();

        // Retrieve the domain controllers.
        $controllers = $this->configuration->getDomainControllers();

        // Retrieve the port we'll be connecting to.
        $port = $this->configuration->getPort();

        // Connect to the LDAP server.
        if ($this->connect($controllers, $port)) {
            $followReferrals = $this->configuration->getFollowReferrals();

            // Set the LDAP options.
            $this->setOption(LDAP_OPT_PROTOCOL_VERSION, 3);
            $this->setOption(LDAP_OPT_REFERRALS, $followReferrals);

            // Get the default guard instance.
            $guard = $this->getGuard();

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
     * {@inheritdoc}
     */
    public function bindt($username, $password, $prefix = null, $suffix = null)
    {
        if (empty($username)) {
            // Allow binding with null username.
            $username = null;
        } else {
            // If the username isn't empty, we'll append the configured
            // account prefix and suffix to bind to the LDAP server.
            if (is_null($prefix)) {
                $prefix = $this->configuration->getAccountPrefix();
            }

            if (is_null($suffix)) {
                $suffix = $this->configuration->getAccountSuffix();
            }

            $username = $prefix.$username.$suffix;
        }

        if (empty($password)) {
            // Allow binding with null password.
            $password = null;
        }

        // We'll mute any exceptions / warnings here. All we need to know
        // is if binding failed and we'll throw our own exception.
        if (!$this->bind($username, $password)) {
            $error = $this->connection->getLastError();

            if ($this->isUsingSSL() && $this->connection->isUsingTLS() === false) {
                $message = 'Bind to Active Directory failed. Either the LDAP SSL connection failed or the login credentials are incorrect. AD said: '.$error;
            } else {
                $message = 'Bind to Active Directory failed. Check the login credentials and/or server details. AD said: '.$error;
            }

            throw new BindException($message, $this->connection->errNo());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function bindAsAdministrator()
    {
        $credentials = $this->configuration->getAdminCredentials();

        list($username, $password, $suffix) = array_pad($credentials, 3, null);

        if (empty($suffix)) {
            // Use the user account suffix if no administrator account suffix is given.
            $suffix = $this->configuration->getAccountSuffix();
        }

        $this->bindt($username, $password, '', $suffix);
    }
    
    /**
     * Prepares the connection by setting configured parameters.
     *
     * @return void
     */
    protected function prepareConnection()
    {
        // Set the beginning protocol options on the connection
        // if they're set in the configuration.
        if ($this->configuration->getUseSSL()) {
            $this->connection->useSSL();
        } elseif ($this->configuration->getUseTLS()) {
            $this->connection->useTLS();
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function isUsingSSL()
    {
        return $this->useSSL;
    }

    /**
     * {@inheritdoc}
     */
    public function isUsingTLS()
    {
        return $this->useTLS;
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
        if (!$this->isUsingSSL() && !$this->isUsingTLS()) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function useSSL()
    {
        $this->useSSL = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function useTLS()
    {
        $this->useTLS = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntries($searchResults)
    {
        return ldap_get_entries($this->getConnection(), $searchResults);
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstEntry($searchResults)
    {
        return ldap_first_entry($this->getConnection(), $searchResults);
    }

    /**
     * {@inheritdoc}
     */
    public function getNextEntry($entry)
    {
        return ldap_next_entry($this->getConnection(), $entry);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes($entry)
    {
        return ldap_get_attributes($this->getConnection(), $entry);
    }

    /**
     * {@inheritdoc}
     */
    public function countEntries($searchResults)
    {
        return ldap_count_entries($this->getConnection(), $searchResults);
    }

    /**
     * {@inheritdoc}
     */
    public function getLastError()
    {
        return ldap_error($this->getConnection());
    }

    /**
     * {@inheritdoc}
     */
    public function getValuesLen($entry, $attribute)
    {
        return ldap_get_values_len($this->getConnection(), $entry, $attribute);
    }

    /**
     * {@inheritdoc}
     */
    public function setOption($option, $value)
    {
        return ldap_set_option($this->getConnection(), $option, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function setRebindCallback(callable $callback)
    {
        return ldap_set_rebind_proc($this->getConnection(), $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function startTLS()
    {
        return ldap_start_tls($this->getConnection());
    }

    /**
     * {@inheritdoc}
     */
    public function connect($hostname = [], $port = '389')
    {
        $protocol = $this::PROTOCOL;

        if ($this->isUsingSSL()) {
            $protocol = $this::PROTOCOL_SSL;
        }

        if (is_array($hostname)) {
            $hostname = $protocol.implode(' '.$protocol, $hostname);
        }

        return $this->connection = ldap_connect($hostname, $port);
    }
    /**
     * Makes the initial connection to LDAP, sets connection options, and starts TLS if specified.
     *
     * @param null|string $server
     * @throws LdapConnectionException
     */
//    protected function initiateLdapConnection($server = null)
//    {
//        $ldapUrl = $this->getLdapUrl($server);
//
//        $this->connection = @ldap_connect($ldapUrl, $this->config->getPort());
//        if (!$this->connection) {
//            throw new LdapConnectionException(
//                sprintf("Failed to initiate LDAP connection with URI: %s", $ldapUrl)
//            );
//        }
//
//        foreach ($this->config->getLdapOptions() as $option => $value) {
//            if (!ldap_set_option($this->connection, $option, $value)) {
//                throw new LdapConnectionException("Failed to set LDAP connection option.");
//            }
//        }
//
//        if ($this->config->getUseTls() && !@ldap_start_tls($this->connection)) {
//            throw new LdapConnectionException(
//                sprintf("Failed to start TLS: %s", $this->getLastError()),
//                $this->getExtendedErrorNumber()
//            );
//        }
//
//        $this->server = explode('://', $ldapUrl)[1];
//    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $connection = $this->getConnection();

        if (is_resource($connection)) {
            ldap_close($connection);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function search($dn, $filter, array $fields)
    {
        return ldap_search($this->getConnection(), $this->configuration->getBaseDn(), $filter, $fields);
    }

    /**
     * {@inheritdoc}
     */
    public function listing($dn, $filter, array $attributes)
    {
        return ldap_list($this->getConnection(), $dn, $filter, $attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function read($dn, $filter, array $fields)
    {
        return ldap_read($this->getConnection(), $dn, $filter, $fields);
    }

    /**
     * {@inheritdoc}
     */
    public function sort($result, $attribute)
    {
        return ldap_sort($this->getConnection(), $result, $attribute);
    }

    /**
     * {@inheritdoc}
     */
    public function bind($username, $password, $sasl = false)
    {
        if ($this->isUsingTLS()) {
            $this->startTLS();
        }

        if ($sasl) {
            return $this->bound = ldap_sasl_bind($this->getConnection(), null, null, 'GSSAPI');
        }

        return $this->bound = ldap_bind($this->getConnection(), $username, $password);
    }
    
    /**
     * Binds to LDAP with the supplied credentials or anonymously if specified.
     *
     * @param string $username The username to bind with.
     * @param string $password The password to bind with.
     * @param bool $anonymous Whether this is an anonymous bind attempt.
     * @throws LdapBindException
     */
//    protected function bind($username, $password, $anonymous = false)
//    {
//        if ($anonymous) {
//            $this->isBound = @ldap_bind($this->connection);
//        } else {
//            $this->isBound = @ldap_bind(
//                $this->connection,
//                LdapUtilities::encode($username, $this->config->getEncoding()),
//                LdapUtilities::encode($password, $this->config->getEncoding())
//            );
//        }
//
//        if (!$this->isBound) {
//            throw new LdapBindException(
//                sprintf('Unable to bind to LDAP: %s', $this->getLastError()),
//                $this->getExtendedErrorNumber()
//            );
//        }
//    }
    

    /**
     * {@inheritdoc}
     */
    public function add($dn, array $entry)
    {
        return ldap_add($this->getConnection(), $dn, $entry);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($dn)
    {
        return ldap_delete($this->getConnection(), $dn);
    }

    /**
     * {@inheritdoc}
     */
    public function rename($dn, $newRdn, $newParent, $deleteOldRdn = false)
    {
        return ldap_rename($this->getConnection(), $dn, $newRdn, $newParent, $deleteOldRdn);
    }

    /**
     * {@inheritdoc}
     */
    public function modify($dn, array $entry)
    {
        return ldap_modify($this->getConnection(), $dn, $entry);
    }

    /**
     * {@inheritdoc}
     */
    public function modifyBatch($dn, array $values)
    {
        return ldap_modify_batch($this->getConnection(), $dn, $values);
    }

    /**
     * {@inheritdoc}
     */
    public function modAdd($dn, array $entry)
    {
        return ldap_mod_add($this->getConnection(), $dn, $entry);
    }

    /**
     * {@inheritdoc}
     */
    public function modReplace($dn, array $entry)
    {
        return ldap_mod_replace($this->getConnection(), $dn, $entry);
    }

    /**
     * {@inheritdoc}
     */
    public function modDelete($dn, array $entry)
    {
        return ldap_mod_del($this->getConnection(), $dn, $entry);
    }

    /**
     * {@inheritdoc}
     */
    public function controlPagedResult($pageSize = 1000, $isCritical = false, $cookie = '')
    {
        if ($this->isPagingSupported()) {
            return ldap_control_paged_result($this->getConnection(), $pageSize, $isCritical, $cookie);
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
            return ldap_control_paged_result_response($this->getConnection(), $result, $cookie);
        }

        $message = 'LDAP Pagination is not supported on your current PHP installation.';

        throw new AdldapException($message);
    }

    /**
     * {@inheritdoc}
     */
    public function errNo()
    {
        return ldap_errno($this->getConnection());
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
    public function err2Str($number)
    {
        return ldap_err2str($number);
    }

    /**
     * {@inheritdoc}
     */
    public function getDiagnosticMessage()
    {
        ldap_get_option($this->getConnection(), LDAP_OPT_ERROR_STRING, $diagnosticMessage);

        return $diagnosticMessage;
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
    
    /**
     * Performs the logic for switching the LDAP server connection.
     *
     * @param string|null $currentServer The server we are currently on.
     * @param string|null $wantedServer The server we want the connection to be on.
     * @param LdapOperationInterface $operation
     */
    protected function switchServerIfNeeded($currentServer, $wantedServer, $operation)
    {
        if ($operation instanceof AuthenticationOperation || strtolower($currentServer) == strtolower($wantedServer)) {
            return;
        }
        if ($this->connection->isBound()) {
            $this->connection->close();
        }
        $this->connection->connect(null, null, false, $wantedServer);
    }
}