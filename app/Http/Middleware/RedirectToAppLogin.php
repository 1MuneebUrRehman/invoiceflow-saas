<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Illuminate\Http\Request;

/**
 * The panel has no Filament login page — guests are sent to the
 * application's own login, which also handles tenant registration.
 */
class RedirectToAppLogin extends FilamentAuthenticate
{
    /**
     * @param  Request  $request
     */
    protected function redirectTo($request): ?string
    {
        return route('login');
    }
}
