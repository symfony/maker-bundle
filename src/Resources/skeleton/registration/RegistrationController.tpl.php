<?php echo "<?php\n" ?>

namespace <?php echo $namespace; ?>;

use <?php echo $user_full_class_name ?>;
use <?php echo $form_full_class_name ?>;
<?php if ($authenticator_full_class_name) { ?>
use <?php echo $authenticator_full_class_name; ?>;
<?php } ?>
use Symfony\Bundle\FrameworkBundle\Controller\<?php echo $parent_class_name; ?>;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
<?php if ($authenticator_full_class_name) { ?>
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
<?php } ?>

class <?php echo $class_name; ?> extends <?php echo $parent_class_name; ?><?php echo "\n" ?>
{
    /**
     * @Route("<?php echo $route_path ?>", name="<?php echo $route_name ?>")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder<?php echo $authenticator_full_class_name ? sprintf(', GuardAuthenticatorHandler $guardHandler, %s $authenticator', $authenticator_class_name) : '' ?>): Response
    {
        $user = new <?php echo $user_class_name ?>();
        $form = $this->createForm(<?php echo $form_class_name ?>::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->set<?php echo ucfirst($password_field) ?>(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email

<?php if ($authenticator_full_class_name) { ?>
            return $guardHandler->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $authenticator,
                '<?php echo $firewall_name; ?>' // firewall name in security.yaml
            );
<?php } else { ?>
            return $this->redirectToRoute('<?php echo $redirect_route_name ?>');
<?php } ?>
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
