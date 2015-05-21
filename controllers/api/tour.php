<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tour REST controller
 *
 * Handles all things pertaining to tours
 *
 * @category LazyComet - REST core
 * @package Controllers
 * @author magnus <m@lazycomet.com>
 *
 */
class Tour extends LazyCometRestController
{
    function __construct()
    {
        parent::__construct();
    }


    /**
     * Gets a tour specified by id
     * @param $id The id of the tour to fetch
     */
    function index_get($id)
    {
        $data = array();
        if (isset($id) && $id !== false && is_numeric($id))
        {
            $this->load->model("toursmodel");
            $data = $this->toursmodel->get($id);
            if ($data != null)
            {
                $this->output($data, HTTP_CODE_OK);
                return true;
            }
        }

        // Respond with an error
        $this->output(array("id" => (isset($id) ? $id : '-1')), HTTP_CODE_NOTFOUND, false, "No tour was found.");
    }


    /**
     * Create a new tour
     */
    function index_post()
    {
        // Retrieve values
        $name = $this->post('name');
        $description = $this->post('description');
        // Sanity check
        if ($description === false || $description == "")
            $description = null;

        // Verify that the core values are set
        if ($this->isValidUser() && ($name !== false && $name != ""))
        {
            // Create band
            $this->load->model("toursmodel");
            $tour = $this->toursmodel->create(
                $this->rest->user_id,
                $name,
                $description
            );
            // Verify success
            if ($tour != -1)
            {
                // We output the id of the newly created tour
                $this->output(array(
                    "id" => $tour
                ), HTTP_CODE_OK);
                return true;
            }
            else
            {
                $this->output($this->put(), HTTP_CODE_SERVERERROR, false, "Unable to create tour.");
                return false;
            }
        }

        // Respond with an error
        $this->output(array("parameters" => $this->put()), HTTP_CODE_FORBIDDEN, false, "Incomplete tour data.");
        return false;
    }

    /**
     * Updates a specified tour
     * @param $id The id of the tour to update
     */
    function index_put($id)
    {
        if (isset($id) && $id !== false && is_numeric($id) && $this->isValidUser())
        {
            $this->load->model("toursmodel");
            // Verify permissions
            if (!$this->toursmodel->hasPermissions($id, $this->rest->user_id))
            {
                // We return with errors
                $this->output(array("id" => $id, "parameters" => $this->put()), HTTP_CODE_UNAUTHORIZED, false, "Change unauthorized due to permissions.");
                return false;
            }
            // Make sure it exists
            if (!$this->toursmodel->doesExist($id))
            {
                // We return with errors
                $this->output(array("id" => $id, "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "No tour found.");
                return false;
            }

            // Retrieve values
            $name = $this->put('name');
            $description = $this->put('description');

            // If no field is posted then we exit
            if ($name === false && $description === false)
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
            if ($description !== false && $description != "")
                $data["description"] = $description;

            // TODO: Make this into a single update query and handle errors on fail
            // Submit changes
            foreach ($data as $field => $value)
                $this->toursmodel->updateItem($id, $field, $value);

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
        $this->output(array("id" => (isset($id) ? $id : '-1'), "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "Invalid tour id.");
        return false;
    }

    /**
     * Deletes a tour from the users listing
     * @param $id The id of the tour to delete
     */
    function index_delete($id)
    {
        $data = array();
        if (isset($id) && $id !== false && is_numeric($id))
        {
            $this->load->model("toursmodel");
            if ($this->isValidUser() &&
                $this->toursmodel->hasPermissions($id, $this->rest->user_id) &&
                $this->toursmodel->delete($id))
            {
                $this->output(array("id" => $id), HTTP_CODE_OK);
                return true;
            }

            // We return with errors
            $this->output(array("id" => $id), HTTP_CODE_UNAUTHORIZED, false, "Change unauthorized due to permissions.");
            return false;
        }

        // Respond with an error
        $this->output(array("id" => (isset($id) ? $id : '-1')), HTTP_CODE_NOTFOUND, false, "No tour was found.");
    }

    /**
     * Attach an event
     * @param $id The id of the tour to attach to
     * @param $event The id of the event to attach
     */
    function attachEvent_post($id, $event)
    {
        // Verify that the core values are set
        if (isset($id) && $id !== false && is_numeric($id) &&
            isset($event) && $event !== false && is_numeric($event))
        {
            $this->load->model("toursmodel");
            // Verify permissions
            if (!$this->toursmodel->hasPermissions($id, $this->rest->user_id))
            {
                // We return with errors
                $this->output(array("id" => $id, "event" => $event, "parameters" => $this->put()), HTTP_CODE_UNAUTHORIZED, false, "Change unauthorized due to permissions.");
                return false;
            }
            // Make sure it exists
            if (!$this->toursmodel->doesExist($id))
            {
                // We return with errors
                $this->output(array("id" => $id, "event" => $event, "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "No tour found.");
                return false;
            }

            // Verify success
            if ($this->toursmodel->attachEvent($id, $event))
            {
                // We output the ids
                $this->output(array(
                    "id" => $id,
                    "event" => $event
                ), HTTP_CODE_OK);
                return true;
            }
            else
            {
                $this->output(array(
                    "id" => (isset($id) ? $id : '-1'),
                    "event" => (isset($event) ? $event : '-1'),
                    "parameters" => $this->put()
                ), HTTP_CODE_SERVERERROR, false, "Unable attach event to tour.");
                return false;
            }
        }

        // Respond with an error
        $this->output(array(
            "id" => (isset($id) ? $id : '-1'),
            "event" => (isset($event) ? $event : '-1'),
            "parameters" => $this->put()
        ), HTTP_CODE_FORBIDDEN, false, "Incomplete data.");
        return false;
    }



    /**
     * Detach an event
     * @param $id The id of the tour to detach from
     * @param $event The id of the event to detach
     */
    function detachEvent_post($id, $event)
    {
        // Verify that the core values are set
        if (isset($id) && $id !== false && is_numeric($id) &&
            isset($event) && $event !== false && is_numeric($event))
        {
            $this->load->model("toursmodel");
            // Verify permissions
            if (!$this->toursmodel->hasPermissions($id, $this->rest->user_id))
            {
                // We return with errors
                $this->output(array("id" => $id, "event" => $event, "parameters" => $this->put()), HTTP_CODE_UNAUTHORIZED, false, "Change unauthorized due to permissions.");
                return false;
            }
            // Make sure it exists
            if (!$this->toursmodel->doesExist($id))
            {
                // We return with errors
                $this->output(array("id" => $id, "event" => $event, "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "No tour found.");
                return false;
            }
            // Make sure the event is attached
            if (!$this->toursmodel->isEventAttached($id, $event))
            {
                // We return with errors
                $this->output(array("id" => $id, "event" => $event, "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "Event is not attached to tour.");
                return false;
            }

            // Verify success
            if ($this->toursmodel->detachEvent($id, $event))
            {
                // We output the ids
                $this->output(array(
                    "id" => $id,
                    "event" => $event
                ), HTTP_CODE_OK);
                return true;
            }
            else
            {
                $this->output(array(
                    "id" => (isset($id) ? $id : '-1'),
                    "event" => (isset($event) ? $event : '-1'),
                    "parameters" => $this->put()
                ), HTTP_CODE_SERVERERROR, false, "Unable detach event from tour.");
                return false;
            }
        }

        // Respond with an error
        $this->output(array(
            "id" => (isset($id) ? $id : '-1'),
            "event" => (isset($event) ? $event : '-1'),
            "parameters" => $this->put()
        ), HTTP_CODE_FORBIDDEN, false, "Incomplete data.");
        return false;
    }

    /**
     * Attach a user
     * @param $id The id of the tour to attach to
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
            $this->load->model("toursmodel");
            // Verify permissions
            if (!$this->toursmodel->hasPermissions($id, $this->rest->user_id))
            {
                // We return with errors
                $this->output(array("id" => $id, "user" => $user, "parameters" => $this->put()), HTTP_CODE_UNAUTHORIZED, false, "Change unauthorized due to permissions.");
                return false;
            }
            // Make sure it exists
            if (!$this->toursmodel->doesExist($id))
            {
                // We return with errors
                $this->output(array("id" => $id, "user" => $user, "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "No tour found.");
                return false;
            }

            // Verify success
            if ($this->toursmodel->attachUser($id, $user))
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
     * @param $id The id of the tour to detach from
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

            $this->load->model("toursmodel");
            // Verify permissions
            if (!$this->toursmodel->hasPermissions($id, $this->rest->user_id))
            {
                // We return with errors
                $this->output(array("id" => $id, "user" => $user, "parameters" => $this->put()), HTTP_CODE_UNAUTHORIZED, false, "Change unauthorized due to permissions.");
                return false;
            }
            // Make sure it exists
            if (!$this->toursmodel->doesExist($id))
            {
                // We return with errors
                $this->output(array("id" => $id, "user" => $user, "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "No tour found.");
                return false;
            }
            // Make sure the event is attached
            if (!$this->toursmodel->isUserAttached($id, $user))
            {
                // We return with errors
                $this->output(array("id" => $id, "user" => $user, "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "User is not attached to tour.");
                return false;
            }

            // Verify success
            if ($this->toursmodel->detachUser($id, $user))
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
                ), HTTP_CODE_SERVERERROR, false, "Unable detach user from tour.");
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