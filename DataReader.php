<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace chrmorandi\ldap;

use Countable;
use Iterator;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Object;

/**
 * DataReader represents a forward-only stream of rows from a query result set.
 *
 * The method returns [[toArray()]] all the rows in a single array.
 * Rows of data can also be read by iterating through the reader. For example,
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
 * @property integer $columnCount The number of columns in the result set. This property is read-only.
 * @property boolean $isClosed Whether the reader is closed or not. This property is read-only.
 * @property integer $rowCount Number of rows contained in the result. This property is read-only.
 *
 * @author Christopher Mota <chrmorandi@gmail.com>
 * @since 1.0.0
 */
class DataReader extends Object implements Iterator, Countable
{
    /**
     * @var array data
     */
    public $entries;
    
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
        
        Yii::beginProfile('ldap_count_entries', 'chrmorandi\ldap\DataReader');
        $this->_count = ldap_count_entries($resource, $this->_result);
        Yii::endProfile('ldap_count_entries', 'chrmorandi\ldap\DataReader');
        
        if ($this->_count === false) {
            throw new LdapException($this->_conn, sprintf('LDAP count entries failed: %s', $this->_conn->getLastError()), $this->getErrNo());
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
    
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Get all entries as an array
     * @return array
     */
    public function toArray()
    {
        if ($this->_count <= 0) {
            return [];
        }
        
        $data = [];
        foreach ($this as $item) {
            $data[] = $item;
        }
        return $data;
    }

    /**
     * Closes the reader.
     * This frees up the resources allocated for executing this SQL statement.
     * Read attempts after this method call are unpredictable.
     */
    public function close()
    {
        if (is_resource($this->_result)) {
            Yii::beginProfile('ldap_free_result', 'chrmorandi\ldap\DataReader');
            $this->_closed = ldap_free_result($this->_result);
            Yii::endProfile('ldap_free_result', 'chrmorandi\ldap\DataReader');

            $this->_result = null;
            $this->_row  = null;
        }
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
     * Resets the iterator to the initial state.
     * This method is required by the interface [[\Iterator]].
     * @throws InvalidCallException if this method is invoked twice
     */
    public function rewind()
    {
        if ($this->_index < 0) {
            reset($this->entries);
            $nextEntry = current($this->entries);
            $this->_row = $nextEntry['resource'];
            $this->_index = 0;
        } else {
            throw new InvalidCallException('DataReader cannot rewind. It is a forward-only reader.');
        }
    }

    /**
     * Returns the result of the current item.
     * This method is required by the interface [[\Iterator]].
     * @return integer the index of the current row.
     */
    public function key()
    {
        Yii::beginProfile('ldap_get_dn', 'chrmorandi\ldap\DataReader');
        $currentDn = ldap_get_dn($this->_conn->resource, $this->_row);
        Yii::endProfile('ldap_get_dn', 'chrmorandi\ldap\DataReader');

        if ($currentDn === false) {
            throw new LdapException($this->_conn, sprintf('LDAP get dn failed: %s', $this->_conn->getLastError()), $this->getErrNo());
        }

        return $currentDn;
    }

    /**
     * Returns the current row.
     * This method is required by the interface [[\Iterator]].
     * @return mixed the current row.
     */
    public function current()
    {
        $entry = ['dn' => $this->key()];

        $resource = $this->_conn->resource;
        
        Yii::beginProfile('current:' . $this->key(), 'chrmorandi\ldap\DataReader');
        $name = ldap_first_attribute($resource, $this->_row);
        
        while ($name) {
            $data = ldap_get_values_len($resource, $this->_row, $name);

            if (!$data) {
                $data = [];
            }

            if (isset($data['count'])) {
                unset($data['count']);
            }

            $attrName = $name;
            $entry[$attrName] = implode(",", $data);

            $name = ldap_next_attribute($resource, $this->_row);
        }
        Yii::endProfile('ldap_first_attribute', 'chrmorandi\ldap\DataReader');
        
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
     * Returns whether there is a row of resource at current position.
     * This method is required by the interface [[\Iterator]].
     * @return boolean whether there is a row of data at current position.
     */
    public function valid()
    {
        return (is_resource($this->_row));
    }
}
