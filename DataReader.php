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
    private $entries = [];   
    /**
     * @var Connection
     */
    private $_conn;
    private $_closed = false;
    private $_row;
    private $_index = -1;
    private $_count = 0;
    private $_results;


    /**
     * Constructor.
     * @param Connection $conn connection interact with result
     * @param resource[]|resource $results result array of search in ldap directory
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct(Connection $conn, $results, $config = [])
    {
        $this->_conn   = $conn;
        $this->_results = $results;

        if(is_array($this->_results)){
            foreach ($this->_results as $result) {
                $this->_count += $this->_conn->countEntries($result);
                $this->setEntries($result);
            }
        } else {
            $this->_count += $this->_conn->countEntries($this->_results);
            $this->setEntries($this->_results);
        }
        

        parent::__construct($config);
    }
    
    public function __destruct()
    {
        $this->close();
    }
    
    /**
     * 
     * @param resource $result
     * @return void
     */
    protected function setEntries($result){
        $identifier = ldap_first_entry(
            $this->_conn->resource,
            $result
        );
        
        $entries = [];

        while (false !== $identifier) {
            $this->entries[] = [
                'resource' => $identifier,
                'sortValue' => '',
            ];

            $identifier = ldap_next_entry(
                $this->_conn->resource,
                $identifier
            );
        }
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
        if (is_resource($this->_results)) {
            $this->_closed = ldap_free_result($this->_results);

            $this->_results = null;
            $this->_row = null;
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
     * @return integer number of entries stored in the result.
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
     * @return string the index of the current row.
     */
    public function key()
    {
        return ldap_get_dn($this->_conn->resource, $this->_row);
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
        
        $name = ldap_first_attribute($resource, $this->_row);
        
        while ($name) {
            $data = @ldap_get_values_len($resource, $this->_row, $name);

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
