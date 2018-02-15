<?php

use Illuminate\Contracts\Auth\Access\Gate;

/**
 * Para Mais informações sobre a criação de diretivas:
 * 
 * https://laravel.com/docs/5.5/blade#extending-blade
 * https://scotch.io/tutorials/all-about-writing-custom-blade-directives
 */

// Em modo de desenvolvimento, as views são sempre apagadas
if (env('APP_DEBUG') || env('APP_ENV') === 'local') {
    // php artisan view:clear
    \Artisan::call('view:clear');
}

    /*
    |--------------------------------------------------------------------------
    | Botões de Ação
    |--------------------------------------------------------------------------
    |
    | Botões que chamam uma url gerenciada pela ACL.
    | @acl_action('role', 'url', 'label', 'view opcional')
    |
    | Ex:
    | @acl_action('users.create', route('users.create'), 'Novo Usuário', 'users.btn-create')
    | @acl_action('users.create', route('users.create'), 'Novo Usuário')
    | @acl_action('users.create', '/usuarios/criar', 'Novo Usuário')
    |
    | Alternativas:
    | @acl_action_sm
    | @acl_action_lg
    */

    function blade_acl_action($expression, $size) {

        // Se a função route for usada, converte a virgula
        $start = strpos($expression, 'route');
        if ($start !== false) {
            $end = strpos($expression, ')', $start);
            $route_origin = substr($expression, $start, ($end-$start));
            $route_fix    = str_replace(',', '#|#|#', $route_origin);
            $expression = str_replace($route_origin, $route_fix, $expression);
        }

        $args = explode(',', $expression);

        $args[0] = $args[0] ?? '';
        $args[1] = $args[1] ?? '';
        $args[2] = $args[2] ?? '';
        $args[3] = $args[3] ?? '';

        // url (apenas action)
        $args[1] = trim($args[1]);

        // demais parâmetros
        foreach([0,2,3] as $index) {
            $args[$index] = str_replace("'", "", $args[$index]);
            $args[$index] = trim($args[$index]);
        }
        
        $ability = $args[0];
        $url     = $args[1];
        $label   = $args[2];
        $view    = $args[3];    

        $perm = preg_replace('#.*\.#', '', $ability);
        if (empty($view)) {
            $view = "laracl::buttons.{$perm}";
        }

        $status  = var_export(\Auth::user()->can($ability), true);
        $url     = ($status == 'true') ? $url : '\'javascript:void(0)\'';

        // Se a função route for usada, converte a virgula
        if ($status == 'true' && $start !== false) {
            $url = str_replace($route_fix, $route_origin, $url);
        }

        $open = "?php";
        $close = "?";

        return "<{$open} echo view('$view')->with(['size' => '$size', 'status' => $status, 'url' => $url, 'label'  => '$label' ])->render(); {$close}>";
    }

    \Blade::directive('acl_action', function ($expression) {
        return blade_acl_action($expression, 'none');
    });

    \Blade::directive('acl_action_sm', function ($expression) {
        return blade_acl_action($expression, 'sm');
    });

    \Blade::directive('acl_action_lg', function ($expression) {
        return blade_acl_action($expression, 'lg');
    });

    /*
    |--------------------------------------------------------------------------
    | Botões de Submissão de Formulário
    |--------------------------------------------------------------------------
    |
    | Botões do tipo submit.
    | @acl_submit('role', 'label', 'view opcional')
    | 
    | Ex:
    | @acl_submit('users.create', 'Gravar Registro', 'users.btn-submit')
    | @acl_submit('users.create', 'Gravar Registro')
    |
    | Alternativas:
    | @acl_submit_sm
    | @acl_submit_lg
    */

    function blade_acl_submit($expression, $size) {

        $args = explode(',', $expression);

        $args[0] = $args[0] ?? '';
        $args[1] = $args[1] ?? '';
        $args[2] = $args[2] ?? '';

        // demais parâmetros
        foreach($args as $index => $nulled) {
            $args[$index] = str_replace("'", "", $args[$index]);
            $args[$index] = trim($args[$index]);
        }
        
        $ability = $args[0];
        $label   = $args[1];
        $view    = $args[2];    

        $perm = preg_replace('#.*\.#', '', $ability);
        if (empty($view)) {
            $view = "laracl::buttons.submit";
        }

        $status  = var_export(\Auth::user()->can($ability), true);

        $open = "?php";
        $close = "?";

        return "<{$open} echo view('$view')->with(['size' => '$size', 'status' => $status, 'label'  => '$label' ])->render(); {$close}>";
    }

    \Blade::directive('acl_submit', function ($expression) {
        return blade_acl_submit($expression, 'none');
    });

    \Blade::directive('acl_submit_sm', function ($expression) {
        return blade_acl_submit($expression, 'sm');
    });

    \Blade::directive('acl_submit_lg', function ($expression) {
        return blade_acl_submit($expression, 'lg');
    });

    /*
    |--------------------------------------------------------------------------
    | Bloqueio de conteudo restrito
    |--------------------------------------------------------------------------
    |
    | Se o usuário não possuir credenciais, exibe uma mensagem de 
    | acesso não autorizado no lugar do conteudo.
    |
    | @aclock('role', 'callback opcional')
    |
    |  ... conteudo html
    |
    | @endaclock('view opcional')
    */

    \Blade::directive('aclock', function ($expression) {

        $args = explode(',', $expression);

        $args[0] = $args[0] ?? '';
        $args[1] = $args[1] ?? '';

        $ability = str_replace("'", "", $args[0]);
        $ability = trim($ability);

        $callback = trim($args[1]);

        $open = "?php";
        $close = "?";

        return "<{$open} if ( \Auth::user()->can('$ability') ): {$close}>";
    });

    \Blade::directive('endaclock', function ($expression) {
        
        $open = "?php";
        $close = "?";

        $expression = trim($expression);
        $view = !empty($expression) ? $expression : 'laracl::messages.forbidden';

        $code = "<{$open} else: {$close}>";
        $code.= "<{$open} echo view('$view')->with(['user' => \Auth::user()])->render(); {$close}>";

        $code.= "<{$open} endif; {$close}>";
        return $code;
    });