<?php

use Symfony\Bundle\MakerBundle\FileManager;

return [
    'description' => 'Create registration form and tests.',
    'dependents' => [
        'auth',
    ],
    'packages' => [
        'symfony/form' => 'all',
        'symfony/validator' => 'all',
    ],
    'configure' => function(FileManager $files) {
        $userEntity = $files->getFileContents('src/Entity/User.php');

        if (str_contains($userEntity, $attribute = "#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]")) {
            // unique constraint already there
            return;
        }

        $userEntity = str_replace(
            [
                '#[ORM\Entity(repositoryClass: UserRepository::class)]',
                'use Doctrine\ORM\Mapping as ORM;'
            ],
            [
                "#[ORM\Entity(repositoryClass: UserRepository::class)]\n{$attribute}",
                "use Doctrine\ORM\Mapping as ORM;\nuse Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;",
            ],
            $userEntity
        );
        $files->dumpFile('src/Entity/User.php', $userEntity);
    },
];
