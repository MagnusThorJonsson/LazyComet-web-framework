<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User library for ImOnTheRoad.com
 * Contains nearly everything you need to manipulate the user accounts
 *
 * @author Maggi
 */
class LazyCometUsers
{
    const SESSION_NAME = 'iotragain-users';
    const COOKIE_NAME = 'iotragain-folks';
    const COOKIE_SALT = 'This is something completely different!';
    const SITE_DOMAIN = '.imontheroad.com';
    
    private $CI = null;
    private $error = '';
    private $lastError = "";

    function __construct()
    {
        $this->CI =& get_instance();
        
 		# If not already logged in
        if ($this->getUserId() == -1 && !$this->_rememberMe())
        {       	
        	# Check via facebook (HACK FOR CRONS)
        	if ($this->CI->input->is_cli_request())
				require '/var/www/imontheroad.com/htdocs/libs/facebook.php';
        	else
				require $_SERVER["DOCUMENT_ROOT"] . '/libs/facebook.php';			

            $facebook = new Facebook(array(
				'appId'  => FB_APP_ID,
				'secret' => FB_APP_SECRET,
				'oauth' => true
			));
        	
	        // See if there is a user from a cookie
			$user = $facebook->getUser();
			if ($user) 
				$this->loginWithFb($user);
        }
    }


    /**
     * Gets the last error reported
     * @return string The error message
     */
    public function getError()
    {
        $returnVal = $this->error;
        $this->lastError = $this->error;
        $this->error = "";
        return $returnVal;
    }
    
    
/*******************************************************************************
 * USER CREATION AND VALIDATION
 */

    /**
     * Creates a new user
     *
     * @param STRING $username
     * @param STRING $password
     * @param STRING $email
     * @param STRING $gender optional
     * @param STRING $dob optional
     * @param STRING $country optional
     * @param STRING $city optional
     * @param STRING $avatar optional
     * @param STRING $about optional
     * @param STRING $website optional
     * @return BOOL
     */
    public function createUser($username, $password, $email, $gender = 'notset', $dob = null, $country = null, $city = null, $avatar = null, $about = null, $website = null)
    {
        # Prepare fields that should be checked for duplicates in DB
        $userDataCheck = array
        (
            'username' => $this->cleanUsername($username),
            'email' => $email
        );
        if (!$this->checkUserData($userDataCheck))
        {
            $about = strip_tags($about, $this->CI->config->item("html_allowed_tags"));
            # Encode the password and generate a safety token
            $password = $this->_encodePassword($password);
            $token = $this->_generateToken($username, $email);

            # Create user and return results
            return $this->CI->db->query("
                INSERT INTO
                    users (username, password, email, gender, dob, country, city, avatar, token, url, description)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array($username, $password, $email, $gender, ($dob==null?'0000-00-00':$dob),$country, $city, $avatar, $token,$website,$about));
        }
        else
            $this->error = "Username or email already exists.";

        return false;
    }

    /**
     * Cleans the username of unwanted characters
     * @param $username The original string
     * @return string The clean version of the username
     */
     public function cleanUsername($username)
    {
        $username = strip_tags($username);
        return preg_replace("/[^a-zA-Z0-9+-_]/", "", $username);
    }

    /**
     * Checks whether data passed in array already exists in db
     *
     * @param ARRAY $userData
     * @return BOOL
     */
    public function checkUserData($userData)
    {
        $check = false;
        $whereClause = '';
        foreach ($userData as $field => $data)
        {
            $userCount = $this->CI->db->query("SELECT count(*) AS userCount FROM users WHERE {$field} = '{$data}'")->row()->userCount;
            if ($userCount > 0)
            {
                $this->error = "{$field} is already in use.";
                $check = true;
                break;
            }
        }

        return $check;
    }

    /**
     * Login user
     *
     * @param STRING $login
     * @param STRING $password
     * @param BOOL $remember
     * @return BOOL
     */
    public function login($login, $password, $remember = false)
    {
        # Make sure password is correct and then login.
        if ($this->verifyPassword($login, $password))
        {
            # Save user id to session
            $this->CI->session->set_userdata('userId', $this->getUserIdByEmailOrUsername($login));
	        $email = $this->getUserData($this->getUserId())->email;
	        if (in_array($email, $this->CI->config->item("admin_emails")))
	        	$this->CI->session->set_userdata('isAdmin', true);
	        
	        # Remember the user if applicable
	        if ($remember)
	        	$this->_createCookie($email, $password);
	        
            return true;
        }

        return false;
    }
    
    /**
     * 
     * Logs a user out
     */
    public function logout()
    {
    	# Fetch cookie and destroy remember me cookie
    	$cookieKey = md5(LazyCometUsers::COOKIE_NAME . LazyCometUsers::COOKIE_SALT) . LazyCometUsers::COOKIE_NAME;
		if ((isset($_COOKIE[$cookieKey]) && $_COOKIE[$cookieKey] != null))
		{    	
    		setcookie($cookieKey, '', time() - 3600, '/');
    		unset($_COOKIE[$cookieKey]);
		}
		
    	$this->CI->session->unset_userdata(array("userId" => "", "isAdmin" => ""));
    }


    /**
     * 
     * Login with Facebook Connect
     * @param string $fb_uid
     */
    public function loginWithFb($fb_uid)
    {
    	$result = $this->CI->db->query("
    		SELECT
    			id
    		FROM
    			users
    		WHERE
    			fb_uid = ?", array($fb_uid));
    	
    	if ($result->num_rows() > 0)
    	{
			# Save user id to session
			$this->CI->session->set_userdata('userId', $result->row()->id);
    		$email = $this->getUserData($this->getUserId())->email;
    		if (in_array($email, $this->CI->config->item("admin_emails")))
				$this->CI->session->set_userdata('isAdmin', true);    		
    		
			return true;
    	}
    	
    	return false;
    }
    
    /**
     * 
     * Get User Id from session
     * @return int
     */
    public function getUserId()
    {
    	if ($this->CI->session->userdata("userId") !== false)
    		return $this->CI->session->userdata("userId", true);
    		    		
    	return -1;
    }
    
    /**
     * 
     * Get User avatar for header (only for logged in user)
     * @return int
     */
    public function getUserSessionAvatar()
    {
    		$result = $this->CI->db->query("
            SELECT
                users.avatar
            FROM
                users
            WHERE
                users.id = ?", array($this->CI->session->userdata("userId", true)));

        if ($result->num_rows() > 0)
        {
            return $result->row();
        }

        return null;

    }    
    
    /**
     * 
     * Checks whether user is an admin
     */
    public function isAdmin()
    {
    	return $this->CI->session->userdata("isAdmin", true);
    }
    
   
    /**
     * 
     * Get user id from username
     * @param unknown_type $username
     */
    public function getUserName($userId)
    {
    	$result = $this->CI->db->query("
    		SELECT	
    			username
    		FROM
    			users
    		WHERE
    			id = ?", array($userId));
    	
    	if ($result->num_rows() > 0)
    	{
    		return $result->row()->username;
    	}
    	
    	return null;
    }

    /**
     * Changes the username for a user
     * @param $id The id of the user
     * @param $username The new username
     * @return bool True on success
     */
    public function changeUserName($id, $username)
    {
        // Verify validity of the user
        if (!is_numeric($id) || $this->getUserName($id) == null)
        {
            $this->error = "User id malformed or user does not exist.";
            return false;
        }

        if (!$this->checkUserData(array("username" => $username)))
        {
            return $this->CI->db->query("
                UPDATE
                    users
                SET
                    username = ?
                WHERE
                    id = ?", array($username, $id));
        }
        else
            $this->error = "Username already exists.";

        return false;
    }
    
    /**
     * Retrieves user data based on given id
     *
     * @param INT $userId
     * @return stdClass
     */
    public function getUserData($userId)
    {
        $result = $this->CI->db->query("
            SELECT
                users.id,
                users.username,
                users.email,
                users.gender,
                users.dob,
                users.avatar,
                users.description,
                users.url,
                users.country,
                users.city,
                users.accepted,
                users.banned,
                users.active,
                users.token,
                users.createdAt
            FROM
                users
            WHERE
                users.id = ?", array($userId, $userId));

        if ($result->num_rows() > 0)
        {
            return $result->row();
        }

        return null;
    }

    /**
     * Updates the users data based on the function parameters
     *
     * @param INT $userId
     * @param STRING $field
     * @param STRING $data
     * @return BOOL
     */
    public function editUserData($userId, $field, $data)
    {
        // Make sure that no primary fields are being changed
        $field = strtolower($field);
        if ($field == "id" ||
            $field == "username" ||
            $field == "password" ||
            $field == "accepted" ||
            $field == "banned" ||
            $field == "active" ||
            $field == "token" ||
            $field == "fb_uid" ||
            $field == "createdAt")
            return false;

        return $this->CI->db->query("UPDATE users SET {$field} = ? WHERE id = ?", array($data, $userId));
    }


    /**
     * Gets the user id by email or username
     *
     * @param STRING $login
     * @return INT
     */
    public function getUserIdByEmailOrUsername($login)
    {
        $result = $this->CI->db->query("SELECT id FROM users WHERE email = ? OR username = ?", array($login, $login));

        if ($result->num_rows() > 0)
        {
            return $result->row()->id;
        }

        return -1;
    }


    /**
     * Gets the user id by email
     *
     * @param STRING $email
     * @return INT
     */
    public function getUserIdByEmail($email)
    {
        $result = $this->CI->db->query("SELECT id FROM users WHERE email = ?", array($email));
		#log_message("DEBUG", "QUERY: SELECT id FROM users WHERE email = '" . $email . "'");
        if ($result->num_rows() > 0)
        {
            return $result->row()->id;
        }

        return -1;
    }


    /**
     * Gets the user id by username
     *
     * @param STRING $username
     * @return INT
     */
    public function getUserIdByUsername($username)
    {
        $result = $this->CI->db->query("SELECT id FROM users WHERE username = ?", array($username));

        if ($result->num_rows() > 0)
        {
            return $result->row()->id;
        }

        return -1;
    }



    /**
     * Gets all users
     *
     * @return array
     */
    public function getUsers()
    {
        $result = $this->CI->db->query("
            SELECT
                id,
                username,
                email,
                gender,
                dob,
                avatar,
                description,
                url,
                country,
                city,
                accepted,
                banned,
                active,
                token,
                createdAt
            FROM
                users");

        if ($result->num_rows() > 0)
        {
            return $result->result();
        }

        return -1;
    }
    
    
    /**
     * 
     * Change users password
     * @param int $userId
     * @param string $password
     */
    public function changePassword($userId, $password)
    {
		return $this->CI->db->query("
    		UPDATE
    			users
    		SET
    			password = ?
    		WHERE
    			id = ?", array($this->_encodePassword($password), $userId));
    }
    
    
    /**
     * 
     * Checks is a token exists in the database
     * @param string $token
     * @return boolean / int
     */
    public function doesTokenExist($token)
    {
    	$result = $this->CI->db->query("
    		SELECT
    			id
    		FROM
    			users
    		WHERE
    			token = ?", array($token));
    	
    	if ($result->num_rows() > 0)
    		return $result->row()->id;
    	
    	return false;
    }


    /**
     *
     * Checks is a user exists in the database
     * @param int $user
     * @return boolean
     */
    public function doesUserExist($user)
    {
        $result = $this->CI->db->query("
    		SELECT
    			id
    		FROM
    			users
    		WHERE
    			id = ?", array($user));

        if ($result->num_rows() > 0)
            return true;

        return false;
    }

    
/*******************************************************************************
 * REMEMBER ME FUNCTIONALITY
 *
 */

    /**
     * 
     * Creates a cookie for remember me
     * @param string $email
     * @param string $password
     * @return boolean
     */
    private function _createCookie($email, $password)
    {
    	# Prepare cookie
    	$cookie = md5($this->_encodePassword($password) . $email . LazyCometUsers::COOKIE_SALT);
    	$prefix = md5(LazyCometUsers::COOKIE_NAME . LazyCometUsers::COOKIE_SALT);
    	# Save
    	setcookie(
	    	$prefix . LazyCometUsers::COOKIE_NAME,
	    	$cookie,
	    	time() + 1209600,
	    	'/'
    	);
    	/* WAS HAVING PROBLEM WITH USING THE BUILT IN SYSTEM
    	return $this->CI->input->set_cookie(
    		$prefix . MixmeUsers::COOKIE_NAME,	# Name
    		$cookie,					# Value
    		time() + 1209600, 			# Expiry: 60 * 60 * 24 * 14 or exactly 2 weeks more or less
    		MixmeUsers::SITE_DOMAIN,	# Domain
    		'/',						# Path
    		'',							# Prefix
    		true						# Secure cookie
    	);
    	*/
    }
    
    /**
     * 
     * Remember me helper
     * @return boolean
     */
    private function _rememberMe()
    {
    	# Fetch cookie
    	$cookieKey = md5(LazyCometUsers::COOKIE_NAME . LazyCometUsers::COOKIE_SALT) . LazyCometUsers::COOKIE_NAME;
    	$cookie = (isset($_COOKIE[$cookieKey]) && $_COOKIE[$cookieKey] != null)?$_COOKIE[$cookieKey]:false;
    	if ($cookie !== false)
    	{
    		$result = $this->CI->db->query("
    			SELECT
    				id,
    				email,
    				password
    			FROM
    				users
    			WHERE
    				md5(concat(password, email, ?)) = ?", array(LazyCometUsers::COOKIE_SALT, $cookie));
    		#die(var_dump($result) . "<---" . $cookie);
    		if ($result->num_rows() > 0)
    		{
    			# Save user id to session
    			$this->CI->session->set_userdata('userId', $result->row()->id);
    			$email = $result->row()->email;
    			if (in_array($email, $this->CI->config->item("admin_emails")))
    				$this->CI->session->set_userdata('isAdmin', true);
    			 
    			return true;
    		}
    	}
    	
    	return false;
    }

    
/*******************************************************************************
 * USER EXTERNAL PROVIDERS
 * 
 */

    public function doesTwitterUserExist($userId)
    {
        $result = $this->CI->db->query("
            SELECT
                *
            FROM
                users_external_providers     
            WHERE
                users_external_providers.oauth_provider = 'twitter' AND 
                users_external_providers.oauth_uid = ?", array($userId));

        if ($result->num_rows() > 0)
        {
            return $result->row();
        }

        return null;
    }  
    
    
    public function updateTwitterToken($oauthToken, $oauthTokenSecret, $oauthId)
    {
        return $this->CI->db->query("UPDATE users_external_providers SET oauth_token = ?, oauth_secret = ? WHERE oauth_provider = 'twitter' AND oauth_uid = ?", array($oauthToken, $oauthTokenSecret, $oauthId));
    }

    public function ConnectTwitterAccount($oauthId, $userId)
    {
        return $this->CI->db->query("UPDATE users_external_providers SET userId = ? WHERE oauth_provider = 'twitter' AND oauth_uid = ?", array($userId, $oauthId));
    }
    
    
    
    public function createTwitterUser($oauth_uid, $oauth_token, $oauth_secret)
    {
        # Create user and return results
        return $this->CI->db->query("
            INSERT INTO
                users_external_providers (oauth_provider, oauth_uid, oauth_token, oauth_secret)
            VALUES
                ('twitter', ?, ?, ?)", array($oauth_uid, $oauth_token, $oauth_secret));

        return false;
    }
    

    public function getTwitterUser($oauth_token, $oauth_secret,$oauth_uid)
    {
        $result = $this->CI->db->query("
            SELECT
                users.id,
                users.username
            FROM
                users LEFT JOIN users_external_providers ON users.id = users_external_providers.userId
            WHERE
                users_external_providers.oauth_uid = ? AND users_external_providers.oauth_token = ? AND users_external_providers.oauth_secret = ?", array($oauth_uid,$oauth_token,$oauth_secret));

        if ($result->num_rows() > 0)
        {
            return $result->row();
        }

        return null;
    }  

/*******************************************************************************
 * USER MANIPULATION FUNCTIONS
 */

    /**
     * Bans user
     *
     * @param INT $userId
     * @return BOOL
     */
    public function banUser($userId)
    {
        return $this->CI->db->query("UPDATE users SET banned = 1 WHERE id = ?", array($userId));
    }

    /**
     * Unbans user
     *
     * @param INT $userId
     * @return BOOL
     */
    public function unbanUser($userId)
    {
        return $this->CI->db->query("UPDATE users SET banned = 0 WHERE id = ?", array($userId));
    }

    /**
     * Accepts user account
     *
     * @param INT $userId
     * @return BOOL
     */
    public function acceptUser($userId)
    {
    	log_message("DEBUG", "USER: " . $userId);
        return $this->CI->db->query("UPDATE users SET accepted = 1 WHERE id = ?", array($userId));
    }

    /**
     * Removes accept from a users account
     *
     * @param INT $userId
     * @return BOOL
     */
    public function unacceptUser($userId)
    {
        return $this->CI->db->query("UPDATE users SET accepted = 0 WHERE id = ?", array($userId));
    }

    /**
     * Sets user account to active
     *
     * @param INT $userId
     * @return BOOL
     */
    public function activateUser($userId)
    {
        return $this->CI->db->query("UPDATE users SET active = 1 WHERE id = ?", array($userId));
    }

    /**
     * Sets user account to deactive
     *
     * @param INT $userId
     * @return BOOL
     */
    public function deactivateUser($userId)
    {
        return $this->CI->db->query("UPDATE users SET active = 0 WHERE id = ?", array($userId));
    }
    

/*******************************************************************************
 * PRIVATE HELPER FUNCTIONS
 */

    /**
     * Compares the password given to the one that matches the login credential
     *
     * @param STRING $login
     * @param STRING $password
     * @return BOOL
     */
    public function verifyPassword($login, $password)
    {
        # Retrieve password from DB
        $dbPassResults = $this->CI->db->query("SELECT password FROM users WHERE active = 1 AND banned = 0 AND accepted = 1 AND (username = ? OR email = ?)", array($login, $login));
        if ($dbPassResults->num_rows() > 0)
        {
            # Prepare password and compare to the one from DB
            $dbPass = $dbPassResults->row()->password;
            $token = substr($dbPass, 32, 8);
            $encodedPass = md5($password . md5($password . $token)) . $token;
            if ($dbPass == $encodedPass)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Takes an unencoded string and encodes it in a silly HMAC
     *
     * @param STRING $password
     * @return STRING
     */
    private function _encodePassword($password)
    {
        // TODO: Generate token more elegantly
        $token = substr(md5($password . md5($password)), 2, 8);
        $encodedPass = md5($password . md5($password . $token)) . $token;

        return $encodedPass;
    }

    /**
     * Temporary token generator
     *
     * @param STRING $a
     * @param STRING $b
     * @return STRING
     */
    private function _generateToken($a, $b)
    {
        return md5($a . md5($b));
    }
}