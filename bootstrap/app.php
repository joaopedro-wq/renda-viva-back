<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API-only — nunca existe rota "login" pra redirecionar (não há
        // `web.php` de verdade). Sem isso, o `Authenticate` padrão do Laravel
        // tenta `route('login')` quando a requisição não pede JSON (curl sem
        // `Accept: application/json`, por exemplo) e crasha com 500
        // (RouteNotFoundException) antes até de chegar num handler de exceção.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(fn (AuthenticationException $e, Request $request) => response()->json(
            ['message' => 'Não autenticado.'],
            401
        ));
    })->create();
