<?php

/**
 * GetBudgetsRequest.php
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
 * GetBudgetsRequest.php

 */

declare(strict_types=1);

namespace App\Ynab\Request;

use App\Exceptions\YnabApiException;
use App\Exceptions\YnabApiHttpException;
use App\Ynab\Response\GetBudgetsResponse;
use App\Ynab\Response\Response;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class GetBudgetsRequest
 */
class GetBudgetsRequest extends Request
{
    /**
     * SystemInformationRequest constructor.
     *
     * @param string $url
     * @param string $token
     */
    public function __construct(string $url, string $token)
    {
        $this->setBase($url);
        $this->setToken($token);
        $this->setUrl('budgets');
    }

    /**
     * @inheritDoc
     */
    public function get(): Response
    {
        try {
            $data = $this->authenticatedGet();
        } catch (YnabApiException | GuzzleException $e) {
            throw new YnabApiHttpException($e->getMessage());
        }

        return new GetBudgetsResponse($data['data'] ?? []);
    }

    /**
     * @inheritDoc
     */
    public function post(): Response
    {
        // TODO: Implement post() method.
    }
}
