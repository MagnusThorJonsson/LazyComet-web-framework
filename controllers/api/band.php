<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Band REST controller
 *
 * Handles all things pertaining to bands
 *
 * @category LazyComet - REST core
 * @package Controllers
 * @author magnus <m@lazycomet.com>
 *
 */
class Band extends LazyCometRestController
{
    function __construct()
    {
        parent::__construct();
    }


    /**
     * Gets a band specified by id
     * @param $id The id of the band to fetch
     */
    function index_get($id)
    {
        $data = array();
        if (isset($id) && $id !== false && is_numeric($id))
        {
            $this->load->model("bandsmodel");
            $data = $this->bandsmodel->get($id);
            if ($data != null)
            {
                $this->output($data, HTTP_CODE_OK);
                return true;
            }
        }

        // Respond with an error
        $this->output(array("id" => (isset($id) ? $id : '-1')), HTTP_CODE_NOTFOUND, false, "No band was found.");
    }


    /**
     * Create a new band
     */
    function index_post()
    {
        // Retrieve values
        $name = $this->post('name');
        $country = $this->post('country');
        $state = $this->post('state');
        $city = $this->post('city');
        $description = $this->post('description');
        $website = $this->post('website');
        // Sanity check
        if ($state === false || $state == "")
            $state = null;
        if ($city === false || $city == "")
            $city = null;
        if ($description === false || $description == "")
            $description = null;
        if ($website === false || $website == "")
            $website = null;

        // Verify that the core values are set
        if ($this->isValidUser() && ($name !== false && is_string($name) && strlen($name) > 0) && ($country !== false && is_string($country) && strlen($country) == 2))
        {
            $country = strtoupper($country);
            if (!array_key_exists($country, config_item('country_list')))
            {
                $this->output($this->put(), HTTP_CODE_BADREQUEST, false, "No country exists with that country code.");
                return false;
            }

            // Create band
            $this->load->model("bandsmodel");
            $band = $this->bandsmodel->create(
                $this->rest->user_id,
                $name,
                $country,
                $state,
                $city,
                $description,
                $website
            );
            // Verify success
            if ($band != -1)
            {
                // We output the id of the newly created band
                $this->output(array(
                    "id" => $band
                ), HTTP_CODE_OK);
                return true;
            }
            else
            {
                $this->output($this->put(), HTTP_CODE_SERVERERROR, false, "Unable to create band.");
                return false;
            }
        }

        // Respond with an error
        $this->output(array("parameters" => $this->put()), HTTP_CODE_FORBIDDEN, false, "Incomplete band data.");
        return false;
    }

    /**
     * Updates a specified band
     * @param $id The id of the band to update
     */
    function index_put($id)
    {
        if (isset($id) && $id !== false && is_numeric($id) && $this->isValidUser())
        {
            $this->load->model("bandsmodel");
            // Verify permissions
            if (!$this->bandsmodel->hasPermissions($id, $this->rest->user_id))
            {
                // We return with errors
                $this->output(array("id" => $id, "parameters" => $this->put()), HTTP_CODE_UNAUTHORIZED, false, "Change unauthorized due to permissions.");
                return false;
            }
            // Make sure it exists
            if (!$this->bandsmodel->doesExist($id))
            {
                // We return with errors
                $this->output(array("id" => $id, "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "No band found.");
                return false;
            }

            // Retrieve values
            $name = $this->put('name');
            $country = $this->put('country');
            $state = $this->put('state');
            $city = $this->put('city');
            $description = $this->put('description');
            $website = $this->put('website');

            // If no field is posted then we exit
            if ($name === false && $country === false && $state === false && $city === false && $description === false && $website === false)
            {
                // We return with errors
                $this->output(array("id" => $id, "parameters" => $this->put()), HTTP_CODE_BADREQUEST, false, "No fields posted for updating.");
                return false;
            }

            // Initialization
            $this->load->helper("validation");
            $errors = "";
            $success = true;

            $data = array();
            // If any of the fields are not empty we add them to the data object
            if ($name !== false && $name != "")
                $data["name"] = $name;
            if ($country !== false)
            {
                $country = strtoupper($country);
                if (array_key_exists($country, config_item('country_list')))
                    $data["country"] = $country;
            }
            if ($state !== false && $state != "")
                $data["state"] = $state;
            if ($city !== false && $city != "")
                $data["city"] = $city;
            if ($description !== false && $description != "")
                $data["description"] = $description;
            if ($website !== false && $website != "")
            {
                if (!isValidUrlFormat(website))
                {
                    $errors .= "Inputted URL is not valid, change was not made.\n";
                    $success = false;
                }
                else
                    $data["website"] = $website;
            }

            // TODO: Make this into a single update query and handle errors on fail
            // Submit changes
            foreach ($data as $field => $value)
                $this->bandsmodel->updateItem($id, $field, $value);

            // If everything went smoothly
            if ($success)
            {
                $this->output($data, HTTP_CODE_OK);
                return true;
            }

            // We return with errors
            $this->output($data, HTTP_CODE_BADREQUEST, false, "Request failed with some errors:\n" . $errors);
            return false;
        }

        // Respond with an error
        $this->output(array("id" => (isset($id) ? $id : '-1'), "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "Invalid band id.");
        return false;
    }

    /**
     * Deletes a band from the users listing
     * @param $id The id of the band to fetch
     */
    function index_delete($id)
    {
        $data = array();
        if (isset($id) && $id !== false && is_numeric($id))
        {
            $this->load->model("bandsmodel");
            if ($this->isValidUser() &&
                $this->bandsmodel->hasPermissions($id, $this->rest->user_id) &&
                $this->bandsmodel->delete($id))
            {
                $this->output(array("id" => $id), HTTP_CODE_OK);
                return true;
            }

            // We return with errors
            $this->output(array("id" => $id), HTTP_CODE_UNAUTHORIZED, false, "Change unauthorized due to permissions.");
            return false;
        }

        // Respond with an error
        $this->output(array("id" => (isset($id) ? $id : '-1')), HTTP_CODE_NOTFOUND, false, "No band was found.");
    }

    /**
     * Attach a user
     * @param $id The id of the band to attach to
     * @param $user The id of the user to attach
     */
    function attachUser_post($id, $user)
    {
        // Verify that the core values are set
        if (isset($id) && $id !== false && is_numeric($id) &&
            isset($user) && $user !== false && is_numeric($user))
        {
            // Make sure user exists
            if (!$this->lazycometusers->doesUserExist($user))
            {
                // We return with errors
                $this->output(array("id" => $id, "user" => $user, "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "No user found.");
                return false;
            }
            $this->load->model("bandsmodel");
            // Verify permissions
            if (!$this->bandsmodel->hasPermissions($id, $this->rest->user_id))
            {
                // We return with errors
                $this->output(array("id" => $id, "user" => $user, "parameters" => $this->put()), HTTP_CODE_UNAUTHORIZED, false, "Change unauthorized due to permissions.");
                return false;
            }
            // Make sure it exists
            if (!$this->bandsmodel->doesExist($id))
            {
                // We return with errors
                $this->output(array("id" => $id, "user" => $user, "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "No band found.");
                return false;
            }

            // Verify success
            if ($this->bandsmodel->attachUser($id, $user))
            {
                // We output the ids
                $this->output(array(
                    "id" => $id,
                    "user" => $user
                ), HTTP_CODE_OK);
                return true;
            }
            else
            {
                $this->output(array(
                    "id" => (isset($id) ? $id : '-1'),
                    "event" => (isset($user) ? $user : '-1'),
                    "parameters" => $this->put()
                ), HTTP_CODE_SERVERERROR, false, "Unable attach user to tour.");
                return false;
            }
        }

        // Respond with an error
        $this->output(array(
            "id" => (isset($id) ? $id : '-1'),
            "user" => (isset($user) ? $user : '-1'),
            "parameters" => $this->put()
        ), HTTP_CODE_FORBIDDEN, false, "Incomplete data.");
        return false;
    }



    /**
     * Detach a user
     * @param $id The id of the band to detach from
     * @param $user The id of the user to detach
     */
    function detachUser_post($id, $user)
    {
        // Verify that the core values are set
        if (isset($id) && $id !== false && is_numeric($id) &&
            isset($user) && $user !== false && is_numeric($user))
        {
            // Make sure user exists
            if (!$this->lazycometusers->doesUserExist($user))
            {
                // We return with errors
                $this->output(array("id" => $id, "user" => $user, "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "No user found.");
                return false;
            }

            $this->load->model("bandsmodel");
            // Verify permissions
            if (!$this->bandsmodel->hasPermissions($id, $this->rest->user_id))
            {
                // We return with errors
                $this->output(array("id" => $id, "user" => $user, "parameters" => $this->put()), HTTP_CODE_UNAUTHORIZED, false, "Change unauthorized due to permissions.");
                return false;
            }
            // Make sure it exists
            if (!$this->bandsmodel->doesExist($id))
            {
                // We return with errors
                $this->output(array("id" => $id, "user" => $user, "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "No band found.");
                return false;
            }

            // Make sure the event is attached
            if (!$this->bandsmodel->isUserAttached($id, $user))
            {
                // We return with errors
                $this->output(array("id" => $id, "user" => $user, "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "User is not attached to band.");
                return false;
            }

            // Verify success
            if ($this->bandsmodel->detachUser($id, $user))
            {
                // We output the ids
                $this->output(array(
                    "id" => $id,
                    "user" => $user
                ), HTTP_CODE_OK);
                return true;
            }
            else
            {
                $this->output(array(
                    "id" => (isset($id) ? $id : '-1'),
                    "user" => (isset($user) ? $user : '-1'),
                    "parameters" => $this->put()
                ), HTTP_CODE_SERVERERROR, false, "Unable detach user from band.");
                return false;
            }
        }

        // Respond with an error
        $this->output(array(
            "id" => (isset($id) ? $id : '-1'),
            "user" => (isset($user) ? $user : '-1'),
            "parameters" => $this->put()
        ), HTTP_CODE_FORBIDDEN, false, "Incomplete data.");
        return false;
    }
}