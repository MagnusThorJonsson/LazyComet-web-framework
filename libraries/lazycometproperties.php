<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Properties library
 *
 * @author Maggi
 */
class LazyCometProperties
{
    private $CI = null;
    private $table = null;

    /**
     * Property library constructor.
     * @param array $params Property "table" specifies the connecting database tables
     * @throws UnexpectedValueException
     */
    function __construct($params)
    {
        $this->CI =& get_instance();
        if (isset($params["table"]) && is_string($params["table"]))
        {
            $this->table = $params["table"];
        }
        else
            throw new UnexpectedValueException("Parameter 'table' was empty or malformed.");
    }

    /**
     * Get a specific property
     * @param $id The id of the property
     * @return object An mysql row object on success else null
     */
    public function get($id)
    {
        $result = $this->CI->db->query("
            SELECT
              id,
              createdBy,
              property,
              value,
              deleted,
              createdAt
            FROM
              {$this->table}properties
            WHERE
              id = ?", array($id));

        if ($result->num_rows() > 0)
            return $result->row();

        return null;
    }

    /**
     * Gets all properties by a specific item
     * @param $item The id of the item
     * @return array An array of mysql row objects on success else null
     */
    public function getByItem($item)
    {
        $result = $this->CI->db->query("
            SELECT
              {$this->table}properties.id,
              {$this->table}properties.createdBy,
              {$this->table}properties.property,
              {$this->table}properties.value,
              {$this->table}properties.deleted,
              {$this->table}properties.createdAt
            FROM
              {$this->table}properties LEFT JOIN {$this->table}properties_{$this->table} ON {$this->table}properties.id = {$this->table}properties_{$this->table}.property
            WHERE
              {$this->table}properties_{$this->table}.item = ?", array($item));

        if ($result->num_rows() > 0)
            return $result->result();

        return null;
    }

    /**
     * Gets all properties by a specific user
     * @param $user The id of the user
     * @return array An array of mysql row objects on success else null
     */
    public function getByCreatedBy($user)
    {
        $result = $this->CI->db->query("
            SELECT
              id,
              createdBy,
              property,
              value,
              deleted,
              createdAt
            FROM
              {$this->table}properties
            WHERE
              {$this->table}properties.createdBy = ?", array($user));

        if ($result->num_rows() > 0)
            return $result->result();

        return null;
    }

    /**
     * Adds a property to an event
     * @param $user The id of the user adding the property
     * @param $item The connecting item id
     * @param $property The property key
     * @param $value The property value
     * @return int The id of the property or -1 on failure
     */
    public function add($user, $item, $property, $value)
    {
        $result = $this->CI->db->query("
            INSERT INTO
              {$this->table}properties (createdBy, property, value)
            VALUES
              (?, ?, ?)", array($user, $property, $value));

        if ($result)
        {
            $id = $this->CI->db->insert_id();
            $result = $this->CI->db->query("
                INSERT INTO
                  {$this->table}properties_{$this->table} (property, item)
                VALUES
                  (?, ?)", array($id, $item));

            if ($result)
                return $id;
        }

        return -1;
    }

    /**
     * Updates the given property
     * @param $property The id of the property
     * @param $value The value to update with
     * @return bool True on success
     */
    public function update($property, $value)
    {
        return $this->CI->db->query("
            UPDATE
              {$this->table}properties
            SET
              value = ?
            WHERE
              id = ?", array($value, $property));
    }

    /**
     * Deletes a property from the database
     * @param $id The id of the property
     * @param bool $delete True for delete/False for undelete
     * @return bool True on success
     */
    public function delete($id, $delete = true)
    {
        return $this->CI->db->query("
            UPDATE
              {$this->table}properties
            SET
              deleted = ?
            WHERE
              id = ?", array((int)$delete, $id));
    }

    /**
     * Attaches a property to an item
     * @param $property The id of the property to attach
     * @param $item The id of the item to attach the property to
     * @return bool True on success
     */
    public function attach($property, $item)
    {
        return $this->CI->db->query("
            INSERT INTO
              {$this->table}properties_{$this->table} (property, item)
            VALUES
              (?, ?)", array($property, $item));
    }

    /**
     * Detaches a property from an item
     * @param $property The id of the property to attach
     * @param $item The id of the item to detach the property from
     * @param bool $delete True on delete/False on undelete
     * @return bool True on success
     */
    public function detach($property, $item, $delete = true)
    {
        return $this->CI->db->query("
            UPDATE
              {$this->table}properties_{$this->table}
            SET
              deleted = ?
            WHERE
              property = ? AND
              item = ?", array((int)$delete, $property, $item));
    }


    /**
     * Does a property exist for a given item
     * @param $item The id of the item
     * @param $property The id of the property
     * @return bool True if found
     */
    public function doesExist($item, $property)
    {
        $result = $this->CI->db->query("
            SELECT
              createdAt
            FROM
              {$this->table}properties_{$this->table}
            WHERE
              item = ? AND
              property = ?", array($item, $property));

        if ($result->num_rows() > 0)
            return true;

        return false;
    }

    /**
     * Checks whether a property exists for a given item
     * @param $item The id of the item
     * @param $key The key name of the property
     * @return bool True if found
     */
    public function doesExistByKey($item, $key)
    {
        $result = $this->CI->db->query("
            SELECT
              {$this->table}properties.id
            FROM
              {$this->table}properties LEFT JOIN {$this->table}properties_{$this->table} ON {$this->table}properties.property = {$this->table}properties_{$this->table}.id
            WHERE
              {$this->table}properties.property = ? AND
              {$this->table}properties_{$this->table}.item = ?", array($key, $item));

        if ($result->num_rows() > 0)
            return true;

        return false;
    }
}
