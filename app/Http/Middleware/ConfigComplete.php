<?php

declare(strict_types=1);


namespace App\Http\Middleware;

use App\Services\Session\Constants;
use Closure;
use Illuminate\Http\Request;

/**
 * Class ConfigComplete.
 */
class ConfigComplete
{
    /**
     * Check if the user has already uploaded files in this session. If so, continue to configuration.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (session()->has(Constants::CONFIG_COMPLETE_INDICATOR) && true === session()->get(Constants::CONFIG_COMPLETE_INDICATOR)) {
            return redirect()->route('import.download.index');
        }

        return $next($request);
    }
}
