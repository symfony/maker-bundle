<?= "<?php\n" ?>

namespace <?= $namespace ?>;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use App\Dto\ResetPasswordInput;
use App\Entity\User;
use App\Message\SendResetPasswordMessage;
use App\Repository\UserRepository;
use Symfony\Component\Messenger\MessageBusInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class <?= $class_name ?> implements ContextAwareDataPersisterInterface
{
    private DataPersisterInterface $decoratedDataPersister;
    private UserRepository $userRepository;
    private ResetPasswordHelperInterface $resetPasswordHelper;
    private MessageBusInterface $messageBus;

    public function __construct(DataPersisterInterface $decoratedDataPersister, UserRepository $userRepository, ResetPasswordHelperInterface $resetPasswordHelper, MessageBusInterface $messageBus)
    {
        $this->decoratedDataPersister = $decoratedDataPersister;
        $this->userRepository = $userRepository;
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->messageBus = $messageBus;
    }

    public function supports($data, array $context = []): bool
    {
        return $data instanceof ResetPasswordInput;
    }

    /**
     * @param ResetPasswordInput $data
     */
    public function persist($data, array $context = []): void
    {
        $user = $this->userRepository->findOneBy(['email' => $data->email]);

        if (!$user instanceof User) {
            return;
        }

        $token = $this->resetPasswordHelper->generateResetToken($user);

        $this->messageBus->dispatch(new SendResetPasswordMessage($user->getEmail(), $token));

        return;
    }

    public function remove($data, array $context = []): void
    {
        $this->decoratedDataPersister->remove($data);
    }
}
