<?= "<?php\n" ?>

namespace <?= $namespace ?>;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use <?= $entity_full_class_name ?>;
use <?= $form_full_class_name ?>;
use <?= $parent_full_class_name ?>;
<?php if (isset($repository_full_class_name)): ?>
    use <?= $repository_full_class_name ?>;
<?php endif ?>

class <?= $class_name ?> extends <?= $parent_class_name ?><?= "\n" ?>
{
<?php if (isset($repository_full_class_name)): ?>
    /**
     * Lists all <?= $entity_class_name ?>.
     * @Rest\Get("<?= $route_path ?>")
     */
    public function get<?= $entity_class_name ?>s(<?= $repository_class_name ?> $<?= $repository_var ?>): Response
    {
        $<?= $entity_var_plural ?> = $<?= $repository_var ?>->findAll();

        return $this->handleView($this->view($<?= $entity_var_plural ?>));
    }
<?php else: ?>
    /**
     * Lists all <?= $entity_class_name ?>.
     * @Rest\Get("<?= $route_path ?>")
     */
    public function get<?= $entity_class_name ?>s(): Response
    {
        $<?= $entity_var_plural ?> = $this->getDoctrine()->getRepository(<?= $entity_class_name ?>::class)->findAll();

        return $this->handleView($this->view($<?= $entity_var_plural ?>));
    }
<?php endif; ?>

    /**
     * List a <?= $entity_class_name ?>
     * @Rest\Get("<?= $route_path ?>/{<?= $entity_identifier ?>}")
     */
    public function get<?= $entity_class_name ?>(<?= $entity_class_name ?> $<?= $entity_var_singular ?>): Response
    {
        return $this->handleView($this->view($<?= $entity_var_singular ?>));
    }

    /**
    * Create <?= $entity_class_name ?>.
    * @Rest\Post("<?= $route_path ?>")
    *
    * @return Response
    */
    public function post<?= $entity_class_name ?>(Request $request)
    {
        $<?= $entity_var_singular ?> = new <?= $entity_class_name ?>();
        $form = $this->createForm(<?= $form_class_name ?>::class, $<?= $entity_var_singular ?>);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($<?= $entity_var_singular ?>);
            $em->flush();
            return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_CREATED));
        }

        return $this->handleView($this->view($form->getErrors()));
    }
}
