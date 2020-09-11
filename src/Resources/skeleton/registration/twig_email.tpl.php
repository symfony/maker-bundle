<h1>Hi! Please confirm your email!</h1>

<p>
    Please confirm your email address by clicking the following link: <br><br>
    <a href="{{ signedUrl|raw }}">Confirm my Email</a>.
    This link will expire in {{ expiresAt|date('g') }} hour(s).
</p>

<p>
    Cheers!
</p>
