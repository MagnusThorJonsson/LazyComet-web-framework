<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Foursquare API.
 * Mostly just the venue lookups
 *
 * @author Maggi
 */
class LazyCometFoursquare
{
    const LFCQ_CLIENT_VERSION = "20130131";
    const LCFQ_API_URL = "https://api.foursquare.com/v2/";
    const LCFQ_USER_AGENT = "Let's get known - Venue Search v0.2 (http://www.letsgetknown.com/)";
    const LFCQ_RESULT_LIMIT = 5;
    const LFCQ_CLIENT_ID = "I2GKM1424MLVUK2LMZDEGCBM4TX54YQJU2HVEN0TGHNFF2LF";
    const LFCQ_CLIENT_SECRET = "3POVBRILINYYXLXHDHVL3CBYQLLMHDJKGKCG35OKCC1NDNV1";
    const LFCQ_ENABLE_CACHE = true;

    private $CI = null;
    private $error = '';
    private $httpCode = 0;

    function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * Finds a venue through the Foursquare API
     * @param string $venue The venue query string
     * @param string $city The city the venue is in
     * @param string $country The country the venue is in (full name or ISO code)
     * @param string $state Optionally you can input the state
     * @param int $limit The maximum number of results, defaults to the defined internal constant
     * @param boolean $doCache Optional Flags whether to cache results to the db
     * @return stdClass A JSON object decoded into a stdClass, null on no response
     */
    public function findVenue($venue, $city, $country, $state = null, $limit = self::LFCQ_RESULT_LIMIT, $doCache = self::LFCQ_ENABLE_CACHE)
    {
        if (is_string($venue) && strlen($venue) > 0 &&
            is_string($city) && strlen($city) > 0 &&
            is_string($country) && strlen($country) > 0)
        {
            // Make sure $state is null if the input is iffy
            if ($state != null && (!is_string($state) || (is_string($state) && strlen($state) == 0)))
                $state = null;

            $this->CI->load->library("lazycometcurl", array("useragent" => self::LCFQ_USER_AGENT));
            $response = $this->CI->lazycometcurl->get(
                self::LCFQ_API_URL . "venues/search",
                array(
                    "v"             => self::LFCQ_CLIENT_VERSION,
                    "limit"         => $limit,
                    "client_id"     => self::LFCQ_CLIENT_ID,
                    "client_secret" => self::LFCQ_CLIENT_SECRET,
                    "near"          => htmlspecialchars($city) . ", " . (htmlspecialchars($state) != null ? htmlspecialchars($state).', ' : '') . htmlspecialchars($country),
                    "query"         => htmlspecialchars($venue)
                )
            );
            ;
            if ($response != null)
            {
                $result = json_decode($response);
                if (isset($result) && $result != null && isset($result->meta) &&
                    isset($result->meta->code) && $result->meta->code == 200 &&
                    isset($result->response))
                {
                    return $this->renderVenueListData($result->response, $doCache);
                }
                else
                {
                    $this->error = "Response returned an error when searching for venues '{$venue}' near '{$city}, " . (htmlspecialchars($state) != null ? htmlspecialchars($state).', ' : '') . " {$country}'\n";
                    // Set http code if available
                    if (isset($result->meta->code))
                        $this->httpCode = $result->meta->code;
                    // Set error
                    if (isset($result->meta->errorType))
                        $this->error .= "ERROR: ({$result->meta->errorType})" . (isset($result->meta->errorDetail) ? ": " . $result->meta->errorDetail : '') . "\n";
                }
            }
            else
                $this->error = "Response was empty when searching for venues '{$venue}' near '{$city}, " . (htmlspecialchars($state) != null ? htmlspecialchars($state).', ' : '') . " {$country}'\n";
        }
        else
            $this->error = "Invalid input data";

        return null;
    }


    /**
     * Gets a specific venue from the Foursquare API
     * @param string $venueId The Foursquare id of the venue
     * @param int $limit The maximum number of results, defaults to the defined internal constant
     * @param boolean $doCache Optional Flags whether to cache results to the db
     * @return stdClass A JSON object decoded into a stdClass, nulll on no response
     */
    public function getVenue($venueId, $limit = self::LFCQ_RESULT_LIMIT, $doCache = self::LFCQ_ENABLE_CACHE)
    {
        if (is_string($venueId) && strlen($venueId) > 0)
        {
            $this->CI->load->library("lazycometcurl", array("useragent" => self::LCFQ_USER_AGENT));
            $response = $this->CI->lazycometcurl->get(
                self::LCFQ_API_URL . "venues/{$venueId}",
                array(
                    "v"             => self::LFCQ_CLIENT_VERSION,
                    "limit"         => $limit,
                    "client_id"     => self::LFCQ_CLIENT_ID,
                    "client_secret" => self::LFCQ_CLIENT_SECRET
                )

            );
            if ($response != null)
            {
                $result = json_decode($response);
                if (isset($result) && $result != null && isset($result->meta) &&
                    isset($result->meta->code) && $result->meta->code == 200 &&
                    isset($result->response))
                {
                    if (is_bool($doCache) && $doCache)
                    {
                        $this->CI->load->library("lazycometvenue");
                        $venueData = $this->renderVenueData($result->response->venue);

                        $result = $this->CI->lazycometvenue->add(
                            "foursquare",
                            $venueData->id,
                            $venueData->name,
                            $venueData->location->city,
                            $venueData->location->country,
                            $venueData->location->state,
                            $venueData->location->address,
                            null,
                            $venueData->location->postalCode,
                            null,
                            $venueData->contact->phone,
                            null,
                            $venueData->description,
                            $venueData->website,
                            $venueData->contact->twitter,
                            $venueData->contact->facebook,
                            $venueData->location->latitude,
                            $venueData->location->longitude
                        );
                        // Log if caching failed
                        if ($result === false)
                            log_message("ERROR", "Couldn't save the venue to db: " . print_r($venueData, true));

                        return $venueData;
                    }
                    else
                        return $this->renderVenueData($result->response->venue);
                }
                else
                {
                    $this->error = "Response returned an error when looking up venue '{$venueId}'";
                    // Set http code if available
                    if (isset($result->meta->code))
                        $this->httpCode = $result->meta->code;
                    // Set error
                    if (isset($result->meta->errorType))
                        $this->error .= "ERROR: ({$result->meta->errorType})" . (isset($result->meta->errorDetail) ? ": " . $result->meta->errorDetail : '') . "\n";
                }
            }
            else
                $this->error = "Response was empty when looking up venue '{$venueId}'";
        }
        else
            $this->error = "Invalid input data";

        return null;
    }

    /**
     * Private helper that renders the required data from the venue search response
     * @param object $response The JSON decoded venue search response
     * @param boolean $doCache Flags whether to cache results to the db
     * @return array|null An array of stdClass objects containing the data from the venue search response
     */
    private function renderVenueListData($response, $doCache)
    {
        if (isset($response->venues) && is_array($response->venues) && count($response->venues) > 0)
        {
            $this->CI->load->library("lazycometvenue");

            $data = array();
            foreach ($response->venues as $venue)
            {
                if (($item = $this->renderVenueData($venue)) != null)
                {
                    if ($doCache)
                    {
                        $result = $this->CI->lazycometvenue->add(
                            "foursquare",
                            $item->id,
                            $item->name,
                            $item->location->city,
                            $item->location->country,
                            $item->location->state,
                            $item->location->address,
                            null,
                            $item->location->postalCode,
                            null,
                            $item->contact->phone,
                            null,
                            $item->description,
                            $item->website,
                            $item->contact->twitter,
                            $item->contact->facebook,
                            $item->location->latitude,
                            $item->location->longitude
                        );
                        // Log if caching failed
                        if ($result === false)
                            log_message("ERROR", "Couldn't save the venue to db: " . print_r($item, true));
                    }

                    array_push($data, $item);
                }
            }

            return $data;
        }

        return null;
    }

    /**
     * Private helper that renders the required data from a venue response
     * @param object $venue The JSON decoded venue response
     * @return stdClass An stdClass object containing data from the venue response
     */
    private function renderVenueData($venue)
    {
        // TODO: Maybe the way we render the values to stdClass is overkill but hey, whatever, why not make it standard
        $data = new stdClass();
        // Base values
        $data->id           = (isset($venue->id)            ? $venue->id            : null);
        $data->name         = (isset($venue->name)          ? $venue->name          : null);
        $data->verified     = (isset($venue->verified)      ? $venue->verified      : false);
        $data->website      = (isset($venue->url)           ? $venue->url           : null);
        $data->description  = (isset($venue->description)   ? $venue->description   : null);

        // Contact
        $data->contact = new stdClass();
        $data->contact->phone       = (isset($venue->contact->formattedPhone)   ? $venue->contact->formattedPhone   : (isset($venue->contact->phone) ? $venue->contact->phone : null));
        $data->contact->twitter     = (isset($venue->contact->twitter)          ? $venue->contact->twitter          : null);
        $data->contact->facebook    = (isset($venue->contact->facebook)         ? $venue->contact->facebook         : null);
        // Location
        $data->location = new stdClass();
        $data->location->address    = (isset($venue->location->address)     ? $venue->location->address     : null);
        $data->location->postalCode = (isset($venue->location->postalCode)  ? $venue->location->postalCode  : null);
        $data->location->city       = (isset($venue->location->city)        ? $venue->location->city        : null);
        $data->location->state      = (isset($venue->location->state)       ? $venue->location->state       : null);
        $data->location->country    = (isset($venue->location->cc)          ? $venue->location->cc          : (isset($venue->location->country) ? $venue->location->country : null));
        $data->location->latitude   = (isset($venue->location->lat)         ? $venue->location->lat         : null);
        $data->location->longitude  = (isset($venue->location->lng)         ? $venue->location->lng         : null);

        // Photos
        $data->photos = (isset($venue->photos) ? $this->renderPhotoData($venue->photos) : null);

        return $data;
    }


    /**
     * A private helper that renders the 'venues' group of the photos portion from the response to an stdClass
     * @param $photos The JSON decoded photos portion of the response
     * @return array|null The rendered photos data
     */
    private function renderPhotoData($photos)
    {
        if (isset($photos) && isset($photos->groups) && is_array($photos->groups) && count($photos->groups) > 0)
        {
            foreach ($photos->groups as $group)
            {
                if (isset($group->type) && $group->type == "venue" &&
                    isset($group->items) && is_array($group->items) && count($group->items) > 0)
                {
                    $data = array();
                    foreach ($group->items as $item)
                    {
                        $photoData = new stdClass();
                        $photoData->id = (isset($item->id) ? $item->id : null);
                        $photoData->width = (isset($item->width) ? $item->width : null);
                        $photoData->height = (isset($item->height) ? $item->height : null);
                        $photoData->path = ((isset($item->prefix) && isset($item->suffix)) ? $item->prefix . $photoData->width . "x" . $photoData->height . $item->suffix : null);
                        array_push($data, $photoData);
                    }

                    return $data;
                }
            }
        }

        return null;
    }


    /**
     * Returns current error message
     * @return string The error string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Gets the current http code
     * @return int The http code
     */
    public function getHttpCode()
    {
        return $this->httpCode;
    }
}