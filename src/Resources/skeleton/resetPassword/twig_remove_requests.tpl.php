<form method="post" action="{{ path('app_reset_password_remove', {'id': user.id}) }}" onsubmit="return confirm('Are you sure you want to delete this item?');">
    <input type="hidden" name="_token" value="{{ csrf_token('remove' ~ user.id) }}">
    <button class="btn">Delete</button>
</form>
