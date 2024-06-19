<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

final class <?= $class_name ?> extends AbstractRequestParser
{
    protected function getRequestMatcher(): RequestMatcherInterface
    {
        <?php if ($use_chained_requests_matcher) : ?>
        return new ChainRequestMatcher([
            <?= empty($request_matchers) ? '// Add RequestMatchers to fit your needs' : '' ?>

            <?php foreach ($request_matchers as $request_matcher) : ?>
            new <?= Symfony\Bundle\MakerBundle\Str::getShortClassName($request_matcher) ?>(<?= $request_matcher_arguments[$request_matcher] ?>),
            <?php endforeach; ?>
        ]);
        <?php else : ?>
        return new <?= Symfony\Bundle\MakerBundle\Str::getShortClassName($request_matchers[0]) ?>(<?= $request_matcher_arguments[$request_matchers[0]] ?>);
        <?php endif; ?>
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
        $payload = $request->getPayload();

        return new RemoteEvent(
            $payload->getString('name'),
            $payload->getString('id'),
            $payload->all(),
        );
    }
}
