<?php

/*
 * This file is part of NotifyMe.
 *
 * (c) Cachet HQ <support@cachethq.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NotifyMeHQ\Ballou;

use GuzzleHttp\Client;
use NotifyMeHQ\NotifyMe\Arr;
use NotifyMeHQ\NotifyMe\FactoryInterface;

class BallouFactory implements FactoryInterface
{
    /**
     * Create a new ballou gateway instance.
     *
     * @param string[] $config
     *
     * @return \NotifyMeHQ\Ballou\BallouGateway
     */
    public function make(array $config)
    {
        Arr::requires($config, ['token']);

        $client = new Client();

        return new BallouGateway($client, $config);
    }
}
