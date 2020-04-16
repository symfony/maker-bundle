<form method="post" action="{{ path('<?php echo $route_name ?>_delete', {'<?php echo $entity_identifier ?>': <?php echo $entity_twig_var_singular ?>.<?php echo $entity_identifier ?>}) }}" onsubmit="return confirm('Are you sure you want to delete this item?');">
    <input type="hidden" name="_method" value="DELETE">
    <input type="hidden" name="_token" value="{{ csrf_token('delete' ~ <?php echo $entity_twig_var_singular ?>.<?php echo $entity_identifier ?>) }}">
    <button class="btn">Delete</button>
</form>
