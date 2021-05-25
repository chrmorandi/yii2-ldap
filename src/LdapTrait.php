<?php

namespace chrmorandi\ldap;

trait LdapTrait
{

    /**
     * @var resource|false
     */
    protected $resource;

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
     * @return resource|false the result entry identifier for the first entry on success and FALSE on error.
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
        return ($result == false) ? [] : $result;
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
