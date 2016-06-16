<?php
namespace Taper\Collection;

/**
 * Collection
 */
class Collection implements CollectionInterface
{
    /**
     * The source data
     *
     * @var array
     */
    protected $data = [];

    /**
     * Create new collection
     *
     * @param array $items Pre-populate collection with this key-value array
     */
    public function __construct(array $items = [])
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Set collection item
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Get collection item for key
     *
     * @param string $key     The data key
     * @param mixed  $default The default value to return if data key does not exist
     *
     * @return mixed The key's value, or the default value
     */
    public function get($key, $default = null)
    {
        return $this->has($key) ? $this->data[$key] : $default;
    }

    /**
     * Add item to collection
     *
     * @param array $items Key-value array of data to append to this collection
     */
    public function replace(array $items)
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Get all items in collection
     *
     * @return array The collection's source data
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Get collection keys
     *
     * @return array The collection's source data keys
     */
    public function keys()
    {
        return array_keys($this->data);
    }

    /**
     * Does this collection have a given key?
     *
     * @param string $key The data key
     *
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Remove item from collection
     *
     * @param string $key The data key
     */
    public function remove($key)
    {
        unset($this->data[$key]);
    }

    /**
     * Remove all items from collection
     */
    public function clear()
    {
        $this->data = [];
    }

    /**
     * Reset the collection.
     */
    public function reset() {
        reset($this->data);
    }

    /**
     * Gets current collection item.
     *
     * @return mixed Value
     */
    public function current() {
        return current($this->data);
    }

    /**
     * Gets current collection key.
     *
     * @return mixed Value
     */
    public function key() {
        return key($this->data);
    }

    /**
     * Gets the next collection value.
     *
     * @return mixed Value
     */
    public function next()
    {
        return next($this->data);
    }

    /**
     * Checks if the current collection key is valid.
     *
     * @return bool Key status
     */
    public function valid()
    {
        $key = key($this->data);
        return ($key !== NULL && $key !== FALSE);
    }

    /**
     * Gets the size of the collection.
     *
     * @return int Collection size
     */
    public function count() {
        return count($this->data);
    }

    /**
     * Gets the collection data.
     *
     * @return array Collection data
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Sets the collection data.
     *
     * @param array $data New collection data
     */
    public function setData(array $data) {
        $this->data = $data;
    }

    /********************************************************************************
     * ArrayAccess interface
     *******************************************************************************/

    /**
     * Does this collection have a given key?
     *
     * @param  string $key The data key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Get collection item for key
     *
     * @param string $key The data key
     *
     * @return mixed The key's value, or the default value
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Set collection item
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Remove item from collection
     *
     * @param string $key The data key
     */
    public function offsetUnset($key)
    {
        $this->remove($key);
    }

    /********************************************************************************
     * IteratorAggregate interface
     *******************************************************************************/

    /**
     * Get collection iterator
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /********************************************************************************
     * Magic methods
     *******************************************************************************/

    /**
     * Gets an item.
     *
     * @param string $key Key
     * @return mixed Value
     */
    public function __get($key) {
        return $this->get($key);
    }

    /**
     * Set collection item
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     */
    public function __set($key, $value) {
        $this->set($key, $value);
    }

    /**
     * Checks if an item exists.
     *
     * @param string $key Key
     * @return bool Item status
     */
    public function __isset($key) {
        return isset($this->data[$key]);
    }

    /**
     * Removes an item.
     *
     * @param string $key Key
     */
    public function __unset($key) {
        unset($this->data[$key]);
    }
}
