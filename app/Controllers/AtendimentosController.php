<?php

class AtendimentosController
{
    private PDO $pdo;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        require __DIR__ . '/../../config/database.php';
        $this->pdo = $pdo;
    }

    public function listar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $sql = 'SELECT a.id, a.descricao, a.status, a.data_atendimento,
                       p.nome as pessoa_nome, 
                       t.nome as tipo_nome
                FROM atendimentos a
                JOIN pessoas p ON a.pessoa_id = p.id
                JOIN tipos_atendimentos t ON a.tipo_atendimento_id = t.id
                ORDER BY a.id DESC';
                
        $stmt = $this->pdo->query($sql);
        $atendimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($atendimentos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function visualizar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['erro' => 'ID inválido.']);
            return;
        }

        $sql = 'SELECT id, pessoa_id, tipo_atendimento_id, descricao, status FROM atendimentos WHERE id = :id';
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

    public function buscarPorId(): void { $this->visualizar(); }
    public function buscar(): void { $this->visualizar(); }
    public function detalhar(): void { $this->visualizar(); }

    public function criar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $pessoa_id = filter_input(INPUT_POST, 'pessoa_id', FILTER_VALIDATE_INT);
        $tipo_atendimento_id = filter_input(INPUT_POST, 'tipo_atendimento_id', FILTER_VALIDATE_INT);
        $descricao = trim($_POST['descricao'] ?? '');
        $status = trim($_POST['status'] ?? 'aberto');
        
        $usuario_id = $this->usuarioResponsavel();

        if (!$pessoa_id || !$tipo_atendimento_id || $descricao === '') {
            http_response_code(400);
            echo json_encode(['erro' => 'Pessoa, tipo de atendimento e descrição são obrigatórios.']);
            return;
        }

        if ($id) {
            $sql = 'UPDATE atendimentos 
                    SET pessoa_id = :pessoa_id, tipo_atendimento_id = :tipo_atendimento_id, descricao = :descricao, status = :status 
                    WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $mensagem = 'Atendimento atualizado com sucesso.';
        } else {
            $sql = 'INSERT INTO atendimentos (pessoa_id, tipo_atendimento_id, usuario_id, descricao, status) 
                    VALUES (:pessoa_id, :tipo_atendimento_id, :usuario_id, :descricao, :status)';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $mensagem = 'Atendimento cadastrado com sucesso.';
        }
        
        $stmt->bindValue(':pessoa_id', $pessoa_id, PDO::PARAM_INT);
        $stmt->bindValue(':tipo_atendimento_id', $tipo_atendimento_id, PDO::PARAM_INT);
        $stmt->bindValue(':descricao', $descricao);
        $stmt->bindValue(':status', $status);
        $stmt->execute();

        echo json_encode(['mensagem' => $mensagem]);
    }

    // ==========================================
    // FUNÇÃO QUE ATUALIZA O STATUS E OBSERVAÇÃO
    // ==========================================
    public function atualizarStatus(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $status = trim($_POST['status'] ?? '');
        
        // Pega a observação final do modal
        $observacao_final = trim($_POST['observacao_final'] ?? '');

        if (!$id || $status === '') {
            http_response_code(400);
            echo json_encode(['erro' => 'ID e status são obrigatórios.']);
            return;
        }

        // Faz o UPDATE também na observação
        $sql = 'UPDATE atendimentos SET status = :status, observacao_final = :observacao_final WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':observacao_final', $observacao_final);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['mensagem' => 'Status do atendimento atualizado com sucesso.']);
    }

    public function alterarStatus(): void { $this->atualizarStatus(); }
    // ==========================================

    public function opcoesFormulario(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $sqlPessoas = "SELECT id, nome FROM pessoas WHERE status = 'ativo' ORDER BY nome ASC";
            $stmtPessoas = $this->pdo->query($sqlPessoas);
            $pessoas = $stmtPessoas->fetchAll(PDO::FETCH_ASSOC);

            $sqlTipos = "SELECT id, nome FROM tipos_atendimentos WHERE status = 'ativo' ORDER BY nome ASC";
            $stmtTipos = $this->pdo->query($sqlTipos);
            $tipos = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'pessoas' => $pessoas,
                'tipos' => $tipos
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro interno ao carregar dados do formulário.']);
        }
    }

    private function usuarioResponsavel(): int
    {
        if (isset($_SESSION['usuario']['id'])) {
            return (int) $_SESSION['usuario']['id'];
        }
        return (int) filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
    }
}