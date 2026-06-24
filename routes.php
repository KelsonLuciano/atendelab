<?php

// 1. CARREGAMENTO DOS CONTROLLERS E MIDDLEWARES
require_once __DIR__ . '/app/Middleware/auth.php';
require_once __DIR__ . '/app/Controllers/AuthController.php';
require_once __DIR__ . '/app/Controllers/UsuariosController.php';
require_once __DIR__ . '/app/Controllers/PessoasController.php';
require_once __DIR__ . '/app/Controllers/TiposAtendimentosController.php';
require_once __DIR__ . '/app/Controllers/AtendimentosController.php';

// 2. CAPTURA DO CONTROLLER E DA AÇÃO VIA URL
$controller = $_GET['controller'] ?? 'auth';
$action = $_GET['action'] ?? 'login';

// 3. ROTEADOR PRINCIPAL
switch ($controller) {
    
    // --- MÓDULO DE AUTENTICAÇÃO ---
    case 'auth':
        $authController = new AuthController();
        switch ($action) {
            case 'login':
                $authController->exibirLogin();
                break;
            case 'entrar':
                $authController->entrar();
                break;
            case 'dashboard':
                $authController->dashboard();
                break;
            case 'logout':
                $authController->logout();
                break;
            default:
                http_response_code(404);
                echo 'Acao de autenticacao nao encontrada.';
                break;
        }
        break;

    // --- MÓDULO DE USUÁRIOS ---
    case 'usuarios':
        $usuariosController = new UsuariosController();
        switch ($action) {
            case 'listar':
                $usuariosController->listar();
                break;
            case 'buscar': // Ajustado
                $usuariosController->buscarPorId();
                break;
            case 'criar':
                $usuariosController->criar();
                break;
            case 'atualizar':
                $usuariosController->atualizar();
                break;
            case 'excluir':
                $usuariosController->excluir();
                break;
            default:
                http_response_code(404);
                echo 'Acao de usuarios nao encontrada.';
                break;
        }
        break;

    // --- MÓDULO DE PESSOAS ---
    case 'pessoas':
        $pessoasController = new PessoasController();
        switch ($action) {
            case 'listar':
                $pessoasController->listar();
                break;
            case 'buscar': // Ajustado para bater com a tabela
                $pessoasController->buscarPorId();
                break;
            case 'criar':
                $pessoasController->criar();
                break;
            case 'atualizar':
                $pessoasController->atualizar();
                break;
            case 'inativar':
                $pessoasController->inativar();
                break;
            default:
                http_response_code(404);
                echo 'Acao de pessoas nao encontrada.';
                break;
        }
        break;

    // --- MÓDULO DE TIPOS DE ATENDIMENTOS ---
    case 'tipos': // Ajustado de 'tipos_atendimentos' para 'tipos'
        $tiposController = new TiposAtendimentosController();
        switch ($action) {
            case 'listar':
                $tiposController->listar();
                break;
            case 'buscar': // Ajustado
                $tiposController->buscarPorId();
                break;
            case 'criar':
                $tiposController->criar();
                break;
            case 'atualizar':
                $tiposController->atualizar();
                break;
            case 'inativar':
                $tiposController->inativar();
                break;
            default:
                http_response_code(404);
                echo 'Acao de tipos nao encontrada.';
                break;
        }
        break;

    // --- MÓDULO DE ATENDIMENTOS ---
    case 'atendimentos':
        $atendimentosController = new AtendimentosController();
        switch ($action) {
            case 'listar':
                $atendimentosController->listar();
                break;
            case 'buscar': // Ajustado
                $atendimentosController->buscarPorId();
                break;
            case 'criar':
                $atendimentosController->criar();
                break;
            case 'alterarStatus': // Ajustado de 'atualizarStatus' para 'alterarStatus'
                $atendimentosController->atualizarStatus();
                break;
            default:
                http_response_code(404);
                echo 'Acao de atendimentos nao encontrada.';
                break;
        }
        break;

    // --- CASO NENHUM CONTROLLER EXISTA ---
    default:
        http_response_code(404);
        echo 'Controller nao encontrado.';
        break;
}