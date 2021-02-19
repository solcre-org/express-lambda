<?php

namespace App\Domain\Utils;


use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ArrayExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('values', [$this, 'values']),
        ];
    }

    /**
     * Return all the values of an array or object
     *
     * @param array $array
     *
     * @return array|null
     */
    public function values(array $array): array
    {
        return isset($array) ? array_values($array) : [];
    }
}
