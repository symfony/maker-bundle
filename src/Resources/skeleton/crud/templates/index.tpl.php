<?= $helper->getHeadPrintCode($entity_class_name.' index'); ?>

{% block stylesheets %}
<style>
    .example-wrapper { margin: 1em auto; max-width: 1018px; width: 95%; }
    .example-wrapper table { width: 100%; border-collapse: collapse; margin-bottom: 1em; }
    .example-wrapper thead th { border-bottom: 1px solid #999; padding: 2px 4px; text-align: left; }
    .example-wrapper tbody tr:nth-child(odd) { background-color: #F5F5F5; }
    .example-wrapper tbody td { padding: 2px 4px; }
    .example-actions form, .example-actions a { display: inline-block; margin: 0 2px; }
</style>
{% endblock %}

{% block body %}
    <div class="example-wrapper">
        <h1><?= $entity_class_name ?> index</h1>
        <table>
            <thead>
                <tr>
<?php foreach ($entity_fields as $field): ?>
                    <th><?= ucfirst($field['fieldName']) ?></th>
<?php endforeach; ?>
                    <th>actions</th>
                </tr>
            </thead>
            <tbody>
            {% for <?= $entity_var_singular ?> in <?= $entity_var_plural ?> %}
                <tr>
<?php foreach ($entity_fields as $field): ?>
                    <td>{{ <?= $helper->getEntityFieldPrintCode($entity_var_singular, $field) ?> }}</td>
<?php endforeach; ?>
                    <td>
                        <a href="{{ path('<?= $route_name ?>_show', {'<?= $entity_identifier ?>': <?= $entity_var_singular ?>.<?= $entity_identifier ?>}) }}">show</a>
                        <a href="{{ path('<?= $route_name ?>_edit', {'<?= $entity_identifier ?>': <?= $entity_var_singular ?>.<?= $entity_identifier ?>}) }}">edit</a>
                    </td>
                </tr>
            {% else %}
                <tr>
                    <td colspan="<?= (count($entity_fields) + 1) ?>">no records found</td>
                </tr>
            {% endfor %}
            </tbody>
        </table>

        <div class="example-actions">
            <a href="{{ path('<?= $route_name ?>_new') }}">Create new</a>
        </div>
    </div>
{% endblock %}