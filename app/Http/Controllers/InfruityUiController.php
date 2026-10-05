<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InfruityUiController extends Controller
{
    public function show(?string $page = null): BinaryFileResponse
    {
        $root = realpath(resource_path('infruity-ui'));
        $file = $root ? realpath($root . DIRECTORY_SEPARATOR . ($page ?: 'index.html')) : false;

        if (! $file || ! str_starts_with($file, $root . DIRECTORY_SEPARATOR) || pathinfo($file, PATHINFO_EXTENSION) !== 'html') {
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()->file($file, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
