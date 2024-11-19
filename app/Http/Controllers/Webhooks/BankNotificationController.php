<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Requests\AmountRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AmountController extends Controller
{
    public function __invoke(Request $request)
    {
        Log::channel('single')->debug($request->all());
        return response()->noContent(200);
    }
}
