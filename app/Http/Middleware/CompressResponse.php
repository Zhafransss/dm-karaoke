<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * gzip for HTML/XML/JSON: the shared host does not compress dynamic PHP output, and page weight
 * affects load speed (a ranking signal). Skips anything already encoded and small responses.
 */
class CompressResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse
            || $response->headers->has('Content-Encoding') || ! function_exists('gzencode')
            || ! str_contains((string) $request->header('Accept-Encoding'), 'gzip')
            || ! preg_match('#^(text/|application/(xml|json))#', (string) $response->headers->get('Content-Type'))) {
            return $response;
        }
        $content = $response->getContent();
        if ($content === false || strlen($content) < 1024 || ($gz = gzencode($content, 6)) === false) {
            return $response;
        }

        $response->setContent($gz);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', (string) strlen($gz));
        $response->headers->set('Vary', trim(($response->headers->get('Vary') ? $response->headers->get('Vary').', ' : '').'Accept-Encoding'));

        return $response;
    }
}
