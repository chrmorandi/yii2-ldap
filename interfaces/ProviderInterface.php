<?php

namespace chrmorandi\ldap\interfaces;

use chrmorandi\ldap\Connections\Configuration;
use chrmorandi\ldap\Contracts\Auth\GuardInterface;
use chrmorandi\ldap\interfaces\SchemaInterface;

interface ProviderInterface
{
    /**
      * Constructor.
      *
      * @param Configuration|array       $configuration
      * @param ConnectionInterface|null  $connection
      * @param SchemaInterface|null      $schema
      */
     public function __construct($configuration, ConnectionInterface $connection, SchemaInterface $schema = null);

    /**
     * Destructor.
     *
     * Closes the current LDAP connection if it exists.
     */
    public function __destruct();

    /**
     * Returns the current configuration instance.
     *
     * @return Configuration
     */
    public function getConfiguration();

    /**
     * Returns the current Guard instance.
     *
     * @return \Adldap\Auth\Guard
     */
    public function getGuard();

    /**
     * Sets the current configuration.
     *
     * @param Configuration|array $configuration
     */
    public function setConfiguration($configuration = []);

    /**
     * Sets the current LDAP attribute schema.
     *
     * @param SchemaInterface|null $schema
     */
    public function setSchema($schema = null);

    /**
     * Returns the current LDAP attribute schema.
     *
     * @return SchemaInterface
     */
    public function getSchema();

    /**
     * Sets the current Guard instance.
     *
     * @param GuardInterface $guard
     */
    public function setGuard(GuardInterface $guard);

    
    /**
     * Connects and Binds to the Domain Controller.
     *
     * If no username or password is specified, then the
     * configured administrator credentials are used.
     *
     * @param string|null $username
     * @param string|null $password
     *
     * @throws \Adldap\exceptions\ConnectionException
     * @throws \Adldap\exceptions\Auth\BindException
     *
     * @return void
     */
    public function connect($username = null, $password = null);

}
