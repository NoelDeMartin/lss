<?php

namespace App\Support\Serializers;

use EasyRdf\Graph;
use EasyRdf\Serialiser\Turtle;

class TurtleSerializer extends Turtle
{
    public function serialise(Graph $graph, $format, array $options = [])
    {
        $turtle = parent::serialise($graph, $format, $options);

        if (isset($options['implicit'])) {
            $escapedUrl = preg_quote($options['implicit'], '/');

            preg_match_all("/<{$escapedUrl}([^>]*)>/", $turtle, $matches);

            foreach ($matches[0] as $i => $match) {
                $turtle = str_replace($match, "<{$matches[1][$i]}>", $turtle);
            }
        }

        return $turtle;
    }
}
