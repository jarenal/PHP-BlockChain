<?php

namespace Jarenal\Controller;

use React\Http\Response;

class WebController
{
    public function index() {
        return new Response(
            200,
            array(
                'Content-Type' => 'text/html'
            ),
            file_get_contents(__DIR__."/../../templates/index.html")
        );
    }

    public function explorer() {
        return new Response(
            200,
            array(
                'Content-Type' => 'text/html'
            ),
            file_get_contents(__DIR__."/../../templates/explorer.html")
        );
    }
}