<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof \Illuminate\Http\Exceptions\PostTooLargeException) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'error' => 'El archivo es demasiado grande para el servidor. Por favor, revisa la configuración de post_max_size en php.ini.'
                ], 413);
            }
        }

        return parent::render($request, $exception);
    }
}
