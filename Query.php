<?php
/**
 * @link      https://github.com/chrmorandi/yii2-ldap for the canonical source repository
 * @package   yii2-ldap
 * @author    Christopher Mota <chrmorandi@gmail.com>
 * @license   Mit License - view the LICENSE file that was distributed with this source code.
 */

namespace chrmorandi\ldap;

use chrmorandi\ldap\Connection;
use Yii;
use yii\base\Component;
use yii\base\InvalidValueException;
use yii\db\Expression;
use yii\db\QueryInterface;
use yii\db\QueryTrait;

/**
 * Query represents a SEARCH in LDAP database directory.
 *
 * Query provides a set of methods to facilitate the specification of different clauses
 * in a SEARCH statement. These methods can be chained together.
 *
 * For example,
 *
 * ```php
 * $query = new Query;
 * // compose the query
 * $query->select('id, name')
 *     ->from('user')
 *     ->limit(10);
 * // build and execute the query
 * $rows = $query->all();
 * ```
 *
 * Query internally uses the [[FilterBuilder]] class to generate the LDAP filters.
 *
 * @author Christopher Mota <chrmorandi@gmail.com>
 * @since 1.0.0
 */
class Query extends Component implements QueryInterface
{
    use QueryTrait;

    const SEARCH_SCOPE_SUB  = 'ldap_search';
    const SEARCH_SCOPE_ONE  = 'ldap_list';
    const SEARCH_SCOPE_BASE = 'ldap_read';
    
    /**
     * @var string the scope of search
     * The search scope:
     * Query::SEARCH_SCOPE_SUB searches the complete subtree including the $baseDn node. This is the default value.
     * Query::SEARCH_SCOPE_ONE restricts search to one level below $baseDn.
     * Query::SEARCH_SCOPE_BASE restricts search to the $baseDn itself; this can be used to efficiently retrieve a single entry by its DN.
     */
    public $scope = self::SEARCH_SCOPE_SUB;
    
    /**
     * @var array the columns being selected. For example, `['id', 'name']`.
     * This is used to construct the SEARCH function in a LDAP statement. If not set, it means selecting all columns.
     * @see select()
     */
    public $select;
    
    /**
     * @var string The search filter. Format is described in the LDAP documentation.
     * @see http://www.faqs.org/rfcs/rfc4515.html
     */
    public $filter;

    /**
     * Creates a LDAP command that can be used to execute this query.
     * @param Connection $db the database connection.
     * If this parameter is not given, the `db` application component will be used.
     * @return DataReader
     */
    public function execute($db = null)
    {
        if ($db === null) {
            $db = Yii::$app->get('ldap');
        }
        
        $this->filter = (new FilterBuilder)->build($this->where);        
        if(empty($this->filter)){
            throw new InvalidValueException('You must define a filter for the search.');
        }
        
        $select = (is_array($this->select)) ? $this->select : [];
        $this->limit = empty($this->limit) ? 0 : $this->limit;

        $params = [
            $db->baseDn,
            $this->filter,
            $select,
            0,
            $this->limit
        ];

        return $db->execute($this->scope, $params);
    }

    /**
     * Executes the query and returns all results as an array.
     * @param Connection $db the database connection.
     * If this parameter is not given, the `db` application component will be used.
     * @return array the query results. If the query results in nothing, an empty array will be returned.
     */
    public function all($db = null)
    {        
        /** @var $result DataReader */
        $result = $this->execute($db);        
        return $this->populate($result->toArray());
    }

    /**
     * Converts the raw query results into the format as specified by this query.
     * This method is internally used to convert the data fetched from database
     * into the format as required by this query.
     * @param array $rows the raw query result from database
     * @return array the converted query result
     */
    public function populate($rows)
    {
        if ($this->indexBy === null) {
            return $rows;
        }
        $result = [];
        foreach ($rows as $row) {
            if (is_string($this->indexBy)) {
                $key = $row[$this->indexBy];
            } else {
                $key = call_user_func($this->indexBy, $row);
            }
            $result[$key] = $row;
        }
        return $result;
    }

    /**
     * Executes the query and returns a single row of result.
     * @param Connection $db the database connection.
     * If this parameter is not given, the `db` application component will be used.
     * @return array|boolean the first row (in terms of an array) of the query result. False is returned if the query
     * results in nothing.
     */
    public function one($db = null)
    {
        $this->limit = 1;
        $result = $this->execute($db);
        return $result->toArray();
    }

    /**
     * Returns the number of entries in a search.
     * @param Connection $db the database connection
     * If this parameter is not given (or null), the `db` application component will be used.
     * @return integer number of entries.
     */
    public function count($q = '*', $db = NULL)
    {        
        $result = $this->execute($db);
        return $result->count();
    }
    

    /**
     * Returns a value indicating whether the query result contains any row of data.
     * @param Connection $db the database connection.
     * If this parameter is not given, the `db` application component will be used.
     * @return boolean whether the query result contains any row of entries.
     */
    public function exists($db = null)
    {        
        $result = $this->execute($db);
        return (boolean) $result->count();
    }

    /**
     * Sets the SELECT part of the query.
     * @param string|array $columns the columns to be selected.
     * Columns can be specified in either a string (e.g. "id, name") or an array (e.g. ['id', 'name']).
     *
     * ```php
     * $query->addSelect(['cn, mail'])->one();
     * ```
     *
     * @return $this the query object itself
     */
    public function select($columns)
    {
        if (!is_array($columns)) {
            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }
        $this->select = $columns;
        return $this;
    }

    /**
     * Add more columns to the select part of the query.
     *
     * ```php
     * $query->addSelect(['cn, mail'])->one();
     * ```
     *
     * @param string|array|Expression $columns the columns to add to the select. See [[select()]] for more
     * details about the format of this parameter.
     * @return $this the query object itself
     * @see select()
     */
    public function addSelect($columns)
    {
        if (!is_array($columns)) {
            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }
        if ($this->select === null) {
            $this->select = $columns;
        } else {
            $this->select = array_merge($this->select, $columns);
        }
        return $this;
    }

    /**
     * Adds a filtering condition for a specific column and allow the user to choose a filter operator.
     *
     * It adds an additional WHERE condition for the given field and determines the comparison operator
     * based on the first few characters of the given value.
     * The condition is added in the same way as in [[andFilterWhere]] so [[isEmpty()|empty values]] are ignored.
     * The new condition and the existing one will be joined using the 'AND' operator.
     *
     * The comparison operator is intelligently determined based on the first few characters in the given value.
     * In particular, it recognizes the following operators if they appear as the leading characters in the given value:
     *
     * - `<`: the column must be less than the given value.
     * - `>`: the column must be greater than the given value.
     * - `<=`: the column must be less than or equal to the given value.
     * - `>=`: the column must be greater than or equal to the given value.
     * - `~=`: the column must approximate the given value.
     * - `=`: the column must be equal to the given value.
     * - If none of the above operators is detected, the `$defaultOperator` will be used.
     *
     * @param string $name the column name.
     * @param string $value the column value optionally prepended with the comparison operator.
     * @param string $defaultOperator The operator to use, when no operator is given in `$value`.
     * Defaults to `=`, performing an exact match.
     * @return $this The query object itself
     */
    public function andFilterCompare($name, $value, $defaultOperator = '=')
    {
        if (preg_match("/^(~=|>=|>|<=|<|=)/", $value, $matches)) {
            $operator = $matches[1];
            $value = substr($value, strlen($operator));
        } else {
            $operator = $defaultOperator;
        }
        return $this->andFilterWhere([$operator, $name, $value]);
    }

    /**
     * Creates a new Query object and copies its property values from an existing one.
     * The properties being copies are the ones to be used by query builders.
     * @param Query $from the source query object
     * @return Query the new Query object
     */
    public static function create(Query $from)
    {
        return new self([
            'where' => $from->where,
            'limit' => $from->limit,
            'offset' => $from->offset,
            'orderBy' => $from->orderBy,
            'indexBy' => $from->indexBy,
            'select' => $from->select,
        ]);
    }
}
