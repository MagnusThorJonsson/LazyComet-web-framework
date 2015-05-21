<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Venue REST controller
 *
 * Handles all things pertaining to venues
 *
 * @category LazyComet - REST core
 * @package Controllers
 * @author magnus <m@lazycomet.com>
 *
 */
class Venue extends LazyCometRestController
{
    function __construct()
    {
        parent::__construct();
    }

    function index_get()
    {
        $this->load->library("APIs/lazycometfoursquare");
        die(var_dump($this->lazycometfoursquare->getVenue("48f0fcdcf964a52041521fe3")));
        //die(var_dump($this->lazycometfoursquare->findVenue("Le Poisson Rouge", "New York", "US")));
    }
}