<?php
/**
 * @link      https://github.com/chrmorandi/yii2-ldap for the source repository
 *
 * @author    Christopher Mota <chrmorandi@gmail.com>
 * @license   MIT License - view the LICENSE file that was distributed with this source code.
 */

namespace chrmorandi\ldap\schemas;

/**
 * @since 1.0.0
 */
interface SchemaInterface
{
    /**
     * Get Array Attributes.
     *
     * @return array of attributes
     */
    public function getAttributes();
}
