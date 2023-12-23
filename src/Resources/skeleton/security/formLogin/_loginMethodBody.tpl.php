<?php

// get the login error if there is one
$error = $authenticationUtils->getLastAuthenticationError();

/** last username entered by the user */
$lastUsername = $authenticationUtils->getLastUsername();

return $this->render($tpl_template_path.'/login.html.twig', [
    'last_username' => $lastUsername,
    'error' => $error,
]);
