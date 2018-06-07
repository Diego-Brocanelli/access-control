
@component('laracl::document')

    @slot('title') Permissões Específicas para {{ $user->name }} @endslot

    @include('laracl::breadcrumb')

    <hr>

    @acl_content('users-permissions.read')

        <div class="row mb-3">

            <div class="col text-right">

                @acl_action('users.update', route($route_user, $user->id), 'Editar Usuário', 'laracl::buttons.users.update')

                @acl_action('groups.read', route($route_groups), 'Grupos de Acesso', 'laracl::buttons.groups.read')

            </div>

        </div>

        @if($user->groupRelation != null)

            <div class="row">

                <div class="col">

                    <div class="alert alert-info">

                        <h4 class="alert-heading">Atenção, este usuário pertence ao grupo <strong>"{{ $user->groupRelation->group->name }}"</strong>!</h4>

                        <p>
                            Atualmente {{ $user->name }} possui as permissões do grupo "{{ $user->groupRelation->group->name }}".
                            Clicando em <i>"Aplicar Permissões"</i>, este usuário possuirá <strong>Privilégios Exclusivos</strong>.
                            Você poderá remover estes privilégios, recolocando {{ $user->name }} novamente em um grupo na tela de edição do usuário, bastando setar um grupo para ele.
                        </p>

                    </div>

                </div>

            </div>

        @endif

        <form method="post" action="{{ route($route_update, $user->id) }}">

            <div class="row">

                <div class="col">

                    {{ csrf_field() }}

                    {{ method_field('PUT') }}
                    {{-- https://laravel.com/docs/5.5/controllers#resource-controllers --}}

                    <table class="table table-striped table-bordered">

                        <thead>

                            <th>Áreas de Acesso</th>

                            <th class="text-center" style="background:rgba(0,0,0,0.05)"><i class="fa fa-eye"></i> Ver</th>

                            <th class="text-center"><i class="fa fa-plus-circle"></i> Criar</th>

                            <th class="text-center" style="background:rgba(0,0,0,0.05)"><i class="fa fa-edit"></i> Editar</th>

                            <th class="text-center"><i class="fa fa-times-circle"></i> Excluir</th>

                        </thead>

                        <tbody>

                            @foreach($structure as $role => $item)

                                <tr>
                                    <td>
                                        {{ $item['label'] }}

                                        {{-- É necessário para que a função de acesso sempre exista na matriz,
                                        mesmo quando não existirem permissões ativas --}}
                                        <input type="hidden" name="permissions[{{ $role }}][exists]" value="1">
                                    </td>

                                    @foreach($item['permissions'] as $perm => $perm_value)

                                        @php
                                        $perm_name = "permissions[{$role}][{$perm}]";
                                        @endphp

                                        @if($loop->iteration%2 == 0)
                                        <td class="text-center">
                                        @else
                                        <td class="text-center" style="background:rgba(0,0,0,0.05)">
                                        @endif

                                            @if($perm_value != null)

                                                <input type="checkbox" name="{{ $perm_name }}" class="check-toggle"
                                                       data-on-text="Sim" data-off-text="Não"
                                                       value="yes" {{ old_check($perm_name, 'yes', $perm_value) }}>

                                            @endif

                                        </td>

                                    @endforeach

                                </tr>

                            @endforeach

                        </tbody>
                    </table>

                </div>

            </div>

            <div class="row">

                <div class="col text-right">

                    @acl_submit_lg('users-permissions.update', 'Aplicar Permissões')

                </div>

            </div>

        </form>

    @end_acl_content

@endcomponent
