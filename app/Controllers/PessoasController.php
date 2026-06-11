<?php

// Controller da entidade pessoas.
class PessoasController
{
    // Cria uma propriedade para guardar a conexão com o banco[cite: 544].
    private PDO $pdo;

    public function __construct()
    {
        // Importa o arquivo que inicializa o objeto $pdo[cite: 190].
        require __DIR__ . '/../../config/database.php';
        $this->pdo = $pdo;
    }

    public function listar(): void
    {
        // Informa ao navegador que a resposta será JSON[cite: 544].
        header('Content-Type: application/json; charset=utf-8');

        $sql = 'SELECT id, nome, documento, telefone, curso, periodo, status FROM pessoas ORDER BY id DESC';
        $stmt = $this->pdo->query($sql);
        $pessoas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Retorna a resposta em formato JSON[cite: 544].
        echo json_encode($pessoas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function buscarPorId(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Valida dados recebidos pela URL[cite: 544].
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['erro' => 'ID inválido.']);
            return;
        }

        // Prepara uma consulta SQL segura com parâmetros[cite: 544].
        $sql = 'SELECT id, nome, documento, telefone, curso, periodo, status FROM pessoas WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        
        // Associa valores aos parâmetros da consulta[cite: 544].
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $pessoa = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pessoa) {
            http_response_code(404);
            echo json_encode(['erro' => 'Pessoa não encontrada.']);
            return;
        }

        echo json_encode($pessoa, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function criar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Coleta dados do formulário via POST
        $nome = trim($_POST['nome'] ?? '');
        $documento = trim($_POST['documento'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $curso = trim($_POST['curso'] ?? '');
        $periodo = trim($_POST['periodo'] ?? '');
        $status = trim($_POST['status'] ?? 'ativo');

        // Validação básica
        if ($nome === '' || $documento === '') {
            // Define código HTTP de erro[cite: 544].
            http_response_code(400);
            echo json_encode(['erro' => 'Nome e documento são obrigatórios.']);
            return;
        }

        try {
            $sql = 'INSERT INTO pessoas (nome, documento, telefone, curso, periodo, status) 
                    VALUES (:nome, :documento, :telefone, :curso, :periodo, :status)';
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindValue(':nome', $nome);
            $stmt->bindValue(':documento', $documento);
            $stmt->bindValue(':telefone', $telefone);
            $stmt->bindValue(':curso', $curso);
            $stmt->bindValue(':periodo', $periodo);
            $stmt->bindValue(':status', $status);
            
            $stmt->execute();

            // Define código HTTP de sucesso[cite: 544].
            http_response_code(201);
            echo json_encode([
                'mensagem' => 'Pessoa cadastrada com sucesso.',
                'id' => $this->pdo->lastInsertId()
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao cadastrar pessoa. O documento já pode estar em uso.']);
        }
    }

    public function atualizar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // ID vem no POST para operação de update[cite: 401].
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nome = trim($_POST['nome'] ?? '');
        $documento = trim($_POST['documento'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $curso = trim($_POST['curso'] ?? '');
        $periodo = trim($_POST['periodo'] ?? '');
        $status = trim($_POST['status'] ?? 'ativo');

        if (!$id || $nome === '' || $documento === '') {
            http_response_code(400);
            echo json_encode(['erro' => 'ID, nome e documento são obrigatórios.']);
            return;
        }

        try {
            $sql = 'UPDATE pessoas SET nome = :nome, documento = :documento, telefone = :telefone, 
                    curso = :curso, periodo = :periodo, status = :status WHERE id = :id';
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':nome', $nome);
            $stmt->bindValue(':documento', $documento);
            $stmt->bindValue(':telefone', $telefone);
            $stmt->bindValue(':curso', $curso);
            $stmt->bindValue(':periodo', $periodo);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            $stmt->execute();

            echo json_encode(['mensagem' => 'Dados da pessoa atualizados com sucesso.'], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao atualizar dados da pessoa.']);
        }
    }

    public function excluir(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Exclusão por ID recebido no corpo da requisição[cite: 503].
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['erro' => 'ID inválido.']);
            return;
        }

        try {
            // Em sistemas reais com chaves estrangeiras, a exclusão física pode ser bloqueada[cite: 706]. 
            // Como a aula propõe o DELETE para demonstração, manteremos o padrão[cite: 705].
            $sql = 'DELETE FROM pessoas WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(['mensagem' => 'Pessoa excluída com sucesso.'], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao excluir pessoa. Verifique se existem atendimentos vinculados a ela.']);
        }
    }
}