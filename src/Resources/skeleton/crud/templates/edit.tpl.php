<?php echo $helper->getHeadPrintCode('Edit '.$entity_class_name) ?>

{% block body %}
    <h1>Edit <?php echo $entity_class_name ?></h1>

    {{ include('<?php echo $route_name ?>/_form.html.twig', {'button_label': 'Update'}) }}

    <a href="{{ path('<?php echo $route_name ?>_index') }}">back to list</a>

    {{ include('<?php echo $route_name ?>/_delete_form.html.twig') }}
{% endblock %}
