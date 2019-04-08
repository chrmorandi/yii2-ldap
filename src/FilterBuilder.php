<?php
/**
 * @link      https://github.com/chrmorandi/yii2-ldap for the source repository
 * @package   yii2-ldap
 * @author    Christopher Mota <chrmorandi@gmail.com>
 * @license   MIT License - view the LICENSE file that was distributed with this source code.
 * @since     1.0.0
 */

namespace chrmorandi\ldap;

use yii\base\InvalidParamException;
use yii\base\BaseObject;

/**
 * FilterBuilder builds a Filter for search in LDAP.
 *
 * FilterBuilder is also used by [[Query]] to build Filters.
 *
 * @author Christopher Mota <chrmorandi@gmail.com>
 * @since 1.0.0
 */
class FilterBuilder extends BaseObject
{
    /**
     * @var string the separator between different fragments of a SQL statement.
     * Defaults to an empty space. This is mainly used by [[build()]] when generating a SQL statement.
     */
    public $separator = ' ';

    /**
     * @var array map of query condition to builder methods.
     * These methods are used by [[buildCondition]] to build SQL conditions from array syntax.
     */
    protected $conditionBuilders = [
        'NOT'         => 'buildAndCondition',
        'AND'         => 'buildAndCondition',
        'OR'          => 'buildAndCondition',
        'IN'          => 'buildInCondition',
        'NOT IN'      => 'buildInCondition',
        'LIKE'        => 'buildLikeCondition',
        'NOT LIKE'    => 'buildLikeCondition',
        'OR LIKE'     => 'buildLikeCondition',
        'OR NOT LIKE' => 'buildLikeCondition',
    ];

    /**
     * @var array map of operator for builder methods.
     */
    protected $operator = [
        'NOT'  => '!',
        'AND'  => '&',
        'OR'   => '|',
    ];

    /**
     * Parses the condition specification and generates the corresponding filters.
     * @param string|array $condition the condition specification. Please refer to [[Query::where()]]
     * on how to specify a condition.
     * @return string the generated
     */
    public function build($condition)
    {
        if (!is_array($condition)) {
            return (string) $condition;
        } elseif (empty($condition)) {
            return '';
        }

        if (isset($condition[0])) { // operator format: operator, operand 1, operand 2, ...
            $operator = strtoupper($condition[0]);
            if (isset($this->conditionBuilders[$operator])) {
                $method = $this->conditionBuilders[$operator];
            } else {
                $method = 'buildSimpleCondition';
            }
            array_shift($condition);
            return $this->$method($operator, $condition);
        } else { // hash format: 'column1' => 'value1', 'column2' => 'value2', ...
            return $this->buildHashCondition($condition);
        }
    }

    /**
     * Creates a condition based on column-value pairs.
     * @param array $condition the condition specification.
     * @return string the generated
     */
    public function buildHashCondition($condition)
    {
        $parts = [];
        foreach ($condition as $attribute => $value) {
            if (is_array($value)) {
                // IN condition
                $parts[] = $this->buildInCondition('IN', [$attribute, $value]);
            } elseif ($value === null) {
                $parts[] = "!$attribute=*";
            } elseif ($attribute === 'dn') {
                $parts[] = LdapHelper::getRdnFromDn($value);
            } else {
                $parts[] = "$attribute=$value";
            }
        }
        return count($parts) === 1 ? '(' . $parts[0] . ')' : '&(' . implode(') (', $parts) . ')';
    }

    /**
     * Connects two or more filters expressions with the `AND`(&) or `OR`(|) operator.
     * @param string $operator The operator to use for connecting the given operands
     * @param array $operands The filter expressions to connect.
     * @return string The generated filter
     */
    public function buildAndCondition($operator, $operands)
    {
        if ($operator === 'NOT' && count($operands) !== 1) {
            throw new InvalidParamException("Operator '$operator' requires exactly one operand.");
        }

        $parts = [];
        foreach ($operands as $operand) {
            if (is_array($operand)) {
                $parts[] = $this->build($operand);
            } elseif ($operand !== '') {
                $parts[] = $operand;
            }
        }

        return empty($parts) ? '' : '(' . $this->operator[$operator] . '(' . implode(') (', $parts) . '))';
    }

    /**
     * Creates an filter expressions with the `IN` operator.
     * @param string $operator the operator to use (e.g. `IN` or `NOT IN`)
     * @param array $operands the first operand is the column name. If it is an array
     * a composite IN condition will be generated.
     * The second operand is an array of values that column value should be among.
     * If it is an empty array the generated expression will be a `false` value if
     * operator is `IN` and empty if operator is `NOT IN`.
     * @return string the generated SQL expression
     * @throws Exception if wrong number of operands have been given.
     */
    public function buildInCondition($operator, $operands)
    {
        if (!isset($operands[0], $operands[1])) {
            throw new InvalidParamException("Operator '$operator' requires two operands.");
        }

        list($attribute, $values) = $operands;

        if (is_string($attribute) || !is_array($values)) {
            throw new InvalidParamException('First operand must to be string and secund operand must to be array.');
        }

        $parts = [];
        foreach ($values as $value) {
            if (is_array($value)) {
                $value = isset($value[$attribute]) ? $value[$attribute] : null;
            }
            if ($value === null) {
                $parts[] = "!$attribute=*";
            } else {
                $parts[] = "$attribute=$value";
            }
        }

        if (empty($parts)) {
            return '';
        } elseif ($operator === 'NOT IN') {
            return '!(' . implode(') (', $parts) . ')';
        }
        return count($parts) === 1 ? '(' . $parts[0] . ')' : '|(' . implode(') (', $parts) . ')';
    }

    /**
     * Creates an SQL expressions with the `LIKE` operator.
     * @param string $operator the operator to use (e.g. `LIKE`, `NOT LIKE`, `OR LIKE` or `OR NOT LIKE`)
     * @param array $operands an array of two or three operands
     *
     * - The first operand is the column name.
     * - The second operand is a single value or an array of values that column value
     *   should be compared with. If it is an empty array the generated expression will
     *   be a `false` value if operator is `LIKE` or `OR LIKE`, and empty if operator
     *   is `NOT LIKE` or `OR NOT LIKE`.
     * - An optional third operand can also be provided to specify how to escape special characters
     *   in the value(s). The operand should be an array of mappings from the special characters to their
     *   escaped counterparts. If this operand is not provided, a default escape mapping will be used.
     *   You may use `false` or an empty array to indicate the values are already escaped and no escape
     *   should be applied. Note that when using an escape mapping (or the third operand is not provided),
     *   the values will be automatically enclosed within a pair of percentage characters.
     * @return string the generated SQL expression
     * @throws InvalidParamException if wrong number of operands have been given.
     */
    public function buildLikeCondition($operator, $operands)
    {
        if (!isset($operands[0], $operands[1])) {
            throw new InvalidParamException("Operator '$operator' requires two operands.");
        }

        $escape = isset($operands[2]) ? $operands[2] : ['*' => '\*', '_' => '\_', '\\' => '\\\\'];
        unset($operands[2]);

        if (!preg_match('/^(OR|)(((NOT|))LIKE)/', $operator, $matches)) {
            throw new InvalidParamException("Invalid operator '$operator'.");
        }
        $andor    = (!empty($matches[1]) ? $matches[1] : 'AND');
        $not      = !empty($matches[3]);
        $operator = $matches[2];

        list($attribute, $values) = $operands;

        if (!is_array($values)) {
            $values = [$values];
        }

        if (empty($values)) {
            return '';
        }

        $not = ($operator == 'NOT LIKE') ? '(' . $this->operator['NOT'] : false;

        $parts = [];
        foreach ($values as $value) {
            $value   = empty($escape) ? $value : strtr($value, $escape);
            $parts[] = $not . '(' . $attribute . '=*' . $value . '*)' . ($not ? ')' : '');
        }

        return '(' . $this->operator[trim($andor)] . implode($parts) . ')';
    }

    /**
     * Creates an LDAP filter expressions like `(attribute operator value)`.
     * @param string $operator the operator to use. A valid list could be used e.g. `=`, `>=`, `<=`, `~<`.
     * @param array $operands contains two column names.
     * @return string the generated LDAP filter expression
     * @throws InvalidParamException if wrong number of operands have been given.
     */
    public function buildSimpleCondition($operator, $operands)
    {
        if (count($operands) !== 2) {
            throw new InvalidParamException("Operator '$operator' requires two operands.");
        }

        list($attribute, $value) = $operands;

        if ($value === null) {
            return "(!$attribute = *)";
        } else {
            return "($attribute $operator $value)";
        }
    }

}
