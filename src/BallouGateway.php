<?php

/*
 * This file is part of NotifyMe.
 *
 * (c) Joseph Cohen <joseph.cohen@dinkbit.com>
 * (c) Graham Campbell <graham@mineuk.com>
 * (c) James Brooks <jbrooksuk@me.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NotifyMeHQ\Ballou;

use GuzzleHttp\Client;
use NotifyMeHQ\NotifyMe\Arr;
use NotifyMeHQ\NotifyMe\GatewayInterface;
use NotifyMeHQ\NotifyMe\HttpGatewayTrait;
use NotifyMeHQ\NotifyMe\Response;

class BallouGateway implements GatewayInterface
{
    use HttpGatewayTrait;

    /**
     * Gateway api endpoint.
     *
     * @var string
     */
    protected $endpoint = 'https://sms.ballou.se';

    /**
     * Ballou api version.
     *
     * @var string
     */
    protected $version = '1';

    /**
     * The http client.
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * Configuration options.
     *
     * @var string[]
     */
    protected $config;

    /**
     * Create a new ballou gateway instance.
     *
     * @param \GuzzleHttp\Client $client
     * @param string[]           $config
     *
     * @return void
     */
    public function __construct(Client $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * Send a notification.
     *
     * @param string   $message
     * @param string[] $options
     *
     * @return \NotifyMeHQ\NotifyMe\Response
     */
    public function notify($message, array $options = [])
    {
        $params = $this->addMessage($message, $params, $options);

        return $this->commit('get', $this->buildUrlFromString('/http/get/SendSms.php'), $params);
    }

    /**
     * Add a message to the request.
     *
     * @param string   $message
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addMessage($message, array $params, array $options)
    {
        $params['UN'] = Arr::get($options, 'UN', '');
        $params['PW'] = Arr::get($options, 'PW', '');
        $params['CR'] = Arr::get($options, 'CR', '');
        $params['RI'] = Arr::get($options, 'RI', '');
        $params['O'] = urlencode(Arr::get($options, 'O', ''));
        $params['D'] = Arr::get($options, 'D', '');
        $params['LONGSMS'] = Arr::get($options, 'LONGSMS', '');
        $params['M'] = urlencode($message);

        return $params;
    }

    /**
     * Commit a HTTP request.
     *
     * @param string   $method
     * @param string   $url
     * @param string[] $params
     * @param string[] $options
     *
     * @return mixed
     */
    protected function commit($method = 'post', $url, array $params = [], array $options = [])
    {
        $success = false;

        $rawResponse = $this->client->{$method}($url, [
            'exceptions'      => false,
            'timeout'         => '80',
            'connect_timeout' => '30',
            'headers'         => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => $params,
        ]);

        if ($rawResponse->getStatusCode() == 200) {
            $response = $rawResponse->xml();
            $success = (bool) $response->ballou_smls_response->response->message->attributes()->status;
        } else {
            $response = $this->responseError($rawResponse);
        }

        return $this->mapResponse($success, $response);
    }

    /**
     * Map HTTP response to response object.
     *
     * @param bool  $success
     * @param array $response
     *
     * @return \NotifyMeHQ\NotifyMe\Response
     */
    protected function mapResponse($success, $response)
    {
        return (new Response())->setRaw($response)->map([
            'success' => $success,
            'message' => $success ? 'Message sent' : implode(', ', $response->ballou_smls_response->response->message->error),
        ]);
    }

    /**
     * Get the request url.
     *
     * @return string
     */
    protected function getRequestUrl()
    {
        return $this->endpoint;
    }
}
