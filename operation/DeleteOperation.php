<?php
/**
 * @package   yii2-ldap
 * @author    @author Christopher Mota <chrmorandi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace chrmorandi\ldap\operation;

/**
 * Represents an operation to remove an existing LDAP object.
 *
 * @author Christopher Mota <chrmorandi@gmail.com>
 * @since 1.0
 */
class DeleteOperation extends \yii\base\Object implements operationInterface
{
    use OperationTrait;
    use ModOperationTrait;

    /**
     * @var string The DN to remove.
     */
    protected $dn;

    /**
     * @param string $dn The DN of the LDAP object to delete.
     */
    public function __construct($dn)
    {
        $this->dn = $dn;
    }

    /**
     * Get the distinguished name to be deleted by this operation.
     *
     * @return null|string
     */
    public function getDn()
    {
        return $this->dn;
    }

    /**
     * Set the distinguished name to be deleted by this operation.
     *
     * @param string $dn
     * @return $this
     */
    public function setDn($dn)
    {
        $this->dn = $dn;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        return [$this->dn];
    }

    /**
     * {@inheritdoc}
     */
    public function getLdapFunction()
    {
        return 'ldap_delete';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Delete';
    }

}
