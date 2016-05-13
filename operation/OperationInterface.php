<?php
/**
 * @package   yii2-ldap
 * @author    @author Christopher Mota <chrmorandi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace chrmorandi\ldap\operation;

use chrmorandi\ldap\Connection\LdapControl;

/**
 * The interface to represent a LDAP operation to be executed.
 *
 * @author Christopher Mota <chrmorandi@gmail.com>
 * @since 1.0
 */
interface OperationInterface
{

    /**
     * Add LDAP controls to the operation.
     *
     * @param LdapControl[] ...$controls
     * @return $this
     */
    public function addControl(LdapControl ...$controls);

    /**
     * Get the controls set for the operation.
     *
     * @return LdapControl[]
     */
    public function getControls();

    /**
     * Gets an array of arguments that will be passed to the LDAP function for executing this operation.
     *
     * @return array
     */
    public function getArguments();

    /**
     * Gets the name of the LDAP function needed to execute this operation.
     *
     * @return string
     */
    public function getLdapFunction();

    /**
     * Get the readable name that this operation represents. This is to be used in messages/exceptions.
     *
     * @return string
     */
    public function getName();

    /**
     * Execute a LDAP operation and return a response.
     *
     * @param LdapOperationInterface $operation
     * @return mixed
     */
    public function execute(LdapOperationInterface $operation);
}
