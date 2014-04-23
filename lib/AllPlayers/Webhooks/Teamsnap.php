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
     * The authentication method used in the post requests.
     *
     * @var string
     */
    public $authentication = 'no_authentication';

    /**
     * The method of data transmission.
     *
     * @var string
     */
    public $method = 'json';

    /**
     * Authenticate using teamsnap_auth.
     */
    public function __construct(array $subscriber = array(), array $data = array(), array $preprocess = array())
    {
        //chdir('../../resque');
        echo getcwd(), "\n";
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
                $send = array(
                    'team' => array(
                        'team_name' => $data['group']['name'],
                        'division_id' => $this->webhook->subscriber['division_id'],
                        'sport_id' => $this->getSport($data['group']['group_category']),
                        'timezone' => '', // determine from zipcode
                        'country' => 'United States',
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
                $send = array(
                    'team' => array(
                        'team_name' => $data['group']['name'],
                        'sport_id' => getSport($data['group']['group_category']),
                        'logo_url' => $data['group']['logo'],
                        'public_subdomain' => $data['group']['url'],
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

                /**
                 * Send get request with user id for the team, so we dont make duplicate users
                 * with different roles. Check if user exists by determing if we contain a partner
                 * ID for the related resource (if not, they havent been added to the TS System).
                 *
                 * if(user exists)
                 *   method = PUT
                 * else
                 *   method = POST
                 */
                $method = 'post'; // remove hardcoded value
                // put/post data to send
                $data = $this->webhook->data;
                $send = array(
                    'team' => array(
                        'available_rosters' => array(
                            'non_player' => (bool) ($data['member']['role_name'] == 'Player' ? false : true),
                            'is_manager' => (bool) ($data['member']['is_admin'] ? true : false),
                            'is_commissioner' => (bool) false,
                            'is_owner' => (bool) ($data['member']['role_name'] == 'Coach' ? true : false),
                        ),
                    ),
                );

                $this->webhook->data = $send;

                // set the correct url and call the correct method
                if ($method == 'post') {
                    $this->domain .= '/teams/' . 'INSERT_TEAM_ID' . '/as_roster/' .
                        $this->webhook->subscriber['commissioner_id'] . '/rosters'; // POST
                    parent::post();
                } elseif ($method == 'put') {
                    $this->domain .= '/teams/' . 'INSERT_TEAM_ID' . '/as_roster/' .
                        $this->webhook->subscriber['commissioner_id'] . '/rosters/' .
                        'INSERT_USER_ROSTER_ID'; // PUT
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
                            'non_player' => (bool) ($data['member']['role_name'] == 'Player' ? false : true),
                            'is_manager' => (bool) ($data['member']['is_admin'] ? true : false),
                            'is_commissioner' => false,
                            'is_owner' => (bool) ($data['member']['role_name'] == 'Coach' ? true : false),
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
        if (!stristr($data[0], 'Sport')) {
            $id = 52; // Non-Sport Group
        } else {
            switch ($data[1]) {
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
                    $id = 24; // Other Sport
                    break;
            }
        }

        return $id;
    }

}
