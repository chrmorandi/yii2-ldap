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
 * Common LDAP operation functions.
 *
 * @author Christopher Mota <chrmorandi@gmail.com>
 * @since 1.0
 */
trait OperationTrait
{
    /**
     * @var null|string
     */
    protected $server;

    /**
     * Get the controls set for the operation.
     *
     * @return LdapControl[]
     */
    public function getControls()
    {
        return $this->controls;
    }

    /**
     * Add a control to the operation.
     *
     * @param LdapControl[] ...$controls
     * @return $this
     */
    public function addControl(LdapControl ...$controls)
    {
        foreach ($controls as $control) {
            $this->controls[] = $control;
        }

        return $this;
    }
    
}
