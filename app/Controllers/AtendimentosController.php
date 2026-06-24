<?php

class AtendimentosController
{
    private PDO $pdo;

    public function __construct()
    {
        require __DIR__ . '/../../config/database.php';
        $this->pdo = $pdo;
    }

    public function listar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        // Listagem com JOIN conforme exigido pelo professor
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

    public function buscarPorId(): void
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

    public function criar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $pessoa_id = filter_input(INPUT_POST, 'pessoa_id', FILTER_VALIDATE_INT);
        $tipo_atendimento_id = filter_input(INPUT_POST, 'tipo_atendimento_id', FILTER_VALIDATE_INT);
        $usuario_id = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
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
}