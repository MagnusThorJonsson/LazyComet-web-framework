<?php

/**
 * Some helper functions for validation
 *
 * @category LazyComet
 * @package Helpers
 * @author Maggi
 *
 */

/**
 * Checks if string is in a valid MySQL datetime format
 * @param $dateTime The string to check
 * @return bool True if string conforms to the correct format
 */
function isValidDateTime($dateTime)
{
    if (preg_match("/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/", $dateTime, $matches)) {
        if (checkdate($matches[2], $matches[3], $matches[1])) {
            return true;
        }
    }

    return false;
}

/**
 * Validate URL format
 *
 * @access  public
 * @param   string
 * @return  string
 */
function isValidUrlFormat($str)
{
    $pattern = "|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i";

    if (!preg_match($pattern, $str))
    {
        return FALSE;
    }

    return TRUE;
}

/**
 * Validates that a URL is accessible. Also takes ports into consideration.
 * Note: If you see "php_network_getaddresses: getaddrinfo failed: nodename nor servname provided, or not known"
 *          then you are having DNS resolution issues and need to fix Apache
 *
 * @access  public
 * @param   string
 * @return  string
 */
function isValidUrl($url)
{
    $url_data = parse_url($url); // scheme, host, port, path, query
    if(!fsockopen($url_data['host'], isset($url_data['port']) ? $url_data['port'] : 80))
    {
        return FALSE;
    }

    return TRUE;
}