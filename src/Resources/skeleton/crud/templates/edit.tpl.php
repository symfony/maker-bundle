<?= $helper->getHead($base_layout_exists, 'Edit '.$entity_class_name) ?>

{% block body %}

    <h1>Edit <?= $entity_class_name ?></h1>

    {{ include('<?= $route_name ?>/_form.html.twig', {'form': form, 'button_label': 'Edit'}) }}

    <a href="{{ path('<?= $route_name ?>_index') }}">back to list</a>

    {{ include('<?= $route_name ?>/_delete_form.html.twig', {'<?= $entity_var_singular ?>': <?= $entity_var_singular ?>}) }}

{% endblock %}