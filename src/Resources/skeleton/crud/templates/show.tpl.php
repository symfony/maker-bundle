{% extends 'base.html.twig' %}

{% block body %}
    <h1><?= $entity_class_name ?></h1>

    <table>
    <?php foreach ($entity_fields as $field): ?>
        <tr>
            <th><?= ucfirst($field['fieldName']) ?></th>
            <td>{{ <?= $entity_var_singular ?>.<?= $field['fieldName'] ?> }}</td>
        </tr>
    <?php endforeach; ?>
    </table>

    <ul>
        <li>
            <a href="{{ path('<?= $route_name ?>_index') }}">back to list</a>
        </li>
        <li>
            <a href="{{ path('<?= $route_name ?>_edit', {'<?= $entity_identifier ?>':<?= $entity_var_singular ?>.<?= $entity_identifier ?>}) }}">edit</a>
        </li>
        <li>
            {{ form_start(delete_form) }}
            <input type="submit" value="Delete">
            {{ form_end(delete_form) }}
        </li>
    </ul>
{% endblock %}