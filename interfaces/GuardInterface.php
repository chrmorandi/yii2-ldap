<?php

namespace chrmorandi\ldap\interfaces;

use chrmorandi\ldap\interfaces\ConnectionInterface;

interface GuardInterface
{
    /**
     * Constructor.
     *
     * @param ConnectionInterface $connection
     * @param Configuration       $configuration
     */
    public function __construct(Connection $connection, Configuration $configuration);

    /**
     * Authenticates a user using the specified credentials.
     *
     * @param string $username   The users AD username.
     * @param string $password   The users AD password.
     * @param bool   $bindAsUser Whether or not to bind as the user.
     *
     * @throws \Adldap\exceptions\Auth\BindException
     * @throws \Adldap\exceptions\Auth\UsernameRequiredException
     * @throws \Adldap\exceptions\Auth\PasswordRequiredException
     *
     * @return bool
     */
    public function attempt($username, $password, $bindAsUser = false);

    /**
     * Binds to the current connection using the
     * inserted credentials.
     *
     * @param string $username
     * @param string $password
     * @param string $prefix
     * @param string $suffix
     *
     * @returns void
     *
     * @throws \Adldap\exceptions\Auth\BindException
     */
    public function bind($username, $password, $prefix = null, $suffix = null);

    /**
     * Binds to the current LDAP server using the
     * configuration administrator credentials.
     *
     * @throws \Adldap\exceptions\Auth\BindException
     */
    public function bindAsAdministrator();
}
