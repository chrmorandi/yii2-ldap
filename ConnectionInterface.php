<?php
/**
 * @link      https://github.com/chrmorandi/yii2-ldap for the canonical source repository
 * @package   yii2-ldap
 * @author    Christopher Mota <chrmorandi@gmail.com>
 * @license   MIT License - view the LICENSE file that was distributed with this source code.
 */

namespace chrmorandi\ldap;

/**
 * The Connection interface used for making connections. Implementing
 * this interface on connection classes helps unit and functional
 * test classes that require a connection.
 *
 * Interface ConnectionInterface
 */
interface ConnectionInterface
{
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
     * Returns true / false if the
     * current connection is supported
     * on the current PHP install.
     *
     * @return bool
     */
    public function isSupported();

    /**
     * Returns true / false if the
     * current connection supports
     * SASL for single sign on
     * capability.
     *
     * @return bool
     */
    public function isSaslSupported();

    /**
     * Returns true / false if the
     * current connection pagination.
     *
     * @return bool
     */
    public function isPagingSupported();

    /**
     * Returns true / false if the
     * current connection supports batch
     * modification.
     *
     * @return bool
     */
    public function isBatchSupported();

    /**
     * Returns true / false if the current
     * connection is bound.
     *
     * @return bool
     */
    public function isBound();

    /**
     * Get the current connection.
     *
     * @return mixed
     */
    public function getResource();

    /**
     * Retrieve the entries from a search result.
     *
     * @param $searchResult
     *
     * @return mixed
     */
    public function getEntries($searchResult);

    /**
     * Returns the number of entries from a search
     * result.
     *
     * @param $searchResult
     *
     * @return int
     */
    public function countEntries($searchResult);

    /**
     * Retrieves the first entry from a search result.
     *
     * @param $searchResult
     *
     * @return mixed
     */
    public function getFirstEntry($searchResult);

    /**
     * Retrieves the next entry from a search result.
     *
     * @param $entry
     *
     * @return mixed
     */
    public function getNextEntry($entry);

    /**
     * Retrieves the ldap entry's attributes.
     *
     * @param $entry
     *
     * @return mixed
     */
    public function getAttributes($entry);

    /**
     * Retrieve the last error on the current
     * connection.
     *
     * @return string
     */
    public function getLastError();

    /**
     * Get all binary values from the specified result entry.
     *
     * @param $entry
     * @param $attribute
     *
     * @return array
     */
    public function getValuesLen($entry, $attribute);

    /**
     * Sets an option on the current connection.
     *
     * @param int   $option
     * @param mixed $value
     *
     * @return mixed
     */
    public function setOption($option, $value);

    /**
     * Set a callback function to do re-binds on referral chasing.
     *
     * @param callable $callback
     *
     * @return bool
     */
    public function setRebindCallback(callable $callback);

    /**
     * Connects to the specified hostname using the
     * specified port.
     *
     * @param string|array $hostname
     * @param int          $port
     *
     * @return mixed
     */
    public function connect($hostname = [], $port = 389);

    /**
     * Starts a connection using TLS.
     *
     * @return mixed
     */
    public function startTLS();

    /**
     * Closes the current connection.
     *
     * @return mixed
     */
    public function close();

    /**
     * Sorts an AD search result by the specified attribute.
     *
     * @param resource $result
     * @param string   $attribute
     *
     * @return bool
     */
    public function sort($result, $attribute);

    /**
     * Adds an entry to the current connection.
     *
     * @param string $dn
     * @param array  $entry
     *
     * @return bool
     */
    public function add($dn, array $entry);

    /**
     * Deletes an entry on the current connection.
     *
     * @param string $dn
     *
     * @return bool
     */
    public function delete($dn);

    /**
     * Modify the name of an entry on the current
     * connection.
     *
     * @param string $dn
     * @param string $newRdn
     * @param string $newParent
     * @param bool   $deleteOldRdn
     *
     * @return bool
     */
    public function rename($dn, $newRdn, $newParent, $deleteOldRdn = false);

    /**
     * Modifies an existing entry on the
     * current connection.
     *
     * @param string $dn
     * @param array  $entry
     *
     * @return bool
     */
    public function modify($dn, array $entry);

    /**
     * Batch modifies an existing entry on the
     * current connection.
     *
     * @param string $dn
     * @param array  $values
     *
     * @return mixed
     */
    public function modifyBatch($dn, array $values);

    /**
     * Add attribute values to current attributes.
     *
     * @param string $dn
     * @param array  $entry
     *
     * @return mixed
     */
    public function modAdd($dn, array $entry);

    /**
     * Replaces attribute values with new ones.
     *
     * @param string $dn
     * @param array  $entry
     *
     * @return mixed
     */
    public function modReplace($dn, array $entry);

    /**
     * Delete attribute values from current attributes.
     *
     * @param string $dn
     * @param array  $entry
     *
     * @return mixed
     */
    public function modDelete($dn, array $entry);

    /**
     * Send LDAP pagination control.
     *
     * @param int    $pageSize
     * @param bool   $isCritical
     * @param string $cookie
     *
     * @return mixed
     */
    public function controlPagedResult($pageSize = 1000, $isCritical = false, $cookie = '');

    /**
     * Retrieve a paginated result response.
     *
     * @param $result
     * @param string $cookie
     *
     * @return mixed
     */
    public function controlPagedResultResponse($result, &$cookie);

    /**
     * Returns the error number of the last command
     * executed on the current connection.
     *
     * @return mixed
     */
    public function getErrNo();

    /**
     * Returns the extended error string of the last command.
     *
     * @return mixed
     */
    public function getExtendedError();

    /**
     * Returns the extended error code of the last command.
     *
     * @return mixed
     */
    public function getExtendedErrorCode();

    /**
     * Returns the error string of the specified
     * error number.
     *
     * @param int $number
     *
     * @return string
     */
    public function err2Str($number);

    /**
     * Return the diagnostic Message.
     *
     * @return string
     */
    public function getDiagnosticMessage();

    /**
     * Extract the diagnostic code from the message.
     *
     * @param string $message
     *
     * @return string|bool
     */
    public function extractDiagnosticCode($message);
    
    
    
    
     /**
     * Destructor.
     *
     * Closes the current LDAP connection if it exists.
     */
    //public function __destruct();

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
     * Connects and Binds to the Domain Controller with a administrator credentials.
     * @param string|null $anonymous provider anonymous authentication
     * @throws LdapException
     * @return void
     */
    public function open($anonymous = null);
}
