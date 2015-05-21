<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Library for venue data.
 *
 * @author Maggi
 */
class LazyCometVenue
{
    private $CI = null;
    private $error = '';

    function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * Gets a specific venue from DB
     * @param $id The local id of the venue
     * @return stdClass Null if empty
     */
    public function get($id)
    {
        if ($id != null && is_numeric($id))
        {
            $result = $this->CI->db->query("
                SELECT
                  id,
                  source,
                  sourceId,
                  name,
                  description,
                  city,
                  country,
                  state,
                  address,
                  address_2,
                  postalCode,
                  email,
                  phone,
                  fax,
                  website,
                  twitter,
                  facebook,
                  latitude,
                  longitude,
                  createdAt,
                  modifiedAt
                FROM
                  venues
                WHERE
                  id = ?", array($id));

            if ($result->num_rows() > 0)
                return $result->row();
        }

        return null;
    }

    /**
     * Gets a venue by source
     * @param $source The source where the venue came from
     * @param $sourceId The source API id of the venue
     * @return stdClass Null if empty
     */
    public function getBySource($source, $sourceId)
    {
        if (($source != null && is_string($source) && strlen($source) > 0) &&
            ($sourceId != null && is_string($sourceId) && strlen($sourceId) > 0))
        {
            $result = $this->CI->db->query("
                SELECT
                  id,
                  source,
                  sourceId,
                  name,
                  description,
                  city,
                  country,
                  state,
                  address,
                  address_2,
                  postalCode,
                  email,
                  phone,
                  fax,
                  website,
                  twitter,
                  facebook,
                  latitude,
                  longitude,
                  createdAt,
                  modifiedAt
                FROM
                  venues
                WHERE
                  source = ? AND
                  sourceId = ?", array($source, $sourceId));

            if ($result->num_rows() > 0)
                return $result->row();
        }

        return null;
    }


    /**
     * Find a venue via the database
     * @param $venue The venue query string
     * @param $city The city string
     * @param $country The country ISO code
     * @param $state Optional The state of where the band is
     * @return array An array of mysql result objects, null if nothing is found
     */
    public function find($venue, $city, $country, $state = null)
    {
        if (($venue != null && is_string($venue) && strlen($venue) > 0) &&
            ($city != null && is_string($city) && strlen($city) > 0) &&
            ($country != null && is_string($country) && strlen($country) == 2))
        {
            if (!is_string($state) || (is_string($state) && strlen($state) == 0))
                $state = null;

            $result = $this->CI->db->query("
                SELECT
                  id,
                  source,
                  sourceId,
                  name,
                  description,
                  city,
                  country,
                  state,
                  address,
                  address_2,
                  postalCode,
                  email,
                  phone,
                  fax,
                  website,
                  twitter,
                  facebook,
                  latitude,
                  longitude,
                  createdAt,
                  modifiedAt
                FROM
                  venues
                WHERE
                  country = ? AND
                  city = ? AND
                  name LIKE '%{$venue}%'
                  " . ($state != null ? 'AND state = ' . $state : ''), array($country, $city));

            if ($result->num_rows() > 0)
                return $result->result();
        }

        return null;
    }

    /**
     * Find a venue via the database
     * @param $source The source API identifier
     * @param $venue The venue query string
     * @param $city The city string
     * @param $country The country ISO code
     * @param $state Optional The state of where the band is
     * @return array An array of mysql result objects, null if nothing is found
     */
    public function findBySource($source, $venue, $city, $country, $state = null)
    {
        if (($source != null && is_string($source) && strlen($source) > 0) &&
            ($venue != null && is_string($venue) && strlen($venue) > 0) &&
            ($city != null && is_string($city) && strlen($city) > 0) &&
            ($country != null && is_string($country) && strlen($country) == 2))
        {
            if (!is_string($state) || (is_string($state) && strlen($state) == 0))
                $state = null;

            $result = $this->CI->db->query("
                SELECT
                  id,
                  source,
                  sourceId,
                  name,
                  description,
                  city,
                  country,
                  state,
                  address,
                  address_2,
                  postalCode,
                  email,
                  phone,
                  fax,
                  website,
                  twitter,
                  facebook,
                  latitude,
                  longitude,
                  createdAt,
                  modifiedAt
                FROM
                  venues
                WHERE
                  source = ? AND
                  country = ? AND
                  city = ? AND
                  name LIKE '%{$venue}%'
                  " . ($state != null ? 'AND state = ' . $state : ''), array($source, $country, $city));

            if ($result->num_rows() > 0)
                return $result->result();
        }

        return null;
    }


    // TODO: Might want to refactor the parameters into an object, this is pretty messy
    /**
     * Adds a new venue to the cache tables
     * @param $source The source of the venue (foursquare, google, facebook, eventful, etc)
     * @param $sourceId The id of the source within the API it came from
     * @param $name The name of the venue
     * @param $city The venue city
     * @param $country The ISO country code
     * @param $state Optional The state where the venue resides
     * @param $address Optional The venue address
     * @param $address_2 Optional The venue second address
     * @param $postalCode Optional The post code of the venue
     * @param $email Optional The venues contact email
     * @param $phone Optional The venues phone number
     * @param $fax Optional The venues fax number
     * @param $description Optional The venues description
     * @param $website Optional The venues website
     * @param $twitter Optional The venues twitter user
     * @param $facebook Optional The venues facebook id
     * @param $lat Optional The venues latitude
     * @param $lng Optional The venues longitude
     * @return mixed DB id on success, false on fail
     */
    public function add($source, $sourceId, $name, $city, $country, $state = null, $address = null, $address_2 = null,
                        $postalCode = null, $email = null, $phone = null, $fax = null, $description = null, $website = null, $twitter = null,
                        $facebook = null, $lat = null, $lng = null)
    {
        // Verify core input values
        if ($source == null || !is_string($source) || (is_string($source) && strlen($source) == 0))
        {
            $this->error = "The source field contains invalid data.";
            return false;
        }
        if ($sourceId == null || !is_string($sourceId) || (is_string($sourceId) && strlen($sourceId) == 0))
        {
            $this->error = "The source id field contains invalid data.";
            return false;
        }
        if ($name == null || !is_string($name) || (is_string($name) && strlen($name) == 0))
        {
            $this->error = "The name field contains invalid data.";
            return false;
        }
        if ($city == null || !is_string($city) || (is_string($city) && strlen($city) == 0))
        {
            $this->error = "The city field contains invalid data.";
            return false;
        }
        if ($country == null || !is_string($country) || (is_string($country) && strlen($country) != 2))
        {
            $this->error = "The country field contains invalid data.";
            return false;
        }

        // Verify optional input values
        $this->CI->load->helper("validation");
        if (!is_string($address) || (is_string($address) && strlen($address) == 0))
            $address = null;
        if (!is_string($address_2) || (is_string($address_2) && strlen($address_2) == 0))
            $address_2 = null;
        if (!is_string($postalCode) || (is_string($postalCode) && strlen($postalCode) == 0))
            $postalCode = null;
        if (!is_string($email) || (is_string($email) && strlen($email) == 0))
            $email = null;
        if (!is_string($phone) || (is_string($phone) && strlen($phone) == 0))
            $phone = null;
        if (!is_string($fax) || (is_string($fax) && strlen($fax) == 0))
            $fax = null;
        if (!is_string($description) || (is_string($description) && strlen($description) == 0))
            $description = null;
        if (!is_string($website) || isValidUrlFormat($website))
            $website = null;
        if (!is_string($twitter) || (is_string($twitter) && strlen($twitter) == 0))
            $twitter = null;
        if (!is_numeric($facebook))
            $facebook = null;
        if (!is_numeric($lat))
            $lat = null;
        if (!is_numeric($lng))
            $lng = null;

        // Insert and return
        $result = $this->CI->db->query("
            INSERT INTO
              venues (source, sourceId, name, description, city, country, state, address, address_2, postalCode, email, phone, fax, website, twitter, facebook, latitude, longitude, createdAt)
            VALUES
              (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
              id = LAST_INSERT_ID(id),
              source = VALUES(source),
              sourceId = VALUES(sourceId),
              name = VALUES(name),
              description = VALUES(description),
              city = VALUES(city),
              country = VALUES(country),
              state = VALUES(state),
              address = VALUES(address),
              address_2 = VALUES(address_2),
              postalCode = VALUES(postalCode),
              email = VALUES(email),
              phone = VALUES(phone),
              fax = VALUES(fax),
              website = VALUES(website),
              twitter = VALUES(twitter),
              facebook = VALUES(facebook),
              latitude = VALUES(latitude),
              longitude = VALUES(longitude),
              modifiedAt = NOW()",
            array($source, $sourceId, $name, $description, $city, $country, $state, $address, $address_2, $postalCode, $email, $phone, $fax, $website, $twitter, $facebook, $lat, $lng)
        );

        if ($result)
            return $this->CI->db->insert_id();

        return false;
    }

    /**
     * Updates a specific venue item
     * @param $id The id of the venue
     * @param $field The parameter to update
     * @param $value The update value
     * @return bool True on success
     */
    public function updateItem($id, $field, $value)
    {
        if (is_numeric($id) && is_string($field) && $field != "" && is_string($value) && $value != "")
        {
            // Make sure no critical fields can be changed
            if ($field == "id"  || $field == "source"  || $field == "sourceId" || $field == "createdAt" || $field == "modifiedAt")
                return false;

            // Sanity check input strings
            $this->load->helpers("validation");
            if ($field == "name" || $field == "city")
            {
                if (!is_string($value) || (is_string($value) && strlen($value) == 0))
                    return false;
            }
            else if ($field == "country")
            {
                if (!is_string($value) || (is_string($value) && strlen($value) == 0) || !array_key_exists($value, config_item('country_list')))
                    return false;
            }
            else if ($field == "website")
            {
                if (!isValidUrlFormat($value))
                    $value = null;
            }
            else if ($field == "state" || $field == "phone" || $field == "fax" || $field == "email" || $field == "description" ||
                     $field == "address" || $field == "address_2" || $field == "postalCode" || $field == "twitter")
            {
                if (!is_string($value) || (is_string($value) && strlen($value) == 0))
                    $value = null;
            }
            else if ($field == "longitude" || $field == "latitude" || $field == "facebook")
            {
                if (!is_numeric($value))
                    $value = null;
            }

            return $this->CI->db->query("
                UPDATE
                  venues
                SET
                  {$field} = ?,
                  modifiedAt = NOW()
                WHERE
                  id = ?", array($value, $id));
        }

        return false;
    }

    /**
     * Returns current error message
     * @return string The error string
     */
    public function getError()
    {
        return $this->error;
    }

}
