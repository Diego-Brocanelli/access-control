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
use Acl\Repositories\AclGroupsRepository;
use Acl\Models\AclUserGroup;
use Acl\Models\AclUserPermission;
use SortableGrid\Traits\HasSortableGrid;

class GroupsService implements CrudFrontContract, CrudBackContract
{
    use HasSortableGrid;

    public function gridList(Request $request = null)
    {
        $this->setInitials('id', 'desc', 10);

        $this->addGridField('ID', 'id');
        $this->addGridField('Nome', 'name');
        $this->addGridField('Criação', 'created_at');
        $this->addGridField('Ações');

        $this->addSearchField('id');
        $this->addSearchField('name');

        $this->addOrderlyField('id');
        $this->addOrderlyField('name');
        $this->addOrderlyField('created_at');

        $provider = $this->getSearcheable();
        $this->setDataProvider($provider);

        $view = config('acl.views.groups.index');
        return $this->gridView($view)->with([
            'route_create'      => config('acl.routes.groups.create'),
            'route_edit'        => config('acl.routes.groups.edit'),
            'route_destroy'     => config('acl.routes.groups.destroy'),
            'route_permissions' => config('acl.routes.groups-permissions.edit'),
            'route_trash'       => config('acl.routes.groups.trash'),
            'breadcrumb'        => [
                '<i class="fas fa-user"></i> Usuários' => route(config('acl.routes.users.index')),
                'Grupos'
            ]
        ]);
    }

    public function gridTrash(Request $request = null)
    {
        $this->setInitials('id', 'desc', 10);

        $this->addGridField('ID', 'id');
        $this->addGridField('Nome', 'name');
        $this->addGridField('Criação', 'created_at');
        $this->addGridField('Ações');

        $this->addSearchField('id');
        $this->addSearchField('name');

        $this->addOrderlyField('id');
        $this->addOrderlyField('name');
        $this->addOrderlyField('created_at');

        $provider = $this->getSearcheable()->onlyTrashed();
        $this->setDataProvider($provider);

        $view = config('acl.views.groups.trash');
        return $this->gridView($view)->with([
            'route_create'      => config('acl.routes.groups.create'),
            'route_edit'        => config('acl.routes.groups.edit'),
            'route_destroy'     => config('acl.routes.groups.destroy'),
            'route_permissions' => config('acl.routes.groups-permissions.edit'),
            'route_trash'       => config('acl.routes.groups.trash'),
            'route_restore'     => config('acl.routes.groups.restore'),
            'breadcrumb'        => [
                '<i class="fas fa-user"></i> Usuários' => route(config('acl.routes.users.index')),
                '<i class="fas fa-user-friends"></i> Grupos' => route(config('acl.routes.groups.index')),
                'Lixeira'
            ]
        ]);
    }

    public function getSearcheable()
    {
        return (new AclGroupsRepository)->newQuery();
    }

    public function formCreate(Request $request = null)
    {
        $view = config('acl.views.groups.create');
        return view($view)->with([
            'model'       => (new AclGroupsRepository)->read(),
            'title'       => 'Novo Grupo de Acesso',
            'route_store' => config('acl.routes.groups.store'),
            'route_users' => config('acl.routes.users.index'),
            'breadcrumb'  => [
                '<i class="fas fa-user"></i> Usuários' => route(config('acl.routes.users.index')),
                '<i class="fas fa-user-friends"></i> Grupos' => route(config('acl.routes.groups.index')),
                'Novo Grupo'
            ]
        ]);
    }

    public function formEdit($id, Request $request = null)
    {
        $view = config('acl.views.groups.edit');
        return view($view)->with([
            'model'             => ($group = (new AclGroupsRepository)->read((int) $id)),
            'title'             => 'Editar Grupo de Acesso',
            'route_update'      => config('acl.routes.groups.update'),
            'route_create'      => config('acl.routes.groups.create'),
            'route_permissions' => config('acl.routes.groups-permissions.edit'),
            'breadcrumb'        => [
                '<i class="fas fa-user"></i> Usuários' => route(config('acl.routes.users.index')),
                '<i class="fas fa-user-friends"></i> Grupos' => route(config('acl.routes.groups.index')),
                $group->name
            ]
        ]);
    }

    public function dataInsert(array $data)
    {
        return (new AclGroupsRepository)->create($data);
    }

    public function dataUpdate($id, array $data)
    {
        return (new AclGroupsRepository)->update($id, $data);
    }

    public function dataDelete($id, array $data = null)
    {
        if (isset($data['mode']) && $data['mode'] == 'soft') {
            $deleted = (new AclGroupsRepository)->delete($id);
        } else {
            $deleted = (new AclGroupsRepository)->delete($id, true);
        }
        return $deleted;
    }

    public function dataRestore($id, array $data = null)
    {
        $restored = (new AclGroupsRepository)->restore($id);
        return $restored;
    }
}
