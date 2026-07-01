<?php

// 1. CARREGAMENTO APENAS DOS MIDDLEWARES E FUNÇÕES GLOBAIS
require_once __DIR__ . '/app/Middleware/auth.php';

// Função auxiliar para padronizar as mensagens de erro de rota não encontrada
function responderRotaNaoEncontrada(string $mensagem): void {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['erro' => $mensagem], JSON_UNESCAPED_UNICODE);
}

// 2. CAPTURA DO CONTROLLER E DA AÇÃO VIA URL
$controller = $_GET['controller'] ?? 'auth';
$action = $_GET['action'] ?? 'login';

// 3. ROTEADOR PRINCIPAL (Com carregamento sob demanda)
switch ($controller) {
    
    // --- MÓDULO DE AUTENTICAÇÃO ---
    case 'auth':
        require_once __DIR__ . '/app/Controllers/AuthController.php';
        $authController = new AuthController();
        
        switch ($action) {
            case 'login':
                $authController->exibirLogin();
                break;
            case 'entrar':
                $authController->entrar();
                break;
            case 'dashboard':
                exigirAutenticacao();
                $authController->dashboard();
                break;
            case 'logout':
                $authController->logout();
                break;
            default:
                responderRotaNaoEncontrada('Ação de autenticação não encontrada.');
                break;
        }
        break;

    // --- MÓDULO FRONTEND (Páginas visuais - ATUALIZADO CONFORME A APOSTILA) ---
    case 'frontend':
        exigirAutenticacao(); // Garante que as páginas estão protegidas por sessão 
        require_once __DIR__ . '/app/Controllers/FrontendController.php';
        $frontendController = new FrontendController();
        
        switch ($action) {
            case 'pessoas':
                $frontendController->pessoas(); // Carrega app/Views/pessoas/index.php 
                break;
            case 'tipos':
                $frontendController->tiposAtendimentos(); // Carrega app/Views/tipos/index.php 
                break;
            case 'atendimentos':
                $frontendController->atendimentos(); // Carrega app/Views/atendimentos/index.php 
                break;
            default:
                responderRotaNaoEncontrada('Página visual não encontrada.');
                break;
        }
        break;

    // --- MÓDULO DE DADOS: USUÁRIOS ---
    case 'usuarios':
        exigirAutenticacao();
        require_once __DIR__ . '/app/Controllers/UsuariosController.php';
        $usuariosController = new UsuariosController();
        
        switch ($action) {
            case 'listar':
                $usuariosController->listar();
                break;
            case 'buscarPorId':
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
                responderRotaNaoEncontrada('Ação de usuários não encontrada.');
                break;
        }
        break;

    // --- MÓDULO DE DADOS: PESSOAS ---
    case 'pessoas':
        exigirAutenticacao();
        require_once __DIR__ . '/app/Controllers/PessoasController.php';
        $pessoasController = new PessoasController();
        
        switch ($action) {
            case 'listar':
                $pessoasController->listar(); // Fornece os dados reais via JSON 
                break;
            case 'buscar':
            case 'buscarPorId':
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
                responderRotaNaoEncontrada('Ação de pessoas não encontrada.');
                break;
        }
        break;

    // --- MÓDULO DE DADOS: TIPOS DE ATENDIMENTOS ---
    case 'tipos':
        exigirAutenticacao();
        require_once __DIR__ . '/app/Controllers/TiposAtendimentosController.php';
        $tiposController = new TiposAtendimentosController();
        
        switch ($action) {
            case 'listar':
                $tiposController->listar(); // Fornece os dados reais via JSON 
                break;
            case 'buscar': // Adicionado suporte a 'buscar' exigido na pág. 7 da apostila 
            case 'buscarPorId':
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
                responderRotaNaoEncontrada('Ação de tipos de atendimento não encontrada.');
                break;
        }
        break;

    // --- MÓDULO DE DADOS: ATENDIMENTOS ---
    case 'atendimentos':
            exigirAutenticacao();
            require_once __DIR__ . '/app/Controllers/AtendimentosController.php';
            $atendimentosController = new AtendimentosController();
            
            switch ($action) {
                case 'listar':
                    $atendimentosController->listar();
                    break;
                case 'buscar':
                case 'buscarPorId':
                case 'visualizar':
                    $atendimentosController->visualizar();
                    break;
                    
                // === ADICIONADO O CASE ATUALIZAR AQUI ===
                case 'atualizar':
                case 'criar':
                    $atendimentosController->criar(); // Controla tanto o insert quanto o update seguros
                    break;
                    
                case 'alterarStatus':
                case 'atualizarStatus':
                    $atendimentosController->atualizarStatus();
                    break;
                case 'opcoesFormulario':
                    $atendimentosController->opcoesFormulario();
                    break;
                default:
                    responderRotaNaoEncontrada('Ação de atendimentos não encontrada.');
                    break;
            }
            break;

    // --- CASO NENHUM CONTROLLER EXISTA ---
    default:
        responderRotaNaoEncontrada('Controller não encontrado no sistema.');
        break;
}