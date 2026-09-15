<?php

namespace App\Services;

use Exception;
use Illuminate\Http\JsonResponse;

class HttpService
{
    public const RESPONSE_ERROR = 'error';
    public const RESPONSE_SUCCESS = 'success';

    public function error(
        string|Exception $message = '',
        int $httpCode = 400
    ): JsonResponse {
        if ($message instanceof Exception) {
            $message = $message->getMessage();
        }

        if ($message === '') {
            $message = match ($httpCode) {
                400 => 'Bad Request',
                401 => 'Unauthorized',
                404 => 'Not Found',
                422 => 'Unprocessable Entity',
                500 => 'Internal Server Error',
                default => 'An error occurred.',
            };
        }

        return response()->json(
            data: $this->buildResponse(
                status: self::RESPONSE_ERROR,
                message: $message,
                data: [],
            ),
            status: $httpCode,
            options: JSON_UNESCAPED_UNICODE,
        );
    }

    public function success(
        mixed $data = [],
        string $message = ''
    ): JsonResponse {
        return response()->json(
            data: $this->buildResponse(
                status: self::RESPONSE_SUCCESS,
                message: $message,
                data: $data,
            ),
            status: 200,
            options: JSON_UNESCAPED_UNICODE,
        );
    }

    private function buildResponse(
        string $status,
        string $message,
        mixed $data,
    ): array {
        return [
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ];
    }
}
