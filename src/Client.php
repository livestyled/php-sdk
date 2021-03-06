<?php

namespace LiveStyled;

use GuzzleHttp\Exception\BadResponseException;
use LiveStyled\Exception\EntityCreationException;
use LiveStyled\Exception\EntityFetchException;

abstract class Client
{
    const DEFAULT_SCHEME = 'https://';
    protected $domain;

    protected $credentials;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    public function __construct($domain, $credentials, $scheme = null, $timeout = 2.0)
    {
        $scheme            = $scheme ?? self::DEFAULT_SCHEME;
        $this->domain      = $this->addScheme($domain, $scheme);
        $this->credentials = $credentials;
        $this->httpClient  = new \GuzzleHttp\Client([
            'base_uri' => rtrim($this->domain, '/'),
            'timeout' => $timeout
        ]);
    }

    function addScheme($domain, $scheme)
    {
        return parse_url($domain, PHP_URL_SCHEME) === null ?
            $scheme . $domain : $domain;
    }

    /**
     * @param $data
     * @return array
     * @throws EntityCreationException
     */
    public function create($data)
    {
        try {
            $response = $this->httpClient->post($this->getPath(), [
                'body'    => json_encode($data),
                'headers' => $this->getHeaders(),
                'connect_timeout' => 1
            ]);
        } catch (BadResponseException $e) {
            throw new EntityCreationException($e->getMessage(), $e->getCode(), $e);
        }

        return json_decode($response->getBody()
                                    ->getContents(), true);
    }

    /**
     * @param $id
     * @param $data
     * @return array
     * @throws EntityCreationException
     */
    public function update($id, $data)
    {
        try {
            $response = $this->httpClient->patch($this->getPathWithId($id), [
                'body'    => json_encode($data),
                'headers' => $this->getHeaders(true),
                'connect_timeout' => 1
            ]);
        } catch (BadResponseException $e) {
            throw new EntityCreationException($e->getMessage(), $e->getCode(), $e);
        }

        return json_decode($response->getBody()
                                    ->getContents(), true);
    }

    /**
     * @param       $id
     * @param array $filters
     * @return mixed
     * @throws EntityFetchException
     */
    public function find($id, $filters = [])
    {
        try {
            $response = $this->httpClient->get($this->getPathWithId($id), [
                'headers' => $this->getHeaders(),
                'query'   => $filters,
                'connect_timeout' => 1
            ]);
        } catch (BadResponseException $e) {
            throw new EntityFetchException($e->getMessage(), $e->getCode(), $e);
        }

        return json_decode($response->getBody()
                                    ->getContents(), true);
    }

    /**
     * @param array $filters
     * @param int   $pageSize
     * @param int   $page
     * @return array
     * @throws EntityFetchException
     */
    public function findAll($filters = [], $pageSize = 10, $page = 1)
    {
        try {
            $response = $this->httpClient->get($this->getPath(), [
                'headers' => $this->getHeaders(),
                'query'   => array_merge(compact('pageSize', 'page'), $filters),
                'connect_timeout' => 1
            ]);
        } catch (BadResponseException $e) {
            throw new EntityFetchException($e->getMessage(), $e->getCode(), $e);
        }

        return json_decode($response->getBody()
                                    ->getContents(), true);
    }

    /**
     * @param string $path
     *
     * @return array
     * @throws EntityFetchException
     */
    public function getByGivenPath(string $path): array
    {
        try {
            $response = $this->httpClient->get($path, [
                'headers' => $this->getHeaders(),
                'connect_timeout' => 1
            ]);
        } catch (BadResponseException $e) {
            throw new EntityFetchException($e->getMessage(), $e->getCode(), $e);
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param bool $patch
     * @return array
     */
    protected function getHeaders($patch = false): array
    {
        $headers = [
            'x-api-key'    => $this->credentials['api_key'],
            'content-type' => 'application/json',
            'connection'   => 'close'
        ];

        if ($patch) {
            $headers['content-type'] = 'application/merge-patch+json';
        }

        return $headers;
    }

    /**
     * @param $id
     * @return string
     */
    protected function getPathWithId($id): string
    {
        return $this->getPath() . '/' . $id;
    }

    abstract protected function getPath();
}
