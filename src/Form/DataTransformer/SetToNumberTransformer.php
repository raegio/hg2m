<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class SetToNumberTransformer implements DataTransformerInterface
{
    private $constants;

    public function __construct(array $constants)
    {
        $this->constants = $constants;
    }

    public function transform($value): array
    {
        $array = [];

        foreach ($this->constants as $constant) {
            $flag = pow(2, $constant - 1);
            
            if ($value & $flag) {
                $array[] = $constant;
            }
        }

        return $array;
    }

    public function reverseTransform($array): int
    {
        $value = 0;

        foreach ($this->constants as $constant) {
            if (false !== array_search($constant, $array)) {
                $value += pow(2, $constant - 1);
            }
        }

        return $value;
    }
}
