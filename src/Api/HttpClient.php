<?php

namespace Pim\Bundle\TextmasterBundle\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Textmaster\HttpClient\HttpClient as BaseHttpClient;
use Textmaster\HttpClient\HttpClientInterface;

class HttpClient extends BaseHttpClient implements HttpClientInterface
{
    public function __construct($key, $secret, array $options = [], LoggerInterface $logger = null)
    {
        $options = array_merge($this->options, $options);

        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) use ($key, $secret, $options) {
            $date = new \DateTime('now', new \DateTimeZone('UTC'));

            return $request
                ->withHeader('User-Agent', $options['user_agent'])
                ->withHeader('Apikey', $key)
                ->withHeader('Date', $date->format('Y-m-d H:i:s'))
                ->withHeader('Signature', sha1($secret.$date->format('Y-m-d H:i:s')))
                ;
        }));

        if ($logger) {
            $mapResponse = Middleware::mapResponse(function (ResponseInterface $response) {
                $response->getBody()->rewind();
                return $response;
            });
            $stack->push($mapResponse);

            $stack->push(Middleware::log(
                $logger,
                new MessageFormatter('Call to TextMaster API, Request: {request}. Response: {response}')
            ));
        }


        $this->options = array_merge($this->options, $options, ['handler' => $stack]);
        $this->options['base_uri'] = sprintf($this->options['base_uri'], $this->options['api_version']);

        $this->client = new Client($this->options);
    }
}
