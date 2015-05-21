<?php

/**
 * Check if the user is logged
 * @author maggi
 * @return boolean
 */
function isLogged()
{
    $CI =& get_instance();

    if ($CI->mixmeusers->getUserId() != -1)
    {
        return true;
    }

    return false;
}