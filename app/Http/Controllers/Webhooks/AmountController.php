<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Requests\AmountRequest;
use Illuminate\Http\Request;

class AmountController extends Controller
{
    public function __invoke(AmountRequest $request)
    {
        return response()->json($request->all(), 200);
    }
}
