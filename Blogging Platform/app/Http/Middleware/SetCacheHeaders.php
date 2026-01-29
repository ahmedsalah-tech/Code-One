<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetCacheHeaders
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
        $response = $next($request);

        if ($this->isStaticAsset($request)) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000');
        }

        return $response;
    }

    protected function isStaticAsset(Request $request)
    {
        // Add extensions of static assets you want to cache
        $staticFileExtensions = ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico'];

        $path = $request->getPathInfo();
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return in_array($extension, $staticFileExtensions);
    }
}
