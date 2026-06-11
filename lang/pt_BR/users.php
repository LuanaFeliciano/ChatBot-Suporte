<?php

return [

    'navigation_label' => 'Usuários',
    'model_label' => 'usuário',
    'plural_model_label' => 'usuários',

    'fields' => [
        'name' => 'Nome',
        'email' => 'Endereço de e-mail',
        'password' => 'Senha',
        'password_confirmation' => 'Confirmar senha',
        'role' => 'Papel',
        'is_active' => 'Ativo',
        'last_login' => 'Último acesso',
        'created_at' => 'Criado em',
        'never' => 'Nunca',
    ],

    'validation' => [
        'cannot_remove_last_admin_role' => 'Não é possível remover o papel de Administrador do único administrador restante.',
        'cannot_deactivate_last_admin' => 'Não é possível desativar o único administrador restante.',
        'cannot_delete_last_admin' => 'Não é possível excluir o único administrador restante.',
    ],

];
