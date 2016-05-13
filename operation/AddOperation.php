<?php
/**
 * @package   yii2-ldap
 * @author    @author Christopher Mota <chrmorandi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace chrmorandi\ldap\operation;

use chrmorandi\ldap\Exception\InvalidArgumentException;
use chrmorandi\ldap\operation\OperationInterface;
use chrmorandi\ldap\operation\OperationTrait;
use chrmorandi\ldap\Utilities\LdapUtilities;

/**
 * Represents an operation to add an object to LDAP.
 *
 * @author Christopher Mota <chrmorandi@gmail.com>
 * @since 1.0
 */
class AddOperation extends \yii\base\Object implements OperationInterface
{
    use OperationTrait;
    use ModOperationTrait;

    /**
     * @var array
     */
    protected $properties = [
        'dn' => null,
        'location' => null,
        'attributes' => [],
    ];

    /**
     * @param string $dn The DN for the LDAP object.
     * @param array $attributes The attributes in [key => value] form for the LDAP object.
     */
    public function __construct($dn = null, $attributes = [])
    {
        $this->properties['dn'] = $dn;
        $this->properties['attributes'] = $attributes;
    }

    /**
     * Get either: The attributes selected for a query operation. The attributes to be set for an add operation.
     *
     * @return array|null
     */
    public function getAttributes()
    {
        return $this->properties['attributes'];
    }

    /**
     * Set the attributes selected or added to/from LDAP (add or select operation).
     *
     * @param array $attributes
     * @return $this;
     */
    public function setAttributes(array $attributes)
    {
        $this->properties['attributes'] = $attributes;

        return $this;
    }

    /**
     * Set the location where the LDAP object should be created (ie. OU/container). This is only valid when the operation
     * is hydrated.
     *
     * @param string $location
     * @return $this
     */
    public function setLocation($location)
    {
        $this->properties['location'] = $location;

        return $this;
    }

    /**
     * Get the location where the LDAP object should be created (ie. OU/container). This is only valid when the operation
     * is hydrated.
     *
     * @return string|null
     */
    public function getLocation()
    {
        return $this->properties['location'];
    }

    /**
     * The distinguished name for an add, delete, or move operation.
     *
     * @return null|string
     */
    public function getDn()
    {
        return $this->properties['dn'];
    }

    /**
     * Set the distinguished name that the operation is working on.
     *
     * @param string $dn
     * @return $this
     */
    public function setDn($dn)
    {
        $this->properties['dn'] = $dn;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        if (is_null($this->properties['dn'])) {
            throw new InvalidArgumentException('The DN cannot be left null for an LDAP add operation.');
        }

        return [$this->properties['dn'], $this->properties['attributes']];
    }

    /**
     * {@inheritdoc}
     */
    public function getLdapFunction()
    {
        return 'ldap_add';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Add';
    }

}
