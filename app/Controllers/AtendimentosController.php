<?php

class AtendimentosController
{
    private PDO $pdo;

    public function __construct()
    {
        require __DIR__ . '/../../config/database.php';
        $this->pdo = $pdo;
    }

    // 1. LISTAR ATENDIMENTOS (Com JOIN)
    public function listar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $sql = 'SELECT a.id, a.data_atendimento, a.horario_atendimento, a.status, a.descricao,
                       p.nome as pessoa_nome, 
                       t.nome as tipo_atendimento_nome, 
                       u.nome as usuario_nome
                FROM atendimentos a
                JOIN pessoas p ON a.pessoa_id = p.id
                JOIN tipos_atendimentos t ON a.tipo_atendimento_id = t.id
                JOIN usuarios u ON a.usuario_id = u.id
                ORDER BY a.data_atendimento DESC, a.horario_atendimento DESC';
                
        $stmt = $this->pdo->query($sql);
        $atendimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($atendimentos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // 2. VISUALIZAR UM ATENDIMENTO (Antigo buscarPorId renomeado para a Aula 05)
    public function visualizar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['erro' => 'ID inválido.']);
            return;
        }

        $sql = 'SELECT * FROM atendimentos WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $atendimento = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$atendimento) {
            http_response_code(404);
            echo json_encode(['erro' => 'Atendimento não encontrado.']);
            return;
        }
        echo json_encode($atendimento, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // 3. CRIAR NOVO ATENDIMENTO
    public function criar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $pessoa_id = filter_input(INPUT_POST, 'pessoa_id', FILTER_VALIDATE_INT);
        $tipo_atendimento_id = filter_input(INPUT_POST, 'tipo_atendimento_id', FILTER_VALIDATE_INT);
        
        // MUDANÇA AQUI: Chamada do método seguro no lugar do INPUT_POST
        $usuario_id = $this->usuarioResponsavel();
        
        $descricao = trim($_POST['descricao'] ?? '');
        $data_atendimento = trim($_POST['data_atendimento'] ?? date('Y-m-d'));
        $horario_atendimento = trim($_POST['horario_atendimento'] ?? date('H:i:s'));
        $status = trim($_POST['status'] ?? 'aberto');

        if (!$pessoa_id || !$tipo_atendimento_id || !$usuario_id || $descricao === '') {
            http_response_code(400);
            echo json_encode(['erro' => 'Pessoa, tipo de atendimento, usuário e descrição são obrigatórios.']);
            return;
        }

        $sql = 'INSERT INTO atendimentos 
                (pessoa_id, tipo_atendimento_id, usuario_id, descricao, data_atendimento, horario_atendimento, status) 
                VALUES 
                (:pessoa_id, :tipo_atendimento_id, :usuario_id, :descricao, :data_atendimento, :horario_atendimento, :status)';
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':pessoa_id', $pessoa_id, PDO::PARAM_INT);
        $stmt->bindValue(':tipo_atendimento_id', $tipo_atendimento_id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->bindValue(':descricao', $descricao);
        $stmt->bindValue(':data_atendimento', $data_atendimento);
        $stmt->bindValue(':horario_atendimento', $horario_atendimento);
        $stmt->bindValue(':status', $status);
        $stmt->execute();

        http_response_code(201);
        echo json_encode(['mensagem' => 'Atendimento cadastrado com sucesso.']);
    }

    // 4. ATUALIZAR STATUS DO ATENDIMENTO (Atende a 'alterarStatus' e 'atualizarStatus')
    public function atualizarStatus(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $status = trim($_POST['status'] ?? '');
        $observacao_final = trim($_POST['observacao_final'] ?? '');

        if (!$id || $status === '') {
            http_response_code(400);
            echo json_encode(['erro' => 'ID e status são obrigatórios.']);
            return;
        }

        $sql = 'UPDATE atendimentos SET status = :status, observacao_final = :observacao_final WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':observacao_final', $observacao_final);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['mensagem' => 'Status do atendimento atualizado com sucesso.']);
    }

    // 5. NOVA ROTA EXIGIDA NA AULA 05 (Retorna listas para alimentar o formulário do Front)
    public function opcoesFormulario(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            // Busca apenas as pessoas que estão com status ativo
            $sqlPessoas = "SELECT id, nome FROM pessoas WHERE status = 'ativo' ORDER BY nome ASC";
            $stmtPessoas = $this->pdo->query($sqlPessoas);
            $pessoas = $stmtPessoas->fetchAll(PDO::FETCH_ASSOC);

            // Busca apenas os tipos de atendimentos que estão ativos
            $sqlTipos = "SELECT id, nome FROM tipos_atendimentos WHERE status = 'ativo' ORDER BY nome ASC";
            $stmtTipos = $this->pdo->query($sqlTipos);
            $tipos = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);

            // Retorna os dois blocos juntos num JSON estruturado para o Frontend
            echo json_encode([
                'pessoas' => $pessoas,
                'tipos' => $tipos
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro interno ao carregar dados do formulário.']);
        }
    }

    // 6. FUNÇÃO DE SEGURANÇA (Pega o usuário da sessão logada)
    private function usuarioResponsavel(): int
    {
        if (isset($_SESSION['usuario']['id'])) {
            return (int) $_SESSION['usuario']['id'];
        }

        // Mantive o filter_input como fallback caso você esteja testando a rota 
        // no Thunder Client e tenha esquecido de passar o cookie de sessão.
        return (int) filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
    }
}