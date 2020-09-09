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
        'base' => 'https://vacansee.xyz',
        'vacancy' => 'https://vacansee.xyz/api/vacancies',
        'category' => 'https://vacansee.xyz/api/categories',
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
     * @param array $query
     *
     * @return mixed
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getVacancies(array $query = [])
    {
        $query['apikey'] = $this->key;
        $response = $this->http->request(
            'GET',
            self::URLS['vacancy'],
            [
                'headers' => ['Accept' => 'application/json'],
                'query' => $query
            ]
        );

        return json_decode($response->getContent());
    }

    /**
     * @param int   $categoryId
     * @param array $query
     *
     * @return mixed
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getVacanciesByCategoryId(int $categoryId, array $query = [])
    {
        $query['apikey'] = $this->key;
        $query['category.id'] = $categoryId;

        $response = $this->http->request(
            'GET',
            self::URLS['vacancy'],
            [
                'headers' => ['Accept' => 'application/json'],
                'query' => $query,
            ]
        );

        return json_decode($response->getContent());
    }

    /**
     * @param string $categorySlug
     * @param array  $query
     *
     * @return mixed
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getVacanciesByCategorySlug(string $categorySlug, array $query = [])
    {
        $query['apikey'] = $this->key;
        $query['category.slug'] = $categorySlug;

        $response = $this->http->request(
            'GET',
            self::URLS['vacancy'],
            [
                'headers' => ['Accept' => 'application/json'],
                'query' => $query,
            ]
        );

        return json_decode($response->getContent());
    }

    /**
     * @param array $query
     *
     * @return mixed
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getCategories(array $query = [])
    {
        $query['apikey'] = $this->key;

        $response = $this->http->request(
            'GET',
            self::URLS['category'],
            [
                'headers' => ['Accept' => 'application/json'],
                'query' => $query,
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
     * @param string $uri
     * @param array  $query
     *
     * @return mixed
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getResourceByUri(string $uri, array $query = [])
    {
        $query['apikey'] = $this->key;

        $response = $this->http->request(
            'GET',
            self::URLS['base'] . $uri,
            [
                'headers' => ['Accept' => 'application/json'],
                'query' => $query,
            ]
        );

        return json_decode($response->getContent());
    }
}