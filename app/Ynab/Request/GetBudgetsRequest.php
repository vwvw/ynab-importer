<?php
declare(strict_types=1);

namespace App\Ynab\Request;

use App\Exceptions\YnabApiException;
use App\Exceptions\YnabApiHttpException;
use App\Ynab\Response\GetBudgetsResponse;
use App\Ynab\Response\Response;
use App\Ynab\Response\UserInformationResponse;
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
        $this->setUri('budgets');
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