<?php

class Bridge
{
    // Libs
    protected $ticksy;
    protected $slack;

    // Settings
    protected $assigned = false;

    # Constructor
    public function Bridge() {

        // Setup
        $this->ticksy = new Ticksy(TICKSY_DOMAIN, TICKSY_API_KEY);

        if (TICKSY_ASSIGNED_ONLY) {
            $this->assigned = true;
        }

        if ($this->ticksy) {

            $result = $this->ticksy->open_tickets();

            $this->debug($result);
        }


        if ( $this->ticksy ) {

            /*
            $slack_messages = prepare_slack_messages($sales);

            $slack = new Slack();
            $slack->push_to_slack($slack_messages);
            */
        }

    }

    public function get_responses_needed() {

        if ( $this->assigned ) {
            $responses = $this->ticksy->my_responses_needed();
        } else {
            $responses = $this->ticksy->my_responses_needed();
        }

        return $responses;
    }

    public function prepare_slack_messages($sales) {

        $slack_messages = array();

        foreach ($sales as $i => $data) {

            $message = array();

            $message['title'] = 'You sold one of your items!';
            $message['text'] = $data['item'];
            $message['text'] .= ' - ' . $data['amount'] . ' EUR';
            $message['text'] .= ' - via Envato (DEBUG)';

            $slack_messages[] = $message;
        }

        return $slack_messages;
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