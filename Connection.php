<?php

namespace chrmorandi\ldap;

use chrmorandi\ldap\exceptions\AdldapException;
use chrmorandi\ldap\interfaces\ConnectionInterface;
use yii\base\Component;

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
     * @var SchemaInterface
     */
    protected $schema;

    /**
     * @var GuardInterface
     */
    protected $guard;
    
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
            $this->guard = new Guard($this->connection, $this->configuration);
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
     * @throws \chrmorandi\ldap\exceptions\ConnectionException
     * @throws \chrmorandi\ldap\exceptions\BindException
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
                $guard->bindAsAdministrator();
            } else {
                // Bind to the server with the specified username and password otherwise.
                $guard->bind($username, $password);
            }
        } else {
            throw new ConnectionException('Unable to connect to LDAP server.');
        }
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
        return ldap_search($this->getConnection(), $dn, $filter, $fields);
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
    
    
}