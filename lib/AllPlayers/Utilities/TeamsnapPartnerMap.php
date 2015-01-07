<?php
/**
 * @file
 * Contains /AllPlayers/Utilities/TeamsnapPartnerMap.
 *
 * Provides the TeamSnap specific Partner-Mapping API functionality.
 */

namespace AllPlayers\Utilities;

class TeamsnapPartnerMap extends PartnerMap
{
    /**
     * Create the PartnerMap instance and create the AllPlayers auth Cookie.
     *
     * @param string $username
     *   The AllPlayers username for APIv1 authentication.
     * @param string $password
     *   The AllPlayers password for APIv1 authentication.
     */
    public function __construct($username, $password)
    {
        parent::__construct('teamsnap', $username, $password);
    }

    /**
     * Fetch the TeamSnap Event ID for the given event and group.
     *
     * @param string $event
     *   The AllPlayers UUID for the Event.
     * @param string $group
     *   The AllPlayers UUID for the Group.
     *
     * @return array
     *   The Partner Mapping response from the AllPlayers Partner Mapping API.
     */
    public function getEventId($event, $group)
    {
        $event = $this->readPartnerMap(
            PartnerMap::PARTNER_MAP_EVENT,
            $event,
            $group
        );

        return $event;
    }

    /**
     * Fetch the TeamSnap Roster ID for the given user and group.
     *
     * @param string $user
     *   The AllPlayers UUID for the User.
     * @param string $group
     *   The AllPlayers UUID for the Group.
     *
     * @return array
     *   The Partner Mapping response from the AllPlayers Partner Mapping API.
     */
    public function getRosterId($user, $group)
    {
        $roster = $this->readPartnerMap(
            PartnerMap::PARTNER_MAP_USER,
            $user,
            $group
        );

        return $roster;
    }

    /**
     * Fetch the TeamSnap Team ID for the and group.
     *
     * @param string $group
     *   The AllPlayers UUID for the Group.
     *
     * @return array
     *   The Partner Mapping response from the AllPlayers Partner Mapping API.
     */
    public function getTeamId($group)
    {
        $team = $this->readPartnerMap(
            PartnerMap::PARTNER_MAP_GROUP,
            $group,
            $group
        );

        return $team;
    }

    /**
     * Add or Update the TeamSnap Event.
     *
     * @param integer $id
     *   The TeamSnap Event ID to link with the given event and group.
     * @param string $event
     *   The AllPlayers UUID for the Event.
     * @param string $group
     *   The AllPlayers UUID for the Group.
     */
    public function setEvent($id, $event, $group)
    {
        $this->createPartnerMap(
            $id,
            PartnerMap::PARTNER_MAP_EVENT,
            $event,
            $group
        );
    }

    /**
     * Add or Update the TeamSnap Rosters Cell Phone ID.
     *
     * @param integer $id
     *   The TeamSnap Phone ID to link with the given user and group.
     * @param string $user
     *   The AllPlayers UUID for the User.
     * @param string $group
     *   The AllPlayers UUID for the Group.
     */
    public function setRosterCellPhone($id, $user, $group)
    {
        $this->createPartnerMap(
            $id,
            PartnerMap::PARTNER_MAP_USER,
            $user,
            $group,
            PartnerMap::PARTNER_MAP_SUBTYPE_USER_PHONE_CELL
        );
    }

    /**
     * Add or Update the TeamSnap Rosters Email ID.
     *
     * @param integer $id
     *   The TeamSnap Email Address ID to link with the given user and group.
     * @param string $user
     *   The AllPlayers UUID for the User.
     * @param string $group
     *   The AllPlayers UUID for the Group.
     */
    public function setRosterEmailId($id, $user, $group)
    {
        $this->createPartnerMap(
            $id,
            PartnerMap::PARTNER_MAP_USER,
            $user,
            $group,
            PartnerMap::PARTNER_MAP_SUBTYPE_USER_EMAIL
        );
    }

    /**
     * Add or Update the TeamSnap Rosters Home Phone ID.
     *
     * @param integer $id
     *   The TeamSnap Phone ID to link with the given user and group.
     * @param string $user
     *   The AllPlayers UUID for the User.
     * @param string $group
     *   The AllPlayers UUID for the Group.
     */
    public function setRosterHomePhone($id, $user, $group)
    {
        $this->createPartnerMap(
            $id,
            PartnerMap::PARTNER_MAP_USER,
            $user,
            $group,
            PartnerMap::PARTNER_MAP_SUBTYPE_USER_PHONE
        );
    }

    /**
     * Add or Update the TeamSnap Rosters Work Phone ID.
     *
     * @param integer $id
     *   The TeamSnap Phone ID to link with the given user and group.
     * @param string $user
     *   The AllPlayers UUID for the User.
     * @param string $group
     *   The AllPlayers UUID for the Group.
     */
    public function setRosterWorkPhone($id, $user, $group)
    {
        $this->createPartnerMap(
            $id,
            PartnerMap::PARTNER_MAP_USER,
            $user,
            $group,
            PartnerMap::PARTNER_MAP_SUBTYPE_USER_PHONE_WORK
        );
    }
}
