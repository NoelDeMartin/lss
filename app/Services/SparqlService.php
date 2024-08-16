<?php

namespace App\Services;

use EasyRdf\Graph;
use EasyRdf\Serialiser\Turtle as TurtleSerializer;

class SparqlService
{
    public function updateTurtle(string $turtle, string $update, array $options = []): string
    {
        $base = $options['base'] ?? '/';
        $baseUri = url($base).(str_ends_with($base, '/') ? '/' : '');
        $graph = new Graph($baseUri);
        $serializer = new TurtleSerializer;
        $count = preg_match_all('/(?:(INSERT|DELETE) DATA {([^}]*)}\s*;?)/si', $update, $matches);

        $graph->parse($turtle, 'turtle');

        for ($i = 0; $i < $count; $i++) {
            $this->applyOperation(strtolower($matches[1][$i]), $matches[2][$i], $graph);
        }

        return $serializer->serialise($graph, 'turtle');
    }

    protected function applyOperation(string $operation, string $turtle, Graph $graph): void
    {
        $operationGraph = new Graph($graph->getUri());

        $operationGraph->parse($turtle, 'turtle');

        foreach ($operationGraph->resources() as $resource) {
            foreach ($resource->propertyUris() as $property) {
                $literals = $resource->allLiterals("<$property>");
                $resources = $resource->allResources("<$property>");

                foreach ($literals as $value) {
                    $operation === 'insert'
                        ? $graph->addLiteral($resource, $property, $value)
                        : $graph->deleteLiteral($resource, $property, $value);
                }

                foreach ($resources as $value) {
                    $operation === 'insert'
                        ? $graph->addResource($resource, $property, $value)
                        : $graph->deleteLiteral($resource, $property, $value);
                }
            }
        }
    }
}
