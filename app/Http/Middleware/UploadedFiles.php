<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Session\Constants;
use Closure;
use Illuminate\Http\Request;

/**
 * Class UploadedFiles.
 */
class UploadedFiles
{
    /**
     * Check if the user has already uploaded files in this session. If so, continue to budget selection.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (session()->has(Constants::UPLOAD_CONFIG_FILE)) {
            return redirect()->route('import.budgets.index');
        }

        return $next($request);
    }
}
