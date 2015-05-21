<?php
/**
 * Description of MixmeController
 *
 * @author Maggi
 */
class LazycometController extends CI_Controller
{
    private $_cssFiles = array();
    private $_jsFiles = array();
    private $_data = array();

    # Modules
    private $_modules = array();

    function __construct()
    {
        parent::__construct();

        $this->load->library("uri");
        $this->_data["title"] = "";
        
        log_message("DEBUG", "LazycometController loaded");
    }


/*******************************************************************************
 * Render functions
 */

    /**
     * Renders the whole template
     *
     * @param STRING $template
     */
    protected function render($template)
    {
        # Load default modules
        if ($this->config->item('modules_default') !== false)
    	{
	        foreach ($this->config->item('modules_default') as $module)
	            $this->modules($module);
    	}
        # Parse JS & CSS files
        $this->_renderResources();

        # Load main template
        $mainTemplate = $this->load->view($template . '.phtml', $this->_data, true);

        # Render the modules, passing $mainTemplate by reference
        $this->_renderModules($mainTemplate);
        
        /*
        // Count page render
        $userId = null;
        if ($this->mixmeusers->getUserId() != -1)
        	$userId = $this->mixmeusers->getUserId();
        	
        $this->mixmeviewtracker->countView(current_url(), $userId);
        unset($userId);
		*/
                
        # Render the final outcome to the browser
        $this->load->view('render.phtml', array('renderedPage' => $mainTemplate));
    }

    /**
     * Helper for the render function. Renders CSS and JS files both default and passed.
     */
    private function _renderResources()
    {
        # Initialize
        $this->_data['js'] = '';
        $this->_data['css'] = '';

        # First render the defaults
        foreach($this->config->item('js_defaults') as $js)
        {
            # If this isn't being overwritten
            if (!in_array($js . '.js', $this->_jsFiles))
            {
				if (substr($js, 0, 7) == "http://")
					$this->_data['js'] .= "<script type=\"text/javascript\" src=\"" . $js . "?v={$this->config->item('cache_version')}\"></script>\n";
				else
            		$this->_data['js'] .= "<script type=\"text/javascript\" src=\"" . base_url() . $this->config->item('js_path') . $js . ".js?v={$this->config->item('cache_version')}\"></script>\n";
            }
        }
        foreach($this->config->item('css_defaults') as $css)
        {
            # Same as with the JS
            if (!in_array($css . '.css', $this->_cssFiles))
                $this->_data['css'] .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . base_url() . $this->config->item('css_path') . $css . ".css?v={$this->config->item('cache_version')}\" />\n";
        }

        # Second render the passed in scripts
        foreach($this->_jsFiles as $js)
        {
            if (substr($js, 0, 4) == "http")
                $this->_data['js'] .= "<script type=\"text/javascript\" src=\"" . $js . "?v={$this->config->item('cache_version')}\"></script>\n";
            else
                $this->_data['js'] .= "<script type=\"text/javascript\" src=\"" . base_url() . $this->config->item('js_path') . $js . "?v={$this->config->item('cache_version')}\"></script>\n";
        }
        foreach($this->_cssFiles as $css)
        {
            $this->_data['css'] .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . base_url() . $this->config->item('css_path') . $css . "?v={$this->config->item('cache_version')}\" />\n";
        }
    }


    /**
     * Renders the modules.
     * Note that this function requires that the template param is sent by reference.
     *
     * @param STRING &$template
     */
    private function _renderModules(&$template)
    {
        foreach ($this->_modules AS $module)
        {
            # If there is string content...
            if (isset($module->content))
            {
                $template = $this->_parseDiv(
                    $template,
                    $module->div,
                    $module->content
                );
            }
            # ...else if the div is set...
            else if (isset($module->div))
            {
                $template = $this->_parseDiv(
                    $template,
                    $module->div,
                    $this->_parseModule($module)
                );
            }
            # ...finally try using the name
            else
            {
                $template = $this->_parseDiv(
                    $template,
                    $module->name,
                    $this->_parseModule($module)
                );
            }
        }
    }



/*******************************************************************************
 * Properties
 */

    /**
     * Adds css files paths to the main template header
     *
     * @param ARRAY/STRING $css Full path to css file location
     *
     * @return self for method chaining
     * @TODO Add handling for duplicate css path entries
     */
    protected function css($css)
    {
        if (is_array($css))
        {
            array_merge($this->_cssFiles, $css);
        }
        else if (is_string($css))
        {
            array_push($this->_cssFiles, $css);
        }

        return $this;
    }

    /**
     * Adds js files paths to the main template header
     *
     * @param ARRAY/STRING $css Full path to js file location
     *
     * @return self for method chaining
     * @TODO Add handling for duplicate js path entries
     */
    protected function js($js)
    {
        if (is_array($js))
        {
            array_merge($this->_jsFiles, $js);
        }
        else if (is_string($js))
        { 
            array_push($this->_jsFiles, $js);
        }

        return $this;
    }


    /**
     * Renders a div within a main template. Pass in the controller, template name
     * and the data that the template requires.
     *
     * @param STRING $id
     * @param STRING $content
     * @return self for method chaining
     */
    public function div($id, $content)
    {
        $module = new stdClass();
        $module->div = $id;
        $module->content = $content;
        array_push($this->_modules, $module);
        
        return $this;
    }

    /**
     * Adds modules for rendering preparation
     *
     * @param ARRAY/STRING $css Full path to js file location
     *
     * @return self for method chaining
     * @TODO Add handling for duplicate js path entries
     */
    protected function modules($module)
    {
        # If we have a bunch of modules to render
        if (is_array($module))
        {
            foreach ($module as $item)
            {
                # Make sure both that the item is an stdClass and has a parameter called name
                if (is_object($item) && isset($item->name))
                {
                    array_push($this->_modules, $item);
                }
            }
        }
        # else make sure that $module is an object and has a parameter called name
        elseif (is_object($module) && isset($module->name))
        {
            array_push($this->_modules, $module);
        }

        return $this;
    }


    /**
     * Populates the data for rendering.
     * If $key is an array $value is ignored.
     *
     * @param ARRAY $key
     * @param ANY $value
     * @return self for method chaining
     */
    protected function data($key = array(), $value = null)
    {
        if (is_array($key))
        {
            foreach ($key as $k => $v)
            {
                $this->_data[$k] = $v;
            }
        }
        elseif (is_string($key))
        {
            $this->_data[$key] = $value;
        }
        else
        {
            log_message('ERROR', "key sent to MixmeController->data() was neither an array nor a string.");
        }

        return $this;
    }
    
    
    /**
     * 
     * Sets the title of a page
     * @param string $title
     * @return self for method chaining
     */
    protected function title($title)
    {
    	if (is_array($this->_data))
	    	$this->_data["title"] = $title;
    	
    	return $this;
    }


/*******************************************************************************
 * Parsers
 */

    /**
     * Parses given content into the DIV which the ID points to
     *
     * @param STRING $template
     * @param STRING $divId
     * @param STRING $content
     * @return STRING
     */
    protected function _parseDiv($template, $divId, $content)
    {
    	// We add a str_replace that adds backslashes before $ because else the regexp would fail
        return preg_replace(
            '!<div([^>]*)id=\"' . $divId. '\"([^>]*)>(.*)</div>!si',
            '<div$1id="'. $divId .'"$2>'. str_replace("$", "\\\$", $content) .'$3</div>',
            $template
        );
    }

    /**
     * Parses a sub template
     *
     * @param STRING $controller
     * @param STRING $template
     * @param ARRAY $data
     * @return STRING
     */
    protected function _parseTemplate($controller, $template, $data = null)
    {
        $content = $this->load->view($controller . '/' . $template . '.phtml', $data, true);

        return $content;
    }

    /**
     * _renderModules helper, parses the module into HTML
     *
     * @param stdClass $module
     * @return STRING
     */
    private function _parseModule($module)
    {
        # Prepare module name and load module
        $moduleName = strtolower($this->config->item("subclass_prefix") . "Module{$module->name}");
        $this->load->library($moduleName);

        # Render the required module and pass in the parameters
        return $this->$moduleName->render((isset($module->params) ? $module->params : null));
    }

    /**
     * Generates a token and saves it to session.
     *
     * @return STRING
     */
    protected function generateFormToken()
    {
        // TODO: Make token generation more elegant
        $token = sha1(microtime());
        $this->session->set_flashdata('token', $token);

        return $token;
    }
}