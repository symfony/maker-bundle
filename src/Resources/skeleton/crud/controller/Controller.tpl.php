<?php echo "<?php\n" ?>

namespace <?php echo $namespace ?>;

use <?php echo $entity_full_class_name ?>;
use <?php echo $form_full_class_name ?>;
<?php if (isset($repository_full_class_name)) { ?>
use <?php echo $repository_full_class_name ?>;
<?php } ?>
use Symfony\Bundle\FrameworkBundle\Controller\<?php echo $parent_class_name ?>;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("<?php echo $route_path ?>")
 */
class <?php echo $class_name ?> extends <?php echo $parent_class_name; ?><?php echo "\n" ?>
{
    /**
     * @Route("/", name="<?php echo $route_name ?>_index", methods={"GET"})
     */
<?php if (isset($repository_full_class_name)) { ?>
    public function index(<?php echo $repository_class_name ?> $<?php echo $repository_var ?>): Response
    {
        return $this->render('<?php echo $templates_path ?>/index.html.twig', [
            '<?php echo $entity_twig_var_plural ?>' => $<?php echo $repository_var ?>->findAll(),
        ]);
    }
<?php } else { ?>
    public function index(): Response
    {
        $<?php echo $entity_var_plural ?> = $this->getDoctrine()
            ->getRepository(<?php echo $entity_class_name ?>::class)
            ->findAll();

        return $this->render('<?php echo $templates_path ?>/index.html.twig', [
            '<?php echo $entity_twig_var_plural ?>' => $<?php echo $entity_var_plural ?>,
        ]);
    }
<?php } ?>

    /**
     * @Route("/new", name="<?php echo $route_name ?>_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $<?php echo $entity_var_singular ?> = new <?php echo $entity_class_name ?>();
        $form = $this->createForm(<?php echo $form_class_name ?>::class, $<?php echo $entity_var_singular ?>);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($<?php echo $entity_var_singular ?>);
            $entityManager->flush();

            return $this->redirectToRoute('<?php echo $route_name ?>_index');
        }

        return $this->render('<?php echo $templates_path ?>/new.html.twig', [
            '<?php echo $entity_twig_var_singular ?>' => $<?php echo $entity_var_singular ?>,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{<?php echo $entity_identifier ?>}", name="<?php echo $route_name ?>_show", methods={"GET"})
     */
    public function show(<?php echo $entity_class_name ?> $<?php echo $entity_var_singular ?>): Response
    {
        return $this->render('<?php echo $templates_path ?>/show.html.twig', [
            '<?php echo $entity_twig_var_singular ?>' => $<?php echo $entity_var_singular ?>,
        ]);
    }

    /**
     * @Route("/{<?php echo $entity_identifier ?>}/edit", name="<?php echo $route_name ?>_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, <?php echo $entity_class_name ?> $<?php echo $entity_var_singular ?>): Response
    {
        $form = $this->createForm(<?php echo $form_class_name ?>::class, $<?php echo $entity_var_singular ?>);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('<?php echo $route_name ?>_index');
        }

        return $this->render('<?php echo $templates_path ?>/edit.html.twig', [
            '<?php echo $entity_twig_var_singular ?>' => $<?php echo $entity_var_singular ?>,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{<?php echo $entity_identifier ?>}", name="<?php echo $route_name ?>_delete", methods={"DELETE"})
     */
    public function delete(Request $request, <?php echo $entity_class_name ?> $<?php echo $entity_var_singular ?>): Response
    {
        if ($this->isCsrfTokenValid('delete'.$<?php echo $entity_var_singular ?>->get<?php echo ucfirst($entity_identifier) ?>(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($<?php echo $entity_var_singular ?>);
            $entityManager->flush();
        }

        return $this->redirectToRoute('<?php echo $route_name ?>_index');
    }
}
