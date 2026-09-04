<?php

namespace App\Support\Serializers;

use EasyRdf\Graph;
use EasyRdf\Serialiser\Turtle;

class TurtleSerializer extends Turtle
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function serialise(Graph $graph, $format, array $options = [])
    {
        /** @var string $turtle */
        $turtle = parent::serialise($graph, $format, $options);

        if (isset($options['implicit_document']) && is_string($options['implicit_document'])) {
            $turtle = $this->replacePrefix($turtle, $options['implicit_document']);
        }

        if (isset($options['implicit_base']) && is_string($options['implicit_base'])) {
            $turtle = $this->replacePrefix($turtle, $options['implicit_base'], '/');
        }

        return $turtle;
    }

    protected function replacePrefix(string $turtle, string $prefix, string $default = ''): string
    {
        $escapedPrefix = preg_quote($prefix, '/');

        preg_match_all("/<{$escapedPrefix}([^>]*)>/", $turtle, $matches);

        foreach ($matches[0] as $i => $match) {
            $replacement = $matches[1][$i] ?? $default;

            if ($replacement !== $default && ! preg_match('/^[\/#]/', $replacement)) {
                continue;
            }

            $turtle = str_replace($match, "<{$replacement}>", $turtle);
        }

        return $turtle;
    }
}
