<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
* Class <?= $class_name ?>
* @package <?= $namespace; ?>
*/
class <?= $class_name ?> extends AbstractExtension
{
    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return Twig_Filter[]
     */
    public function getFilters(): array
    {
        return [
            // If your filter generates SAFE HTML, you should add a third
            // parameter: ['is_safe' => ['html']]
            // Reference: https://twig.symfony.com/doc/2.x/advanced.html#automatic-escaping
            new TwigFilter('filter_name', [$this, 'doSomething']),
        ];
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return Twig_Function[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('function_name', [$this, 'doSomething']),
        ];
    }

    /**
     * This is your custom method
     *
     * @param mixed $value
     */
    public function doSomething($value)
    {
        // ...
    }
}
