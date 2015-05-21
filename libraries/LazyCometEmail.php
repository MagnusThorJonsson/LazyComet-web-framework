<?php
/**
 * Description of MixmeEmail
 *
 * @author Maggi
 */
class LazyCometEmail extends CI_Email
{
    # Override CI settings
    var	$mailtype		= "html";   // text/html  Defines email formatting
    var	$wordwrap		= FALSE;    // TRUE/FALSE  Turns word-wrap on/off
    var	$charset		= "utf-8";	// Default char set: iso-8859-1 or us-ascii
    // TODO: Remove when done testing
    
    var $protocol		= "smtp";
    var	$smtp_host		= "ssl://smtp.googlemail.com";		// SMTP Server.  Example: mail.earthlink.net
    var	$smtp_user		= "mixme@mixme.fm";		// SMTP Username
    var $smtp_timeout 	= 30;
    var	$smtp_pass		= "cr33d5uck5";		// SMTP Password
    var	$smtp_port		= "465";		// SMTP Port
    var $newline		= "\r\n";
	
    function __construct()
    {
        parent::__construct();
    }

    /**
	 * Send an email using the specified template.
     * This function requires that you pass into the data in an array form.
     *
     * @param STRING $type
     * @param STRING $template
     * @param STRING $to
     * @param STRING $subject
     * @param ARRAY $data
     * @param STRING $from optional, will use config variable if not set
     * @return <type>
     */
	function post($type, $template, $to, $subject, $data, $from = null, $fromName = null, $doTest = false)
	{
        # Make sure this email is getting sent somewhere
        if ($subject == "" || $to == "" || $to == null)
        {
            if ($subject == "")
                $this->_set_error_message('No subject was set.');
            else
                $this->_set_error_message('No email recipients were included.');

            return FALSE;
        }
        else
            $this->to($to);

        # Prepare template with data
        if (!$this->_renderTemplate($type, $template, $data))
        {
            $this->_set_error_message('There was an internal problem during preparation of the email.');
            return FALSE;
        }
        else if ($doTest == true)
        {
        	return $this->_body;
        }
        # Set the subject
        $this->subject(utf8_encode($subject));

        # Default to the config email if none is passed
        if ($from == null)
        {
            $CI =& get_instance();
            $this->from($CI->config->item('email_contact'), $CI->config->item('email_contact_name'));
        }
        else
            $this->from($from, $fromName);

        # Prepare message build
        if ($this->_prepareSend())
        {
        	
            # Build and send
            $this->_build_headers();
            $this->_build_message();
            if (!$this->_spool_email())
            {
            	return FALSE;
            }

            return TRUE;
        }

    	return FALSE;
	}


    /**
     * Private function helper for the post function.
     * Handles rendering of the email templates
     *
     * @param STRING $type
     * @param STRING $template
     * @param ARRAY $data
     * @return BOOL
     */
    private function _renderTemplate($type, $template, $data)
    {
        $CI =& get_instance();

        # Render and set the normal body text
        if (@file_exists(APPPATH."views/emails/{$type}/{$template}.phtml"))
        {
           $body = $CI->load->view("emails/{$type}/{$template}.phtml", $data, true);
           $this->message($body);
        }
        # No template found
        else
            return FALSE;

        # Render and set the alt body if applicable
        if (@file_exists(APPPATH."views/emails/{$type}/{$template}_alt.phtml"))
        {
           $altBody = $CI->load->view("emails/{$type}/{$template}_alt.phtml", $data, true);
           $this->set_alt_message($altBody);
        }

        return TRUE;
    }

    /**
     * Private helper for the post() function
     *
     * @return bool
     */
    private function _prepareSend()
    {
		if ($this->_replyto_flag == FALSE)
			$this->reply_to($this->_headers['From']);

		if ((!isset($this->_recipients) AND !isset($this->_headers['To'])) AND
			(!isset($this->_bcc_array) AND !isset($this->_headers['Bcc'])) AND
			(!isset($this->_headers['Cc'])))
		{
			$this->_set_error_message('email_no_recipients');
			return FALSE;
		}

        return TRUE;
    }
}