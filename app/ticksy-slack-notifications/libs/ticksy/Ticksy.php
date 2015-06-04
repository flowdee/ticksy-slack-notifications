<?php

class Ticksy {

    // API settings
    private $api_url = 'https://api.ticksy.com/v1';

    // Wrapper Setup
    protected $cache_dir = 'cache';
    public $cache_expires = 5;

    // User credentials
    private $domain;
    private $api_key;
    private $access;

    # Constructor
    public function Ticksy($domain, $api_key) {

        // Initialize
        $this->domain = $domain;
        $this->api_key = $api_key;

        // Verify
        $this->verify_credentials();
    }

    /**
     * Verify the api credentials and unlock set status
     */
    public function verify_credentials()
    {
        if ( ! isset($this->domain) ) exit('You have not set a domain yet.');
        if ( ! isset($this->api_key) ) exit('You have not set an api key yet.');

        $result = $this->call('responses-needed.json');

        if ( isset($result->error) ) return 'Username or API Key invalid.';

        $this->access = true;
    }

    /**
     * List of open tickets assigned to you
     */
    public function my_tickets()
    {
        return $this->call('my-tickets.json');
    }

    /**
     * Your responses needed
     */
    public function my_responses_needed()
    {
        return $this->call('my-responses-needed.json');
    }

    /**
     * List of all open tickets
     */
    public function open_tickets()
    {
        return $this->call('open-tickets.json');
    }

    /**
     * List of all closed tickets
     */
    public function closed_tickets()
    {
        return $this->call('closed-tickets.json');
    }

    /**
     * Comments from ticket
     */
    public function ticket_comments($id)
    {
        return $this->call('ticket-comments.json/' . $id . '/');
    }

    /**
     * All responses needed
     */
    public function responses_needed()
    {
        return $this->call('responses-needed.json');
    }

    /**
     * List of open tickets assigned to you
     */
    public function get_url($link, $id)
    {
        $links = array(
            'ticket' => 'ticket'
        );

        if ( isset($links[$link]) ) {
            return 'https://' . $this->domain . '.ticksy.com/' . $link . '/' . $id . '/';
        }

        return false;
    }

    /**
     * Verify the api credentials and unlock set status
     */
    protected function call($set)
    {
        if ( !$this->access ) {
            return false;
        }

        $url = "$this->api_url/$this->domain/$this->api_key/$set";

        $result = $this->fetch($url);

        if ( isset($result->error) ) return 'Sorry something went wrong with your request.';

        return $result;
    }

    /*
    * Either fetches the desired data from the API and caches it, or fetches the cached version
    *
    * @param string $url The url to the API call
    * @param string $set (optional) The name of the set to retrieve.
    */
    protected function fetch($url, $set = null)
    {
        // Use the API url to generate the cache file name.
        // So: http://marketplace.envato.com/api/edge/collection:739793.json
        // Becomes: collection-739793.json
        $cache_path = $this->cache_dir . '/' . str_replace(':', '-', substr(strrchr($url, '/'), 1));

        if ( $this->has_expired($cache_path) ) {
            // get fresh copy
            $data = $this->curl($url);

            if ($data) {
                $data = isset($set) ? $data->{$set} : $data; // if a set is needed, update
            } else exit('Could not retrieve data.');

            $this->cache_it($cache_path, $data);

            return $data;
        } else {
            // if available in cache, use that
            return json_decode(file_get_contents($cache_path));
        }
    }

    /**
     * General purpose function to query the ticksy API.
     *
     * @param string $url The url to access, via curl.
     * @return object The results of the curl request.
     */
    protected function curl($url)
    {
        if ( empty($url) ) return false;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (compatible; Ticksy API Wrapper PHP)');

        $data = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($data);

        return $data; // string or null
    }

    /*
    * Caches the results request to keep from hammering the API
    *
    * @param string $cache_path - A path to the cache file
    * @param string $data - The results from the API call - should be encoded
    */
    protected function cache_it($cache_path, $data)
    {
        if ( !isset($data) ) return;
        !file_exists($this->cache_dir) && mkdir($this->cache_dir);
        file_put_contents( $cache_path, json_encode($data) );

        return $cache_path;
    }

    /*
    * Determines whether the provided file has expired yet
    *
    * @param string $cache_path The path to the cached file
    * @param string $expires - In minutes, how long the file should cache for.
    */
    protected function has_expired($cache_path, $expires = null)
    {
        if ( !isset($expires) ) $expires = $this->cache_expires;

        if ( file_exists($cache_path) ) {
            return time() - $expires * 60 > filemtime($cache_path);
        }

        return true;
    }

    /*
    * Helper function that deletes all of the files in your cache directory.
    */
    public function clear_cache(){
        array_map('unlink', glob("$this->cache_dir/*"));
    }
}