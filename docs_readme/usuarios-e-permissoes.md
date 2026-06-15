# Usuários e Permissões

## Objetivo

Descrever o controle de acesso baseado em papéis (RBAC) do painel administrativo,
construído sobre o `spatie/laravel-permission`, e a trilha de auditoria.

## Acesso ao painel

`User` implementa `FilamentUser`. O acesso ao painel exige:

```php
canAccessPanel() => is_active === true && (hasRole(Admin) || hasRole(Support))
```

Ou seja, um usuário precisa estar **ativo** e ter o papel **Admin** ou **Support**.

## Papéis (`RoleName`)

| Papel | Descrição |
|---|---|
| `Admin` | Acesso total. Recebe **todas** as permissões. |
| `Support` | Acesso de suporte: conversas, lacunas de conhecimento e feedback. |

## Permissões (`PermissionName`)

| Permissão | Concede acesso a |
|---|---|
| `manage-users` | Gestão de usuários |
| `manage-roles` | Gestão de papéis/permissões |
| `manage-documents` | Gestão da base de conhecimento |
| `view-documents` | Visualização (somente leitura) da base de conhecimento |
| `view-conversations` | Explorador de conversas |
| `view-analytics` | Dashboard de Analytics e widgets |
| `view-audit-logs` | Logs de auditoria |
| `view-knowledge-gaps` | Página de Lacunas de Conhecimento |
| `view-feedback` | Dados de feedback |

### Atribuições padrão (seeder)

`RolesAndPermissionsSeeder` cria as 9 permissões e atribui:

- **Admin** → todas as permissões.
- **Support** → `view-conversations`, `view-knowledge-gaps`, `view-feedback`, `view-documents`.

E cria o usuário admin inicial a partir de `ADMIN_EMAIL`/`ADMIN_PASSWORD`
(`config/admin.php`), com o papel `Admin`.

## Policies

As policies (registradas em `AppServiceProvider`) traduzem permissões em
autorização por modelo:

| Policy | Regra |
|---|---|
| `DocumentPolicy` | `viewAny`/`view` exigem `manage-documents` **ou** `view-documents` (acesso somente leitura); `create`/`update`/`delete` exigem `manage-documents`. |
| `UserPolicy` | Todas as ações exigem `manage-users`. |
| `RolePolicy` | Todas as ações exigem `manage-roles`. |
| `BotMessagePolicy` | `viewAny`/`view` exigem `view-conversations`; **`create`/`update`/`delete` retornam sempre `false`** (conversas são somente leitura). |

## Regras de proteção

- **Último admin**: ao excluir um usuário, se ele for o **único** com papel
  `Admin`, a exclusão é bloqueada com uma notificação de erro (`EditUser`).
- **Papel `Admin` imutável**: na edição de papéis, as permissões do papel `Admin`
  **não** podem ser alteradas — o conjunto é sempre todas as permissões
  (`EditRole` ignora as permissões enviadas para o papel `Admin`).

## Trilha de auditoria

Ações administrativas geram registros em `audit_logs`
(`action`, `entity_type`, `entity_id`, `payload`, `performed_by`).

### Ações registradas (`AuditAction`)

| Ação | Quando |
|---|---|
| `document.uploaded` | Documento indexado com sucesso |
| `document.updated` | Documento substituído (com `old_document_id` no payload) |
| `document.deleted` | Documento removido |
| `document.error` | Falha de indexação |
| `user.created` | Usuário criado |
| `user.updated` | Usuário editado |
| `user.deactivated` | Usuário desativado (`is_active` true → false) |
| `user.role_changed` | Papel alterado (com `old_role`/`new_role`) |
| `role.permissions_updated` | Permissões de um papel alteradas |

`AuditEntityType`: `document`, `bot_message`, `user`, `role`.

> Em edições de usuário, um único save pode gerar múltiplos logs: sempre
> `user.updated`, e adicionalmente `user.deactivated` e/ou `user.role_changed`
> conforme o que mudou.

## Internacionalização

`RoleName`, `PermissionName` e `DocumentStatus` implementam `HasLabel` e usam
traduções de `lang/{locale}/enums.php`. Ver [internacionalizacao.md](internacionalizacao.md).

## Componentes relacionados

- `App\Models\User`, `App\Enums\RoleName`, `App\Enums\PermissionName`
- `App\Policies\*`
- `App\Filament\Resources\Users\*`, `App\Filament\Resources\Roles\*`
- `database/seeders/RolesAndPermissionsSeeder`
- `App\Models\AuditLog`, `App\Enums\AuditAction`, `App\Enums\AuditEntityType`
