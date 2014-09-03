<?php

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\ProcessInterface;

class UserCreatesGroup implements ProcessInterface
{
    public function process()
    {
        // Set the original webhook data.
        $data = $this->getData();
        $this->setOriginalData($data);
    }
}
