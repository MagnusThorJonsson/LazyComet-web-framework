<?php defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH.'/libraries/REST_Controller.php';

/**
 * LazyComet REST controller
 *
 * Wraps the Rest Server controller
 *
 * @category LazyComet - REST core
 * @package Libraries
 * @author Magnus <m@lazycomet.com>
 *
 */
abstract class LazyCometRestController extends REST_Controller
{
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Response wrapper.
     * Wraps the response into a JSON success object
     * @param array $data The data to display
     * @param string $http_code The header type (see HTTP_CODE* constants)
     * @param boolean $success Flags whether the REST call was successful
     * @param string $message The error message
     * @return void
     */
    public function output($data, $http_code, $success = true, $message = '')
    {
        $data = array(
            "success"   => $success,
            "response"  => $data
        );

        if ($success === false)
        {
            $data['error'] = array(
                'message' => $message
            );
        }

        $this->response($data, $http_code);
    }

    /**
     * Checks to see if the user generating the command is valid
     * @return bool
     */
    public function isValidUser()
    {
        return (isset($this->rest->user_id) && $this->rest->user_id != null && is_numeric($this->rest->user_id));
    }

    /**
     * Override for API key detection, adds a HMAC verification layer
     * @return bool True if authenticity was established
     */
    protected function _detect_api_key()
    {
        // Get the api key name variable set in the config file
        $hmacKeyHeaderName = config_item('rest_hmac_key_name');
        // Work out the name of the SERVER entry based on config
        $hmacKeyServerName = 'HTTP_'.strtoupper(str_replace('-', '_', $hmacKeyHeaderName));

        // Check if key is actually in the header
        $keyRow = parent::_detect_api_key();
        if ($keyRow !== false && ($hmac = (isset($this->_args[$hmacKeyHeaderName]) ? $this->_args[$hmacKeyHeaderName] : $this->input->server($hmacKeyServerName))))
        {
            // TODO: finish the hmac authentication
            return true;
        }

        return $keyRow;
    }
}
