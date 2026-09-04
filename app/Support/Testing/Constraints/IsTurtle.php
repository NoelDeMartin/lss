<?php

namespace App\Support\Testing\Constraints;

use EasyRdf\Graph;
use EasyRdf\Parser\Exception as ParserException;
use PHPUnit\Framework\Constraint\Constraint;

class IsTurtle extends Constraint
{
    private ?string $uri;

    public function __construct(?string $uri = null)
    {
        $this->uri = $uri;
    }

    public function toString(): string
    {
        return 'is valid Turtle';
    }

    protected function matches(mixed $other): bool
    {
        return is_string($other) && is_null($this->getParsingErrorMessage($other));
    }

    protected function failureDescription(mixed $other): string
    {
        return sprintf(
            'a string is valid Turtle (%s)',
            is_string($other) ? ($this->getParsingErrorMessage($other) ?? 'Nothing is actually wrong with this Turtle') : 'Given value is not a string',
        );
    }

    protected function getParsingErrorMessage(string $turtle): ?string
    {
        try {
            (new Graph($this->uri ?? ''))->parse($turtle, 'turtle');

            return null;
        } catch (ParserException $e) {
            return $e->getMessage();
        }
    }
}
