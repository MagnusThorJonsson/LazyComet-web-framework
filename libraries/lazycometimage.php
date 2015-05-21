<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Description of MixmeImage
 *
 * @author Maggi
 */
class LazyCometImage
{
    private $CI = null;
    private $data = null;
    private $error = '';
    
    function __construct()
    {
            
            $this->CI =& get_instance();
            $this->CI->load->library('upload');
            $this->CI->load->library('image_lib');
    }

    /**
     * Upload one image
     *
     * This method requires the name of the form input file element.
     *
     *
     * @param STRING $field
     * @return BOOL
     */
    public function uploadImage( $field )
    {
        //log_message('DEBUG', "Starting file upload");
        // Check permissins on tmp folder
        if(!is_readable($this->CI->config->item('image_temp')) || !is_writable($this->CI->config->item('image_temp')) )
        {
            $this->setError("Folder [" . $this->CI->config->item('image_temp') . "] not read/writable");
            log_message('ERROR', "Folder [" . $this->CI->config->item('image_temp') . "] not read/writable");
            return false;
        }
        
        // Upload
        if (!$this->_doUpload($field, $this->CI->config->item('image_temp')))
        {
            return false;
        }
        
        // Copy original
        if (!copy($this->CI->config->item('image_temp') . $this->data['file_name'], $this->CI->config->item('image_path') . $this->data['file_name']))
        {
            log_message('ERROR', "Unable to copy file [" . $this->data['file_name'] . "] from temp folder to main image folder");
            return false;
        }
/*
        // Copy original and resize
        if (!$this->_uploadCopyAndResize())
        {
            $this->setError("We messed up during upload. Please try again.");
            return false;
        }
*/
        
        // Process cropped files
        if (!$this->_uploadCrop())
        {
        	$this->setError('Could not crop original image. Please try again.');
        	log_message("ERROR", $this->error);
            return false;
        }
        // Resize
        foreach($this->CI->config->item('image_sizes') as $size)
        {
        	// Set path and resize
        	$resizedFilePath = $this->CI->config->item('image_path') . $size . '/' . $this->data['file_name'];
        	
        	$this->resize($this->CI->config->item('image_path') . "crop_" . $this->data['file_name'], $resizedFilePath, $size, $size);
        }

        // Delete uploaded file
        if (!$this->_deleteImage($this->CI->config->item('image_temp') . $this->data['file_name']))
        {
            $this->setError("Couldn't delete [{$file}]");
        }
         

        return true;
    }

    /**
     * Helper for upload, uploads an image to the given path
     *
     * @param STRING $field
     * @param STRING $path
     * @return BOOL
     */
    private function _doUpload($field, $path)
    {
        $config = array(
            'upload_path' => $path,
            'allowed_types' => $this->CI->config->item('image_ext'),
            'max_size' => 2000, // KB
            'encrypt_name' => true // Create a random name for the photo.
        );

        // Initialize
        $this->CI->upload->initialize($config);

        //	try to upload, exiting and logging if error
        if (!$this->CI->upload->do_upload($field))
        {
            
            $this->setError($this->CI->upload->error_msg[0]);
            log_message('ERROR', $this->CI->upload->error_msg[0]);
            
            return false;
        }
        
        //	Make upload data available to the class
        $this->data = $this->CI->upload->data();

        return true;
    }

    /**
     * Deletes an image
     *
     * @param STRING $file
     * @return BOOL
     */
    private function _deleteImage($file)
    {
        if( @unlink($file) )
        {
            log_message('DEBUG', "Garbage collect [{$file}]");
            return true;
        }
        else
        {
            log_message('DEBUG', "Can't garbage collect [{$file}]");
            return false;
        }
    }

    /**
     * Helper for upload. Resizes and copies original
     *
     * @return BOOL
     */
    private function _uploadCopyAndResize()
    {
        // Copy original
        if (!copy($this->CI->config->item('image_temp') . $this->data['file_name'], $this->CI->config->item('image_path') . $this->data['file_name']))
        {
            log_message('ERROR', "Unable to copy file [" . $this->data['file_name'] . "] from temp folder to main image folder");
            return false;
        }

        // Resize
        foreach($this->CI->config->item('image_sizes') as $size)
        {
            // Set path and resize
            $resizedFilePath = $this->CI->config->item('image_path') . $size . '/' . $this->data['file_name'];
            $this->resize($this->data['full_path'], $resizedFilePath, $size, $size);
        }

        return true;
    }

    /**
     * Helper for upload. Crops images into a square
     *
     * @return BOOL
     */
    private function _uploadCrop()
    {
        // Start by cropping the original
        $croppedOriginalImage = $this->CI->config->item('image_path') . 'crop_' . $this->data['file_name'];
        if(!$this->CI->mixmeimage->cropToSquare($this->data['full_path'], $croppedOriginalImage))
        {
            log_message('ERROR', "Error on image cropping.");
            return false;
        }

        return true;
    }

    /**
     * Resizes an image
     *
     * @param STRING $originalImage Full path to the file to resize
     * @param STRING $resizedImage  Full path to resized file
     * @param INT $width
     * @param INT $height
     * @return bool
     */
    public function resize($originalImage, $resizedImage, $width, $height)
    {
        $config = array(
            'image_library' => $this->CI->config->item('image_lib'),
            'width' => $width,
            'height' => $height,
            'maintain_ratio'=> true,
            'source_image' => $originalImage,
            'new_image' => $resizedImage
        );

        log_message('DEBUG', "Resizing image [{$originalImage}] to [{$resizedImage}] {$width}x{$height}");

        if ( !$this->CI->image_lib->initialize($config) )
        {
            $this->setError($this->CI->image_lib->error_msg[0]);
            log_message('error', $this->CI->image_lib->error_msg[0]);
            return false;
        }

        if ( !$this->CI->image_lib->resize() )
        {
            $this->setError($this->CI->image_lib->error_msg[0]);
            log_message('error', $this->CI->image_lib->error_msg[0]);
            return false;
        }
        return true;
    }

    /**
     * cropToSquare takes an image and croppes it so that
     * the width equals the height. Whatever dimension is bigger gets cropped.
     *
     * @param STRING $original full path on server to the image to crop.
     * @param STRING $cropped full path to where to place cropped version.
     * @return BOOL
     */
    public function cropToSquare($original, $cropped)
    {
    	$config = array(
    	            'image_library' => $this->CI->config->item('image_lib'),
    	            'maintain_ratio'=> false,
    	            'source_image' => $original,
    	            'new_image' => $cropped
    	);
    	
        if(!is_file($original))
        {
            return false;
        }

        # Get height and width of image.
        $imageProperties = $this->CI->image_lib->get_image_properties($original, true);

        # Case 1: X == Y
        if($imageProperties['height'] == $imageProperties['width'])
        {
            return copy($original, $cropped);
        }

        # Case 2. X > Y
        if($imageProperties['height'] < $imageProperties['width'])
        {
        	
        	$config['width'] = $imageProperties['height'];
        	$config['height'] = $imageProperties['height'];
        	$config['x_axis'] = (($imageProperties['width'] / 2) - ($config['width'] / 2));
        	
        	
            //return $this->cropByX($original, $cropped);
        }
        else # Case 3. Y > X
        {
        	
        	$config['height'] = $imageProperties['width'];
        	$config['width'] = $imageProperties['width'];
       	    $config['y_axis'] = (($imageProperties['height'] / 2) - ($config['height'] / 2));
//            return $this->cropByY($original, $cropped);
        }
        
        $this->CI->image_lib->initialize($config);
        if (!$this->CI->image_lib->crop()) {
        	$this->error = $this->CI->image_lib->display_errors();
        	return false;
        }
        
        $this->CI->image_lib->clear();
        return true;
    }

    /**
     * cropByX croppes an image on the Xaxis/widht so that it will be
     * equal to its height.
     *
     * @param STRING $original image to crop
     * @param STRING $cropped cropped image
     * @return BOOL
     *
     */
    private function cropByX($original, $cropped)
    {
        //$this->load->library('image_lib');
        $this->CI->image_lib->clear();
        $config['image_library'] = $this->CI->config->item('image_lib');

        # Make sure that the X axis is bigger that the Y axis
        $imageProperties = $this->CI->image_lib->get_image_properties($original, true);
        if($imageProperties['width'] <= $imageProperties['height'])
        {
            return false;
        }
        $config['source_image'] = $original;
        $config['new_image'] = $cropped;
        $config['x_axis'] =  floor(($imageProperties['width'] - $imageProperties['height'])/2);
        $config['y_axis'] = 0;
        $this->CI->image_lib->initialize($config);
        if (!$this->CI->image_lib->crop())
        {
            return false;
        }

        # Now we rotate the image by 180 deg and cut from the other end.
        $config['source_image'] = $config['new_image'];
        $config['rotation_angle'] = 180;

        if($config['x_axis'] < (($imageProperties['width'] - $imageProperties['height']) /2) ) # if there is a remainder
        {
            $config['x_axis'] = $config['x_axis'] + 1;

        }
        $this->CI->image_lib->initialize($config);
        if(!$this->CI->image_lib->rotate())
        {
            return false;
        }
        if (!$this->CI->image_lib->crop())
        {
            return false;
        }

        #Rotate back
        $this->CI->image_lib->initialize($config);
        if(!$this->CI->image_lib->rotate() )
        {
            return false;
        }
        $this->CI->image_lib->clear();
        
        return true;
    }

    /**
     * cropByY croppes an image on the Yaxis/height so that it will be
     * equal to its width.
     *
     * @param STRING $original image to crop
     * @param STRING $cropped cropped image
     * @return BOOL true on success, else false.
     *
     */
    private function cropByY($original, $cropped)
    {
        $this->CI->load->library('image_lib');
        $this->CI->image_lib->clear();
        $config['image_library'] = $this->CI->config->item('image_lib');
        $imageproperties = $this->CI->image_lib->get_image_properties($original, true);

        # Make sure we can use this function.
        if($imageproperties['height'] <= $imageproperties['width'])
        {
            return false;
        }

        # Rotate so that X axis becomes Y axis and we can use cropByX
        # TODO: replace me with a version that doesn't depend on cropByX as
        # it brings extra overhead.
        $config['source_image'] = $original;
        $config['new_image'] = $cropped;
        $config['rotation_angle'] = 90;
        $this->CI->image_lib->initialize($config);
        if(!$this->CI->image_lib->rotate())
        {
            return false;
        }

        if(!$this->cropByX($config['new_image'], $cropped))
        {
            return false;
        }

        # Rotate back
        $config['source_image'] = $config['new_image'];
        $config['rotation_angle'] = 270;
        $this->CI->image_lib->initialize($config);
        if(!$this->CI->image_lib->rotate())
        {
            return false;
        }
        $this->CI->image_lib->clear();
        return true;
    }

    /**
     * Set error string
     * 
     * @param STRING $error
     */
    public function setError($error)
    {
        switch ($error)
        {
            case "upload_invalid_filetype":
                $error = "The file you are trying to upload is not an image file.  Please try another file.";
                break;

            case "upload_no_file_selected":
                $error = "The image your are trying to upload is corrupted.  Please try another file.";
                break;

            case "upload_invalid_filesize":
                $error = "The file you are trying to upload is larger than the maximum size (2MB).  Please try another file.";
                break;
        }

        $this->error = $error;
    }

    /**
     * Returns any error messages
     *
     * @return STRING
     */
    public function getError()
    {
        return $this->error;
    }
 
    /**
     * Get upload data
     *
     * <ul>
     * 	<li>file_name</li>
     *  <li>file_type</li>
     *  <li>file_path</li>
     *  <li>full_path</li>
     *  <li>raw_name</li>
     *  <li>orig_name</li>
     *  <li>file_ext</li>
     *  <li>file_size</li>
     *  <li>is_image</li>
     *  <li>image_width</li>
     *  <li>image_height</li>
     *  <li>image_type</li>
     *  <li>image_size_str</li>
     * </ul>
     * @return Array
     */
    public function getData(){
        return $this->data;
    }    
    
}
?>
