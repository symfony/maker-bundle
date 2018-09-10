{% extends 'base.html.twig' %}

{% block title %}Login!{% endblock %}

{% block stylesheets %}
{{ parent() }}

<link rel="stylesheet" href="{{ asset('css/login.css') }}">
{% endblock %}

{% block body %}
<form class="form-signin" method="post">
    {% if error %}
    <div class="alert alert-danger">{{ error.messageKey|trans(error.messageData, 'security') }}</div>
    {% endif %}

    <h1 class="h3 mb-3 font-weight-normal">Please sign in</h1>
    <label for="inputEmail" class="sr-only">Email address</label>
    <input type="email" value="{{ last_username }}" name="email" id="inputEmail" class="form-control" placeholder="Email address" required autofocus>
    <label for="inputPassword" class="sr-only">Password</label>
    <input type="password" name="password" id="inputPassword" class="form-control" placeholder="Password" required>

    <input type="hidden" name="_csrf_token"
           value="{{ csrf_token('authenticate') }}"
    >

    <div class="checkbox mb-3">
        <label>
            <input type="checkbox" name="_remember_me"> Remember me
        </label>
    </div>

    <button class="btn btn-lg btn-primary btn-block" type="submit">
        Sign in
    </button>
</form>
{% endblock %}
