<?= $helper->getHead($base_layout_exists, 'New '.$entity_class_name); ?>

<?= $helper->getBodyStart($base_layout_exists); ?>

    <h1>Create new <?= $entity_class_name; ?></h1>

    {% include '<?= $route_name; ?>/_form.html.twig' with {'form': form} only %}

<?= $helper->getBodyEnd($base_layout_exists); ?>
