<?php
/**
 * @link      https://github.com/chrmorandi/yii2-ldap for the canonical source repository
 * @package   yii2-ldap
 * @author    Christopher Mota <chrmorandi@gmail.com>
 * @license   MIT License - view the LICENSE file that was distributed with this source code.
 */

namespace chrmorandi\ldap;

use chrmorandi\ldap\Connection;
use yii\base\Exception;

/**
 * Exception represents an exception that is caused by some ldap operations.
 *
 * @author Christopher Mota <chrmorandi@gmail.com>
 * @since 1.0.0
 */
class LdapException extends Exception
{
    /**
     * Constructor.
     * @param string $message LDAP error message
     * @param integer $code LDAP error code
     * @param \Exception $previous The previous exception used for the exception chaining.
     */
    public function __construct($message, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'LDAP Exception';
    }
}