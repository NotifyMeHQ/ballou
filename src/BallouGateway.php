<?php

/*
 * This file is part of NotifyMe.
 *
 * (c) Alt Three LTD <support@alt-three.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NotifyMeHQ\Ballou;

use GuzzleHttp\Client;
use NotifyMeHQ\Contracts\GatewayInterface;
use NotifyMeHQ\NotifyMe\Arr;
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
     * @param string $to
     * @param string $message
     *
     * @return \NotifyMeHQ\Contracts\ResponseInterface
     */
    public function notify($to, $message)
    {
        $params = [
            'UN'      => Arr::get($this->config, 'UN', ''),
            'PW'      => Arr::get($this->config, 'PW', ''),
            'CR'      => Arr::get($this->config, 'CR', ''),
            'RI'      => Arr::get($this->config, 'RI', ''),
            'O'       => urlencode(Arr::get($this->config, 'O', '')),
            'D'       => Arr::get($this->config, 'D', ''),
            'LONGSMS' => Arr::get($this->config, 'LONGSMS', ''),
            'M'       => urlencode($message),
        ];

        return $this->commit($params);
    }

    /**
     * Commit a HTTP request.
     *
     * @param string[] $params
     *
     * @return mixed
     */
    protected function commit(array $params)
    {
        $success = false;

        $rawResponse = $this->client->get($this->buildUrlFromString('/http/get/SendSms.php'), [
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
     * @return \NotifyMeHQ\Contracts\ResponseInterface
     */
    protected function mapResponse($success, $response)
    {
        return (new Response())->setRaw($response)->map([
            'success' => $success,
            'message' => $success ? 'Message sent' : implode(', ', $response->ballou_smls_response->response->message->error),
        ]);
    }

    /**
     * Get the default json response.
     *
     * @param \GuzzleHttp\Message\Response $rawResponse
     *
     * @return array
     */
    protected function jsonError($rawResponse)
    {
        $msg = 'API Response not valid.';
        $msg .= " (Raw response API {$rawResponse->getBody()})";

        return [
            'error' => $msg,
        ];
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
