<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class InvalidFlows
{
    public function json(): JsonResponse
    {
        return new JsonResponse([], 200);
    }

    public function string(): string
    {
        return 'hello world';
    }
}
