<?php
/**
 * @link      https://github.com/chrmorandi/yii2-ldap for the canonical source repository
 * @package   yii2-ldap
 * @author    Christopher Mota <chrmorandi@gmail.com>
 * @license   Mit License - view the LICENSE file that was distributed with this source code.
 */

namespace chrmorandi\ldap;

use Yii;
use yii\base\Component;
use chrmorandi\ldap\Connection;
use yii\db\BatchQueryResult;
use yii\db\Expression;
use yii\db\QueryInterface;
use yii\db\QueryTrait;

/**
 * Query represents a SEARCH in LDAP database directory.
 *
 * Query provides a set of methods to facilitate the specification of different clauses
 * in a SEARCH statement. These methods can be chained together.
 *
 * By calling [[createCommand()]], we can get a [[Command]] instance which can be further
 * used to perform/execute the LDAP query against a database directory.
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
 * // alternatively, you can create LDAP command and execute it
 * $command = $query->createCommand();
 * // $command->argument returns the actual LDAP arguments for the search
 * $rows = $command->queryAll();
 * ```
 *
 * Query internally uses the [[QueryBuilder]] class to generate the LDAP statement.
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
     * @var boolean whether to select distinct rows of data only. If this is set true,
     * the return of SEARCH funtion not show duplicade results.
     */
    public $distinct;
    
    /**
     * @var array the table(s) to be selected from. For example, `['user', 'post']`.
     * This is used to construct the FROM clause in a SQL statement.
     * @see from()
     */
    public $from;
    
    /**
     * @var array how to group the query results. For example, `['company', 'department']`.
     * This is used to construct the GROUP BY clause in a SQL statement.
     */
    public $groupBy; 
    
    /**
     * @var string|array the condition to be applied in the GROUP BY clause.
     * It can be either a string or an array. Please refer to [[where()]] on how to specify the condition.
     */
    public $having;

    /**
     * Creates a LDAP command that can be used to execute this query.
     * @param Connection $db the database connection used to generate the SQL statement.
     * If this parameter is not given, the `db` application component will be used.
     * @return Command the created DB command instance.
     */
    public function createCommand($db = null)
    {
        if ($db === null) {
            $db = Yii::$app->get('ldap');
        }
        
        $filter = (new FilterBuilder)->build($this->where);
        
        $params = [
            $db->baseDn,
            $filter,
            //$this->select,
            //0,
            //$sizelimit, 
            //$timelimit 
        ];
       
        return $params;

        /** @var Command $command */
//        $command = new Command([
//            'db' => $db,
//            'scope' => $this->scope            
//        ]);

        //return $command->bindValues($params);
    }

    /**
     * Starts a batch query.
     *
     * A batch query supports fetching data in batches, which can keep the memory usage under a limit.
     * This method will return a [[BatchQueryResult]] object which implements the [[\Iterator]] interface
     * and can be traversed to retrieve the data in batches.
     *
     * For example,
     *
     * ```php
     * $query = (new Query)->from('user');
     * foreach ($query->batch() as $rows) {
     *     // $rows is an array of 10 or fewer rows from user table
     * }
     * ```
     *
     * @param integer $batchSize the number of records to be fetched in each batch.
     * @param Connection $db the database connection. If not set, the "db" application component will be used.
     * @return BatchQueryResult the batch query result. It implements the [[\Iterator]] interface
     * and can be traversed to retrieve the data in batches.
     */
//    public function batch($batchSize = 100, $db = null)
//    {
//        return Yii::createObject([
//            'class' => BatchQueryResult::className(),
//            'query' => $this,
//            'batchSize' => $batchSize,
//            'db' => $db,
//            'each' => false,
//        ]);
//    }

    /**
     * Starts a batch query and retrieves data row by row.
     * This method is similar to [[batch()]] except that in each iteration of the result,
     * only one row of data is returned. For example,
     *
     * ```php
     * $query = (new Query)->from('user');
     * foreach ($query->each() as $row) {
     * }
     * ```
     *
     * @param integer $batchSize the number of records to be fetched in each batch.
     * @param Connection $db the database connection. If not set, the "db" application component will be used.
     * @return BatchQueryResult the batch query result. It implements the [[\Iterator]] interface
     * and can be traversed to retrieve the data in batches.
     */
//    public function each($batchSize = 100, $db = null)
//    {
//        return Yii::createObject([
//            'class' => BatchQueryResult::className(),
//            'query' => $this,
//            'batchSize' => $batchSize,
//            'db' => $db,
//            'each' => true,
//        ]);
//    }

    /**
     * Executes the query and returns all results as an array.
     * @param Connection $db the database connection used to generate the SQL statement.
     * If this parameter is not given, the `db` application component will be used.
     * @return array the query results. If the query results in nothing, an empty array will be returned.
     */
    public function all($db = null)
    {
        if ($db === null) {
            $db = Yii::$app->get('ldap');
        }
        
        $params = $this->createCommand($db);        
        return $db->execute($this->scope, $params);
        //return $this->populate($rows);
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
     * @param Connection $db the database connection used to generate the SQL statement.
     * If this parameter is not given, the `db` application component will be used.
     * @return array|boolean the first row (in terms of an array) of the query result. False is returned if the query
     * results in nothing.
     */
    public function one($db = null)
    {
        return $this->createCommand($db)->queryOne();
    }

    /**
     * Returns the query result as a scalar value.
     * The value returned will be the first column in the first row of the query results.
     * @param Connection $db the database connection used to generate the SQL statement.
     * If this parameter is not given, the `db` application component will be used.
     * @return string|boolean the value of the first column in the first row of the query result.
     * False is returned if the query result is empty.
     */
//    public function scalar($db = null)
//    {
//        return $this->createCommand($db)->queryScalar();
//    }

    /**
     * Executes the query and returns the first column of the result.
     * @param Connection $db the database connection used to generate the SQL statement.
     * If this parameter is not given, the `db` application component will be used.
     * @return array the first column of the query result. An empty array is returned if the query results in nothing.
     */
//    public function column($db = null)
//    {
//        if (!is_string($this->indexBy)) {
//            return $this->createCommand($db)->queryColumn();
//        }
//        if (is_array($this->select) && count($this->select) === 1) {
//            $this->select[] = $this->indexBy;
//        }
//        $rows = $this->createCommand($db)->queryAll();
//        $results = [];
//        foreach ($rows as $row) {
//            if (array_key_exists($this->indexBy, $row)) {
//                $results[$row[$this->indexBy]] = reset($row);
//            } else {
//                $results[] = reset($row);
//            }
//        }
//        return $results;
//    }

    /**
     * Returns the number of records.
     * @param string $q the COUNT expression. Defaults to '*'.
     * Make sure you properly [quote](guide:db-dao#quoting-table-and-column-names) column names in the expression.
     * @param Connection $db the database connection used to generate the SQL statement.
     * If this parameter is not given (or null), the `db` application component will be used.
     * @return integer|string number of records. The result may be a string depending on the
     * underlying database engine and to support integer values higher than a 32bit PHP integer can handle.
     */
    public function count($q = '*', $db = null)
    {
        return $this->queryScalar("COUNT($q)", $db);
    }
    

    /**
     * Returns a value indicating whether the query result contains any row of data.
     * @param Connection $db the database connection used to generate the SQL statement.
     * If this parameter is not given, the `db` application component will be used.
     * @return boolean whether the query result contains any row of data.
     */
    public function exists($db = null)
    {
        $command = $this->createCommand($db);
        $params = $command->params;
        $command->setSql($command->db->getQueryBuilder()->selectExists($command->getSql()));
        $command->bindValues($params);
        return (boolean)$command->queryScalar();
    }

    /**
     * Queries a scalar value by setting [[select]] first.
     * Restores the value of select to make this query reusable.
     * @param string|Expression $selectExpression
     * @param Connection|null $db
     * @return boolean|string
     */
//    protected function queryScalar($selectExpression, $db)
//    {
//        $select = $this->select;
//        $limit = $this->limit;
//        $offset = $this->offset;
//
//        $this->select = [$selectExpression];
//        $this->limit = null;
//        $this->offset = null;
//        $command = $this->createCommand($db);
//
//        $this->select = $select;
//        $this->limit = $limit;
//        $this->offset = $offset;
//
//        if (empty($this->groupBy) && empty($this->having) && empty($this->union) && !$this->distinct) {
//            return $command->queryScalar();
//        } else {
//            return (new Query)->select([$selectExpression])
//                ->from(['c' => $this])
//                ->createCommand($command->db)
//                ->queryScalar();
//        }
//    }

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
    public function select($columns, $option = null)
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
     * It sets the value indicating whether the search returns with Distinct or no filter
     * @param boolean $value whether to Search use Distinct or not.
     * @return $this the query object itself
     */
    public function distinct($value = true)
    {
        $this->distinct = $value;
        return $this;
    }

    /**
     * Sets the FROM part of the query.
     * @param string|array $nodes the node(s) to be selected from. This can be either a string (e.g. `'user'`)
     * or an array (e.g. `['user', 'group']`) specifying one or several nodes names.
     *
     * @return $this the query object itself
     */
    public function from($nodes)
    {
        if (!is_array($nodes)) {
            $nodes = preg_split('/\s*,\s*/', trim($nodes), -1, PREG_SPLIT_NO_EMPTY);
        }
        $this->from = $nodes;
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
     * Sets the GROUP BY part of the query.
     * @param string|array $columns the columns to be grouped by.
     * Columns can be specified in either a string (e.g. "id, name") or an array (e.g. ['id', 'name']).
     *
     * @return $this the query object itself
     * @see addGroupBy()
     */
    public function groupBy($columns)
    {
        if (!is_array($columns)) {
            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }
        $this->groupBy = $columns;
        return $this;
    }

    /**
     * Adds additional group-by columns to the existing ones.
     * @param string|array $columns additional columns to be grouped by.
     * Columns can be specified in either a string (e.g. "id, name") or an array (e.g. ['id', 'name']).
     *
     * @return $this the query object itself
     * @see groupBy()
     */
    public function addGroupBy($columns)
    {
        if (!is_array($columns)) {
            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }
        if ($this->groupBy === null) {
            $this->groupBy = $columns;
        } else {
            $this->groupBy = array_merge($this->groupBy, $columns);
        }
        return $this;
    }

    /**
     * Sets the HAVING part of the query.
     * @param string|array $condition the conditions to be put after HAVING.
     * Please refer to [[where()]] on how to specify this parameter.
     * @return $this the query object itself
     * @see andHaving()
     * @see orHaving()
     */
    public function having($condition)
    {
        $this->having = $condition;
        return $this;
    }

    /**
     * Adds an additional HAVING condition to the existing one.
     * The new condition and the existing one will be joined using the 'AND' operator.
     * @param string|array $condition the new HAVING condition. Please refer to [[where()]]
     * on how to specify this parameter.
     * @return $this the query object itself
     * @see having()
     * @see orHaving()
     */
    public function andHaving($condition)
    {
        if ($this->having === null) {
            $this->having = $condition;
        } else {
            $this->having = ['and', $this->having, $condition];
        }
        return $this;
    }

    /**
     * Adds an additional HAVING condition to the existing one.
     * The new condition and the existing one will be joined using the 'OR' operator.
     * @param string|array $condition the new HAVING condition. Please refer to [[where()]]
     * on how to specify this parameter.
     * @return $this the query object itself
     * @see having()
     * @see andHaving()
     */
    public function orHaving($condition)
    {
        if ($this->having === null) {
            $this->having = $condition;
        } else {
            $this->having = ['or', $this->having, $condition];
        }
        return $this;
    }

    /**
     * Creates a new Query object and copies its property values from an existing one.
     * The properties being copies are the ones to be used by query builders.
     * @param Query $from the source query object
     * @return Query the new Query object
     */
    public static function create($from)
    {
        return new self([
            'where' => $from->where,
            'limit' => $from->limit,
            'offset' => $from->offset,
            'orderBy' => $from->orderBy,
            'indexBy' => $from->indexBy,
            'select' => $from->select,
            'distinct' => $from->distinct,
            'from' => $from->from,
            'groupBy' => $from->groupBy,
            'having' => $from->having,
        ]);
    }
}
