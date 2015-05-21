<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/
define('FOPEN_READ',							'rb');
define('FOPEN_READ_WRITE',						'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE',		'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',	'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE',					'ab');
define('FOPEN_READ_WRITE_CREATE',				'a+b');
define('FOPEN_WRITE_CREATE_STRICT',				'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT',		'x+b');

/*
|--------------------------------------------------------------------------
| Response codes
|--------------------------------------------------------------------------
|
| HTTP response codes
|
*/
// 200.
define("HTTP_CODE_OK", 200);
define("HTTP_CODE_CREATED", 201);
define("HTTP_CODE_ACCEPTED", 202);

// 400
define("HTTP_CODE_BADREQUEST",         400);
define("HTTP_CODE_UNAUTHORIZED",       401);
define("HTTP_CODE_FORBIDDEN",          403);
define("HTTP_CODE_NOTFOUND",           404);
define("HTTP_CODE_METHODNOTALLOWED",   405);

// 500
define("HTTP_CODE_SERVERERROR", 500);
define("HTTP_CODE_NOTIMPLEMENTED", 501);

/*
|--------------------------------------------------------------------------
| Authentication constants
|--------------------------------------------------------------------------
|
|
*/
define("FB_APP_ID", "163995103688870");
define("FB_APP_SECRET", "5f5f776a2feffbaa89847c4374db4bf4");

/*
|--------------------------------------------------------------------------
| Settings
|--------------------------------------------------------------------------
|
| Various settings
|
*/
define("DB_MAXROWS_BANDS", 100);
define("DB_MAXROWS_TOURS", 100);

/* End of file constants.php */
/* Location: ./application/config/constants.php */