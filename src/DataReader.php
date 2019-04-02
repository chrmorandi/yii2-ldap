<?php
/**
 * @link      https://github.com/chrmorandi/yii2-ldap for the source repository
 * @package   yii2-ldap
 * @author    Christopher Mota <chrmorandi@gmail.com>
 * @license   MIT License - view the LICENSE file that was distributed with this source code.
 * @since     1.0.0
 */

namespace chrmorandi\ldap;

use Countable;
use Iterator;
use Yii;
use yii\base\InvalidCallException;
use yii\base\BaseObject;
use yii\caching\Cache;
use yii\caching\TagDependency;

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
class DataReader extends BaseObject implements Iterator, Countable
{
    const CACHE_TAG = 'ldap.data';

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
    private $_index  = -1;
    private $_count  = 0;
    private $_results;

    /**
     * Constructor.
     * @param Connection $conn connection interact with result
     * @param resource[]|resource $results result array of search in ldap directory
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct(Connection $conn, $results, $config = [])
    {
        $this->_conn    = $conn;
        $this->_results = $results;

        if (is_array($this->_results)) {
            foreach ($this->_results as $result) {
                $this->_count += $this->_conn->countEntries($result);
                //$this->setEntries($result);
            }
        } else {
            $this->_count += $this->_conn->countEntries($this->_results);
            //$this->setEntries($this->_results);
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
    protected function setEntries($result)
    {
        $identifier = $this->_conn->getFirstEntry($result);

        while (false !== $identifier) {
            $this->entries[] = [
                'resource'  => $identifier,
                'sortValue' => '',
            ];

            $identifier = $this->_conn->getNextEntry($identifier);
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

        $token = 'Get entries with limit pagination ' . $this->_conn->pageSize;
        Yii::beginProfile($token, __METHOD__);
        if ($this->_conn->offset > 0) {
            $this->setEntries($this->_results[intval($this->_conn->offset / $this->_conn->pageSize)]);
        } else {
            if (is_array($this->_results)) {
                foreach ($this->_results as $result) {
                    $this->setEntries($result);
                }
            } else {
                $this->setEntries($this->_results);
            }
        }
        Yii::endProfile($token, __METHOD__);

        $token = 'Get Attributes of entries with limit pagination in ' . $this->_conn->pageSize;
        Yii::beginProfile($token, __METHOD__);
        $data  = [];
        foreach ($this as $item) {
            $data[] = $item;
        }
        Yii::endProfile($token, __METHOD__);

        return $data;
    }

    /**
     * Closes the reader.
     * This frees up the resources allocated for executing this SQL statement.
     * Read attempts after this method call are unpredictable.
     */
    public function close()
    {
        if (is_array($this->_results)) {
            foreach ($this->_results as $result) {
                $this->_conn->freeResult($result);
            }
        } else {
            $this->_conn->freeResult($this->_results);
        }

        $this->_closed  = true;
        $this->_results = null;
        $this->_row     = null;
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
            $nextEntry    = current($this->entries);
            $this->_row   = $nextEntry['resource'];
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
        return $this->_conn->getDn($this->_row);
    }

    /**
     * Returns the current row.
     * This method is required by the interface [[\Iterator]].
     * @return mixed the current row.
     */
    public function current()
    {
        $entry = ['dn' => $this->key()];

        $info = $this->_conn->getCacheInfo(3600, new TagDependency(['tags' => self::CACHE_TAG]));
        if (is_array($info)) {
            /* @var $cache Cache */
            $cache    = $info[0];
            $cacheKey = [__CLASS__, $entry['dn']];
            $result   = $cache->get($cacheKey);
            if (is_array($result) && isset($result[0])) {
                Yii::trace('Query result served from cache', __METHOD__);
                return $result[0];
            }
        }

        $name = $this->_conn->getFirstAttribute($this->_row);

        while ($name) {
            $data = $this->_conn->getValuesLen($this->_row, $name);

            if (isset($data['count'])) {
                unset($data['count']);
            }

            $attrName         = $name;
            $entry[$attrName] = implode(",", $data);

            $name = $this->_conn->getNextAttribute($this->_row);
        }

        ksort($entry, SORT_LOCALE_STRING);

        if (isset($cache, $cacheKey, $info)) {
            $cache->set($cacheKey, [$entry], $info[1], $info[2]);
            Yii::trace('Saved query result in cache', __METHOD__);
        }

        return $entry;
    }

    /**
     * Moves the internal pointer to the next row.
     * This method is required by the interface [[\Iterator]].
     */
    public function next()
    {
        next($this->entries);
        $nextEntry  = current($this->entries);
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
