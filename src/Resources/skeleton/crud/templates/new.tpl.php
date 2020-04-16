<?php echo $helper->getHeadPrintCode('New '.$entity_class_name) ?>

{% block body %}
    <h1>Create new <?php echo $entity_class_name ?></h1>

    {{ include('<?php echo $route_name ?>/_form.html.twig') }}

    <a href="{{ path('<?php echo $route_name ?>_index') }}">back to list</a>
{% endblock %}
