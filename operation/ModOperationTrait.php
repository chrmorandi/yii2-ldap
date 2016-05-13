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
 * Common LDAP operation functions.
 *
 * @author Christopher Mota <chrmorandi@gmail.com>
 * @since 1.0
 */
trait ModOperationTrait
{
    /**
     * Execute a LDAP operation and return a response.
     *
     * @param OperationInterface $operation
     * @return mixed
     */
    static public function execute(OperationInterface $operation, \chrmorandi\ldap\Connection $conn)
    {
        $result = @call_user_func(
            $operation->getLdapFunction(),
            $conn,
            ...$operation->getArguments()
        );

        if ($result === false) {
            throw new ConnectionException(sprintf(
                'LDAP %s Operation Error: %s',
                $operation->getName(),
                $conn->getLastError()
            ));
        }

        return $result;
    }
}

