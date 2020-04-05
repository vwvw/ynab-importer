<?php


declare(strict_types=1);

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use App\Http\Middleware\BudgetComplete;
use App\Http\Request\BudgetPostRequest;
use App\Services\Configuration\Configuration;
use App\Services\Session\Constants;
use App\Ynab\Request\GetBudgetsRequest;
use App\Ynab\Response\GetBudgetsResponse;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Class BudgetController.
 */
class BudgetController extends Controller
{
    /**
     * StartController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        app('view')->share('pageTitle', 'Budget selection');
        $this->middleware(BudgetComplete::class)->except('download');
    }

    /**
     * @throws \App\Exceptions\YnabApiHttpException
     * @return Factory|View
     */
    public function index()
    {
        // get config from session
        $configuration = Configuration::fromArray([]);
        if (session()->has(Constants::CONFIGURATION)) {
            $configuration = Configuration::fromArray(session()->get(Constants::CONFIGURATION));
        }
        // if config says to skip it, skip it:
        if (null !== $configuration && true === $configuration->isSkipBudgetSelection()) {
            // skipForm, go to YNAB download
            return redirect()->route('import.configure.index');
        }

        app('log')->debug(sprintf('Now at %s', __METHOD__));
        $mainTitle = 'Select your budget from YNAB';
        $subTitle  = 'Select the YNAB budget';

        $uri     = (string) config('ynab.api_uri');
        $token   = (string) config('ynab.api_code');
        $request = new GetBudgetsRequest($uri, $token);
        /** @var GetBudgetsResponse $budgets */
        $budgets = $request->get();

        return view('import.budgets.index', compact('mainTitle', 'subTitle', 'budgets'));
    }

    /**
     * @param BudgetPostRequest $request
     *
     * @return RedirectResponse
     */
    public function postIndex(BudgetPostRequest $request)
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));
        $fromRequest = $request->getAll();

        // get config from session
        $configuration = Configuration::fromArray([]);
        if (session()->has(Constants::CONFIGURATION)) {
            $configuration = Configuration::fromArray(session()->get(Constants::CONFIGURATION));
        }

        // update config
        $configuration->setBudgets($fromRequest['budgets']);
        $configuration->setSkipBudgetSelection($fromRequest['skip_budget_selection']);

        // store config in session.
        session()->put(Constants::CONFIGURATION, $configuration->toArray());

        // set budget selection done.
        session()->put(Constants::BUDGET_COMPLETE_INDICATOR, true);

        // redirect to import things?
        return redirect()->route('import.configure.index');
    }
}

