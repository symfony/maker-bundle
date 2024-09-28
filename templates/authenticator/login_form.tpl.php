{% extends 'base.html.twig' %}

{% block title %}Log in!{% endblock %}

{% block body %}
<form method="post">
    {% if error %}
        <div class="alert alert-danger">{{ error.messageKey|trans(error.messageData, 'security') }}</div>
    {% endif %}

<?php if ($logout_setup): ?>
    {% if app.user %}
        <div class="mb-3">
            You are logged in as {{ app.user.userIdentifier }}, <a href="{{ path('app_logout') }}">Logout</a>
        </div>
    {% endif %}
<?php endif; ?>

    <h1 class="h3 mb-3 font-weight-normal">Please sign in</h1>
    <label for="input<?= ucfirst($username_field); ?>"><?= $username_label; ?></label>
    <input type="<?= $username_is_email ? 'email' : 'text'; ?>" value="{{ last_username }}" name="<?= $username_field; ?>" id="input<?= ucfirst($username_field); ?>" class="form-control" autocomplete="<?= $username_is_email ? 'email' : 'username'; ?>" required autofocus>
    <label for="inputPassword">Password</label>
    <input type="password" name="password" id="inputPassword" class="form-control" autocomplete="current-password" required>

    <input type="hidden" name="_csrf_token"
           value="{{ csrf_token('authenticate') }}"
    >
<?php if($support_remember_me && !$always_remember_me): ?>

    <div class="checkbox mb-3">
        <label>
            <input type="checkbox" name="_remember_me"> Remember me
        </label>
    </div>
<?php endif; ?>

    <button class="btn btn-lg btn-primary" type="submit">
        Sign in
    </button>
</form>
{% endblock %}
