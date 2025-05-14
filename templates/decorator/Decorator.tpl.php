<?= "<?php\n" ?>

namespace <?= $class_data->getNamespace() ?>;

<?= $class_data->getUseStatements(); ?>

<?= $decorated_info->getDecorateAttributeDeclaration(); ?>

<?= $class_data->getClassDeclaration(); ?>

{
    public function __construct(
        #[AutowireDecorated]
        private readonly <?= $decorated_info->getShortNameInnerType(); ?> $inner,
    ) {
    }
<?php foreach ($decorated_info->getPublicMethods() as $method): ?>

    <?= $method->getDeclaration() ?>

    {
        <?php if ($method->isStatic() && !$class_data->hasExtends()): ?>// @TODO Implements this static method<?php endif; ?>

        <?php if ($method->isStatic() && !$class_data->hasExtends()): ?>// <?php endif; ?><?php if (!$method->isReturnVoid()): ?>return <?php endif; ?><?= ($method->isStatic()) ? 'parent::' : '$this->inner->' ; ?><?php echo $method->getName() ?>(<?= $method->getArgumentsUse() ?>);
    }
<?php endforeach; ?>
}
