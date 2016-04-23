<?php

class Bridge
{
    // Libs
    protected $ticksy;
    protected $slack;

    // Caching
    protected $cache_dir = 'cache';
    protected $timestamp_new;
    protected $timestamp_old;

    // Settings
    protected $assigned = false;

    # Constructor
    public function Bridge() {

        // Setup
        $this->handle_cache();
        $this->ticksy = new Ticksy(TICKSY_DOMAIN, TICKSY_API_KEY);

        if (TICKSY_ASSIGNED_ONLY) {
            $this->assigned = true;
        }

        if ($this->ticksy) {

            // Responsed needed
            $responses_needed = $this->get_responses_needed();

            if ( $responses_needed ) {
                $tickets = $this->get_tickets_to_respond();

                if (count($tickets) > 0) {

                    $slack_messages = $this->prepare_slack_messages($tickets);

                    $slack = new Slack(SLACK_WEBHOOK_URL);

                    if ( $slack ) {
                        $slack->push_to_slack($slack_messages);
                    } else {
                        echo 'Communication with Slack failed.';
                    }
                }
            }
        }

    }

    public function get_responses_needed() {

        if ( $this->assigned ) {
            $responses = $this->ticksy->my_responses_needed();
        } else {
            $responses = $this->ticksy->my_responses_needed();
        }

        $responses = $this->toArray($responses);

        if ( isset($responses['responses-needed']) && $responses['responses-needed'] > 0 ) {
            return true;
        } else {
            return false;
        }
    }

    public function get_tickets_to_respond() {

        if ( $this->assigned ) {
            $tickets = $this->ticksy->my_tickets();
        } else {
            $tickets = $this->ticksy->open_tickets();
        }

        $tickets = $this->toArray($tickets);

        if ( isset($tickets['my-tickets']) ) {
            $tickets = $tickets['my-tickets'];
        } else {
            $tickets = $tickets['open-tickets'];
        }

        $tickets = $this->filter_tickets($tickets);

        return $tickets;
    }

    public function filter_tickets($tickets) {

        foreach ($tickets as $i => &$ticket) {

            // Remove tickets without response needed
            if (isset($ticket['needs_response']) && $ticket['needs_response'] == 0) {
                unset($tickets[$i]);
                continue;
            }

            // Get latest comment
            $comments = $ticket['ticket_comments'];
            $comments = $this->filter_comments($comments);

            // Don't push old comments on first run
            if ($comments) {
                $comment = ($comments[0]) ? $comments[0] : false;

                if ($comment) {
                    $ticket['comment'] = array(
                        'timestamp' => $comment['time_stamp'],
                        'user' => $comment['commenter_name'],
                        'text' => $comment['comment']
                    );
                }
            }

            if (!isset($ticket['comment'])) {
                unset($tickets[$i]);
            }
        }

        return $tickets;
    }

    protected function handle_cache() {

        date_default_timezone_set('America/New_York');
        //date_default_timezone_set("EST"); // TODO: Update to GMT if available by Ticksy
        $this->timestamp_new = time();

        $cache_path = $this->cache_dir . '/watcher.json';

        if ( file_exists($cache_path) ) {
            $this->timestamp_old = intval(json_decode(file_get_contents($cache_path)));
        }

        $this->cache_it($cache_path, $this->timestamp_new);
    }

    public function filter_comments($comments) {

        if ( !$this->timestamp_old ) {
            return false;
        }

        foreach ($comments as $i => $comment) {
            //date_default_timezone_set("EST");
            $comment_timestamp = strtotime($comment['time_stamp']);
            $deadline_timestamp = $this->timestamp_old;

            if ($comment_timestamp < $deadline_timestamp) {
                unset($comments[$i]);
            } else {
                echo $comment['ticket_id'] . ' - OLD: ' . date("d.m.Y H:i:s",$deadline_timestamp) . ' - Comment: ' . date("d.m.Y H:i:s",$comment_timestamp) . ' - NOW: ' . date("d.m.Y H:i:s",time()) . '<br>';
            }
        }

        return $comments;
    }

    public function get_comments($ticket_id) {
        $comments = $this->toArray($this->ticksy->ticket_comments($ticket_id));

        return $comments['ticket-comments'];
    }

    public function prepare_slack_messages($tickets) {

        $slack_messages = array();

        foreach ($tickets as $i => $ticket) {

            $ticket_url = $this->ticksy->get_url('ticket', $ticket['ticket_id']);
            $comment_text = strip_tags(html_entity_decode($ticket['comment']['text']));

            $message = array();

            $message['pretext'] = 'Ticket created/updated: <' . $ticket_url .'|#' . $ticket['ticket_id'] .'>';
            $message['title'] = $ticket['ticket_title'];
            $message['text'] = $comment_text;

            $slack_messages[] = $message;
        }

        return $slack_messages;
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
    * Helper function that deletes all of the files in your cache directory.
    */
    public function clear_cache(){
        array_map('unlink', glob("$this->cache_dir/*"));
    }

    /*
     * Object to Array
     */
    public function toArray($obj){
        return json_decode(json_encode($obj), true);
    }

    /*
     * Debugging
     */
    private function debug($arg) {
        echo '<pre>';
        print_r($arg);
        echo '</pre>';
    }
}
