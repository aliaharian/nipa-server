<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class translateable
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check header request and determine localizaton
        $local = ($request->hasHeader('language')) ? $request->header('language') : 'fa';
        // set laravel localization
        app()->setLocale($local);
        // continue request
        return $next($request);
    }
}
