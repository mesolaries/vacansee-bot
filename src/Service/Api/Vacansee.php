<?php

namespace App\Service\Api;


use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Vacansee
{
    private string $url;

    private string $key;

    private HttpClientInterface $http;

    public function __construct(string $url, string $key, HttpClientInterface $http)
    {
        $this->url = $url;
        $this->key = $key;
        $this->http = $http;
    }

    /**
     * @param int $id
     *
     * @return mixed
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getVacancy(int $id)
    {
        $response = $this->http->request(
            'GET',
            $this->url . "/$id",
            ['headers' => ['Accept' => 'application/json'], 'query' => ['apikey' => $this->key]]
        );

        return json_decode($response->getContent());
    }

    /**
     * @return mixed
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getVacancies()
    {
        $response = $this->http->request(
            'GET',
            $this->url,
            ['headers' => ['Accept' => 'application/json'], 'query' => ['apikey' => $this->key]]
        );

        return json_decode($response->getContent());
    }
}