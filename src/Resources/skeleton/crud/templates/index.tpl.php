{% extends 'base.html.twig' %}

{% block body %}
    <h1><?= $entity_class_name ?> index</h1>
    <table>
        <tr>
            <?php foreach ($entity_fields as $field): ?><th><?= ucfirst($field['fieldName']) ?></th>
            <?php endforeach; ?>
            <th>actions</th>
        </tr>
        {% for <?= $entity_var_singular ?> in <?= $entity_var_plural ?> %}
            <tr>
                <?php foreach ($entity_fields as $field): ?><td>{{ <?= $entity_var_singular ?>.<?= $field['fieldName'] ?> }}</td>
                <?php endforeach; ?>
                <td>
                    <a href="{{ path('<?= $route_name ?>_show', {'<?= $entity_identifier ?>':<?= $entity_var_singular ?>.<?= $entity_identifier ?>}) }}">show</a>
                    <a href="{{ path('<?= $route_name ?>_edit', {'<?= $entity_identifier ?>':<?= $entity_var_singular ?>.<?= $entity_identifier ?>}) }}">edit</a>
                </td>
            </tr>
        {% else %}
            <tr>
                <td colspan="<?= (count($entity_fields) + 1) ?>">no records found</td>
            </tr>
        {% endfor %}
    </table>
    <a href="{{ path('<?= $route_name ?>_new') }}">Create new</a>
{% endblock %}