<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $routeNames = [
            'home',
            'features',
            'pricing',
            'seo.wisp-management-software',
            'seo.wisp-crm',
            'seo.mikrotik-isp-software',
            'alternatives.splynx',
            'alternatives.sonar',
            'changelog',
            'contact.show',
        ];

        return response()
            ->view('sitemap', ['routeNames' => $routeNames])
            ->header('Content-Type', 'application/xml');
    }
}
