<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * User REST controller
 *
 * Handles all things pertaining to users
 *
 * @category LazyComet - REST core
 * @package Controllers
 * @author magnus <m@lazycomet.com>
 *
 */
class User extends LazyCometRestController
{
    function __construct()
    {
        parent::__construct();
    }


    /**
     * Gets a user specified by id
     * @param $id The id of the user to fetch
     */
    function index_get($id)
    {
        $data = array();
        if (isset($id) && $id !== false && is_numeric($id))
        {
            $data = $this->lazycometusers->getUserData($id);
            if ($data != null)
            {
                $this->output($data, HTTP_CODE_OK);
                return true;
            }
        }

        // Respond with an error
        $this->output(array("id" => (isset($id) ? $id : '-1')), HTTP_CODE_NOTFOUND, false, "No user was found.");
    }


    /**
     * Create a new user
     */
    function index_post()
    {
        // Retrieve values
        $username = $this->post('username');
        $password = $this->post('password');
        $email = $this->post('email');

        $country = $this->post('country');
        $country = ($country === false ? null : $country);
        $city = $this->post('city');
        $city = ($city === false ? null : $city);

        $avatar = $this->post('avatar');
        $avatar = ($avatar === false ? null : $avatar);
        $about = $this->post('about');
        $about = ($about === false ? null : $about);
        $website = $this->post('website');
        $website = ($website === false ? null : $website);

        // Verify that the core values are set
        if (($username !== false && $username != "") &&
            ($password !== false && $password != "") &&
            ($email !== false && $email != ""))
        {
            // Create user
            $success = $this->lazycometusers->createUser(
                $username,
                $password,
                $email,
                'notset',
                null,
                $country,
                $city,
                $avatar,
                $about,
                $website
            );
            // Verify success
            if ($success === true)
            {
                // We output the user id of the newly created user
                $this->output(array(
                    "user" => $this->lazycometusers->getUserIdByUsername($username)
                ), HTTP_CODE_OK);
                return true;
            }
            else
            {
                $this->output($this->put(), HTTP_CODE_SERVERERROR, false, $this->lazycometusers->getError());
                return false;
            }
        }

        // Respond with an error
        $this->output(array("parameters" => $this->put()), HTTP_CODE_FORBIDDEN, false, "Incomplete user data.");
        return false;
    }

    /**
     * Updates a specified user
     * @param $id The id of the user to update
     */
    function index_put($id)
    {
        if (isset($id) && $id !== false && is_numeric($id))
        {
            $this->load->helper("validation");
            $errors = "";
            $success = true;

            // Non critical values
            $gender = $this->put("gender");
            $dob = $this->put("dob");
            $description = $this->put("description");
            $url = $this->put("url");
            $country = $this->put("country");
            $city = $this->put("city");

            // Critical values
            $username = $this->put("username");
            $password = $this->put("password");
            $email = $this->put("email");

            $data = array();
            // If any of the non crucial fields are not empty we add them to the data object
            if ($gender !== false && $gender != "")
            {
                if (!($gender == "male" || $gender == "female" || $gender == "other" || $gender == "notset"))
                {
                    $errors .= "Inputted gender was not valid, change was not made.";
                    $success = false;
                }
                else
                    $data["gender"] = $gender;

            }
            if ($dob !== false && $dob != "")
            {
                if (!isValidDateTime($dob))
                {
                    $errors .= "Inputted date of birt was not valid, change was not made.\n";
                    $success = false;
                }
                else
                    $data["dob"] = $dob;
            }
            if ($description !== false && $description != "")
                $data->description = $description;
            if ($url !== false && $url != "")
            {
                if (!isValidUrlFormat($url))
                {
                    $errors .= "Inputted URL is not valid, change was not made.\n";
                    $success = false;
                }
                else
                    $data["url"] = $url;
            }
            // TODO: Implement GeoIP lookup for validations
            if ($country !== false && strlen($country) == 2)
                $data["country"] = $country;
            if ($city !== false && $city != "")
                $data["city"] = $city;

            // TODO: Make this into a single update query and handle errors on fail
            // Submit non critical changes
            foreach ($data as $field => $value)
                $this->lazycometusers->editUserData($id, $field, $value);


            // If any of the critical values are not empty we apply the changes
            $username = $this->lazycometusers->cleanUsername($username);
            if ($username !== false && $username != "")
            {
                if (!$this->lazycometusers->changeUserName($id, $username))
                {
                    $errors .= $this->lazycometusers->getError() . "\n";
                    $success = false;
                }
            }
            if ($email !== false && $email != "")
            {
                $this->load->helper("email");
                if (valid_email($email))
                {
                    if (!$this->lazycometusers->editUserData($id, "email", $email))
                    {
                        $errors .= "Couldn't change email.\n";
                        $success = false;
                    }
                }
                else
                {
                    $errors .= "Inputted email was invalid, change was not made.\n";
                    $success = false;
                }
            }
            if ($password !== false && $password != "")
            {
                if (!$this->lazycometusers->changePassword($id, $password))
                {
                    $errors .= "Couldn't change password.\n";
                    $success = false;
                }
            }

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
        $this->output(array("id" => (isset($id) ? $id : '-1'), "parameters" => $this->put()), HTTP_CODE_NOTFOUND, false, "Invalid user id.");
        return false;
    }

}