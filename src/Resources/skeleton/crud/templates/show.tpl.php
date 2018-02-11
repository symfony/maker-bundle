<?= $helper->getHead($base_layout_exists, $entity_class_name); ?>

<?= $helper->getBodyStart($base_layout_exists); ?>

    <h1><?= $entity_class_name; ?></h1>

    <table><?php foreach ($entity_fields as $field): ?>

        <tr>
            <th><?= ucfirst($field['fieldName']); ?></th>
            <td>{{ <?= $helper->getEntityFieldPrintCode($entity_var_singular, $field); ?> }}</td>
        </tr>
    <?php endforeach; ?></table>

    <a href="{{ path('<?= $route_name; ?>_index') }}">back to list</a>

    <a href="{{ path('<?= $route_name; ?>_edit', {'<?= $entity_identifier; ?>':<?= $entity_var_singular; ?>.<?= $entity_identifier; ?>}) }}">edit</a>

    {% include '<?= $route_name; ?>/_delete_form.html.twig' with {'identifier': <?= $entity_var_singular; ?>.<?= $entity_identifier; ?>} only %}

<?= $helper->getBodyEnd($base_layout_exists); ?>
