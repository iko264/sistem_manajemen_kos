<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', // prefix '/api' + middleware 'api' otomatis
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $isApi = fn (Request $request): bool => $request->is('api/*');

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e): bool => $isApi($request) || $request->expectsJson()
        );

        $exceptions->render(function (ValidationException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return ApiResponse::error('Data yang dikirim tidak valid', $e->errors(), 422);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return ApiResponse::error('Silakan login terlebih dahulu', null, 401);
            }
        });

        $forbidden = function (Request $request) use ($isApi) {
            if ($isApi($request)) {
                return ApiResponse::error('Anda tidak berhak mengakses sumber daya ini', null, 403);
            }
        };
        $exceptions->render(fn (AuthorizationException $e, Request $request) => $forbidden($request));
        $exceptions->render(fn (AccessDeniedHttpException $e, Request $request) => $forbidden($request));

        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return ApiResponse::error('Data tidak ditemukan', null, 404);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return ApiResponse::error('Endpoint tidak ditemukan', null, 404);
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return ApiResponse::error('Method tidak diizinkan untuk endpoint ini', null, 405);
            }
        });

        // Penutup: exception HTTP lain (mis. 429) dan error server tak terduga (disembunyikan saat produksi).
        $exceptions->render(function (Throwable $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            if ($e instanceof HttpExceptionInterface) {
                return ApiResponse::error($e->getMessage() ?: 'Permintaan tidak dapat diproses', null, $e->getStatusCode());
            }

            if (! config('app.debug')) {
                return ApiResponse::error('Terjadi kesalahan pada server', null, 500);
            }

            return null;
        });
    })->create();
