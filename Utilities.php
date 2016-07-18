<?php
/**
 * @link      https://github.com/chrmorandi/yii2-ldap for the canonical source repository
 * @package   yii2-ldap
 * @author    Christopher Mota <chrmorandi@gmail.com>
 * @license   MIT License - view the LICENSE file that was distributed with this source code.
 */

namespace chrmorandi\ldap;

use LdapTools\Exception\InvalidArgumentException;

/**
 * 
 */
class Utilities
{
    /**
     * Converts a DN string into an array of RDNs.
     *
     * This will also decode hex characters into their true
     * UTF-8 representation embedded inside the DN as well.
     *
     * @param string $dn
     * @param bool   $removeAttributePrefixes
     *
     * @return array
     */
    public static function explodeDn($dn, $removeAttributePrefixes = true)
    {
        $dn = ldap_explode_dn($dn, ($removeAttributePrefixes ? 1 : 0));

        if (is_array($dn) && array_key_exists('count', $dn)) {
            foreach ($dn as $rdn => $value) {
                $dn[$rdn] = self::unescape($value);
            }
        }

        return $dn;
    }

    /**
     * Returns true / false if the current
     * PHP install supports escaping values.
     *
     * @return bool
     */
    public static function isEscapingSupported()
    {
        return function_exists('ldap_escape');
    }

    /**
     * Returns an escaped string for use in an LDAP filter.
     *
     * @param string $value
     * @param string $ignore
     * @param $flags
     *
     * @return string
     */
    public static function escape($value, $ignore = '', $flags = 0)
    {
        if (!static::isEscapingSupported()) {
            return static::escapeManual($value, $ignore, $flags);
        }

        return ldap_escape($value, $ignore, $flags);
    }

    /**
     * Escapes the inserted value for LDAP.
     *
     * @param string $value
     * @param string $ignore
     * @param int    $flags
     *
     * @return string
     */
    protected static function escapeManual($value, $ignore = '', $flags = 0)
    {
        // If a flag was supplied, we'll send the value off
        // to be escaped using the PHP flag values
        // and return the result.
        if ($flags) {
            return static::escapeManualWithFlags($value, $ignore, $flags);
        }

        // Convert ignore string into an array.
        $ignores = static::ignoreStrToArray($ignore);

        // Convert the value to a hex string.
        $hex = bin2hex($value);

        // Separate the string, with the hex length of 2, and
        // place a backslash on the end of each section.
        $value = chunk_split($hex, 2, '\\');

        // We'll append a backslash at the front of the string
        // and remove the ending backslash of the string.
        $value = '\\'.substr($value, 0, -1);

        // Go through each character to ignore.
        foreach ($ignores as $charToIgnore) {
            // Convert the character to ignore to a hex.
            $hexed = bin2hex($charToIgnore);

            // Replace the hexed variant with the original character.
            $value = str_replace('\\'.$hexed, $charToIgnore, $value);
        }

        // Finally we can return the escaped value.
        return $value;
    }

    /**
     * Escapes the inserted value with flags. Supplying either 1
     * or 2 into the flags parameter will escape only certain values.
     *
     *
     * @param string $value
     * @param string $ignore
     * @param int    $flags
     *
     * @return string
     */
    protected static function escapeManualWithFlags($value, $ignore = '', $flags = 0)
    {
        // Convert ignore string into an array
        $ignores = static::ignoreStrToArray($ignore);

        // The escape characters for search filters
        $escapeFilter = ['\\', '*', '(', ')'];

        // The escape characters for distinguished names
        $escapeDn = ['\\', ',', '=', '+', '<', '>', ';', '"', '#'];

        switch ($flags) {
            case 1:
                // Int 1 equals to LDAP_ESCAPE_FILTER
                $escapes = $escapeFilter;
                break;
            case 2:
                // Int 2 equals to LDAP_ESCAPE_DN
                $escapes = $escapeDn;
                break;
            case 3:
                // If both LDAP_ESCAPE_FILTER and LDAP_ESCAPE_DN are used
                $escapes = array_unique(array_merge($escapeDn, $escapeFilter));
                break;
            default:
                // We've been given an invalid flag, we'll escape everything to be safe.
                return static::escapeManual($value, $ignore);
        }

        foreach ($escapes as $escape) {
            // Make sure the escaped value isn't being ignored.
            if (!in_array($escape, $ignores)) {
                $hexed = static::escape($escape);

                $value = str_replace($escape, $hexed, $value);
            }
        }

        return $value;
    }

    /**
     * Un-escapes a hexadecimal string into
     * its original string representation.
     *
     * @param string $value
     *
     * @return string
     */
    public static function unescape($value)
    {
        $callback = function ($matches) {
            return chr(hexdec($matches[1]));
        };

        return preg_replace_callback('/\\\([0-9A-Fa-f]{2})/', $callback, $value);
    }

    /**
     * Convert a binary SID to a string SID.
     *
     * @param string $binSid A Binary SID
     *
     * @return string
     */
    public static function binarySidToString($binSid)
    {
        if (trim($binSid) == '' || is_null($binSid)) {
            return;
        }

        $hex = bin2hex($binSid);

        $rev = hexdec(substr($hex, 0, 2));

        $subCount = hexdec(substr($hex, 2, 2));

        $auth = hexdec(substr($hex, 4, 12));

        $result = "$rev-$auth";

        $subauth = [];

        for ($x = 0; $x < $subCount; $x++) {
            $subauth[$x] = hexdec(static::littleEndian(substr($hex, 16 + ($x * 8), 8)));

            $result .= '-'.$subauth[$x];
        }

        return 'S-'.$result;
    }

    /**
     * Convert a binary GUID to a string GUID.
     *
     * @param string $binGuid
     *
     * @return string
     */
    public static function binaryGuidToString($binGuid)
    {
        if (trim($binGuid) == '' || is_null($binGuid)) {
            return;
        }

        $hex = unpack('H*hex', $binGuid)['hex'];

        $hex1 = substr($hex, -26, 2).substr($hex, -28, 2).substr($hex, -30, 2).substr($hex, -32, 2);
        $hex2 = substr($hex, -22, 2).substr($hex, -24, 2);
        $hex3 = substr($hex, -18, 2).substr($hex, -20, 2);
        $hex4 = substr($hex, -16, 4);
        $hex5 = substr($hex, -12, 12);

        $guid = sprintf('%s-%s-%s-%s-%s', $hex1, $hex2, $hex3, $hex4, $hex5);

        return $guid;
    }

    /**
     * Converts a little-endian hex number to one that hexdec() can convert.
     *
     * @param string $hex A hex code
     *
     * @return string
     */
    public static function littleEndian($hex)
    {
        $result = '';

        for ($x = strlen($hex) - 2; $x >= 0; $x = $x - 2) {
            $result .= substr($hex, $x, 2);
        }

        return $result;
    }

    /**
     * Encode a password for transmission over LDAP.
     *
     * @param string $password The password to encode
     *
     * @return string
     */
    public static function encodePassword($password)
    {
        return iconv('UTF-8', 'UTF-16LE', '"'.$password.'"');
    }

    /**
     * Round a Windows timestamp down to seconds and remove
     * the seconds between 1601-01-01 and 1970-01-01.
     *
     * @param float $windowsTime
     *
     * @return float
     */
    public static function convertWindowsTimeToUnixTime($windowsTime)
    {
        return round($windowsTime / 10000000) - 11644473600;
    }

    /**
     * Convert a Unix timestamp to Windows timestamp.
     *
     * @param float $unixTime
     *
     * @return float
     */
    public static function convertUnixTimeToWindowsTime($unixTime)
    {
        return ($unixTime + 11644473600) * 10000000;
    }

    /**
     * Validates that the inserted string is an object SID.
     *
     * @param string $sid
     *
     * @return bool
     */
    public static function isValidSid($sid)
    {
        preg_match("/S-1-5-21-\d+-\d+\-\d+\-\d+/", $sid, $matches);

        if (count($matches) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Converts an ignore string into an array.
     *
     * @param string $ignore
     *
     * @return array
     */
    protected static function ignoreStrToArray($ignore)
    {
        $ignore = trim($ignore);

        if (!empty($ignore)) {
            return str_split($ignore);
        }

        return [];
    }
    
     /**
     * Regex to match a GUID.
     */
    const MATCH_GUID = '/^([0-9a-fA-F]){8}(-([0-9a-fA-F]){4}){3}-([0-9a-fA-F]){12}$/';

    /**
     * Regex to match a Windows SID.
     */
    const MATCH_SID = '/^S-\d-(\d+-){1,14}\d+$/i';

    /**
     * Regex to match an OID.
     */
    const MATCH_OID = '/^[0-9]+(\.[0-9]+?)*?$/';

    /**
     * Regex to match an attribute descriptor.
     */
    const MATCH_DESCRIPTOR = '/^\pL[\pL\pN-]+$/iu';

    /**
     * The prefix for a LDAP DNS SRV record.
     */
    const SRV_PREFIX = '_ldap._tcp.';

    /**
     * The mask to use when sanitizing arrays with LDAP password information.
     */
    const MASK = '******';

    /**
     * The attributes to mask in a batch/attribute array.
     */
    const MASK_ATTRIBUTES = [
        'unicodepwd',
        'userpassword',
    ];

    /**
     * Escape any special characters for LDAP to their hexadecimal representation.
     *
     * @param mixed $value The value to escape.
     * @param null|string $ignore The characters to ignore.
     * @param null|int $flags The context for the escaped string. LDAP_ESCAPE_FILTER or LDAP_ESCAPE_DN.
     * @return string The escaped value.
     */
    public static function escapeValue($value, $ignore = null, $flags = null)
    {
        // If this is a hexadecimal escaped string, then do not escape it.
        $value = preg_match('/^(\\\[0-9a-fA-F]{2})+$/', (string) $value) ? $value : ldap_escape($value, $ignore, $flags);

        // Per RFC 4514, leading/trailing spaces should be encoded in DNs, as well as carriage returns.
        if ((int)$flags & LDAP_ESCAPE_DN) {
            if (!empty($value) && $value[0] === ' ') {
                $value = '\\20' . substr($value, 1);
            }
            if (!empty($value) && $value[strlen($value) - 1] === ' ') {
                $value = substr($value, 0, -1) . '\\20';
            }
            // Only carriage returns seem to be valid, not line feeds (per testing of AD anyway).
            $value = str_replace("\r", '\0d', $value);
        }

        return $value;
    }

    /**
     * Un-escapes a value from its hexadecimal form back to its string representation.
     *
     * @param string $value
     * @return string
     */
    public static function unescapeValue($value)
    {
        $callback = function ($matches) {
            return chr(hexdec($matches[1]));
        };

        return preg_replace_callback('/\\\([0-9A-Fa-f]{2})/', $callback, $value);
    }

    /**
     * Converts a string distinguished name into its separate pieces.
     *
     * @param string $dn
     * @param int $withAttributes Set to 0 to get the attribute names along with the value.
     * @return array
     */
    public static function explodeDn($dn, $withAttributes = 1)
    {
        $pieces = ldap_explode_dn($dn, $withAttributes);

        if ($pieces === false || !isset($pieces['count']) || $pieces['count'] == 0) {
            throw new InvalidArgumentException(sprintf('Unable to parse DN "%s".', $dn));
        }
        for ($i = 0; $i < $pieces['count']; $i++) {
            $pieces[$i] = self::unescapeValue($pieces[$i]);
        }
        unset($pieces['count']);

        return $pieces;
    }

    /**
     * Given a DN as an array in ['cn=Name', 'ou=Employees', 'dc=example', 'dc=com'] form, return it as its string
     * representation that is safe to pass back to a query or to save back to LDAP for a DN.
     *
     * @param array $dn
     * @return string
     */
    public static function implodeDn(array $dn)
    {
        foreach ($dn as $index => $piece) {
            $values = explode('=', $piece, 2);
            if (count($values) === 1) {
                throw new InvalidArgumentException(sprintf('Unable to parse DN piece "%s".', $values[0]));
            }
            $dn[$index] = $values[0].'='.self::escapeValue($values[1], null, LDAP_ESCAPE_DN);
        }

        return implode(',', $dn);
    }

    /**
     * Encode a string for LDAP with a specific encoding type.
     *
     * @param string $value The value to encode.
     * @param string $toEncoding The encoding type to use (ie. UTF-8)
     * @return string The encoded value.
     */
    public static function encode($value, $toEncoding)
    {
        // If the encoding is already UTF-8, and that's what was requested, then just send the value back.
        if ($toEncoding == 'UTF-8' && preg_match('//u', $value)) {
            return $value;
        }

        if (function_exists('mb_detect_encoding')) {
            $value = iconv(mb_detect_encoding($value, mb_detect_order(), true), $toEncoding, $value);
        } else {
            // How else to better handle if they don't have mb_* ? The below is definitely not an optimal solution.
            $value = utf8_encode($value);
        }

        return $value;
    }

    /**
     * Given a string, try to determine if it is a valid distinguished name for a LDAP object. This is a somewhat
     * unsophisticated approach. A regex might be a better solution, but would probably be rather difficult to get
     * right.
     *
     * @param string $dn
     * @return bool
     */
    public static function isValidLdapObjectDn($dn)
    {
        return (($pieces = ldap_explode_dn($dn, 1)) && isset($pieces['count']) && $pieces['count'] > 2);
    }

    /**
     * Determine whether a value is a valid attribute name or OID. The name should meet the format described in RFC 2252.
     * However, the regex is fairly forgiving for each.
     *
     * @param string $value
     * @return bool
     */
    public static function isValidAttributeFormat($value)
    {
        return (preg_match(self::MATCH_DESCRIPTOR, $value) || preg_match(self::MATCH_OID, $value));
    }

    /**
     * Attempts to mask passwords in a LDAP batch array while keeping the rest intact.
     *
     * @param array $batch
     * @return array
     */
    public static function maskBatchArray(array $batch)
    {
        foreach ($batch as $i => $batchItem) {
            if (!isset($batchItem['attrib']) || !isset($batchItem['values'])) {
                continue;
            }
            if (!in_array(strtolower($batchItem['attrib']), self::MASK_ATTRIBUTES)) {
                continue;
            }
            $batch[$i]['values'] = [self::MASK];
        }

        return $batch;
    }

    /**
     * Attempts to mask password attribute values used in logging.
     *
     * @param array $attributes
     * @return array
     */
    public static function maskAttributeArray(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if (in_array(strtolower($key), self::MASK_ATTRIBUTES)) {
                $attributes[$key] = self::MASK;
            }
        }

        return $attributes;
    }

    /**
     * Get an array of all the LDAP servers for a domain by querying DNS.
     *
     * @param string $domain The domain name to query.
     * @return string[]
     */
    public static function getLdapServersForDomain($domain)
    {
        $hosts = (new Dns())->getRecord(self::SRV_PREFIX.$domain, DNS_SRV);

        return is_array($hosts) ? array_column($hosts, 'target') : [];
    }

    /**
     * Given a full escaped DN return the RDN in escaped form.
     *
     * @param string $dn
     * @return string
     */
    public static function getRdnFromDn($dn)
    {
        $rdn = self::explodeDn($dn, 0)[0];
        $rdn = explode('=', $rdn, 2);

        return $rdn[0].'='.self::escapeValue($rdn[1], null, LDAP_ESCAPE_DN);
    }

    /**
     * Given an attribute, split it between its alias and attribute. This will return an array where the first value
     * is the alias and the second is the attribute name. If there is no alias then the first value will be null.
     * 
     * ie. list($alias, $attribute) = LdapUtilities::getAliasAndAttribute($attribute);
     * 
     * @param string $attribute
     * @return array
     */
    public static function getAliasAndAttribute($attribute)
    {
        $alias = null;

        if (strpos($attribute, '.') !== false) {
            $pieces = explode('.', $attribute, 2);
            $alias = $pieces[0];
            $attribute = $pieces[1];
        }
        
        return [$alias, $attribute];
    }

    /**
     * Looks up an array value in a case-insensitive way and return it how it appears in the array.
     *
     * @param string $needle
     * @param array $haystack
     * @return string
     */
    public static function getValueCaseInsensitive($needle, array $haystack)
    {
        $lcNeedle = strtolower($needle);
        $lcKeys = array_change_key_case(array_flip($haystack));
        
        if (!isset($lcKeys[$lcNeedle])) {
            throw new InvalidArgumentException(sprintf('Value "%s" not found in array.', $needle));
        }

        return $haystack[$lcKeys[$lcNeedle]];
    }
}
