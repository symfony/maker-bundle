<?= "<?php\n" ?>

namespace <?= $namespace ?>;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use App\Dto\ResetPasswordInput;
use <?= $user_full_class_name ?>;
use App\Message\SendResetPasswordMessage;
use <?= $repository_full_class_name ?>;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class <?= $class_name ?> implements ContextAwareDataPersisterInterface
{
    private <?= $use_typed_properties ? sprintf('%s %s', $repository_class_name, $repository_var) : $repository_var ?>;
    private <?= $use_typed_properties ? 'ResetPasswordHelperInterface ' : null ?>$resetPasswordHelper;
    private <?= $use_typed_properties ? 'MessageBusInterface ' : null ?>$messageBus;
    private <?= $use_typed_properties ? 'UserPasswordEncoderInterface ' : null ?>$userPasswordEncoder;

    public function __construct(<?= $repository_class_name ?> <?= $repository_var ?>, ResetPasswordHelperInterface $resetPasswordHelper, MessageBusInterface $messageBus, UserPasswordEncoderInterface $userPasswordEncoder)
    {
        $this-><?= $repository_property_var ?> = <?= $repository_var?>;
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->messageBus = $messageBus;
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    public function supports($data, array $context = []): bool
    {
        if (!$data instanceof ResetPasswordInput) {
            return false;
        }

        if (isset($context['collection_operation_name']) && 'post' === $context['collection_operation_name']) {
            return true;
        }

        if (isset($context['item_operation_name']) && 'put' === $context['item_operation_name']) {
            return true;
        }

        return false;
    }

    /**
     * @param ResetPasswordInput $data
     */
    public function persist($data, array $context = []): void
    {
        if (isset($context['collection_operation_name']) && 'post' === $context['collection_operation_name']) {
            $this->generateRequest($data->email);

            return;
        }

        if (isset($context['item_operation_name']) && 'put' === $context['item_operation_name']) {
            if (!$context['previous_data'] instanceof <?= $user_class_name ?>) {
                return;
            }

            $this->changePassword($context['previous_data'], $data->plainTextPassword);
        }
    }

    public function remove($data, array $context = []): void
    {
        throw new \RuntimeException('Operation not supported.');
    }

    private function generateRequest(string $email): void
    {
<?php if ('$manager' === $repository_var): ?>
        $repository = $this-><?= $repository_property_var ?>->getRepository(<?= $user_class_name ?>::class);
        $user = $repository->findOneBy(['email' => $data->email]);
<?php else: ?>
        $user = $this-><?= $repository_property_var ?>->findOneBy(['email' => $data->email]);
<?php endif; ?>

        if (!$user instanceof <?= $user_class_name?>) {
            return;
        }

        $token = $this->resetPasswordHelper->generateResetToken($user);

        $this->messageBus->dispatch(new SendResetPasswordMessage($user->getEmail(), $token));
    }

    private function changePassword(<?= $user_class_name?> $previousUser, string $plainTextPassword): void
    {
        $userId = $previousUser->getId();

<?php if ('$manager' === $repository_var): ?>
        $repository = $this-><?= $repository_property_var ?>->getRepository(<?= $user_class_name ?>::class);
        $user = $repository->find($userId);
<?php else: ?>
        $user = $this-><?= $repository_property_var ?>->find($userId);
<?php endif; ?>

        if (null === $user) {
            return;
        }

        $encoded = $this->userPasswordEncoder->encodePassword($user, $plainTextPassword);

<?php if ('$manager' === $repository_var): ?>
        $user->setPassword($encoded);

        $repository->flush();
<?php else: ?>
        $this-><?= $repository_property_var ?>->upgradePassword($user, $encoded);
<?php endif; ?>
    }
}
