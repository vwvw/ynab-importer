<?php
/**
 * TokenController.php
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

declare(strict_types=1);


namespace App\Http\Controllers;

use App\Exceptions\ImportException;
use App\Exceptions\YnabApiHttpException;
use App\Ynab\Request\UserInformationRequest;
use App\Ynab\Response\UserInformationResponse;
use GrumpyDictator\FFIIIApiSupport\Exceptions\ApiHttpException;
use GrumpyDictator\FFIIIApiSupport\Request\SystemInformationRequest;
use GrumpyDictator\FFIIIApiSupport\Response\SystemInformationResponse;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class TokenController.
 */
class TokenController extends Controller
{
    /**
     * Check if the Firefly III API responds properly.
     *
     * @throws YnabApiHttpException
     * @return JsonResponse
     */
    public function doValidate(): JsonResponse
    {
        $response = ['result' => 'OK', 'message' => null];
        $token    = (string) config('ynab.access_token');
        $uri      = (string) config('ynab.uri');
        app('log')->debug(sprintf('Going to try and access %s', $uri));
        $request = new SystemInformationRequest($uri, $token);
        try {
            /** @var SystemInformationResponse $result */
            $result = $request->get();
        } catch (ApiHttpException $e) {
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());
            $response = ['result' => 'NOK', 'message' => $e->getMessage()];
        }

        if (isset($result)) {
            $minimum = config('ynab.minimum_version');
            $compare = version_compare($minimum, $result->version);
            if (1 === $compare) {
                $errorMessage = sprintf('Your Firefly III version %s is below the minimum required version %s', $result->version, $minimum);
                $response     = ['result' => 'NOK', 'message' => $errorMessage];
            }
        }

        $uri     = (string) config('ynab.api_uri');
        $token   = (string) config('ynab.api_code');
        $request = new UserInformationRequest($uri, $token);
        /** @var UserInformationResponse $result */
        $result = $request->get();
        if ('' === $result->id) {
            $errorMessage = sprintf('No user ID from YNAB');
            $response     = ['result' => 'NOK', 'message' => $errorMessage];
        }

        return response()->json($response);
    }

    /**
     * Same thing but not over JSON.
     *
     * @throws YnabApiHttpException
     * @return Factory|RedirectResponse|Redirector|View
     */
    public function index()
    {
        $token = (string) config('ynab.access_token');
        $uri   = (string) config('ynab.uri');
        app('log')->debug(sprintf('Going to try and access %s', $uri));
        $request      = new SystemInformationRequest($uri, $token);
        $errorMessage = 'No error message.';
        $isError      = false;
        $result       = null;
        $compare      = 1;
        try {
            /** @var SystemInformationResponse $result */
            $result = $request->get();
        } catch (ApiHttpException $e) {
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());
            $errorMessage = $e->getMessage();
            $isError      = true;
        }

        if (false === $isError) {
            $minimum = config('ynab.minimum_version');
            $compare = version_compare($minimum, $result->version);
        }
        if (false === $isError && 1 === $compare) {
            $errorMessage = sprintf('Your Firefly III version %s is below the minimum required version %s', $result->version, $minimum);
            $isError      = true;
        }

        $uri   = (string) config('ynab.api_uri');
        $token = (string) config('ynab.api_code');

        $request = new UserInformationRequest($uri, $token);
        /** @var UserInformationResponse $result */
        $result = $request->get();
        if ('' === $result->id) {
            $isError      = true;
            $errorMessage = 'No user ID from YNAB';
        }

        if (false === $isError) {
            return redirect(route('index'));
        }
        $pageTitle = 'Token error';

        return view('token.index', compact('errorMessage', 'pageTitle'));
    }
}
