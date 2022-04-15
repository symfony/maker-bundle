{% extends 'base.html.twig' %}

{% block title %}Log in!{% endblock %}

{% block body %}
<div class="row">
    <form class="col-md-6 offset-md-3" method="post">
        <h1>Please sign in</h1>

        {% if error %}
            <div class="alert alert-danger">{{ error.messageKey|trans(error.messageData, 'security') }}</div>
        {% endif %}

        <div class="mb-3">
            <label for="inputEmail">Email</label>
            <input type="email" value="{{ last_username }}" name="email" id="inputEmail" class="form-control" autocomplete="email" required autofocus>
        </div>
        <div class="mb-3">
            <label for="inputPassword">Password</label>
            <input type="password" name="password" id="inputPassword" class="form-control" autocomplete="current-password" required>
        </div>
        <div class="checkbox mb-3">
            <label>
                <input type="checkbox" checked name="_remember_me"> Remember me
            </label>
        </div>
        <input type="hidden" name="_csrf_token" value="{{ csrf_token('authenticate') }}">

        {% if app.request.query.has('target') %}
            <input type="hidden" name="_target_path" value="{{ app.request.query.get('target') }}"/>
        {% endif %}

        <button class="btn btn-primary" type="submit">
            Sign in
        </button>
    </form>
</div>
{% endblock %}
