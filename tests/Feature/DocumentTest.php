<?php

use App\Enums\DocumentStatus;
use App\Jobs\ProcessDocument;
use App\Models\Client;
use App\Models\Document;
use App\Models\Organization;
use App\Models\User;
use App\Support\CurrentOrganization;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

function uploadDocument(Client $client, UploadedFile $file): TestResponse
{
    return test()
        ->actingAs(test()->user)
        ->post(route('clients.documents.store', $client), ['file' => $file]);
}

function createDocument(Client $client): Document
{
    app(CurrentOrganization::class)->set($client->organization);

    return $client->documents()->create([
        'original_filename' => 'facture.pdf',
        'path' => 'documents/test.pdf',
        'mime_type' => 'application/pdf',
        'size' => 1000,
    ]);
}

beforeEach(function () {
    Storage::fake('local');
    Queue::fake();

    $this->user = User::factory()->withOrganization()->create();
    $this->client = $this->user->currentOrganization->clients()->create(['name' => 'Client de test']);

});

test('stocke un PDF, crée le document en pending et dispatch le job', function () {
    $file = UploadedFile::fake()->create('facture.pdf', 500, 'application/pdf');

    uploadDocument($this->client, $file)->assertRedirect();

    $document = Document::withoutGlobalScopes()->sole();

    expect($document->status)->toBe(DocumentStatus::Pending)
        ->and($document->original_filename)->toBe('facture.pdf')
        ->and($document->organization_id)->toBe($this->user->current_organization_id);

    /** @var FilesystemAdapter $disk */
    $disk = Storage::disk('local');
    $disk->assertExists($document->path);

    Queue::assertPushed(
        ProcessDocument::class,
        fn (ProcessDocument $job) => $job->document->is($document),
    );
});

test('refuse un fichier qui n\'est pas un PDF, même renommé en .pdf', function () {
    $file = UploadedFile::fake()->create('fake.pdf', 500, 'text/plain');

    uploadDocument($this->client, $file)->assertSessionHasErrors('file');

    $this->assertDatabaseEmpty('documents');

    Queue::assertNothingPushed();
});

test('refuse un fichier PDF trop lourd', function () {
    $file = UploadedFile::fake()->create('heavy_file.pdf', 20481, 'application/pdf');

    uploadDocument($this->client, $file)->assertSessionHasErrors('file');

    $this->assertDatabaseEmpty('documents');

    Queue::assertNothingPushed();
});

test('accepte un fichier PDF avec la taille maximum', function () {
    $file = UploadedFile::fake()->create('heavy_file_ok.pdf', 20480, 'application/pdf');

    uploadDocument($this->client, $file)->assertRedirect()->assertSessionHasNoErrors();

    expect(Document::withoutGlobalScopes()->count())->toBe(1);
});

test('télécharge un document de son organisation avec son nom d\'origine', function () {
    app(CurrentOrganization::class)->set($this->user->currentOrganization);

    $document = $this->client->documents()->create([
        'original_filename' => 'facture.pdf',
        'path' => 'documents/test.pdf',
        'mime_type' => 'application/pdf',
        'size' => 1000,
    ]);

    Storage::disk('local')->put($document->path, 'contenu factice');

    $this->actingAs($this->user)
        ->get(route('documents.download', $document))
        ->assertDownload('facture.pdf');
});

test('bloque le téléchargement d\'un document d\'une autre organisation', function () {
    $orgB = Organization::factory()->create();
    $clientB = $orgB->clients()->create(['name' => 'client de B']);
    app(CurrentOrganization::class)->set($orgB);

    $document = $clientB->documents()->create([
        'original_filename' => 'facture.pdf',
        'path' => 'documents/test.pdf',
        'mime_type' => 'application/pdf',
        'size' => 1000,
    ]);

    Storage::disk('local')->put($document->path, 'contenu factice');

    $this->actingAs($this->user)
        ->get(route('documents.download', $document))
        ->assertNotFound();
});

test('refuse l\'upload sur le client d\'une autre organisation', function () {
    $orgB = Organization::factory()->create();
    $clientB = $orgB->clients()->create(['name' => 'client de B']);

    $file = UploadedFile::fake()->create('facture.pdf', 1000, 'application/pdf');

    uploadDocument($clientB, $file)->assertNotFound();

    $this->assertDatabaseEmpty('documents');
});

test('le job pose le contexte de l\'org du document et le passe en processing', function () {
    $orgB = Organization::factory()->create();
    $clientB = $orgB->clients()->create(['name' => 'Client de B']);
    $document = createDocument($clientB);

    // Le contexte est sur B après la création : on le remet sur une autre org,
    // sinon le test passerait même si le job ne posait rien.
    app(CurrentOrganization::class)->set($this->user->currentOrganization);

    (new ProcessDocument($document))->handle(app(CurrentOrganization::class));

    expect(app(CurrentOrganization::class)->id())->toBe($orgB->id)
        ->and($document->refresh()->status)->toBe(DocumentStatus::Processing);
});

test('failed() passe le document en failed et enregistre le message', function () {
    $document = createDocument($this->client);

    (new ProcessDocument($document))->failed(new RuntimeException('Service Python injoignable'));

    $document->refresh();

    expect($document->status)->toBe(DocumentStatus::Failed)
        ->and($document->error_message)->toBe('Service Python injoignable');
});
