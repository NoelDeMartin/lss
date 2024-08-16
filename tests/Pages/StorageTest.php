<?php

use App\Support\Facades\PodStorage;

beforeEach(function () {
    $this->fakePodStorage = PodStorage::fake();
    $this->fakePodStorage->write('/profile/card.ttl', '
        @prefix foaf: <http://xmlns.com/foaf/0.1/>.
        @prefix solid: <http://www.w3.org/ns/solid/terms#>.
        @prefix pim: <http://www.w3.org/ns/pim/space#>.

        <> a foaf:PersonalProfileDocument .

        <#me>
            a foaf:Person;
            pim:storage <http://localhost/>.
    ');
    $this->fakePodStorage->write('/movies/.meta.ttl', '<> rdfs:label "Movies" .');
    $this->fakePodStorage->write('/movies/spirited-away.ttl', '
        @prefix schema: <https://schema.org/> .
        @prefix ldp: <http://www.w3.org/ns/ldp#> .
        @prefix terms: <http://purl.org/dc/terms/> .
        @prefix XML: <http://www.w3.org/2001/XMLSchema#> .

        <#it>
            a schema:Movie ;
            schema:name "Spirited Away" ;
            schema:image "https://image.tmdb.org/t/p/w500/39wmItIWsg5sZMyRUHLkWBcuVCM.jpg" ;
            terms:created "2021-09-03T14:40:00Z"^^XML:dateTime ;
            terms:modified "2021-09-03T14:40:00Z"^^XML:dateTime .
    ');
    $this->fakePodStorage->write('/movies/action/.meta.ttl', '<> rdfs:label "Action Movies" .');
});

test('Reads documents', function () {
    $response = $this->get('/profile/card');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/turtle; charset=UTF-8');
    $response->assertSee('a foaf:PersonalProfileDocument');
});

test('Reads containers', function () {
    $response = $this->get('/movies/');

    $response->assertStatus(200);
    $response->assertSee('rdfs:label "Movies"', false);
    $response->assertSee('<> a <http://www.w3.org/ns/ldp#Container>', false);
    $response->assertSee('<http://www.w3.org/ns/ldp#contains> </movies/spirited-away>', false);
    $response->assertSee('<http://www.w3.org/ns/ldp#contains> </movies/action/>', false);
});

test('Updates documents', function () {
    $response = $this->sparqlUpdate('/profile/card', '
        INSERT DATA {
            <http://localhost/profile/card#me> <http://www.w3.org/ns/solid/terms#privateTypeIndex> <http://localhost/settings/privateTypeIndex> .
        }
    ');

    $response->assertStatus(200);
    $this->fakePodStorage->assertContains('/profile/card.ttl', 'privateTypeIndex <http://localhost/settings/privateTypeIndex>');
});

test('Creates documents using PUT', function () {
    $response = $this->putTurtle('/settings/privateTypeIndex', '<> a <http://www.w3.org/ns/solid/terms#TypeIndex> .');

    $response->assertStatus(201);
    $this->fakePodStorage->assertContains('/settings/privateTypeIndex.ttl', '<> a <http://www.w3.org/ns/solid/terms#TypeIndex> .');
});

test('Creates documents using PATCH', function () {
    $response = $this->sparqlUpdate('/settings/privateTypeIndex', '
        INSERT DATA {
            <> a <http://www.w3.org/ns/solid/terms#TypeIndex> .
    }');

    $response->assertStatus(201);
    $this->fakePodStorage->assertContains('/settings/privateTypeIndex.ttl', 'TypeIndex');
});

test('Creates containers', function () {
    $response = $this->putTurtle('/cookbook/', '<> rdfs:label "Container" .');

    $response->assertStatus(201);
    $this->fakePodStorage->assertContains('/cookbook/.meta.ttl', '<> rdfs:label "Container" .');
});
