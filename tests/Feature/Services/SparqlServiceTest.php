<?php

use App\Models\User;
use App\Support\Facades\Sparql;

it('inserts triples', function () {
    // Arrange.
    $turtle = '';
    $sparql = '
        INSERT DATA {
            @prefix card: <https://alice.com/profile/card#> .
            @prefix solid: <http://www.w3.org/ns/solid/terms#> .

            card:me solid:privateTypeIndex <https://alice.com/settings/privateTypeIndex> .
        }
    ';

    // Act.
    $updated = Sparql::updateTurtle($turtle, $sparql);

    // Assert.
    expect($updated)->toContain('<https://alice.com/profile/card#me>');
    expect($updated)->toContain(':privateTypeIndex');
    expect($updated)->toContain('<https://alice.com/settings/privateTypeIndex>');
});

it('respects relative paths', function () {
    // Arrange.
    $base = User::factory()->create()->url();
    $turtle = '';
    $sparql = '
        INSERT DATA {
            @prefix schema: <https://schema.org/> .

            <#me> schema:imageUrl </cookbook/ramen.jpg> .
        }
    ';

    // Act.
    $updated = Sparql::updateTurtle($turtle, $sparql, [
        'base' => $base,
        'document' => "{$base}/cookbook/ramen",
    ]);

    // Assert.
    expect($updated)->toContain('</cookbook/ramen.jpg>');
});

it('deletes triples', function () {
    // Arrange.
    $turtle = '
        @prefix card: <https://alice.com/profile/card#> .
        @prefix solid: <http://www.w3.org/ns/solid/terms#> .

        card:me solid:privateTypeIndex <https://alice.com/settings/privateTypeIndex> .
    ';
    $sparql = '
        DELETE DATA {
            @prefix card: <https://alice.com/profile/card#> .
            @prefix solid: <http://www.w3.org/ns/solid/terms#> .

            card:me solid:privateTypeIndex <https://alice.com/settings/privateTypeIndex> .
        }
    ';

    // Act.
    $updated = Sparql::updateTurtle($turtle, $sparql);

    // Assert.
    expect($updated)->toBe('');
});

it('updates triples', function () {
    // Arrange.
    $turtle = '
        @prefix rdfs: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .

        <https://alice.com/movies/> rdfs:label "Old name" .
    ';
    $sparql = '
        DELETE DATA {
            @prefix rdfs: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .

            <https://alice.com/movies/> rdfs:label "Old name" .
        };
        INSERT DATA {
            @prefix rdfs: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .

            <https://alice.com/movies/> rdfs:label "New name" .
        }
    ';

    // Act.
    $updated = Sparql::updateTurtle($turtle, $sparql);

    // Assert.
    expect($updated)->toContain('<https://alice.com/movies/>');
    expect($updated)->toContain('"New name"');
    expect($updated)->not->toContain('"Old name"');
});
