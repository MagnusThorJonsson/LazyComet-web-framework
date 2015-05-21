<?php

/**
 * Simple cURL wrapper
 *
 * @author Maggi
 */
class LazyCometCurl
{
    private $curlHandler = null;

    public function __construct($params = null)
    {
        #$this->CI =& get_instance();

        $this->curlHandler = curl_init();
        curl_setopt($this->curlHandler, CURLOPT_HEADER, false);
        curl_setopt($this->curlHandler, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->curlHandler, CURLOPT_MAXREDIRS, 2);
        curl_setopt($this->curlHandler, CURLOPT_CONNECTTIMEOUT, 5);

        if ($params != null && isset($params["useragent"]) && $params["useragent"] != "")
            curl_setopt($this->curlHandler, CURLOPT_USERAGENT, $params['useragent']);

        if ($params != null && isset($params["encoding"]) && $params["encoding"] != "")
            curl_setopt($this->curlHandler, CURLOPT_ENCODING, $params["encoding"]);
    }

    public function __destruct()
    {
        curl_close($this->curlHandler);
    }

    /**
     * Post via cURL
     * @param STRING $url
     * @param ARRAY $parameters
     * @param BOOL/HTML $return
     * @return object
     */
    public function post($url, $parameters, $return = true)
    {
        $data = null;
        if ($url != "" && is_array($parameters))
        {
            $params = "";
            foreach ($parameters as $key => $param)
                $params .= urlencode($key) . "=" . urlencode($param) . "&";
            $params = substr($params, 0, -1);

            curl_setopt($this->curlHandler, CURLOPT_URL, $url);
            curl_setopt($this->curlHandler, CURLOPT_RETURNTRANSFER, $return);
            curl_setopt($this->curlHandler, CURLOPT_POST, true);
            curl_setopt($this->curlHandler, CURLOPT_POSTFIELDS, $params);

            $data = curl_exec($this->curlHandler);
        }

        return $data;
    }

    /**
     * Get via cURL
     * @param STRING $url
     * @param ARRAY $parameters
     * @param BOOL/HTML $return
     * @return object
     */
    public function get($url, $parameters, $return = true)
    {
        $data = null;
        if ($url != "" && is_array($parameters))
        {
            # Add get parameters to url
            $url .= "?";
            foreach ($parameters as $key => $param)
                $url .= urlencode($key) . "=" . urlencode($param) . "&";
            $url = substr($url, 0, -1);

            curl_setopt($this->curlHandler, CURLOPT_URL, $url);
            curl_setopt($this->curlHandler, CURLOPT_RETURNTRANSFER, $return);
            curl_setopt($this->curlHandler, CURLOPT_POST, false);

            $data = curl_exec($this->curlHandler);
        }

        return $data;
    }

    /**
     *
     * Fetches a URL and saves it as a file on disk
     * @param string $remoteFile
     * @param string $localFile
     * @return boolean
     */
    function fetchFile($remoteFile, $localFile)
    {
        $this->curlHandler = curl_init($remoteFile);
        curl_setopt($this->curlHandler, CURLOPT_HEADER, 0);
        curl_setopt($this->curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curlHandler, CURLOPT_BINARYTRANSFER, 1);
        $rawdata = curl_exec($this->curlHandler);
        curl_close($this->curlHandler);

        if (file_exists($localFile))
            unlink($localFile);

        $fp = fopen($localFile,'x');
        fwrite($fp, $rawdata);
        fclose($fp);

        return true;
    }
}