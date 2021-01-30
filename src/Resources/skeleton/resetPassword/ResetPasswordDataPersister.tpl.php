<?= "<?php\n" ?>

namespace <?= $namespace ?>;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use App\Dto\ResetPasswordInput;
use <?= $user_full_class_name ?>;
use App\Message\SendResetPasswordMessage;
use <?= $repository_full_class_name ?>;
use Symfony\Component\Messenger\MessageBusInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class <?= $class_name ?> implements ContextAwareDataPersisterInterface
{
    private <?= $use_typed_properties ? 'DataPersisterInterface ' : null ?> $decoratedDataPersister;
    private <?= $use_typed_properties ? sprintf('%s %s', $repository_class_name, $repository_var) : $repository_var ?>;
    private <?= $use_typed_properties ? 'ResetPasswordHelperInterface ' : null ?>$resetPasswordHelper;
    private <?= $use_typed_properties ? 'MessageBusInterface ' : null ?>$messageBus;

    public function __construct(DataPersisterInterface $decoratedDataPersister, <?= $repository_class_name ?> <?= $repository_var ?>, ResetPasswordHelperInterface $resetPasswordHelper, MessageBusInterface $messageBus)
    {
        $this->decoratedDataPersister = $decoratedDataPersister;
        $this-><?= $repository_property_var ?> = <?= $repository_var?>;
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

        return;
    }

    public function remove($data, array $context = []): void
    {
        $this->decoratedDataPersister->remove($data);
    }
}
