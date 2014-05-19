<?php

/**
 * @file ProcessInterface.php
 *
 * Provides the processing interface for all custom webhooks that require
 * processing.
 */
interface ProcessInterface
{

    public function processResponse(\Guzzle\Http\Message\Response $response);
}
