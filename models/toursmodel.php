<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
* Tours model
* Handles all tour model connectivity
*
* @category LazyComet - REST core
* @package Models
* @author magnus <m@lazycomet.com>
*/
class toursmodel extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Gets a specific tours by id
     * @param $id The id of the tours
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
                    description,
                    deleted,
                    ended,
                    createdAt,
                    modifiedAt
                FROM
                    tours
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
     * Gets all tours
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
            $maxRows = ($maxRows < 1 ? DB_MAXROWS_TOURS : ($maxRows > DB_MAXROWS_TOURS ? DB_MAXROWS_TOURS : $maxRows));

            // Prepare the page value
            $page = ($page < 0 ? 0 : $page);
            $page = $page * $maxRows;

            $result = $this->db->query("
                SELECT
                    id,
                    createdBy,
                    name,
                    safename,
                    description,
                    deleted,
                    ended,
                    createdAt,
                    modifiedAt
                FROM
                    tours " .
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
     * Creates a tour
     * @param $user The id of the user creating the tour
     * @param $createdBy The name of the tour
     * @param $description The basic description of the tour
     * @return int The id of the tour on success, -1 on failure
     */
    public function create($createdBy, $name, $description)
    {
        // Make sure critical data is valid
        if ((is_numeric($createdBy) && $this->lazycometusers->getUserName($createdBy) != null) &&
            (is_string($name) && strlen($name) > 0))
        {
            if (!is_string($description) || (is_string($description) && strlen($description) == 0))
                $description = null;

            $result = $this->db->query("
                INSERT INTO
                  tours (createdBy, name, safename, description)
                VALUES
                  (?, ?, ?, ?)", array($createdBy, $name, $this->generateSafename($name), $description));

            if ($result !== false)
            {
                $tour = $this->db->insert_id();
                $this->attachUser($tour, $createdBy);
                return $tour;
            }
        }
        return -1;
    }

    /**
     * @param $id The id of the tour
     * @param $name The name of the tour
     * @param $description The basic description of the tour
     * @return bool
     */
    public function update($id, $name, $description)
    {
        if (is_numeric($id) && $this->doesExist($id))
        {
            // If the name is empty we return false
            if (!is_string($name) || (is_string($name) && strlen($name) == 0))
                return false;

            if (!is_string($description) || (is_string($description) && strlen($description) == 0))
                $description = null;

            return $this->db->query("
                UPDATE
                  tours
                SET
                  name = ?,
                  safename = ?,
                  description = ?,
                  modifiedAt = NOW()
                WHERE
                  id = ?", array($name, $this->generateSafename($name), $description, $id));
        }

        return false;
    }


    /**
     * Updates a specific tour item
     * @param $id The id of the tour
     * @param $field The parameter to update
     * @param $value The update value
     * @return bool True on success
     */
    public function updateItem($id, $field, $value)
    {
        if (is_numeric($id) && $this->doesExist($id) && is_string($field) && $field != "" && is_string($value) && $value != "")
        {
            // Make sure no critical fields can be changed
            if ($field == "id"  || $field == "createdBy"  || $field == "safename" || $field == "deleted" || $field == "createdAt" || $field == "modifiedAt")
                return false;

            // Sanity check input strings
            if ($field == "name")
            {
                if (!is_string($value) || (is_string($value) && strlen($value) == 0))
                    $value = null;
            }
            else if ($field == "description")
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
     * Deletes a tour
     * @param $band The id of the tour
     * @param bool $delete Delete or undelete, defaults to delete
     * @return bool True on success
     */
    public function delete($id, $delete = true)
    {
        if (is_numeric($id) && $this->doesExist($id))
        {
            return $this->db->query("
                UPDATE
                  tours
                SET
                  deleted = 1
                WHERE
                  id = ?", array($id, (int)$delete));
        }

        return false;
    }


    /**
     * Flags tour as being ended
     * @param $id The id of the tour
     * @return bool True on success
     */
    public function flagAsEnded($id)
    {
        if (is_numeric($id) && $this->doesExist($id))
        {
            return $this->db->query("
                UPDATE
                  tours
                SET
                  ended = 1
                WHERE
                  id = ?", array($id));
        }

        return false;
    }

    /**
     * Unflags tour as being ended
     * @param $id The id of the tour
     * @return bool True on success
     */
    public function unflagAsEnded($id)
    {
        if (is_numeric($id) && $this->doesExist($id))
        {
            return $this->db->query("
                UPDATE
                  tours
                SET
                  ended = 1
                WHERE
                  id = ?", array($id));
        }

        return false;
    }

    /**
     * Attaches a user to a tour
     * @param $user The id of the user
     * @param $tour The id of the band
     * @return bool True on success
     */
    public function attachUser($tour, $user)
    {
        return $this->db->query("
            INSERT INTO
              user_tours (user, tour)
            VALUES
              (?, ?)", array($user, $tour));
    }

    /**
     * Detaches a user from a tour
     * @param $user The id of the user
     * @param $tour The id of the tour
     * @return bool True on success
     */
    public function detachUser($tour, $user)
    {
        return $this->db->query("
            DELETE FROM
              user_tours
            WHERE
              user = ? AND
              tour = ?", array($user, $tour));
    }

    /**
     * Verifies that an user is attached
     * @param $tour The id of the tour
     * @param $user The id of the user
     * @return bool True if attached
     */
    public function isUserAttached($tour, $user)
    {
        $result = $this->db->query("
            SELECT
              createdAt
            FROM
              user_tours
            WHERE
              tour = ? AND
              user = ?", array($tour, $user));

        if ($result->num_rows() > 0)
            return true;

        return false;
    }

    /**
     * Attaches an event to a tour
     * @param $tour The id of the band
     * @param $event The id of the event
     * @return bool True on success
     */
    public function attachEvent($tour, $event)
    {
        // Make sure we aren't already attached to this tour
        if ($this->isEventAttached($tour, $event))
            return false;

        return $this->db->query("
            INSERT INTO
              tour_events (event, tour)
            VALUES
              (?, ?)", array($event, $tour));
    }

    /**
     * Detaches an event from a tour
     * @param $tour The id of the tour
     * @param $event The id of the event
     * @return bool True on success
     */
    public function detachEvent($tour, $event)
    {
        return $this->db->query("
            DELETE FROM
              tour_events
            WHERE
              event = ? AND
              tour = ?", array($event, $tour));
    }

    /**
     * Verifies that an event is attached
     * @param $tour The id of the tour
     * @param $event The id of the event
     * @return bool True if attached
     */
    public function isEventAttached($tour, $event)
    {
        $result = $this->db->query("
            SELECT
              createdAt
            FROM
              tour_events
            WHERE
              tour = ? AND
              event = ?", array($tour, $event));

        if ($result->num_rows() > 0)
            return true;

        return false;
    }


    /**
     * Checks if a user has the correct permission
     * @param $user The id of the user
     * @param $band The id of the tour
     * @return bool True if permissions are applicable
     */
    public function hasPermissions($user, $tour)
    {
        // TODO: Finish permissions for tour
        return true;

        $result = $this->db->query("
            SELECT
              createdAt
            FROM
              user_tours
            WHERE
              user = ? AND
              band = ?", array($user, $tour));

        if ($result->num_rows() > 0)
            return true;

        return false;
    }

    /**
     * Does a tour exist
     * @param $id The id of the tour
     * @return bool True if found
     */
    public function doesExist($id)
    {
        $result = $this->db->query("
            SELECT
              id
            FROM
              tours
            WHERE
              id = ?", array($id));

        if ($result->num_rows() > 0)
            return true;

        return false;
    }


    /**
     * Generates a safename for a tour
     * @param $name The name of the tour
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
              tours
            WHERE
              safename = ?", array($safename));

        if ($result->num_rows() > 0)
            return true;

        return false;
    }
}