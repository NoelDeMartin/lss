<?php

use App\Support\Facades\Sparql;

it('Inserts triples', function () {
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

it('Deletes triples', function () {
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

it('Updates triples', function () {
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
