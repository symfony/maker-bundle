<?php

namespace App\Entity;

class ProductCatalog
{
    public const DEFAULT_CURRENCY = 'EUR';

    /**
     * Maximum number of items allowed per order
     */
    final private const int MAX_ITEMS_PER_ORDER = 50;

    private string $name;
}
