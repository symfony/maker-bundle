<?php

use Symfony\Bundle\MakerBundle\Str;

?>
<?= $helper->getHeadPrintCode($entity_class_name) ?>

{% block body %}
    <h1><?= $entity_class_name ?></h1>

    <table class="table">
        <tbody>
<?php foreach ($entity_fields as $field): ?>
            <tr>
                <th><?= Str::asHumanWords($field['fieldName']) ?></th>
                <td>{{ <?= $helper->getEntityFieldPrintCode($entity_twig_var_singular, $field) ?> }}</td>
            </tr>
<?php endforeach; ?>
        </tbody>
    </table>

    <a href="{{ path('<?= $route_name ?>_index') }}">Back to list</a>

    <a href="{{ path('<?= $route_name ?>_edit', {'<?= $entity_identifier ?>': <?= $entity_twig_var_singular ?>.<?= $entity_identifier ?>}) }}">Edit</a>

    {{ include('<?= $route_name ?>/_delete_form.html.twig') }}
{% endblock %}
