<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace chrmorandi\ldap;

use chrmorandi\ldap\Connection;
use chrmorandi\ldap\exceptions\LdapException;
use Countable;
use Iterator;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Object;

/**
 * DataReader represents a forward-only stream of rows from a query result set.
 *
 * To read the current row of data, call [[read()]]. The method [[readAll()]]
 * returns all the rows in a single array. Rows of data can also be read by
 * iterating through the reader. For example,
 *
 * ```php
 * $command = $connection->createCommand('SELECT * FROM post');
 * $reader = $command->query();
 *
 * while ($row = $reader->read()) {
 *     $rows[] = $row;
 * }
 *
 * // equivalent to:
 * foreach ($reader as $row) {
 *     $rows[] = $row;
 * }
 *
 * // equivalent to:
 * $rows = $reader->readAll();
 * ```
 *
 * Note that since DataReader is a forward-only stream, you can only traverse it once.
 * Doing it the second time will throw an exception.
 *
 * It is possible to use a specific mode of data fetching by setting
 * [[fetchMode]]. See the [PHP manual](http://www.php.net/manual/en/function.PDOStatement-setFetchMode.php)
 * for more details about possible fetch mode.
 *
 * @property integer $columnCount The number of columns in the result set. This property is read-only.
 * @property integer $fetchMode Fetch mode. This property is write-only.
 * @property boolean $isClosed Whether the reader is closed or not. This property is read-only.
 * @property integer $rowCount Number of rows contained in the result. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class DataReader extends Object implements Iterator, Countable
{
    /**
     * @var array data
     */
    public  $entries;
    /**
     * @var Connection
     */
    private $_conn;
    private $_closed = false;
    private $_row;
    private $_index = -1;
    private $_count = -1;    
    private $_result;


    /**
     * Constructor.
     * @param Connection $conn connection interact with result
     * @param resource $result result of search in ldap directory
     * @param array $config name-value pairs that will be used to initialize the object properties
     * @return DataReader
     */
    public function __construct(Connection $conn, $result, $config = [])
    {
        $this->_conn   = $conn;
        $this->_result = $result;
        $resource      = $conn->resource;
        
        Yii::beginProfile('ldap_count_entries', 'chrmorandi\ldap\DataReader::ldap_entries');
        $this->_count = ldap_count_entries($resource, $this->_result);
        Yii::endProfile('ldap_count_entries', 'chrmorandi\ldap\DataReader::ldap_entries');
        
        if ($this->_count === false) {
            throw new LdapException($this->_conn, sprintf('LDAP count entries failed: %s', $this->getLastError()), $this->getErrNo());
        }

        $identifier = ldap_first_entry(
            $resource,
            $this->_result
        );

        while (false !== $identifier) {
            $this->entries[] = [
                'resource' => $identifier,
                'sortValue' => '',
            ];

            $identifier = ldap_next_entry(
                $resource,
                $identifier
            );
        }

        parent::__construct($config);
    }

    /**
     * Get all entries as an array
     * @return array
     */
    public function toArray()
    {
        $data = [];
        foreach ($this as $item) {
            $data[] = $item;
        }
        return $data;
    }

    /**
     * Set the default fetch mode for this statement
     * @param integer $mode fetch mode
     * @see http://www.php.net/manual/en/function.PDOStatement-setFetchMode.php
     */
    public function setFetchMode($mode)
    {
        $params = func_get_args();
        call_user_func_array([$this->_statement, 'setFetchMode'], $params);
    }

    /**
     * Advances the reader to the next row in a result set.
     * @return array the current row, false if no more row available
     */
    public function read()
    {
        return $this->_statement->fetch();
    }

    /**
     * Returns a single column from the next row of a result set.
     * @param integer $columnIndex zero-based column index
     * @return mixed the column of the current row, false if no more rows available
     */
    public function readColumn($columnIndex)
    {
        return $this->_statement->fetchColumn($columnIndex);
    }

    /**
     * Returns an object populated with the next row of data.
     * @param string $className class name of the object to be created and populated
     * @param array $fields Elements of this array are passed to the constructor
     * @return mixed the populated object, false if no more row of data available
     */
    public function readObject($className, $fields)
    {
        return $this->_statement->fetchObject($className, $fields);
    }

    /**
     * Reads the whole result set into an array.
     * @return array the result set (each array element represents a row of data).
     * An empty array will be returned if the result contains no row.
     */
    public function readAll()
    {
        return $this->_statement->fetchAll();
    }

    /**
     * Advances the reader to the next result when reading the results of a batch of statements.
     * This method is only useful when there are multiple result sets
     * returned by the query. Not all DBMS support this feature.
     * @return boolean Returns true on success or false on failure.
     */
    public function nextResult()
    {
        if (($result = $this->_statement->nextRowset()) !== false) {
            $this->_index = -1;
        }

        return $result;
    }

    /**
     * Closes the reader.
     * This frees up the resources allocated for executing this SQL statement.
     * Read attempts after this method call are unpredictable.
     */
    public function close()
    {
        $isClosed = false;
        if (is_resource($this->resultId)) {
            //ErrorHandler::start();
            $isClosed       = ldap_free_result($this->resultId);
            //ErrorHandler::stop();

            $this->resultId = null;
            $this->current  = null;
        }
        return $isClosed;
        
        $this->_statement->closeCursor();
        $this->_closed = true;
    }

    /**
     * whether the reader is closed or not.
     * @return boolean whether the reader is closed or not.
     */
    public function getIsClosed()
    {
        return $this->_closed;
    }

    /**
     * Returns the number of rows in the result set.
     * This method is required by the Countable interface.
     * Note, most DBMS may not give a meaningful count.
     * In this case, use "SELECT COUNT(*) FROM tableName" to obtain the number of rows.
     * @return integer number of rows contained in the result.
     */
    public function count()
    {
        return $this->_count;
    }

    /**
     * Returns the number of columns in the result set.
     * Note, even there's no row in the reader, this still gives correct column number.
     * @return integer the number of columns in the result set.
     */
    public function getColumnCount()
    {
        return $this->_statement->columnCount();
    }

    /**
     * Resets the iterator to the initial state.
     * This method is required by the interface [[\Iterator]].
     * @throws InvalidCallException if this method is invoked twice
     */
    public function rewind()
    {        
        reset($this->entries);
        $nextEntry = current($this->entries);
        $this->_row = $nextEntry['resource'];
    }

    /**
     * Returns the result of the current item.
     * This method is required by the interface [[\Iterator]].
     * @return integer the index of the current row.
     */
    public function key()
    {
        if (!is_resource($this->_row)) {
            $this->rewind();
        }
        if (is_resource($this->_row)) {
            $resource = $this->_conn->resource;
            //ErrorHandler::start();
            $currentDn = ldap_get_dn($resource, $this->_row);
            //ErrorHandler::stop();

            if ($currentDn === false) {
                //throw new Exception\LdapException($this->ldap, 'getting dn');
            }

            return $currentDn;
        } else {
            return;
        }
    }

    /**
     * Returns the current row.
     * This method is required by the interface [[\Iterator]].
     * @return mixed the current row.
     */
    public function current()
    {
        if (!is_resource($this->_row)) {
            $this->rewind();
        }
        if (!is_resource($this->_row)) {
            return;
        }

        $entry = ['dn' => $this->key()];

        $resource = $this->_conn->resource;
        //ErrorHandler::start();
        $name = ldap_first_attribute($resource, $this->_row);
        //ErrorHandler::stop();
        
        while ($name) {
            //ErrorHandler::start();
            $data = ldap_get_values_len($resource, $this->_row, $name);
            //ErrorHandler::stop();

            if (!$data) {
                $data = [];
            }

            if (isset($data['count'])) {
                unset($data['count']);
            }

//            switch ($this->attributeNameTreatment) {
//                case self::ATTRIBUTE_TO_LOWER:
//                    $attrName = strtolower($name);
//                    break;
//                case self::ATTRIBUTE_TO_UPPER:
//                    $attrName = strtoupper($name);
//                    break;
//                case self::ATTRIBUTE_NATIVE:
//                    $attrName = $name;
//                    break;
//                default:
//                    $attrName = call_user_func($this->attributeNameTreatment, $name);
//                    break;
//            }
            $attrName = $name;
            $entry[$attrName] = $data;

            //ErrorHandler::start();
            $name = ldap_next_attribute($resource, $this->_row);
            //ErrorHandler::stop();
        }
        ksort($entry, SORT_LOCALE_STRING);
        return $entry;
    }

    /**
     * Moves the internal pointer to the next row.
     * This method is required by the interface [[\Iterator]].
     */
    public function next()
    {
        next($this->entries);
        $nextEntry = current($this->entries);
        $this->_row = $nextEntry['resource'];
        $this->_index++;
    }

    /**
     * Returns whether there is a row of data at current position.
     * This method is required by the interface [[\Iterator]].
     * @return boolean whether there is a row of data at current position.
     */
    public function valid()
    {
        return $this->_row !== false;
    }
}
