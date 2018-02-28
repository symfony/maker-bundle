{{ form_start(form) }}
    {{ form_widget(form) }}
    <div>
        <button>{{ button_label|default('Save') }}</button>
    </div>
{{ form_end(form) }}