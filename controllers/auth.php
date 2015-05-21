<?php
/**
 * Auth controller.
 *
 * external authentication for users
 *
 * @category mixme
 * @package Controllers
 * @author einar <einar@gogoyoko.com>
 *
 */
class auth extends LazycometController
{
    function __construct()
    {
        parent::__construct();
        
        $this->title("mixme.fm - bring back the mixtape!");
    }


    public function index()
    {
        echo 'auth biatch';
    }

    
    public function twitter()
    {
        $this->load->library("mixmeauth");
    	$result = $this->mixmeauth->twitterAuth();
    }
    
    
    public function twitterLogin()
    {    
        $this->load->library("mixmeauth");
        parse_str($this->input->server('QUERY_STRING'), $stringArray);
    	$userInfo = $this->mixmeauth->twitterLogin( $stringArray['oauth_verifier'] ); 
        
        if(isset($userInfo->error)){  
            // Something's wrong, go back to square 1  
            header('Location: /auth/twitter'); 
        } else { 
            // Let's find the user by its ID  
            $twitterUser = $this->mixmeusers->doesTwitterUserExist( $userInfo->id );
            
            // Access token
            $accessToken = $this->session->userdata("access_token");
            
            // no user, let's add it to the database  
            if(empty($twitterUser))
            {                  
                
                //create an entry in the external provider table
                $this->mixmeusers->createTwitterUser($userInfo->id, $accessToken['oauth_token'], $accessToken['oauth_token_secret']);
                
                //DATA
                $data = array(
                    'oauth_userid' => $userInfo->id,
                    'user' => $userInfo
                );   
                
                
                //rendder view for user to complete signup        
                $this->css('page/signup.css')
                        ->js('page/auth/signup-twitter.js')
                        ->div('content', $this->_renderTwitterSignup($data, false, ""))
                        ->render('landingpage');
                
 
                
            } 
            else if( !empty($twitterUser) && $twitterUser->userId == null)
            {
                //mixme user creation failed during signup, we need to revisit
                //DATA
                $data = array(
                    'oauth_userid' => $userInfo->id,
                    'user' => $userInfo
                );   
                
                
                //rendder view for user to complete signup
                $this->css('page/signup.css')
                        ->js('page/auth/signup-twitter.js')
                        ->div('content', $this->_renderTwitterSignup($data, false, ""))
                        ->render('landingpage');
            }
            else {  
                // Update the tokens  
                $this->mixmeusers->updateTwitterToken( $accessToken['oauth_token'], $accessToken['oauth_token_secret'], $userInfo->id );
                //get user id based on secret, auoth token and  twitter id
                $mixmeUserId = $this->mixmeusers->getMixmeTwitterUser( $accessToken['oauth_token'], $accessToken['oauth_token_secret'], $userInfo->id );
                //LOGIN
                # Save user id to session
                $this->session->set_userdata('userId', $mixmeUserId->id);
                redirect(base_url(), 'location');

            }  
     

        } 

    }
    
    
    
    
    /**
     * Renders the Twitter Signup page
     *
     * @return STRING
     */

    /**
     * Renders the signup form
     * @return STRING
     */
    private function _renderTwitterSignup($data = null, $error = false, $message = "")
    {
        $data['token'] = $this->generateFormToken();
        
        if ($error && $message != "")
        	$data["error"] = $message;

        return $this->_parseTemplate('signup', 'twitter-signup', $data);
    }
    
    
    
    /**
     * Renders the Twitter Signup page
     *
     * @return STRING
     */

    /**
     * Renders the signup form
     * @return STRING
     */
    private function _renderTwitterConnect($data = null, $error = false, $message = "")
    {
        $data['token'] = $this->generateFormToken();
        
        if ($error && $message != "")
        	$data["error"] = $message;

        return $this->_parseTemplate('signup', 'twitter-connect', $data);
    }
    
    
    
    public function twitterSignup()
    {
        
        if (isPost())
        {
            $username = $this->input->post('username', true);
            $about = $this->input->post('about', true);
            $email = $this->input->post('email', true);
            $website = $this->input->post('website', true);
            $u_id = $this->input->post('u_id', true);
            $avatar = $this->input->post('t_avatar', true);
            
            
                //EMAIL ALREADY EXIST
                //  we'll need to 'connect' the user, otherwise we're facing some security issues
                if ($this->mixmeusers->getUserIdByEmail($email) != -1 )
                {
                    $data = array(
                        'oauth_userid' => $u_id
                    );

                    //rendder view for user to connect users
                    $this->css('page/signup.css')
                        ->js('page/auth/signup-connect.js')    
                        ->div('content', $this->_renderTwitterConnect($data, false, ""))
                        ->render('landingpage'); 
     
                    
                    
                }
                else if ($this->mixmeusers->checkUserData(array("username" => $username)) )
                { //USERNAME ALREADY EXISTS
                    //DATA
                    $userObject = new stdClass();
                    $userObject->screen_name = $username;
                    $userObject->url = $website;
                    $userObject->email = $email;
                    $userObject->description = $about;
                    $userObject->profile_image_url = $avatar;

                    $data = array(
                        'oauth_userid' => $u_id,
                        'user' => $userObject
                    );           
                    $this->css('page/signup.css')
                            ->js('page/auth/signup-twitter.js')
                            ->div('content', $this->_renderTwitterSignup($data, true, "Username already exists, please select another one."))
                            ->render('landingpage');

                    
                }
                else
                {
                    $tempPass = md5(microtime() . $u_id . "HACK");

                    $originalTwitterImage = $this->getTwitterOriginalImageURL($avatar);
                    $imageName = $this->_fetchUrlImage($originalTwitterImage);


                    # If we managed to create a user
                    if ($this->mixmeusers->createUser($username, $tempPass, $email, 'notset', null, null, null, $imageName, $about, $website))
                    {
                        $userId = $this->mixmeusers->getUserIdByEmail($email);
                            $this->mixmeusers->acceptUser($userId);
                                $this->mixmeusers->activateUser($userId);

                        # Hookup with twitter_oath_id
                        $this->mixmeusers->ConnectTwitterAccount($u_id, $userId);

                        //LOGIN
                        # Save user id to session
                        $this->session->set_userdata('userId', $userId);
                        redirect(base_url(), 'location');
                    }
                    else
                    {
                        $userObject = new stdClass();
                        $userObject->screen_name = $username;
                        $userObject->url = $website;
                        $userObject->email = $email;
                        $userObject->description = $about;
                        $userObject->profile_image_url = $avatar;

                        $data = array(
                            'oauth_userid' => $u_id,
                            'user' => $userObject
                        );           
                        $this->css('page/signup.css')
                                ->js('page/auth/signup-twitter.js')
                                ->div('content', $this->_renderTwitterSignup($data, true, "Something went terribly wrong :/ Plz try again"))
                                ->render('landingpage');
                    }

                }

            


        }
        else
        {
                // TODO: Make an error library
                redirect(base_url() . 'auth/twitter', 'location');
        }            
        
    }
  
    
    
    /**
     * This function is for users that already have an mixme account
     * and are conencting via twitter. We'll need to connect and verify that
     * everything is as it is.
     */
    public function twitterConnect()
    {
        

        
        if (isPost())
        {
            $username = $this->input->post('username', true);
            $password = $this->input->post('password', true);
            $u_id = $this->input->post('u_id', true);
            
            if ($this->mixmeusers->login($username, $password))
            {
                # Hookup with twitter_oath_id
                $this->mixmeusers->ConnectTwitterAccount($u_id, $this->mixmeusers->getUserIdByUsername($username));
                redirect(base_url().'user/', 'location');
            }
            else
            {
                $data = array(
                        'oauth_userid' => $u_id
                );
                
                $this->css('page/signup.css')
                        ->js('page/auth/signup-twitter.js')
                        ->div('content', $this->_renderTwitterConnect($data, true, "Username and/or Password was incorrect. Please try again"))
                        ->render('landingpage');
            }
        }     
            
          
        
    }
    
    
    
    /**
     * Get the URL to the full sized avatar
     *
     * @return string The URL to the image file
     */
    protected function getTwitterOriginalImageURL($profileImageUrl) {

        // get the regular sized avatar
        $url = $profileImageUrl;

        // save the extension for later
        $ext = strrchr($url, '.');

        //strip the "_normal' suffix and add back the extension
        return substr($url, 0, strrpos($url, "_")) . $ext;
    }
    
    
    private function _fetchUrlImage($imgUrl)
    {
    	# Check filetype
    	if (substr($imgUrl, -3) == "png")
    	{
    		$imageName = md5("twitter" . microtime()) . ".png";
    		$localFile = $this->config->item('image_path') . $imageName;
    		$image = imagecreatefrompng($imgUrl);
    		imagepng($image, $localFile);
    	}
    	else if (substr($imgUrl, -3) == "jpg" || substr($imgUrl, -4) == "jpeg")
    	{
    		$imageName = md5("twitter" . microtime()) . ".jpg";
    		$localFile = $this->config->item('image_path') . $imageName;
    		$image = imagecreatefromjpeg($imgUrl);
    		imagejpeg($image, $localFile);
    	}
    	else if (substr($imgUrl, -3) == "gif")
    	{
    		$imageName = md5("twitter" . microtime()) . ".gif";
    		$localFile = $this->config->item('image_path') . $imageName;
    		$image = imagecreatefromgif($imgUrl);
    		imagegif($image, $localFile);
    	}
    	else
	    	return false; # No filetype found
    	 
    	# Crop to square if fetching worked
    	$croppedImg = $this->config->item('image_path') . "crop_" . $imageName;
        //	load mixme image library class
        $this->load->library('mixmeimage');
    	if ($this->mixmeimage->cropToSquare($localFile, $croppedImg) !== false)
    	{
    		# Resize all dem mafakkas
    		foreach($this->config->item('image_sizes') as $size)
    		{
				$resizedFilePath = $this->config->item('image_path') . $size . '/' . $imageName;
				$this->mixmeimage->resize($croppedImg, $resizedFilePath, $size, $size);
    		}
		}
		
		return $imageName;
    }
    
    
    
    
    
    /**
     * TODO // FACEBOOK AUTH
     */
    
    
    public function facebook()
    {
        
    }
    
    public function facebookLogin()
    {
        
    }
    
    
}
