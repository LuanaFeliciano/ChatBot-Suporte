<?php

return [

    'navigation_label' => 'Documents',
    'model_label' => 'document',
    'plural_model_label' => 'documents',

    'fields' => [
        'id' => 'ID',
        'name' => 'Name',
        'file' => 'File',
        'module' => 'Module',
        'doc_version' => 'Version',
        'original_filename' => 'Original filename',
        'status' => 'Status',
        'openai_file_id' => 'OpenAI File ID',
        'vector_store_file_id' => 'Vector Store File ID',
        'error_message' => 'Error message',
        'uploaded_by' => 'Uploaded by',
        'file_size' => 'File size',
        'mime_type' => 'MIME type',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
        'deleted_at' => 'Deleted at',
    ],

    'actions' => [
        'replace' => 'Replace',
        'replace_modal_heading' => 'Replace document',
        'replace_modal_description' => 'A new file will be uploaded and indexed. Once indexing succeeds, the current document will be retired (soft-deleted). This is not an in-place edit — a new document record is created.',
        'retry' => 'Retry',
        'retry_modal_heading' => 'Retry indexing',
        'retry_modal_description' => 'This will attempt to index this document again.',
        'delete' => 'Delete',
        'delete_modal_heading' => 'Delete document',
        'delete_modal_description' => 'This will remove the file from the Vector Store and OpenAI, then soft-delete the document record.',
    ],

];
