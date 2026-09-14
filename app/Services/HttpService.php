<?php

namespace App\Services;

use Exception;
use Illuminate\Http\JsonResponse;

class HttpService
{
    public const RESPONSE_ERROR = 'error';
    public const RESPONSE_SUCCESS = 'success';

    private string $status;

    private ?string $message = '';

    private mixed $data;

    public function __construct()
    {
        $this->status = self::RESPONSE_SUCCESS;
        $this->data = (object) [];
    }

    public function error($message = '', int $httpCode = 400): JsonResponse
    {
        if ($message instanceof Exception) {
            $message = $message->getMessage();
        }

        if (empty($message)) {
            $message = match ($httpCode) {
                400 => 'Bad Request',
                401 => 'Unauthorized',
                404 => 'Not Found',
                422 => 'Unprocessable Entity',
                500 => 'Internal Server Error',
                default => ''
            };
        }

        $this->message = $message;
        $this->status = self::RESPONSE_ERROR;

        return response()->json($this->parse(), $httpCode, [
            'Content-Type' => 'application/json;charset=UTF-8',
            'Charset' => 'utf-8',
        ], JSON_UNESCAPED_UNICODE);
    }

    public function success(mixed $data = [], string $message = ''): JsonResponse
    {
        $this->message = $message;
        $this->data = $data;
        $this->status = self::RESPONSE_SUCCESS;

        return response()->json($this->parse(), 200, [
            'Content-Type' => 'application/json;charset=UTF-8',
            'Charset' => 'utf-8',
        ], JSON_UNESCAPED_UNICODE);
    }

    private function parse(): object
    {
        return (object) [
            'status' => $this->status,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }
}
