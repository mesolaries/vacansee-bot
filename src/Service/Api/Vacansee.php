<?php

namespace App\Service\Api;


use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Vacansee
{
    private const URLS = [
        'base' => 'http://localhost:8001',
        'vacancy' => 'http://localhost:8001/api/vacancies',
        'category' => 'http://localhost:8001/api/categories',
    ];

    private string $key;

    private HttpClientInterface $http;

    public function __construct(string $key, HttpClientInterface $http)
    {
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
            self::URLS['vacancy'] . "/$id",
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
            self::URLS['vacancy'],
            [
                'headers' => ['Accept' => 'application/json'],
                'query' => ['apikey' => $this->key, 'order[createdAt]' => 'desc']
            ]
        );

        return json_decode($response->getContent());
    }

    /**
     * @param $categoryId
     *
     * @return mixed
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getVacanciesByCategory($categoryId)
    {
        $response = $this->http->request(
            'GET',
            self::URLS['vacancy'],
            [
                'headers' => ['Accept' => 'application/json'],
                'query' => ['apikey' => $this->key, 'category.id' => $categoryId],
            ]
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
    public function getCategories()
    {
        $response = $this->http->request(
            'GET',
            self::URLS['category'],
            [
                'headers' => ['Accept' => 'application/json'],
                'query' => ['apikey' => $this->key, 'order[id]' => 'asc'],
            ]
        );

        return json_decode($response->getContent());
    }

    /**
     * @param $id
     *
     * @return mixed
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getCategoryById($id)
    {
        $response = $this->http->request(
            'GET',
            self::URLS['category'] . "/$id",
            [
                'headers' => ['Accept' => 'application/json'],
                'query' => ['apikey' => $this->key],
            ]
        );

        return json_decode($response->getContent());
    }

    /**
     * @param $uri
     *
     * @return mixed
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getCategoryByUri($uri)
    {
        $response = $this->http->request(
            'GET',
            self::URLS['base'] . $uri,
            [
                'headers' => ['Accept' => 'application/json'],
                'query' => ['apikey' => $this->key],
            ]
        );

        return json_decode($response->getContent());
    }
}