<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There area two reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router what URI segments to use if those provided
| in the URL cannot be matched to a valid route.
|
*/
$route['default_controller']    = "welcome";

# USER function routes
$route['api/user/(:num)']   = 'api/user/index/$1';

# BAND function routes
$route['api/band/(:num)']                       = 'api/band/index/$1';
$route['api/band/attach/user/(:num)/(:num)']    = 'api/band/attachUser/$1/$2';
$route['api/band/detach/user/(:num)/(:num)']    = 'api/band/detachUser/$1/$2';
$route['api/band/properties/(:num)']            = 'api/properties/index/bands/$1';
$route['api/band/properties/(:num)/(:num)']     = 'api/properties/index/bands/$1/$2';
$route['api/band/properties/all/(:any)/(:num)'] = 'api/properties/all/bands/$1/$2';

# TOUR function routes
$route['api/tour/(:num)']                       = 'api/tour/index/$1';
$route['api/tour/attach/event/(:num)/(:num)']   = 'api/tour/attachEvent/$1/$2';
$route['api/tour/detach/event/(:num)/(:num)']   = 'api/tour/detachEvent/$1/$2';
$route['api/tour/attach/user/(:num)/(:num)']    = 'api/tour/attachUser/$1/$2';
$route['api/tour/detach/user/(:num)/(:num)']    = 'api/tour/detachUser/$1/$2';
$route['api/tour/properties/(:num)']            = 'api/properties/index/tours/$1';
$route['api/tour/properties/(:num)/(:num)']     = 'api/properties/index/tours/$1/$2';
$route['api/tour/properties/all/(:any)/(:num)'] = 'api/properties/all/tours/$1/$2';

# VENUE function routes
$route['api/venue'] = 'api/venue/index';

# EVENT function routes
$route['api/event'] = 'api/event/index';


# HEADER OVERRIDES
$route['404_override']              = '';

/* End of file routes.php */
/* Location: ./application/config/routes.php */