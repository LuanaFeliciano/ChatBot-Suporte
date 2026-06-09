<?php

use App\Services\DocumentService;
use OpenAI\Contracts\ClientContract;
use OpenAI\Contracts\Resources\FilesContract;
use OpenAI\Contracts\Resources\VectorStoresContract;
use OpenAI\Contracts\Resources\VectorStoresFilesContract;
use OpenAI\Responses\Files\CreateResponse;
use OpenAI\Responses\Files\DeleteResponse;
use OpenAI\Responses\VectorStores\Files\VectorStoreFileDeleteResponse;
use OpenAI\Responses\VectorStores\Files\VectorStoreFileResponse;

it('uploadFile calls the OpenAI Files API and returns the file id', function () {
    $filesResource = Mockery::mock(FilesContract::class);
    $filesResource->shouldReceive('upload')
        ->once()
        ->andReturn(CreateResponse::fake(['id' => 'file-test-123']));

    $client = Mockery::mock(ClientContract::class);
    $client->shouldReceive('files')->andReturn($filesResource);

    $file = tempnam(sys_get_temp_dir(), 'doctest_');
    file_put_contents($file, 'test content');

    $result = (new DocumentService($client))->uploadFile($file, 'text/plain');

    expect($result)->toBe('file-test-123');

    unlink($file);
});

it('addToVectorStore calls the Vector Store Files API and returns the vector_store_file_id', function () {
    config(['services.openai.vector_store_id' => 'vs-test-id']);

    $vsFilesResource = Mockery::mock(VectorStoresFilesContract::class);
    $vsFilesResource->shouldReceive('create')
        ->once()
        ->with('vs-test-id', ['file_id' => 'file-abc'])
        ->andReturn(VectorStoreFileResponse::fake(['id' => 'vsf-test-123', 'chunking_strategy' => ['type' => 'other']]));

    $vsResource = Mockery::mock(VectorStoresContract::class);
    $vsResource->shouldReceive('files')->andReturn($vsFilesResource);

    $client = Mockery::mock(ClientContract::class);
    $client->shouldReceive('vectorStores')->andReturn($vsResource);

    $result = (new DocumentService($client))->addToVectorStore('file-abc');

    expect($result)->toBe('vsf-test-123');
});

it('pollIndexingStatus returns completed when the status reaches completed within the timeout', function () {
    config(['services.openai.vector_store_id' => 'vs-test-id']);

    $vsFilesResource = Mockery::mock(VectorStoresFilesContract::class);
    $vsFilesResource->shouldReceive('retrieve')
        ->once()
        ->andReturn(VectorStoreFileResponse::fake(['status' => 'completed', 'chunking_strategy' => ['type' => 'other']]));

    $vsResource = Mockery::mock(VectorStoresContract::class);
    $vsResource->shouldReceive('files')->andReturn($vsFilesResource);

    $client = Mockery::mock(ClientContract::class);
    $client->shouldReceive('vectorStores')->andReturn($vsResource);

    $result = (new DocumentService($client))->pollIndexingStatus('vsf-test', 60);

    expect($result)->toBe('completed');
});

it('pollIndexingStatus returns failed when the API reports failed status', function () {
    config(['services.openai.vector_store_id' => 'vs-test-id']);

    $vsFilesResource = Mockery::mock(VectorStoresFilesContract::class);
    $vsFilesResource->shouldReceive('retrieve')
        ->once()
        ->andReturn(VectorStoreFileResponse::fake(['status' => 'failed', 'chunking_strategy' => ['type' => 'other']]));

    $vsResource = Mockery::mock(VectorStoresContract::class);
    $vsResource->shouldReceive('files')->andReturn($vsFilesResource);

    $client = Mockery::mock(ClientContract::class);
    $client->shouldReceive('vectorStores')->andReturn($vsResource);

    $result = (new DocumentService($client))->pollIndexingStatus('vsf-test', 60);

    expect($result)->toBe('failed');
});

it('pollIndexingStatus returns failed when the timeout is exceeded without completion', function () {
    config(['services.openai.vector_store_id' => 'vs-test-id']);

    $vsFilesResource = Mockery::mock(VectorStoresFilesContract::class);
    $vsFilesResource->shouldReceive('retrieve')
        ->andReturn(VectorStoreFileResponse::fake(['status' => 'in_progress', 'chunking_strategy' => ['type' => 'other']]));

    $vsResource = Mockery::mock(VectorStoresContract::class);
    $vsResource->shouldReceive('files')->andReturn($vsFilesResource);

    $client = Mockery::mock(ClientContract::class);
    $client->shouldReceive('vectorStores')->andReturn($vsResource);

    $result = (new DocumentService($client))->pollIndexingStatus('vsf-test', 1);

    expect($result)->toBe('failed');
});

it('removeFromVectorStore calls the Vector Store Files delete endpoint', function () {
    config(['services.openai.vector_store_id' => 'vs-test-id']);

    $vsFilesResource = Mockery::mock(VectorStoresFilesContract::class);
    $vsFilesResource->shouldReceive('delete')
        ->once()
        ->with('vs-test-id', 'vsf-abc')
        ->andReturn(VectorStoreFileDeleteResponse::fake());

    $vsResource = Mockery::mock(VectorStoresContract::class);
    $vsResource->shouldReceive('files')->andReturn($vsFilesResource);

    $client = Mockery::mock(ClientContract::class);
    $client->shouldReceive('vectorStores')->andReturn($vsResource);

    (new DocumentService($client))->removeFromVectorStore('vsf-abc');
});

it('deleteFile calls the OpenAI Files delete endpoint', function () {
    $filesResource = Mockery::mock(FilesContract::class);
    $filesResource->shouldReceive('delete')
        ->once()
        ->with('file-abc')
        ->andReturn(DeleteResponse::fake());

    $client = Mockery::mock(ClientContract::class);
    $client->shouldReceive('files')->andReturn($filesResource);

    (new DocumentService($client))->deleteFile('file-abc');
});
