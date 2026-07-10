<?php

use App\Models\User;
use App\Support\Facades\Cloud;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->cloud = Cloud::fake();
    $this->user = User::factory()->nextcloud()->create();
    $this->filesystem = $this->cloud->forUser($this->user);

    $this->filesystem->put('/Solid/.meta.ttl', '<> rdfs:label "Root" .');
    $this->filesystem->put('/Solid/profile/card.ttl', '
        @prefix foaf: <http://xmlns.com/foaf/0.1/>.
        @prefix solid: <http://www.w3.org/ns/solid/terms#>.
        @prefix pim: <http://www.w3.org/ns/pim/space#>.

        <> a foaf:PersonalProfileDocument .

        <#me>
            a foaf:Person;
            pim:storage </>.
    ');
    $this->filesystem->put('/Solid/movies/.meta.ttl', '<> <http://www.w3.org/1999/02/22-rdf-syntax-ns#label> "Movies" .');
    $this->filesystem->put('/Solid/movies/spirited-away.ttl', '
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
    $this->filesystem->put('/Solid/movies/spirited-away.jpg', 'SPIRITED AWAY IMAGE');
    $this->filesystem->put('/Solid/movies/action/.meta.ttl', '<> rdfs:label "Action Movies" .');

    $this->filesystem->put('/Solid/shows/freaks-and-geeks.ttl', '
        @prefix schema: <https://schema.org/> .

        <#it>
            a schema:TVSeries ;
            schema:name "Freaks and Geeks" .
    ');
});

it('requires authentication', function () {
    $this->forUserDomain($this->user);

    // The profile is the only publicly readable document.
    $this->getTurtle('/profile/card')->assertStatus(200);

    // Everything else requires authentication.
    $this->getTurtle('/movies/')->assertStatus(401);
    $this->getTurtle('/movies/spirited-away')->assertStatus(401);
    $this->sparqlUpdate('/profile/card', '')->assertStatus(401);
    $this->putTurtle('/profile/card', '')->assertStatus(401);
    $this->putTurtle('/settings/privateTypeIndex', '')->assertStatus(401);
});

it('negotiates content in root', function () {
    $this->get('/')->assertSee('LSS');
    $this->authenticated()->getTurtle('/')->assertSee('<> a <http://www.w3.org/ns/ldp#Container>', false);
});

it('reads profile', function () {
    $response = $this->forUserDomain($this->user)->getTurtle('/profile/card');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/turtle; charset=UTF-8');
    $response->assertSee('a foaf:PersonalProfileDocument');
    $response->assertValidTurtle();
});

it('reads documents', function () {
    $lastModified = Carbon::createFromTimestamp($this->filesystem->lastModified('/Solid/movies/spirited-away.ttl'));
    $response = $this->authenticated()->getTurtle('/movies/spirited-away');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/turtle; charset=UTF-8');
    $response->assertHeader('Last-Modified', $lastModified->toRfc7231String());
    $response->assertSee('a schema:Movie');
    $response->assertValidTurtle();
});

it('reads binaries', function () {
    $response = $this->authenticated()->getFile('/movies/spirited-away.jpg');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'image/jpeg');
    $response->assertSee('SPIRITED AWAY IMAGE');
});

it('reads containers', function () {
    $lastModified = Carbon::createFromTimestamp($this->filesystem->lastModified('/Solid/movies/'));

    $response = $this->authenticated()->getTurtle('/movies/');

    $response->assertStatus(200);
    $response->assertSee('<http://www.w3.org/1999/02/22-rdf-syntax-ns#label> "Movies"', false);
    $response->assertSee('<> a <http://www.w3.org/ns/ldp#Container>', false);
    $response->assertSee('<> <https://vocab.noeldemartin.com/solid-extra/deepLastModified>', false);
    $response->assertSee('<http://www.w3.org/ns/ldp#contains> </movies/spirited-away>', false);
    $response->assertSee('</movies/spirited-away> a <http://www.w3.org/ns/ldp#Resource>', false);
    $response->assertSee('</movies/spirited-away> a <http://www.w3.org/ns/iana/media-types/text/turtle#Resource>', false);
    $response->assertSee('</movies/spirited-away> <http://purl.org/dc/terms/modified>', false);
    $response->assertSee('<http://www.w3.org/ns/ldp#contains> </movies/spirited-away.jpg>', false);
    $response->assertSee('</movies/spirited-away.jpg> a <http://www.w3.org/ns/ldp#Resource>', false);
    $response->assertSee('</movies/spirited-away.jpg> a <http://www.w3.org/ns/iana/media-types/image/jpeg#Resource>', false);
    $response->assertSee('</movies/spirited-away.jpg> <http://purl.org/dc/terms/modified>', false);
    $response->assertSee('<http://www.w3.org/ns/ldp#contains> </movies/action/>', false);
    $response->assertSee('</movies/action/> a <http://www.w3.org/ns/ldp#Resource>', false);
    $response->assertSee('</movies/action/> a <http://www.w3.org/ns/ldp#Container>', false);
    $response->assertSee('</movies/action/> a <http://www.w3.org/ns/ldp#BasicContainer>', false);
    $response->assertSee('</movies/action/> <https://vocab.noeldemartin.com/solid-extra/deepLastModified>', false);
    $response->assertHeader('Last-Modified', $lastModified->toRfc7231String());
    $response->assertValidTurtle();
});

it('reads containers without .meta', function () {
    $response = $this->authenticated()->getTurtle('/shows/');

    $response->assertStatus(200);
    $response->assertSee('<> a <http://www.w3.org/ns/ldp#Container>', false);
    $response->assertSee('<http://www.w3.org/ns/ldp#contains> </shows/freaks-and-geeks>', false);
    $response->assertSee('</shows/freaks-and-geeks> a <http://www.w3.org/ns/ldp#Resource>', false);
    $response->assertSee('</shows/freaks-and-geeks> a <http://www.w3.org/ns/iana/media-types/text/turtle#Resource>', false);
    $response->assertSee('</shows/freaks-and-geeks> <http://purl.org/dc/terms/modified>', false);
    $response->assertValidTurtle();
});

it('updates documents', function () {
    $user = User::factory()->nextcloud()->create();
    $response = $this->authenticated($user)->sparqlUpdate('/profile/card', "
        INSERT DATA {
            <{$user->url('/profile/card#me')}> <http://www.w3.org/ns/solid/terms#privateTypeIndex> <{$user->url('/settings/privateTypeIndex')}> .
        }
    ");

    $response->assertStatus(200);
    $this->cloud->assertContains('/Solid/profile/card.ttl', '<#me>');
    $this->cloud->assertContains('/Solid/profile/card.ttl', 'privateTypeIndex </settings/privateTypeIndex>');
});

it('creates documents using PUT', function () {
    $response = $this->authenticated()->putTurtle('/settings/privateTypeIndex', '<> a <http://www.w3.org/ns/solid/terms#TypeIndex> .');

    $response->assertStatus(201);
    $this->cloud->assertContains('/Solid/settings/privateTypeIndex.ttl', '<> a <http://www.w3.org/ns/solid/terms#TypeIndex> .');
});

it('creates binaries using PUT', function () {
    $response = $this->authenticated()->putFile('/profile/avatar.jpg', 'AVATAR');

    $response->assertStatus(201);
    $this->cloud->assertContains('/Solid/profile/avatar.jpg', 'AVATAR');
});

it('overrides binaries using PUT', function () {
    $response = $this->authenticated()->putFile('/movies/spirited-away.jpg', 'NEW DATA');

    $response->assertStatus(200);
    $this->cloud->assertContains('/Solid/movies/spirited-away.jpg', 'NEW DATA');
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
