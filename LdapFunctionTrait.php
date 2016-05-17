<?php

namespace chrmorandi\ldap;

/**
 * Class LdapFunctionTrait.
 */
trait LdapFunctionTrait
{
    
    /**
     * @var ConnectionInterface
     */
    protected $resource;
    
    /**
     * Get the current resource of connection.
     *
     * @return mixed
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Sorts an AD search result by the specified attribute.
     *
     * @param resource $result
     * @param string   $attribute
     *
     * @return bool
     */
    public function sort($result, $attribute)
    {
        return ldap_sort($this->resource, $result, $attribute);
    }   

    /**
     * Adds an entry to the current connection.
     *
     * @param string $dn
     * @param array  $entry
     *
     * @return bool
     */
    public function add($dn, array $entry)
    {
        return ldap_add($this->resource, $dn, $entry);
    }

    /**
     * Deletes an entry on the current connection.
     *
     * @param string $dn
     *
     * @return bool
     */
    public function delete($dn)
    {
        return ldap_delete($this->resource, $dn);
    }

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
    public function rename($dn, $newRdn, $newParent, $deleteOldRdn = false)
    {
        return ldap_rename($this->resource, $dn, $newRdn, $newParent, $deleteOldRdn);
    }

    /**
     * Modifies an existing entry on the
     * current connection.
     *
     * @param string $dn
     * @param array  $entry
     *
     * @return bool
     */
    public function modify($dn, array $entry)
    {
        return ldap_modify($this->resource, $dn, $entry);
    }

    /**
     * Batch modifies an existing entry on the
     * current connection.
     *
     * @param string $dn
     * @param array  $values
     *
     * @return mixed
     */
    public function modifyBatch($dn, array $values)
    {
        return ldap_modify_batch($this->resource, $dn, $values);
    }

     /**
     * Add attribute values to current attributes.
     *
     * @param string $dn
     * @param array  $entry
     *
     * @return mixed
     */
    public function modAdd($dn, array $entry)
    {
        return ldap_mod_add($this->resource, $dn, $entry);
    }

    /**
     * Replaces attribute values with new ones.
     *
     * @param string $dn
     * @param array  $entry
     *
     * @return mixed
     */
    public function modReplace($dn, array $entry)
    {
        return ldap_mod_replace($this->resource, $dn, $entry);
    }

    /**
     * Delete attribute values from current attributes.
     *
     * @param string $dn
     * @param array  $entry
     *
     * @return mixed
     */
    public function modDelete($dn, array $entry)
    {
        return ldap_mod_del($this->resource, $dn, $entry);
    }
    
    /**
     * Retrieve the entries from a search result.
     *
     * @param $searchResult
     *
     * @return mixed
     */
    public function getEntries($searchResults)
    {
        return ldap_get_entries($this->resource, $searchResults);
    }
    
    /**
     * Returns the number of entries from a search
     * result.
     *
     * @param $searchResult
     *
     * @return int
     */
    public function countEntries($searchResults)
    {
        return ldap_count_entries($this->resource, $searchResults);
    }

    /**
     * Retrieves the first entry from a search result.
     *
     * @param $searchResult
     *
     * @return mixed
     */
    public function getFirstEntry($searchResults)
    {
        return ldap_first_entry($this->resource, $searchResults);
    }

    /**
     * Retrieves the next entry from a search result.
     *
     * @param $entry
     *
     * @return mixed
     */
    public function getNextEntry($entry)
    {
        return ldap_next_entry($this->resource, $entry);
    }

    /**
     * Retrieves the ldap entry's attributes.
     *
     * @param $entry
     *
     * @return mixed
     */
    public function getAttributes($entry)
    {
        return ldap_get_attributes($this->resource, $entry);
    }    

    /**
     * Get all binary values from the specified result entry.
     *
     * @param $entry
     * @param $attribute
     *
     * @return array
     */
    public function getValuesLen($entry, $attribute)
    {
        return ldap_get_values_len($this->resource, $entry, $attribute);
    }

    /**
     * {@inheritdoc}
     */
    public function setOption($option, $value)
    {
        return ldap_set_option($this->resource, $option, $value);
    }

    /**
     * Sets an option on the current connection.
     *
     * @param int   $option
     * @param mixed $value
     *
     * @return mixed
     */
    public function setRebindCallback(callable $callback)
    {
        return ldap_set_rebind_proc($this->resource, $callback);
    }
    
    /**
     * Starts a connection using TLS.
     *
     * @return mixed
     */
    public function startTLS()
    {
        return ldap_start_tls($this->resource);
    }
    
     /**
     * Returns the error number of the last command
     * executed on the current connection.
     *
     * @return mixed
     */
    public function getErrNo()
    {
        return ldap_errno($this->resource);
    }
    
    /**
     * Retrieve the last error on the current
     * connection.
     *
     * @return string
     */
    public function getLastError()
    {
        return ldap_error($this->resource);
    }

    /**
     * Returns the error string of the specified
     * error number.
     *
     * @param int $number
     *
     * @return string
     */
    public function err2Str($number)
    {
        return ldap_err2str($number);
    }

    /**
     * Return the diagnostic Message.
     *
     * @return string
     */
    public function getDiagnosticMessage()
    {
        ldap_get_option($this->resource, LDAP_OPT_ERROR_STRING, $diagnosticMessage);

        return $diagnosticMessage;
    }
}


