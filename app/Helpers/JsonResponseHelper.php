<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class JsonResponseHelper
{
    private ResponseStatus $status = ResponseStatus::SUCCESS;
    private ?array $data = null;
    private int $httpStatus = Response::HTTP_OK;

    public function make(): JsonResponse
    {
        return response()->json([
            'status' => $this->status->value,
            'data' => $this->data,
        ], status: $this->httpStatus);
    }

    public function success(): self
    {
        $this->status = ResponseStatus::SUCCESS;
        return $this;
    }

    public function fail(): self
    {
        $this->status = ResponseStatus::FAIL;
        return $this;
    }

    public function withMessage(string $message): self
    {
        if ($this->data === null) {
            $this->data = [];
        }
        $this->data['message'] = $message;
        return $this;
    }

    public function withData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function withStatus(int $httpStatus): self
    {
        // force status to match HTTP response code
        if ($httpStatus === 200) {
            $this->success();
        }
        if ($httpStatus >= 400) {
            $this->fail();
        }

        $this->httpStatus = $httpStatus;
        return $this;
    }

    public function ok(): JsonResponse
    {
        $this->withStatus(Response::HTTP_OK);
        return $this->make();
    }

    public function badRequest(): JsonResponse
    {
        $this->withStatus(Response::HTTP_BAD_REQUEST);
        return $this->make();
    }

    public function unauthorized(): JsonResponse
    {
        $this->withStatus(Response::HTTP_UNAUTHORIZED);
        return $this->make();
    }

    public function serverError(): JsonResponse
    {
        $this->withStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
        return $this->make();
    }
}
