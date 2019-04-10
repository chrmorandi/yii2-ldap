<?php
/**
 * @link      https://github.com/chrmorandi/yii2-ldap for the source repository
 *
 * @author    Christopher Mota <chrmorandi@gmail.com>
 * @license   MIT License - view the LICENSE file that was distributed with this source code.
 *
 * @since     1.0.0
 */

namespace chrmorandi\ldap;

use yii\base\InvalidArgumentException;

/**
 * Some common helper LDAP functions.
 */
class LdapHelper
{
    /**
     * Converts a string distinguished name into its separate pieces.
     *
     * @param string $dn
     * @param int    $withAttributes Set to 0 to get the attribute names along with the value.
     *
     * @return array
     */
    public static function explodeDn($dn, $withAttributes = 1)
    {
        $pieces = ldap_explode_dn($dn, $withAttributes);

        if ($pieces === false || !isset($pieces['count']) || $pieces['count'] == 0) {
            throw new InvalidArgumentException(sprintf('Unable to parse DN "%s".', $dn));
        }
        unset($pieces['count']);

        return $pieces;
    }

    /**
     * Given a DN as an array in ['cn=Name', 'ou=Employees', 'dc=example', 'dc=com'] form, return it as its string
     * representation that is safe to pass back to a query or to save back to LDAP for a DN.
     *
     * @param array $dn
     *
     * @return string
     */
    public static function implodeDn(array $dn)
    {
        foreach ($dn as $index => $piece) {
            $values = explode('=', $piece, 2);
            if (count($values) === 1) {
                throw new InvalidArgumentException(sprintf('Unable to parse DN piece "%s".', $values[0]));
            }
            $dn[$index] = $values[0].'='.$values[1];
        }

        return implode(',', $dn);
    }

    /**
     * Given a full escaped DN return the RDN in escaped form.
     *
     * @param string $dn
     *
     * @return string Return string like "attribute = value"
     */
    public static function getRdnFromDn($dn)
    {
        $rdn = self::explodeDn($dn, 0)[0];
        $rdn = explode('=', $rdn, 2);

        return $rdn[0].'='.$rdn[1];
    }

    /**
     * Recursively implodes an array with optional key inclusion.
     *
     * Example of $include_keys output: key, value, key, value, key, value
     *
     * @param array  $array        multi-dimensional array to recursively implode
     * @param string $glue         value that glues elements together
     * @param bool   $include_keys include keys before their values
     * @param bool   $trim_all     trim ALL whitespace from string
     *
     * @return string imploded array
     */
    public static function recursiveImplode(array $array, $glue = ',', $include_keys = false, $trim_all = true)
    {
        $glued_string = '';

        // Recursively iterates array and adds key/value to glued string
        array_walk_recursive($array, function ($value, $key) use ($glue, $include_keys, &$glued_string) {
            $include_keys and $glued_string .= $key.$glue;
            $glued_string .= $value.$glue;
        });

        // Removes last $glue from string
        strlen($glue) > 0 and $glued_string = substr($glued_string, 0, -strlen($glue));

        // Trim ALL whitespace
        $trim_all and $glued_string = preg_replace("/(\s)/ixsm", '', $glued_string);

        return (string) $glued_string;
    }
}
