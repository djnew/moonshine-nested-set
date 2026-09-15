<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class FakeResponseFactory implements ResponseFactory
{
    public function make($content = '', $status = 200, array $headers = [])
    {
        return new Response($content, $status, $headers);
    }

    public function noContent($status = 204, array $headers = [])
    {
        return new Response('', $status, $headers);
    }

    public function view($view, $data = [], $status = 200, array $headers = [])
    {
        return new Response('', $status, $headers);
    }

    public function json($data = [], $status = 200, array $headers = [], $options = 0)
    {
        return new JsonResponse($data, $status, $headers, $options);
    }

    public function jsonp($callback, $data = [], $status = 200, array $headers = [], $options = 0)
    {
        return $this->json($data, $status, $headers, $options)->setCallback($callback);
    }

    public function eventStream($callback, array $headers = [], $endStreamWith = '</stream>')
    {
        return new StreamedResponse($callback, 200, $headers);
    }

    public function stream($callback, $status = 200, array $headers = [])
    {
        return new StreamedResponse($callback, $status, $headers);
    }

    public function streamJson($data, $status = 200, $headers = [], $encodingOptions = 15)
    {
        return new StreamedResponse(
            static function () use ($data, $encodingOptions): void {
                echo json_encode($data, $encodingOptions);
            },
            $status,
            $headers
        );
    }

    public function streamDownload($callback, $name = null, array $headers = [], $disposition = 'attachment')
    {
        return new StreamedResponse($callback, 200, $headers);
    }

    public function download($file, $name = null, array $headers = [], $disposition = 'attachment')
    {
        return new BinaryFileResponse($file, 200, $headers, true, $disposition);
    }

    public function file($file, array $headers = [])
    {
        return new BinaryFileResponse($file, 200, $headers);
    }

    public function redirectTo($path, $status = 302, $headers = [], $secure = null)
    {
        throw new RuntimeException('Redirect responses are not used in package tests.');
    }

    public function redirectToRoute($route, $parameters = [], $status = 302, $headers = [])
    {
        throw new RuntimeException('Redirect responses are not used in package tests.');
    }

    public function redirectToAction($action, $parameters = [], $status = 302, $headers = [])
    {
        throw new RuntimeException('Redirect responses are not used in package tests.');
    }

    public function redirectGuest($path, $status = 302, $headers = [], $secure = null)
    {
        throw new RuntimeException('Redirect responses are not used in package tests.');
    }

    public function redirectToIntended($default = '/', $status = 302, $headers = [], $secure = null)
    {
        throw new RuntimeException('Redirect responses are not used in package tests.');
    }
}
