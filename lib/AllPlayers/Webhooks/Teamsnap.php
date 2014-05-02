<?php

/**
 * @file Teamsnap.php
 *
 * Provides the TeamSnap Webhooks plugin definition. The TeanSnap Webhook sends
 * data to various API Endpoints, using a custom token based authentication.
 */

namespace AllPlayers\Webhooks;

/**
 * Base TeamSnap Webhook definition.
 */
class Teamsnap extends Webhook
{

    /**
     * The URL to post the webhook.
     *
     * @var string
     */
    public $domain = 'https://api.teamsnap.com/v2';

    /**
     * The method used for Client authentication.
     *
     * Options:
     *   'no_authentication'
     *   'basic_auth'
     *   'oauth'
     * Default:
     *   'no_authentication'
     *
     * If using 'basic_auth', the $subscriber must contain: user and pass.
     * If using 'oauth', the $subscriber must contain: consumer_key, consumer_secret, token, and secret.
     */
    public $authentication = 'no_authentication';

    /**
     * The method of data transmission.
     *
     * Options:
     *   'form-urlencoded'
     *   'json'
     * Default:
     *   'json'
     *
     * @var string
     */
    public $method = 'json';

    /**
     * Determines if the webhook will return data that requires processing.
     *
     * Options:
     *   true
     *   false
     * Default:
     *   false
     *
     * @var boolean
     */
    public $processing = true;

    /**
     * Authenticate using teamsnap_auth.
     */
    public function __construct(array $subscriber = array(), array $data = array(), array $preprocess = array())
    {
        include 'config/config.php';

        if (isset($config['teamsnap'])) {
            parent::__construct(array('token' => $config['teamsnap']['token'],
                'commissioner_id' => $config['teamsnap']['commissioner_id'],
                'division_id' => $config['teamsnap']['division_id']), $data, $preprocess);

            $this->headers['X-Teamsnap-Token'] = $this->webhook->subscriber['token'];
            $this->process();
        }
    }

    /**
     * Process the webhook data and set the domain to the appropriate URL
     *
     * TODO, Fix case blockers.
     */
    public function process()
    {
        switch ($this->webhook->data['webhook_type']) {
            case 'user_creates_group':
                // INTERNAL BLOCKER => need the ability to get location information from group admin
                $this->domain .= '/teams';

                // post data to send
                $data = $this->webhook->data;
                $geographical = $this->getRegion($data['group']['timezone']);
                $send = array(
                    'team' => array(
                        'team_name' => $data['group']['name'],
                        'division_id' => intval($this->webhook->subscriber['division_id']),
                        'sport_id' => $this->getSport($data['group']['group_category']),
                        'timezone' => $geographical['timezone'],
                        'country' => $geographical['location'],
                        'zipcode' => $data['group']['postalcode'],
                    ),
                );

                $this->webhook->data = $send;
                parent::post();
                break;
            case 'user_updates_group':
                // INTERNAL BLOCKER => need the ability to get TEAM_ID

                $this->domain .= '/teams/' . 'INSERT_TEAM_ID';

                // put data to send
                $data = $this->webhook->data;
                $geographical = $this->getRegion($data['group']['timezone']);
                $send = array(
                    'team' => array(
                        'team_name' => $data['group']['name'],
                        'sport_id' => $this->getSport($data['group']['group_category']),
                        'timezone' => $geographical['timezone'],
                        'country' => $geographical['location'],
                        'zipcode' => $data['group']['postalcode'],
                        'logo_url' => $data['group']['logo'],
                        'cname' => $data['group']['url'],
                    ),
                );

                $this->webhook->data = $send;
                parent::put();
                break;
            case 'user_deletes_group':
                // EXTERNAL BLOCKER => need to know how to properly delete a team, from TeamSnap, (NYI).
                break;
            case 'user_adds_role':
                // INTERNAL BLOCKER => need the ability to get TEAM_ID
                // INTERNAL BLOCKER => need the ability to determine if the user previously exists in the
                //                     TeamSnap system.
                // INTERNAL BLOCKER => need to process the user_creates_group webhook before user_adds_role,
                //                     so the owner exists, and we dont need to make extra api calls.
                // build put/post data to send
                $data = $this->webhook->data;
                $send = array(
                    'roster' => array(
                        "first" => $data['member']['first_name'],
                        "last" => $data['member']['last_name'],
                        'non_player' => $data['member']['role_name'] == 'Player' ? 0 : 1,
                        'is_manager' => $data['member']['is_admin'] ? 1 : 0,
                        'is_commissioner' => 0,
                        'is_owner' => $data['member']['role_name'] == 'Coach' ? 1 : 0,
                    ),
                );
                $this->webhook->data = $send;

                /**
                 * Check partner mapping db to determine if user exists. if not,
                 * send post to create and add roles, else update roles.
                 *
                 * if(user does not exist)
                 *   method = POST
                 * else
                 *   method = PUT
                 */
                if (false) {
                    $this->domain .= '/teams/' . 'INSERT_TEAM_ID' . '/as_roster/' .
                        $this->webhook->subscriber['commissioner_id'] . '/rosters'; // POST

                    parent::post();
                } else {
                    $this->domain .= '/' . 'INSERT_USER_ROSTER_ID'; // PUT

                    parent::put();
                }

                break;
            case 'user_removes_role':
                $this->domain .= '/teams/' . 'INSERT_TEAM_ID' . '/as_roster/' .
                    $this->webhook->subscriber['commissioner_id'] . '/rosters/' . 'INSERT_ROSTER_ID';

                // put data to send
                $data = $this->webhook->data;
                $send = array(
                    'team' => array(
                        'available_rosters' => array(
                            'non_player' => $data['member']['role_name'] == 'Player' ? 0 : 1,
                            'is_manager' => $data['member']['is_admin'] ? 1 : 0,
                            'is_commissioner' => 0,
                            'is_owner' => $data['member']['role_name'] == 'Coach' ? 1 : 0,
                        ),
                    ),
                );

                $this->webhook->data = $send;
                parent::put();
                break;
            case 'user_adds_submission':
                // Functionality currently unused by TeamSnap, however, if they plan to implement it,
                // we can store it here: https://github.com/teamsnap/apiv2-docs/wiki/Roster-Custom-Data
                break;
        }
    }

    /**
     * Process the webhook data returned from sending the webhook; The value
     * returned is used to map a AllPlayers internal resource to a partners
     * resource.
     *
     * @param array $data
     *   Response from the webhook being processed/called.
     *
     * @return
     *   The partner resource to be correlated with the AllPlayers resource.
     */
    public function processResponse($data)
    {
        $response = '';

        switch ($this->webhook->data['webhook_type']) {
            case 'user_creates_group':
                $response = $data['team']['id'];
                break;
            case 'user_adds_role':
                $response = $data['roster']['id'];
                break;
        }

        return $response;
    }

    /**
     * Select the id corresponding to the sport name.
     *
     * @param array $data
     *   Array of the Group Category selected in the group creation process on AllPlayers.
     *
     * @return int
     *   The sport id corresponding to the available sports in the TeamSnap API.
     */
    public function getSport($data)
    {
        $id = NULL;

        // if the sport group was not selected, default to non-sport group.
        switch ($data) {
            case 'Archery':
                $id = 59;
                break;
            case 'Australian Football':
                $id = 26;
                break;
            case 'Badminton':
                $id = 27;
                break;
            case 'Bandy':
                $id = 28;
                break;
            case 'Baseball':
                $id = 5;
                break;
            case 'Basketball':
                $id = 1;
                break;
            case 'Bocce':
                $id = 29;
                break;
            case 'Bowling':
                $id = 13;
                break;
            case 'Broomball':
                $id = 30;
                break;
            case 'Cheerleading':
                $id = 31;
                break;
            case 'Chess':
                $id = 32;
                break;
            case 'Cow Tipping':
                $id = 54;
                break;
            case 'Cricket':
                $id = 8;
                break;
            case 'Croquet':
                $id = 33;
                break;
            case 'Curling':
                $id = 34;
                break;
            case 'Cycling':
                $id = 35;
                break;
            case 'Dodgeball':
                $id = 14;
                break;
            case 'Dragon Boat':
                $id = 25;
                break;
            case 'Fencing':
                $id = 36;
                break;
            case 'Field Hockey':
                $id = 15;
                break;
            case 'Floor Hockey':
                $id = 60;
                break;
            case 'Floorball':
                $id = 44;
                break;
            case 'Foosball':
                $id = 37;
                break;
            case 'Football':
                $id = 7;
                break;
            case 'Golf':
                $id = 46;
                break;
            case 'Gymnastics-Men':
                $id = 56;
                break;
            case 'Gymnastics-Women':
                $id = 57;
                break;
            case 'Hurling':
                $id = 38;
                break;
            case 'Ice Hockey':
                $id = 16;
                break;
            case 'Indoor Soccer':
                $id = 39;
                break;
            case 'Inline Hockey':
                $id = 17;
                break;
            case 'Ki-O-Rahi':
                $id = 50;
                break;
            case 'Kickball':
                $id = 18;
                break;
            case 'Lacrosse':
                $id = 10;
                break;
            case 'Netball':
                $id = 40;
                break;
            case 'Non-Sport Group':
                $id = 52;
                break;
            case 'Other Sport':
                $id = 24;
                break;
            case 'Outrigger':
                $id = 53;
                break;
            case 'Paintball':
                $id = 19;
                break;
            case 'Petanque':
                $id = 45;
                break;
            case 'Polo':
                $id = 20;
                break;
            case 'Racquetball':
                $id = 55;
                break;
            case 'Ringette':
                $id = 51;
                break;
            case 'Roller Derby':
                $id = 48;
                break;
            case 'Rowing':
                $id = 21;
                break;
            case 'Rugby':
                $id = 9;
                break;
            case 'Running':
                $id = 41;
                break;
            case 'Sailing':
                $id = 47;
                break;
            case 'Slo-pitch':
                $id = 61;
                break;
            case 'Soccer':
                $id = 2;
                break;
            case 'Softball':
                $id = 4;
                break;
            case 'Street Hockey':
                $id = 62;
                break;
            case 'Swimming':
                $id = 42;
                break;
            case 'Tennis':
                $id = 43;
                break;
            case 'Track And Field':
                $id = 58;
                break;
            case 'Ultimate':
                $id = 22;
                break;
            case 'Volleyball':
                $id = 6;
                break;
            case 'Water Polo':
                $id = 23;
                break;
            case 'Wiffleball':
                $id = 11;
                break;
            case 'Wrestling':
                $id = 49;
                break;
            default:
                $id = 52; // Non-Sport Group
                break;
        }

        return $id;
    }

    /**
     * Translate UTC/GMT timezone offset to TeamSnap allocated Timezones and estimated region.
     *
     * @param int $offset
     *   The timezone offset from UTC/GMT.
     *
     * @return array
     *   [timezone] = TeamSnap Timezone
     *   [location] = Estimated Geographical Location
     */
    public function getRegion($offset)
    {
        $timezone = '';
        $location = '';

        switch ($offset) {
            case '13:00':
                $timezone = 'Samoa';
                $location = 'Samoa';
                break;
            case '12:00':
                $timezone = 'Auckland';
                $location = 'New Zealand';
                break;
            case '11:00':
                $timezone = 'Vladivostok';
                $location = 'Russia';
                break;
            case '10:00':
                $timezone = 'Sydney';
                $location = 'Australia';
                break;
            case '9:30':
                $timezone = 'Adelaide';
                $location = 'Australia';
                break;
            case '9:00':
                $timezone = 'Osaka';
                $location = 'Japan';
                break;
            case '8:00':
                $timezone = 'Chongqing';
                $location = 'China';
                break;
            case '7:00':
                $timezone = 'Jakarta';
                $location = 'Indonesia';
                break;
            case '6:30':
                $timezone = 'Rangoon';
                $location = 'Myanmar';
                break;
            case '6:00':
                $timezone = 'Dhaka';
                $location = 'Bangladesh';
                break;
            case '5:45':
                $timezone = 'Kathmandu';
                $location = 'Nepal';
                break;
            case '5:30':
                $timezone = 'Mumbai';
                $location = 'India';
                break;
            case '5:00':
                $timezone = 'Karachi';
                $location = 'Pakistan';
                break;
            case '4:30':
                $timezone = 'Kabul';
                $location = 'Afghanistan';
                break;
            case '4:00':
                $timezone = 'Moscow';
                $location = 'Russia';
                break;
            case '3:30':
                $timezone = 'Tehran';
                $location = 'Iran';
                break;
            case '3:00':
                $timezone = 'Riyadh';
                $location = 'Saudi Arabia';
                break;
            case '2:00':
                $timezone = 'Athens';
                $location = 'Greece';
                break;
            case '1:00':
                $timezone = 'Berlin';
                $location = 'Germany';
                break;
            case '-1:00':
                $timezone = 'Cape Verde Is.';
                $location = 'Republic of Cabo Verde';
                break;
            case '-2:00':
                $timezone = 'Mid-Atlantic';
                $location = 'United Kingdom';
                break;
            case '-3:00':
                $timezone = 'Brasilia';
                $location = 'Brazil';
                break;
            case '-3:30':
                $timezone = 'Newfoundland';
                $location = 'Canada';
                break;
            case '-4:00':
                $timezone = 'Atlantic Time (Canada)';
                $location = 'Canada';
                break;
            case '-4:00':
                $timezone = 'Caracas';
                $location = 'Venezuela';
                break;
            case '-5:00':
                $timezone = 'Eastern Time (US & Canada)';
                $location = 'United States';
                break;
            case '-6:00':
                $timezone = 'Central Time (US & Canada)';
                $location = 'United States';
                break;
            case '-7:00':
                $timezone = 'Mountain Time (US & Canada)';
                $location = 'United States';
                break;
            case '-8:00':
                $timezone = 'Pacific Time (US & Canada)';
                $location = 'United States';
                break;
            case '-9:00':
                $timezone = 'Alaska';
                $location = 'United States';
                break;
            case '-10:00':
                $timezone = 'Hawaii';
                $location = 'United States';
                break;
            case '-11:00':
                $timezone = 'American Samoa';
                $location = 'United States';
                break;
            default:
                $timezone = 'UTC';
                $location = 'United Kingdom';
                break;
        }

        return array('timezone' => $timezone, 'location' => $location);
    }

}
