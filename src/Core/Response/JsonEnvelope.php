<?php

namespace Devkit\Core\Response;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * JSON response envelope. Wraps any payload (array, scalar, null) in the
 * canonical `{"code": <int>, "message": <string>, "data": <mixed>}` shape
 * and returns it as a PSR-7 ResponseInterface built via injected PSR-17
 * factories.
 *
 * Per openspec/specs/devkit-http-foundation/spec.md scenarios:
 *   - success(['id' => 1]) -> {"code":0,"message":"OK","data":{"id":1}}, 200
 *   - failure('invalid input', 422, ['field' => 'email'])
 *           -> {"code":422,"message":"invalid input","data":{"field":"email"}}, 422
 *
 * Pure PHP — depends on PSR-7 + PSR-17 only. No Illuminate imports.
 * The Laravel adapter (Wave 5) converts these responses to
 * Illuminate\Http\JsonResponse when callers want Laravel-native return.
 */
class JsonEnvelope
{
    /**
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * @var StreamFactoryInterface
     */
    protected $streamFactory;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * Wrap data in the success envelope shape and return a 200 response.
     *
     * @param  mixed  $data
     * @param  string  $message
     * @return ResponseInterface
     */
    public function success($data = null, $message = 'OK')
    {
        return $this->build(200, 0, $message, $data);
    }

    /**
     * Wrap data in the failure envelope shape and return a response whose
     * HTTP status matches the envelope's `code` field.
     *
     * @param  string  $message
     * @param  int  $code  Used as both the envelope `code` and the HTTP status.
     * @param  mixed  $data
     * @return ResponseInterface
     */
    public function failure($message, $code = 500, $data = null)
    {
        return $this->build((int) $code, (int) $code, (string) $message, $data);
    }

    /**
     * @param  int  $statusCode
     * @param  int  $envelopeCode
     * @param  string  $message
     * @param  mixed  $data
     * @return ResponseInterface
     */
    protected function build($statusCode, $envelopeCode, $message, $data)
    {
        $body = json_encode(array(
            'code' => $envelopeCode,
            'message' => $message,
            'data' => $data,
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $stream = $this->streamFactory->createStream($body === false ? '' : $body);
        $response = $this->responseFactory->createResponse($statusCode);

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody($stream);
    }
}
