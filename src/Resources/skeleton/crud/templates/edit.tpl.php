<?= $helper->getHead($base_layout_exists, 'Edit '.$entity_class_name); ?>

<?= $helper->getBodyStart($base_layout_exists); ?>

    <h1>Edit <?= $entity_class_name; ?></h1>

    {% include '<?= $route_name; ?>/_form.html.twig' with {'form': form, 'button_label': 'Edit'} only %}

    <a href="{{ path('<?= $route_name; ?>_index') }}">back to list</a>

    {% include '<?= $route_name; ?>/_delete_form.html.twig' with {'identifier': <?= $entity_var_singular; ?>.<?= $entity_identifier; ?>} only %}

<?= $helper->getBodyEnd($base_layout_exists); ?>
