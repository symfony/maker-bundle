<?PHP
if (null === $user) {
    return $this->json(['message' => 'missing credentials'], Response::HTTP_UNAUTHORIZED);
}

return $this->json(['user' => $user->getUserIdentifier()]);
