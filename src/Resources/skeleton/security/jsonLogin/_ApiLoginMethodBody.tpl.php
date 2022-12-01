<?PHP
if (null === $user) {
    return $this->json(['message' => 'missing credentials'], Response::HTTP_UNAUTHORIZED);
}

/** @TODO Somehow create the API Token for your $user */
$token = 'Fake API Token';

return $this->json(['user' => $user->getUserIdentifier(), 'token' => $token]);
