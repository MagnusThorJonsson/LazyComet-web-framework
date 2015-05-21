<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bands model
 * Handles all bands model connectivity
 *
 * @category LazyComet - REST core
 * @package Models
 * @author magnus <m@lazycomet.com>
 */
class bandsmodel extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Gets a specific band by id
     * @param $id The id of the band
     * @param $deleted Null for both, true/false for either one
     * @return object MySQL row object if found, else null
     */
    public function get($id, $deleted = null)
    {
        if (is_numeric($id))
        {
            $result = $this->db->query("
                SELECT
                    id,
                    createdBy,
                    name,
                    safename,
                    city,
                    state,
                    country,
                    description,
                    website,
                    deleted,
                    createdAt,
                    modifiedAt
                FROM
                    bands
                WHERE
                    id = ? " .
                    (($deleted !== null && is_bool($deleted)) ?
                        ($deleted == true ?
                            'AND deleted = 1' :
                            'AND deleted = 0') :
                    ''), array($id));

            if ($result->num_rows() > 0)
                return $result->row();
        }

        return null;
    }

    /**
     * Gets all bands
     * @param null $deleted
     * @param int $page
     * @param int $maxRows
     * @return null
     */
    public function getAll($deleted = null, $page = 0, $maxRows = 1000)
    {
        if (is_numeric($page) && is_numeric($maxRows))
        {
            // Make sure maxRows don't go below 1 or above the maximum rows allowed for bands
            $maxRows = ($maxRows < 1 ? DB_MAXROWS_BANDS : ($maxRows > DB_MAXROWS_BANDS ? DB_MAXROWS_BANDS : $maxRows));

            // Prepare the page value
            $page = ($page < 0 ? 0 : $page);
            $page = $page * $maxRows;

            $result = $this->db->query("
                SELECT
                    id,
                    createdBy,
                    name,
                    safename,
                    city,
                    state,
                    country,
                    description,
                    website,
                    deleted,
                    createdAt,
                    modifiedAt
                FROM
                    bands " .
                (($deleted !== null && is_bool($deleted)) ?
                    ($deleted == true ?
                        'WHERE deleted = 1 ' :
                        'WHERE deleted = 0 ') :
                '') .
                "LIMIT ?, ?", array($page, $maxRows));

            if ($result->num_rows() > 0)
                return $result->result();
        }

        return null;
    }

    /**
     * Create a band
     * @param $createdBy The user creating the band
     * @param $name The name of the band
     * @param $country The country ISO code
     * @param $state The state of the country
     * @param $city Optional The name of the city
     * @param $description Optional Description of the band
     * @param $website Optional The bands website
     * @return int Id of the created band if successful, else -1
     */
    public function create($createdBy, $name, $country, $state = null, $city = null, $description = null, $website = null)
    {
        // Make sure critical data is valid
        if ((is_numeric($createdBy) && $this->lazycometusers->getUserName($createdBy) != null) &&
            (is_string($name) && strlen($name) > 0))
        {
            // If the country variable is invalid we quit
            if (!is_string($country) || (is_string($country) && array_key_exists($country, config_item('country_list'))))
                return -1;

            // Sanity check input strings
            $this->load->helpers("validation");
            if (!isValidUrlFormat($website))
                $website = null;
            if (!is_string($description) || (is_string($description) && strlen($description) == 0))
                $description = null;
            if (!is_string($state) || (is_string($state) && strlen($state) == 0))
                $state = null;
            if (!is_string($city) || (is_string($city) && strlen($city) == 0))
                $city = null;

            // Create band
            $result = $this->db->query("
                INSERT INTO
                  bands (createdBy, name, safename, country, state, city, description, website)
                VALUES
                  (?, ?, ?, ?, ?, ?, ?)", array($createdBy, $name, $this->generateSafename($name), $country, $state, $city, $description, $website));

            if ($result !== false)
            {
                $band = $this->db->insert_id();
                $this->attachUser($band, $createdBy);
                return $band;
            }
        }

        return -1;
    }

    /**
     * Attaches a user to a band
     * @param $user The id of the user
     * @param $band The id of the band
     * @return bool True on success
     */
    public function attachUser($band, $user)
    {
        return $this->db->query("
            INSERT INTO
              user_bands (user, band)
            VALUES
              (?, ?)", array($user, $band));
    }

    /**
     * Detaches a user from a band
     * @param $user The id of the user
     * @param $band The id of the band
     * @return bool True on success
     */
    public function detachUser($band, $user)
    {
        return $this->db->query("
            DELETE FROM
              user_bands
            WHERE
              user = ? AND
              band = ?", array($user, $band));
    }

    /**
     * Verifies that an user is attached
     * @param $band The id of the band
     * @param $user The id of the user
     * @return bool True if attached
     */
    public function isUserAttached($band, $user)
    {
        $result = $this->db->query("
            SELECT
              createdAt
            FROM
              user_bands
            WHERE
              band = ? AND
              user = ?", array($band, $user));

        if ($result->num_rows() > 0)
            return true;

        return false;
    }

    /**
     * Updates a band
     * @param $id The id of the band
     * @param $name The name of the band
     * @param $country The country ISO code
     * @param $state Optional The state of the country
     * @param $city Optional The name of the city
     * @param $description The bands description
     * @param $website The website for the band
     * @return bool True if successful, otherwise false
     */
    public function update($id, $name, $country, $state, $city, $description, $website)
    {
        if (is_numeric($id))
        {
            // If the name is empty we return false
            if (!is_string($name) || (is_string($name) && strlen($name) == 0))
                return false;

            // If the country variable is invalid we quit
            if (!is_string($country) || (is_string($country) && array_key_exists($country, config_item('country_list'))))
                return false;

            // Sanity check input strings
            $this->load->helpers("validation");
            if (!isValidUrlFormat($website))
                $website = null;
            if (!is_string($state) || (is_string($state) && strlen($state) == 0))
                $state = null;
            if (!is_string($city) || (is_string($city) && strlen($city) == 0))
                $city = null;
            if (!is_string($description) || (is_string($description) && strlen($description) == 0))
                $description = null;

            return $this->db->query("
                UPDATE
                  bands
                SET
                  name = ?,
                  safename = ?,
                  country = ?,
                  state = ?,
                  city = ?,
                  description = ?,
                  website = ?,
                  modifiedAt = NOW()
                WHERE
                  id = ?", array($name, $this->generateSafename($name), $country, $state, $city, $description, $website, $id));
        }

        return false;
    }

    /**
     * Updates a specific band item
     * @param $id The id of the band
     * @param $field The parameter to update
     * @param $value The update value
     * @return bool True on success
     */
    public function updateItem($id, $field, $value)
    {
        if (is_numeric($id) && is_string($field) && $field != "" && is_string($value) && $value != "")
        {
            // Make sure no critical fields can be changed
            if ($field == "id"  || $field == "createdBy"  || $field == "safename" || $field == "deleted" || $field == "createdAt" || $field == "modifiedAt")
                return false;

            // Sanity check input strings
            $this->load->helpers("validation");
            if ($field == "name")
            {
                if (!is_string($value) || (is_string($value) && strlen($value) == 0))
                    return false;
            }
            else if ($field == "country")
            {
                if (!is_string($value) || (is_string($value) && strlen($value) == 0) || !array_key_exists($value, config_item('country_list')))
                    $value = null;
            }
            else if ($field == "website")
            {
                if (!isValidUrlFormat($value))
                    $value = null;
            }
            else if ($field == "description" || $field == "state" || $field == "city")
            {
                if (!is_string($value) || (is_string($value) && strlen($value) == 0))
                    $value = null;
            }

            return $this->db->query("
                UPDATE
                  bands
                SET
                  {$field} = ?,
                  " . ($field=='name'?'safename = "'. $this->generateSafename($value) .'",':'') . "
                  modifiedAt = NOW()
                WHERE
                  id = ?", array($value, $id));
        }

        return false;
    }

    /**
     * Deletes a band
     * @param $band The id of the band
     * @param bool $delete Delete or undelete, defaults to delete
     * @return bool True on success
     */
    public function delete($band, $delete = true)
    {
        return $this->db->query("
            UPDATE
              bands
            SET
              deleted = 1
            WHERE
              id = ?", array($band, (int)$delete));
    }

    /**
     * Generates a safename for a band
     * @param $name The name of the band
     * @param int $count The current iteration
     * @return string The generated safename
     */
    public function generateSafename($name, $count = 0)
    {
        $this->load->library("lazycometsafename");
        $safename = $this->lazycometsafename->urlize($name);

        if ($this->doesSafenameExist($safename))
        {
            // Make sure we never end in a loop by return a purely random safename
            if ($count >= 10)
                return md5($name . $count + rand(10, 1000) . microtime() . uniqid());

            return $this->generateSafename($name . rand(10, 1000), $count++);
        }

        return $safename;
    }

    /**
     * Checks if a user has the correct permission
     * @param $user The id of the user
     * @param $band The id of the band
     * @return bool True if permissions are applicable
     */
    public function hasPermissions($user, $band)
    {
        $result = $this->db->query("
            SELECT
              createdAt
            FROM
              user_bands
            WHERE
              user = ? AND
              band = ?", array($user, $band));

        if ($result->num_rows() > 0)
            return true;

        return false;
    }

    /**
     * Does a band exist
     * @param $id The id of the band
     * @return bool True if found
     */
    public function doesExist($id)
    {
        $result = $this->db->query("
            SELECT
              id
            FROM
              bands
            WHERE
              id = ?", array($id));

        if ($result->num_rows() > 0)
            return true;

        return false;
    }



    /**
     * Checks whether a safename already exists in the db
     * @param $safename The safename to verify
     * @return bool True if one is found
     */
    public function doesSafenameExist($safename)
    {
        $result = $this->db->query("
            SELECT
              id
            FROM
              bands
            WHERE
              safename = ?", array($safename));

        if ($result->num_rows() > 0)
            return true;

        return false;
    }
}
