<?php

namespace App\Webhook;

use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

final class RemoteServiceRequestParser extends AbstractRequestParser
{
    protected function getRequestMatcher(): RequestMatcherInterface
    {
        return new ChainRequestMatcher([
            // Add RequestMatchers to fit your needs
        ]);
    }

    /**
     * @throws JsonException
     */
    protected function doParse(Request $request, #[\SensitiveParameter] string $secret): ?RemoteEvent
    {
        // TODO: Adapt or replace the content of this method to fit your need.

        // Validate the request against $secret.
        $authToken = $request->headers->get('X-Authentication-Token');
        if ($authToken !== $secret) {
            throw new RejectWebhookException(Response::HTTP_UNAUTHORIZED, 'Invalid authentication token.');
        }

        // Validate the request payload.
        if (!$request->getPayload()->has('name')
            || !$request->getPayload()->has('id')) {
            throw new RejectWebhookException(Response::HTTP_BAD_REQUEST, 'Request payload does not contain required fields.');
        }

        // Parse the request payload and return a RemoteEvent object.
        $payload = $request->getPayload()->all();

        return new RemoteEvent(
            $payload['name'],
            $payload['id'],
            $payload,
        );
    }
}
