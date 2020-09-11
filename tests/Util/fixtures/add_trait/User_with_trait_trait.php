<?php

namespace App\Entity;

use App\TestTrait;
use App\TraitAlreadyHere;

class User
{
    use TraitAlreadyHere;
    use TestTrait;
}
