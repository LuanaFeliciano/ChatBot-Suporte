<?php

return [

    'navigation_label' => 'Documentos',
    'model_label' => 'documento',
    'plural_model_label' => 'documentos',

    'fields' => [
        'id' => 'ID',
        'name' => 'Nome',
        'file' => 'Arquivo',
        'module' => 'Módulo',
        'doc_version' => 'Versão',
        'original_filename' => 'Nome do arquivo original',
        'status' => 'Status',
        'openai_file_id' => 'ID do arquivo na OpenAI',
        'vector_store_file_id' => 'ID do arquivo no Vector Store',
        'error_message' => 'Mensagem de erro',
        'uploaded_by' => 'Enviado por',
        'file_size' => 'Tamanho do arquivo',
        'mime_type' => 'Tipo MIME',
        'created_at' => 'Criado em',
        'updated_at' => 'Atualizado em',
        'deleted_at' => 'Excluído em',
    ],

    'actions' => [
        'replace' => 'Substituir',
        'replace_modal_heading' => 'Substituir documento',
        'replace_modal_description' => 'Um novo arquivo será enviado e indexado. Quando a indexação for concluída com sucesso, o documento atual será aposentado (excluído de forma reversível). Isso não é uma edição no local — um novo registro de documento é criado.',
        'retry' => 'Tentar novamente',
        'retry_modal_heading' => 'Tentar indexação novamente',
        'retry_modal_description' => 'Isso tentará indexar este documento novamente.',
        'delete' => 'Excluir',
        'delete_modal_heading' => 'Excluir documento',
        'delete_modal_description' => 'Isso removerá o arquivo do Vector Store e da OpenAI e, em seguida, excluirá o registro do documento de forma reversível.',
    ],

];
