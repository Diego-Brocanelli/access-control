<?php
/**
 * @see       https://github.com/rpdesignerfly/access-control
 * @copyright Copyright (c) 2018 Ricardo Pereira Dias (https://rpdesignerfly.github.io)
 * @license   https://github.com/rpdesignerfly/access-control/blob/master/license.md
 */

declare(strict_types=1);

namespace Acl\Services;

use Illuminate\Http\Request;
use Acl\Repositories\AclUsersRepository;
use Acl\Repositories\AclUsersPermissionsRepository;
use Acl\Repositories\AclRolesRepository;
use Acl\Models\AclUserPermission;

class UsersPermissionsService implements EditPermissionsContract
{
    public function formEdit($id, Request $request = null)
    {
        $view = config('acl.views.users-permissions.edit');
        return view($view)->with([
            'user'         => ($user = (new AclUsersRepository)->read($id)),
            'structure'    => $this->getStructure($user->id, true),
            'route_index'  => config('acl.routes.users.index'),
            'route_user'   => config('acl.routes.users.edit'),
            'route_update' => config('acl.routes.users-permissions.update'),
            'route_groups' => config('acl.routes.groups.index'),
            'breadcrumb'   => [
                '<i class="fas fa-user"></i> Usuários' => route(config('acl.routes.users.index')),
                'Usuário "' . $user->name . '"' => route(config('acl.routes.users.edit'), $user->id),
                'Permissões'
            ]
        ]);
    }

    public function dataUpdate($id, array $data)
    {
        $results = [];
        foreach ($data['permissions'] as $slug => $perms) {

            $role = (new AclRolesRepository)->findBySlug($slug);

            // Aplica as permissões para o usuário
            $model = AclUserPermission::firstOrNew([
                'user_id' => $id,
                'role_id' => $role->id,
                ]);

            $model->fill([
                'create' => ($perms['create'] ?? 'no'),
                'read'   => ($perms['read'] ?? 'no'),
                'update' => ($perms['update'] ?? 'no'),
                'delete' => ($perms['delete'] ?? 'no'),
                ]);

            $results[] = $model->save();
        }

        $results = \array_unique($results);
        return count($results) == 1 && $results[0] == true;
    }

    /**
     * Este método devolve a estrutura de permissões para
     * a geração do formulário de edição.
     * Se $allows_null for true e o usuário não possuir permissões,
     * o valor null será retornado, caso contrário, uma estrutura
     * com valores desativados será retornada.
     *
     * @param  int  $id
     * @param  bool $allows_null
     * @return array|null
     */
    public function getStructure($id, $allows_null = false)
    {
        $permissions = [];

        $collection = (new AclUsersPermissionsRepository)->collectByUserID($id);
        if ($collection->count() > 0) {
            // Apenas as habilidades do usuário
            foreach ($collection as $item) {
                $permissions[$item->role->slug] = [
                    'create' => $item->create,
                    'read'   => $item->read,
                    'update' => $item->update,
                    'delete' => $item->delete,
                ];
            }
        }

        $structure = [];

        $all_abilities = (new RolesService)->getStructure();
        foreach ($all_abilities as $role => $item) {
            // Todas as habilidades disponiveis
            // no arquivo de coniguração ('permissions' => 'create,read,update,delete')
            foreach ($item['permissions'] as $ability => $nullable) {
                if ($nullable !== null) {
                    $structure[$role]['label'] = $all_abilities[$role]['label'];
                    $structure[$role]['permissions'][$ability] = isset($permissions[$role])
                        ? $permissions[$role][$ability] : 'no';
                } elseif($allows_null == true) {
                    // Nulos disponiveis para o formulário
                    $structure[$role]['permissions'][$ability] = null;
                }
            }
        }

        return $structure;
    }

    /**
     * Devolve as permissões para o usuário na função de acesso especificada
     * O formato procede assim: users.edit = {$role_slug}.edit

     * @param  int $user_id
     * @param  string $role_slug
     * @return Collection
     */
    public function getPermissionsByUserID($user_id, string $role_slug)
    {
        if (session('user.abilities') == null) {

            // Gera um cache de permissões
            // para evitar consultas ao banco de dados

            $cache_all   = [];
            $cache_slugs = [];
            $roles = \Acl\Models\AclRole::all();
            foreach($roles as $item) {
                $cache_all[$item->slug] = $item->toArray();
                $cache_slugs[$item->id] = $item->slug;
            }

            // As permissões setadas para o usuário tem precedência
            $user_permissions = \Acl\Models\AclUserPermission::where('user_id', $user_id)->get();
            if ($user_permissions->count() > 0) {
                foreach($user_permissions as $item) {
                    if (isset($cache_slugs[$item->role_id])) {
                        $slug = $cache_slugs[$item->role_id];
                        $cache_all[$slug]['permissions'] = $item->toArray();
                    }
                }

                if(isset($cache_all[$role_slug]) && isset($cache_all[$role_slug]['permissions'])) {
                    // A função de acesso foi encontrada nas permissões de usuário
                    \Acl\Core::traceCurrentAbilityOrigin('user');
                }
            }
            // Quando não existem permissões setadas para o usuário,
            // as permissões do grupo são usadas no lugar
            else {

                $group_relation = \Acl\Models\AclUser::find($user_id)->groupRelation;
                if($group_relation != null) {
                    $group_id = $group_relation->group_id;
                    $group_permissions = \Acl\Models\AclGroupPermission::where('group_id', $group_id)->get();
                } else {
                    $group_permissions = collect([]);
                }
                foreach($group_permissions as $item) {
                    if (isset($cache_slugs[$item->role_id])) {
                        $slug = $cache_slugs[$item->role_id];
                        $cache_all[$slug]['permissions'] = $item->toArray();
                    }
                }

                if(isset($cache_all[$role_slug]) && isset($cache_all[$role_slug]['permissions'])) {
                    // A função de acesso foi encontrada nas permissões de grupo
                    \Acl\Core::traceCurrentAbilityOrigin('group');
                }
            }

            session([ 'user.abilities' => collect($cache_all) ]);
        }

        $user_abilities = session('user.abilities');

        if (isset($user_abilities[$role_slug]) && isset($user_abilities[$role_slug]['permissions'])) {
            // A função de acesso foi encontrada
            return $user_abilities[$role_slug];
        } else {
            return null;
        }
    }
}
