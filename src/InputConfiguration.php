<?php

namespace Symfony\Bundle\MakerBundle;

final class InputConfiguration
{
    private $nonInteractiveArguments = [];

    /**
     * Call in MakerInterface::configureCommand() to disable the automatic interactive
     * prompt for an argument.
     *
     * @param string $argumentName
     */
    public function setArgumentAsNonInteractive(string $argumentName)
    {
        $this->nonInteractiveArguments[] = $argumentName;
    }

    public function getNonInteractiveArguments(): array
    {
        return $this->nonInteractiveArguments;
    }
}
