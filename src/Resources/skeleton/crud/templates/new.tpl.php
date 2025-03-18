<?= $helper->getHeadPrintCode('New '.$entity_class_name, $templates_inherited) ?>

{% block body %}
    <h1>Create new <?= $entity_class_name ?></h1>

    {{ include('<?= $templates_path ?>/_form.html.twig') }}

    <a href="{{ path('<?= $route_name ?>_index') }}">back to list</a>
{% endblock %}
