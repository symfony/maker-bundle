<?= "<?php\n" ?>

namespace <?= $class_data->getNamespace(); ?>;

<?= $class_data->getUseStatements(); ?>

<?= $class_data->getClassDeclaration(); ?>

{
<?= $generator->generateRouteForControllerMethod($route_path, $route_name); ?>
    public function <?= $method_name ?>(): <?php if ($with_template) { ?>Response<?php } else { ?>JsonResponse<?php } ?>

    {
<?php if ($with_template) { ?>
        return $this->render('<?= $template_name ?>', [
            'controller_name' => '<?= $class_data->getClassName() ?>',
        ]);
<?php } else { ?>
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => '<?= $relative_path; ?>',
        ]);
<?php } ?>
    }
}
