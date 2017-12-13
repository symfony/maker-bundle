<?php

namespace Symfony\Bundle\MakerBundle\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Mapping\DisconnectedMetadataFactory as BaseClass;
use Doctrine\Bundle\DoctrineBundle\Mapping\ClassMetadataCollection;

class DisconnectedMetadataFactory extends BaseClass
{
    public function findNamespaceAndPathForMetadata(ClassMetadataCollection $metadata, $path = null)
    {
        $all = $metadata->getMetadata();
        if (class_exists($all[0]->name)) {
            $r = new \ReflectionClass($all[0]->name);
            $path = dirname($r->getFilename());
            $ns = $r->getNamespaceName();
        } elseif ($path) {
            // Get namespace by removing the last component of the FQCN
            $nsParts = explode('\\', $all[0]->name);
            array_pop($nsParts);
            $ns = implode('\\', $nsParts);
        } else {
            throw new \RuntimeException(sprintf('Unable to determine where to save the "%s" class (use the --path option).', $all[0]->name));
        }

        $metadata->setPath($path);
        $metadata->setNamespace($ns);
    }
}
