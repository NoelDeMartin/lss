<?php

use App\Models\User;
use App\Support\Facades\Cloud;

beforeEach(function () {
    $this->user = User::factory()->nextcloud()->create();
    $this->cloud = Cloud::fake();

    $filesystem = $this->cloud->forUser($this->user);
    $filesystem->put('/Solid/.meta.ttl', '<> rdfs:label "Root" .');
    $filesystem->put('/Solid/profile/card.ttl', '
        @prefix foaf: <http://xmlns.com/foaf/0.1/>.
        @prefix solid: <http://www.w3.org/ns/solid/terms#>.
        @prefix pim: <http://www.w3.org/ns/pim/space#>.

        <> a foaf:PersonalProfileDocument .

        <#me>
            a foaf:Person;
            pim:storage </>.
    ');
    $filesystem->put('/Solid/movies/.meta.ttl', '<> rdfs:label "Movies" .');
    $filesystem->put('/Solid/movies/spirited-away.ttl', '
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
    $filesystem->put('/Solid/movies/action/.meta.ttl', '<> rdfs:label "Action Movies" .');
});

it('requires authentication', function () {
    $this->forUserDomain($this->user);

    // The profile is the only publicly readable document.
    $this->readTurtle('/profile/card')->assertStatus(200);

    // Everything else requires authentication.
    $this->readTurtle('/movies/')->assertStatus(401);
    $this->readTurtle('/movies/spirited-away')->assertStatus(401);
    $this->sparqlUpdate('/profile/card', '')->assertStatus(401);
    $this->putTurtle('/profile/card', '')->assertStatus(401);
    $this->putTurtle('/settings/privateTypeIndex', '')->assertStatus(401);
});

it('negotiates content in root', function () {
    $this->get('/')->assertSee('LSS');
    $this->authenticated()->readTurtle('/')->assertSee('<> a <http://www.w3.org/ns/ldp#Container>', false);
});

it('reads profile', function () {
    $response = $this->forUserDomain($this->user)->readTurtle('/profile/card');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/turtle; charset=UTF-8');
    $response->assertSee('a foaf:PersonalProfileDocument');
});

it('reads documents', function () {
    $response = $this->authenticated()->readTurtle('/movies/spirited-away');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/turtle; charset=UTF-8');
    $response->assertSee('a schema:Movie');
});

it('reads containers', function () {
    $response = $this->authenticated()->readTurtle('/movies/');

    $response->assertStatus(200);
    $response->assertSee('rdfs:label "Movies"', false);
    $response->assertSee('<> a <http://www.w3.org/ns/ldp#Container>', false);
    $response->assertSee('<http://www.w3.org/ns/ldp#contains> </movies/spirited-away>', false);
    $response->assertSee('<http://www.w3.org/ns/ldp#contains> </movies/action/>', false);
});

it('updates documents', function () {
    $response = $this->authenticated()->sparqlUpdate('/profile/card', '
        INSERT DATA {
            <http://localhost/profile/card#me> <http://www.w3.org/ns/solid/terms#privateTypeIndex> <http://localhost/settings/privateTypeIndex> .
        }
    ');

    $response->assertStatus(200);
    $this->cloud->assertContains('/Solid/profile/card.ttl', 'privateTypeIndex <http://localhost/settings/privateTypeIndex>');
});

it('creates documents using PUT', function () {
    $response = $this->authenticated()->putTurtle('/settings/privateTypeIndex', '<> a <http://www.w3.org/ns/solid/terms#TypeIndex> .');

    $response->assertStatus(201);
    $this->cloud->assertContains('/Solid/settings/privateTypeIndex.ttl', '<> a <http://www.w3.org/ns/solid/terms#TypeIndex> .');
});

it('creates documents using PATCH', function () {
    $response = $this->authenticated()->sparqlUpdate('/settings/privateTypeIndex', '
        INSERT DATA {
            <> a <http://www.w3.org/ns/solid/terms#TypeIndex> .
    }');

    $response->assertStatus(201);
    $this->cloud->assertContains('/Solid/settings/privateTypeIndex.ttl', 'TypeIndex');
});

it('creates containers', function () {
    $response = $this->authenticated()->putTurtle('/cookbook/', '<> rdfs:label "Container" .');

    $response->assertStatus(201);
    $this->cloud->assertContains('/Solid/cookbook/.meta.ttl', '<> rdfs:label "Container" .');
});
