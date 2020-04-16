<?php echo $helper->getHeadPrintCode($entity_class_name.' index'); ?>

{% block body %}
    <h1><?php echo $entity_class_name ?> index</h1>

    <table class="table">
        <thead>
            <tr>
<?php foreach ($entity_fields as $field) { ?>
                <th><?php echo ucfirst($field['fieldName']) ?></th>
<?php } ?>
                <th>actions</th>
            </tr>
        </thead>
        <tbody>
        {% for <?php echo $entity_twig_var_singular ?> in <?php echo $entity_twig_var_plural ?> %}
            <tr>
<?php foreach ($entity_fields as $field) { ?>
                <td>{{ <?php echo $helper->getEntityFieldPrintCode($entity_twig_var_singular, $field) ?> }}</td>
<?php } ?>
                <td>
                    <a href="{{ path('<?php echo $route_name ?>_show', {'<?php echo $entity_identifier ?>': <?php echo $entity_twig_var_singular ?>.<?php echo $entity_identifier ?>}) }}">show</a>
                    <a href="{{ path('<?php echo $route_name ?>_edit', {'<?php echo $entity_identifier ?>': <?php echo $entity_twig_var_singular ?>.<?php echo $entity_identifier ?>}) }}">edit</a>
                </td>
            </tr>
        {% else %}
            <tr>
                <td colspan="<?php echo (count($entity_fields) + 1) ?>">no records found</td>
            </tr>
        {% endfor %}
        </tbody>
    </table>

    <a href="{{ path('<?php echo $route_name ?>_new') }}">Create new</a>
{% endblock %}
