<?php echo $helper->getHeadPrintCode($entity_class_name) ?>

{% block body %}
    <h1><?php echo $entity_class_name ?></h1>

    <table class="table">
        <tbody>
<?php foreach ($entity_fields as $field) { ?>
            <tr>
                <th><?php echo ucfirst($field['fieldName']) ?></th>
                <td>{{ <?php echo $helper->getEntityFieldPrintCode($entity_twig_var_singular, $field) ?> }}</td>
            </tr>
<?php } ?>
        </tbody>
    </table>

    <a href="{{ path('<?php echo $route_name ?>_index') }}">back to list</a>

    <a href="{{ path('<?php echo $route_name ?>_edit', {'<?php echo $entity_identifier ?>': <?php echo $entity_twig_var_singular ?>.<?php echo $entity_identifier ?>}) }}">edit</a>

    {{ include('<?php echo $route_name ?>/_delete_form.html.twig') }}
{% endblock %}
