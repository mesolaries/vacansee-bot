<?php

namespace App\Service\Api;


use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Vacansee
{
    private array $urls;

    private string $key;

    private HttpClientInterface $http;

    public function __construct(array $urls, string $key, HttpClientInterface $http)
    {
        $this->urls = $urls;
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
            $this->urls['vacancy'] . "/$id",
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
            $this->urls['vacancy'],
            [
                'headers' => ['Accept' => 'application/json'],
                'query' => ['apikey' => $this->key, 'order[createdAt]' => 'desc']
            ]
        );

        return json_decode($response->getContent());
    }

    public function getCategories()
    {
        $response = $this->http->request(
            'GET',
            $this->urls['category'],
            [
                'headers' => ['Accept' => 'application/json'],
                'query' => ['apikey' => $this->key],
            ]
        );

        return json_decode($response->getContent());
    }
}