<?= "<?php\n" ?>

namespace <?= $namespace ?>;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="<?= $repository_class_name ?>")
 */
class <?= $class_name ?>

{
    const LIFETIME_HOURS = 24;
    const SELECTOR_LENGTH = 20; // in chars

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $selector;

    /**
     * @ORM\Column(type="string")
     */
    private $token;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $requestedAt;

    /**
     * @ORM\ManyToOne(targetEntity="<?= $user_full_class_name ?>")
     */
    private $user;

    private $plainToken;

    public function __construct(User $user)
    {
        $this->requestedAt = new \DateTimeImmutable('now');
        $this->selector = strtr(base64_encode(random_bytes(self::SELECTOR_LENGTH * 3 / 4)), '+/', '-_');
        $this->plainToken = strtr(base64_encode(random_bytes(18)), '+/', '-_');
        $this->token = password_hash($this->plainToken, PASSWORD_DEFAULT);
        $this->user = $user;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAsString(): string
    {
        if (!$this->selector || !$this->plainToken) {
            throw new \Exception('You can get PasswordResetToken as a string only immediately after creation.');
        }

        return $this->selector.$this->plainToken;
    }

    public function getUser(): <?= $user_class_name ?>

    {
        return $this->user;
    }

    public function isTokenEquals(string $token): bool
    {
        return password_verify($token, $this->token);
    }

    public function isExpired(): bool
    {
        if (($this->requestedAt->getTimestamp() + self::LIFETIME_HOURS * 3600) <= time()) {
            return true;
        }

        return false;
    }
}
