<?php

namespace LdapTools\Utilities;

/**
 * A very thin wrapper around some PHP DNS functions. Mostly for the purpose of testing.
 *
 */
class Dns
{
    /**
     * Call this just like you would dns_get_record.
     *
     * @param mixed ...$arguments
     * @return array
     */
    public function getRecord(...$arguments)
    {
        return dns_get_record(...$arguments);
    }
}
