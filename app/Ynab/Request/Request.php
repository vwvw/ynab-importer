<?php

/**
 * Request.php
 * Copyright (c) 2020 james@firefly-iii.org
 *
 * This file is part of the Firefly III YNAB importer
 * (https://github.com/firefly-iii/ynab-importer).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Request.php

 */

declare(strict_types=1);
/**
 * Request.php
 * Copyright (c) 2020 james@firefly-iii.org.
 *
 * This file is part of the Firefly III CSV importer
 * (https://github.com/firefly-iii/csv-importer).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Ynab\Request;

use App\Exceptions\YnabApiException;
use App\Exceptions\YnabApiHttpException;
use App\Ynab\Response\Response;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class Request.
 */
abstract class Request
{
    protected const VALIDATION_ERROR_MSG = 'The given data was invalid.';
    /** @var string */
    private $base;
    /** @var array */
    private $body;
    /** @var array */
    private $parameters;
    /** @var string */
    private $token;
    /** @var string */
    private $url;

    /**
     * @throws YnabApiHttpException
     * @return Response
     */
    abstract public function get(): Response;

    /**
     * @return mixed
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * @param mixed $base
     */
    public function setBase($base): void
    {
        $this->base = $base;
    }

    /**
     * @return array
     */
    public function getBody(): ?array
    {
        return $this->body;
    }

    /**
     * @param array $body
     */
    public function setBody(array $body): void
    {
        $this->body = $body;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters ?? [];
    }

    /**
     * @param array $parameters
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token): void
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @throws YnabApiHttpException
     * @return Response
     */
    abstract public function post(): Response;

    /**
     * @throws YnabApiException
     * @return array
     */
    protected function authenticatedGet(): array
    {
        $fullUrl = sprintf('%s/%s', $this->getBase(), $this->getUrl());
        if (null !== $this->parameters) {
            $fullUrl = sprintf('%s?%s', $fullUrl, http_build_query($this->parameters));
        }

        $client = $this->getClient();
        try {
            $res = $client->request(
                'GET', $fullUrl, [
                         'headers' => [
                             'Accept'        => 'application/json',
                             'Content-Type'  => 'application/json',
                             'Authorization' => sprintf('Bearer %s', $this->getToken()),
                         ],
                     ]
            );
        } catch (Exception $e) {
            throw new YnabApiException(sprintf('GuzzleException: %s', $e->getMessage()));
        }
        if (200 !== $res->getStatusCode()) {
            throw new YnabApiException(
                sprintf('Error accessing %s. Status code is %d. Body is: %s', $fullUrl, $res->getStatusCode(), (string) $res->getBody())
            );
        }

        $body = (string) $res->getBody();
        $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        if (null === $json) {
            throw new YnabApiException(sprintf('Body is empty. Status code is %d.', $res->getStatusCode()));
        }

        return $json;
    }

    /**
     * @throws YnabApiException
     * @return array
     */
    protected function authenticatedPost(): array
    {
        $fullUrl = sprintf('%s/api/v1/%s', $this->getBase(), $this->getUrl());
        if (null !== $this->parameters) {
            $fullUrl = sprintf('%s?%s', $fullUrl, http_build_query($this->parameters));
        }
        $client  = $this->getClient();
        $options = [
            'headers'    => [
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Authorization' => sprintf('Bearer %s', $this->getToken()),
            ],
            'exceptions' => false,
            'body'       => (string) json_encode($this->getBody(), JSON_THROW_ON_ERROR, 512),
        ];

        $debugOpt = $options;
        unset($debugOpt['body']);

        $res = $client->request('POST', $fullUrl, $options);

        if (422 === $res->getStatusCode()) {
            $body = (string) $res->getBody();
            $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            if (null === $json) {
                throw new YnabApiException(sprintf('Body is empty. Status code is %d.', $res->getStatusCode()));
            }

            return $json;
        }
        if (200 !== $res->getStatusCode()) {
            throw new YnabApiException(sprintf('Status code is %d: %s', $res->getStatusCode(), (string) $res->getBody()));
        }

        $body = (string) $res->getBody();
        $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        if (null === $json) {
            throw new YnabApiException(sprintf('Body is empty. Status code is %d.', $res->getStatusCode()));
        }

        return $json;
    }

    /**
     * @return Client
     */
    private function getClient(): Client
    {
        // config here

        return new Client;
    }
}
