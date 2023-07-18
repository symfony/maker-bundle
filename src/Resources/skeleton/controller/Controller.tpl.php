<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>
<?php if ($is_standalone) { ?>

#[AsController]
<?php } ?>
class <?= $class_name; ?><?= !$is_standalone ? ' extends AbstractController': '' ?>
{
<?php if ($is_standalone && $with_template) { ?>
    public function __construct(
        private Environment $twig,
    ) {
    }

<?php } ?>
<?= $generator->generateRouteForControllerMethod($route_path, $route_name); ?>
    public function <?= $method_name ?>(): <?php if ($with_template) { ?>Response<?php } else { ?>JsonResponse<?php } ?>

    {
<?php if ($with_template) { ?>
        <?php if ($is_standalone) { ?>
        return new Response($this->twig->render('<?= $template_name ?>', [
            'controller_name' => '<?= $class_name ?>',
        ]));
        <?php } else { ?>
        return $this->render('<?= $template_name ?>', [
            'controller_name' => '<?= $class_name ?>',
        ]);
        <?php } ?>
<?php } else { ?>
        <?php if ($is_standalone) { ?>
        return new JsonResponse([
            'message' => 'Welcome to your new controller!',
            'path' => '<?= $relative_path; ?>',
        ]);
        <?php } else { ?>
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => '<?= $relative_path; ?>',
        ]);
        <?php } ?>
<?php } ?>
    }
}
