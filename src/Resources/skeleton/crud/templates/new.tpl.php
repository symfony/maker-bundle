{% extends 'base.html.twig' %}

{% block body %}
    <h1>Create new <?= $entity_class_name; ?></h1>

    {% include '<?= $route_name?>/_form.html.twig' with {'form': form} only %}

{% endblock %}
