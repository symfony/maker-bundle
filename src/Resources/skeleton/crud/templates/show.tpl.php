<?= $helper->getHeadPrintCode($entity_class_name) ?>

{% block stylesheets %}
<style>
    .example-wrapper { margin: 1em auto; max-width: 1018px; width: 95%; }
    .example-wrapper table { width: 100%; border-collapse: collapse; margin-bottom: 1em; }
    .example-wrapper th { border-right: 1px solid #999; padding: 2px 4px; text-align: left; width: 8em; }
    .example-wrapper tbody td { padding: 2px 4px; }
    .example-actions form, .example-actions a { display: inline-block; margin: 0 2px; }
</style>
{% endblock %}

{% block body %}
    <div class="example-wrapper">
        <h1><?= $entity_class_name ?></h1>

        <table>
            <tbody>
<?php foreach ($entity_fields as $field): ?>
                <tr>
                    <th><?= ucfirst($field['fieldName']) ?></th>
                    <td>{{ <?= $helper->getEntityFieldPrintCode($entity_var_singular, $field) ?> }}</td>
                </tr>
<?php endforeach; ?>
            </tbody>
        </table>

        <div class="example-actions">
            <a href="{{ path('<?= $route_name ?>_index') }}">back to list</a>

            <a href="{{ path('<?= $route_name ?>_edit', {'<?= $entity_identifier ?>': <?= $entity_var_singular ?>.<?= $entity_identifier ?>}) }}">edit</a>

            {{ include('<?= $route_name ?>/_delete_form.html.twig', {'<?= $entity_var_singular ?>': <?= $entity_var_singular ?>}) }}
        </div>
    </div>
{% endblock %}