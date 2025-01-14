<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontReport([
            \App\Exceptions\SilentVerifyOrderTimeoutJobException::class
        ]);

        $exceptions->reportable(function (\Throwable $e) {
            $trace = collect($e->getTrace())->take(5);

            Log::channel('telegram')->info('Error', [
                'message' => $e->getMessage(),
                'trace' => $trace->toJson()
            ]);
        });

    })->create();
