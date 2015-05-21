<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Generic properties REST controller
 *
 * Handles all things pertaining to properties
 *
 * @category LazyComet - REST core
 * @package Controllers
 * @author magnus <m@lazycomet.com>
 *
 */
class Properties extends LazyCometRestController
{
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Helper function that verifies that a property type is valid
     * @param $type The property type
     * @return bool True on success
     */
    private function isValidPropertyType($type)
    {
        if (config_item("properties_available")[$type])
            return true;

        return false;
    }

    /**
     * Gets a specific property
     * @param $type The property type
     * @param $id The id of the item
     * @param $property The id of the property
     */
    function index_get($type, $id, $property)
    {
        if (isset($id) && $id !== false && is_numeric($id) && is_string($type) && strlen($type) > 0 && $this->isValidUser() && $this->isValidPropertyType($type))
        {
            $this->load->model($type."model", "itemmodel");
            // Verify permissions
            if (!$this->itemmodel->hasPermissions($id, $this->rest->user_id))
            {
                // We return with errors
                $this->output(array("item" => $id, "parameters" => $this->put()), HTTP_CODE_UNAUTHORIZED, false, "Change unauthorized due to permissions.");
                return false;
            }

            $this->load->library("lazycometproperties", array("table" => $type));
            $property = $this->lazycometproperties->get($property);
            // Verify that we managed to receive an item
            if ($property != null)
            {
                // We output the property
                $this->output($property, HTTP_CODE_OK);
                return true;
            }
        }

        // Respond with an error
        $this->output(array("item" => (isset($id) ? $id : '-1'), "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "Invalid id.");
        return false;
    }

    /**
     * Gets a specific property
     * @param $type The property type
     * @param $by The type to which to get the id's by (item or user)
     * @param $id The id of the item
     */
    function all_get($type, $by, $id)
    {
        if (isset($id) && $id !== false && is_numeric($id) && $this->isValidUser() &&
            is_string($by) && ($by == 'user' || $by == 'item') &&
            is_string($type) && strlen($type) > 0 && $this->isValidPropertyType($type))
        {
            $this->load->model($type."model", "itemmodel");
            // Verify permissions
            if (!$this->itemmodel->hasPermissions($id, $this->rest->user_id))
            {
                // We return with errors
                $this->output(array($by => $id, "parameters" => $this->put()), HTTP_CODE_UNAUTHORIZED, false, "Change unauthorized due to permissions.");
                return false;
            }

            $this->load->library("lazycometproperties", array("table" => $type));
            $properties = null;
            if ($by == 'item')
                $properties = $this->lazycometproperties->getByItem($id);
            else if ($by == 'user')
                $properties = $this->lazycometproperties->getByCreatedBy($id);

            // Verify that we managed to receive an item
            if ($properties != null)
            {
                // We output the properties
                $this->output($properties, HTTP_CODE_OK);
                return true;
            }

            // Respond with an error
            $this->output(array($by => $id, "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "No items found.");
            return false;
        }

        // Respond with an error
        $this->output(array($by => (isset($id) ? $id : '-1'), "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "Invalid id.");
        return false;
    }

    /**
     * Adds a property to a item
     * @param $type The property type
     * @param $id The id of the item
     */
    function index_post($type, $id)
    {
        if (isset($id) && $id !== false && is_numeric($id) && is_string($type) && strlen($type) > 0 && $this->isValidUser() && $this->isValidPropertyType($type))
        {
            $this->load->model($type."model", "itemmodel");
            // Verify permissions
            if (!$this->itemmodel->hasPermissions($id, $this->rest->user_id))
            {
                // We return with errors
                $this->output(array("item" => $id, "parameters" => $this->put()), HTTP_CODE_UNAUTHORIZED, false, "Change unauthorized due to permissions.");
                return false;
            }
            // Make sure it exists
            if (!$this->itemmodel->doesExist($id))
            {
                // We return with errors
                $this->output(array("item" => $id, "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "No item found.");
                return false;
            }

            // Retrieve values
            $key = $this->post('key');
            $value = $this->post('value');

            // Verify that the values are set
            if (($key !== false && $key != "") &&
                ($value !== false && $value != ""))
            {
                $this->load->library("lazycometproperties", array("table" => $type));
                $property = $this->lazycometproperties->add(
                    $this->rest->user_id,
                    $id,
                    $key,
                    $value
                );
                // Verify success
                if ($property != -1)
                {
                    // We output the id of the newly created $property
                    $this->output(array(
                        "item" => $id,
                        "property" => $property
                    ), HTTP_CODE_OK);
                    return true;
                }
                else
                {
                    $this->output(array("item" => $id, "parameters" => $this->put()), HTTP_CODE_SERVERERROR, false, "Unable to create property.");
                    return false;
                }
            }

            // Respond with an error
            $this->output(array("item" => $id, "parameters" => $this->put()), HTTP_CODE_FORBIDDEN, false, "Incomplete property data.");
            return false;
        }

        // Respond with an error
        $this->output(array("item" => (isset($id) ? $id : '-1'), "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "Invalid id.");
        return false;
    }

    /**
     * Updates a property on a item
     * @param $type The property type
     * @param $id The id of the item
     * @param $property The id of the property
     */
    function index_put($type, $id, $property)
    {
        if (isset($id) && $id !== false && is_numeric($id)  && is_string($type) && strlen($type) > 0 &&
            isset($property) && $property !== false && is_numeric($property) && $this->isValidUser() && $this->isValidPropertyType($type))
        {
            $this->load->model($type."model", "itemmodel");
            // Verify permissions
            if (!$this->itemmodel->hasPermissions($id, $this->rest->user_id))
            {
                // We return with errors
                $this->output(array("item" => $id, "property" => $property, "parameters" => $this->put()), HTTP_CODE_UNAUTHORIZED, false, "Change unauthorized due to permissions.");
                return false;
            }
            // Make sure it exists
            if (!$this->itemmodel->doesExist($id))
            {
                // We return with errors
                $this->output(array("item" => $id, "property" => $property, "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "No item found.");
                return false;
            }
            $this->load->library("lazycometproperties", array("table" => $type));
            if (!$this->lazycometproperties->doesExist($id, $property))
            {
                // We return with errors
                $this->output(array("item" => $id, "property" => $property, "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "No property found.");
                return false;
            }

            // Retrieve values
            $key = $this->put('key');
            $value = $this->put('value');

            // Verify that the values are set
            if (($key !== false && $key != "") &&
                ($value !== false && $value != ""))
            {
                $property = $this->lazycometproperties->update(
                    $property,
                    $key,
                    $value
                );
                // Verify success
                if ($property)
                {
                    // We output the id of the newly created property
                    $this->output(array(
                        "item" => $id,
                        "property" => $property
                    ), HTTP_CODE_OK);
                    return true;
                }
                else
                {
                    $this->output(array(
                        "item" => (isset($id) ? $id : '-1'),
                        "property" => (isset($property) ? $property : '-1'),
                        "parameters" => $this->put()
                    ), HTTP_CODE_SERVERERROR, false, "Unable to create property.");
                    return false;
                }
            }

            // Respond with an error
            $this->output(array(
                "item" => (isset($id) ? $id : '-1'),
                "property" => (isset($property) ? $property : '-1'),
                "parameters" => $this->put()
            ), HTTP_CODE_FORBIDDEN, false, "Incomplete property data.");
            return false;
        }

        // Respond with an error
        $this->output(array(
            "item" => (isset($id) ? $id : '-1'),
            "property" => (isset($property) ? $property : '-1'),
            "parameters" => $this->put()
        ), HTTP_CODE_NOTFOUND, false, "Invalid id.");
        return false;
    }

    /**
     * Deletes a property from the item
     * @param $type The property type
     * @param $id The id of the item
     * @param property The id of the property to delete
     */
    function index_delete($type, $id, $property)
    {
        $data = array();
        if (isset($id) && $id !== false && is_numeric($id) && is_string($type) && strlen($type) > 0 && $this->isValidUser() && $this->isValidPropertyType($type))
        {
            $this->load->library("lazycometproperties", array("table" => $type));
            $this->load->model($type."model", "itemmodel");
            // Verify permissions
            if (!$this->itemmodel->hasPermissions($id, $this->rest->user_id))
            {
                // We return with errors
                $this->output(array("item" => $id, "property" => $property, "parameters" => $this->put()), HTTP_CODE_UNAUTHORIZED, false, "Change unauthorized due to permissions.");
                return false;
            }
            if (!$this->lazycometproperties->doesExist($id, $property))
            {
                // We return with errors
                $this->output(array("item" => $id, "property" => $property, "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "No property found.");
                return false;
            }

            if ($this->lazycometproperties->delete($property))
            {
                $this->output(array("item" => $id, "property" => $property), HTTP_CODE_OK);
                return true;
            }

            // We return with errors
            $this->output(array("item" => $id, "property" => $property), HTTP_CODE_UNAUTHORIZED, false, "Change unauthorized due to permissions.");
            return false;
        }

        // Respond with an error
        $this->output(array(
            "item" => (isset($id) ? $id : '-1'),
            "property" => (isset($property) ? $property : '-1'),
            "parameters" => $this->put()
        ), HTTP_CODE_NOTFOUND, false, "Invalid id.");
        return false;
    }
}