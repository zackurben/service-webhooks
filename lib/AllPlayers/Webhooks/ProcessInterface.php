<?php

/**
 * @file ProcessInterface.php
 *
 * Provides the processing interface for all custom webhooks that require
 * processing.
 */

namespace AllPlayers\Webhooks;

use Guzzle\Http\Message\Response;

/**
 * Interface definition.
 */
interface ProcessInterface
{

    /**
     * Process the webhook data returned from sending the webhook.
     *
     * This function should relate a piece of AllPlayers data to a piece of
     * third-party data; This information relationship will be made via the
     * AllPlayers Public PHP API.
     *
     * @param \Guzzle\Http\Message\Response $response
     *   Response from the webhook being processed/called.
     */
    public function processResponse(Response $response);
}
