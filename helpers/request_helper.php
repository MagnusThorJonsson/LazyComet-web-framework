<?php
/**
 * Some helper functions for HTTP/1.1 requests.
 *
 *
 * @category mixme
 * @package Helpers
 * @author einar
 *
 */

/**
 * Check if the request is GET
 * @author einar
 * @return boolean
 */
function isGet(){
    return ( strtolower($_SERVER['REQUEST_METHOD']) == 'get' )
        ? true
        : false ;
}

/**
 * Check if the request is POST
 * @author einar
 * @return boolean
 */
function isPost(){
	return ( strtolower($_SERVER['REQUEST_METHOD']) == 'post' )
	? true
	: false ;
}

/**
 * Check if the request is XHR/AJAX request
 * by examining the $_SERVER['HTTP_X_REQUESTED_WITH'] variable.
 * @author einar
 * @return boolean
 */
function isXhr(){
	return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'))
		? true
		: false ;
}


