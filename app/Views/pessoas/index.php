<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastro de Pessoas - AtendeLab</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <span class="navbar-brand">AtendeLab - Módulo Pessoas</span>
        <a class="btn btn-outline-light btn-sm" href="?controller=auth&action=dashboard">Voltar ao Painel</a>
    </div>
</nav>

<div class="container mt-4">
    <div id="alerta"></div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Gerenciamento de Pessoas</h2>
        <button class="btn btn-primary" onclick="abrirFormulario()">Nova Pessoa</button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Nome</th>
                            <th>Documento</th>
                            <th>E-mail</th>
                            <th>Curso</th>
                            <th>Período</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="tabelaPessoas">
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Carregando pessoas...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPessoa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitulo">Cadastrar Pessoa</h5>
                <button type="button" class="btn-close" onclick="fecharFormulario()"></button>
            </div>
            <form id="formPessoa">
                <div class="modal-body">
                    <input type="hidden" id="pessoaId" name="id">

                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome Completo</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>
                    <div class="mb-3">
                        <label for="documento" class="form-label">Documento (CPF/RG)</label>
                        <input type="text" class="form-control" id="documento" name="documento" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="curso" class="form-label">Curso (Opcional)</label>
                        <input type="text" class="form-control" id="curso" name="curso">
                    </div>
                    <div class="mb-3">
                        <label for="periodo" class="form-label">Período (Opcional)</label>
                        <input type="text" class="form-control" id="periodo" name="periodo">
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="fecharFormulario()">Cancelar</button>
                    <button type="submit" class="btn btn-success">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $baseUrl ?? '/atendelab/public/' ?>assets/js/api.js"></script>

<script>
    // Inicializa o componente de Modal do Bootstrap
    const modalBootstrap = new bootstrap.Modal(document.getElementById('modalPessoa'));
    const formPessoa = document.getElementById('formPessoa');

    // Funções de controle visual da Janela (Modal)
    function abrirFormulario() {
        formPessoa.reset();
        document.getElementById('pessoaId').value = '';
        document.getElementById('modalTitulo').textContent = 'Cadastrar Pessoa';
        modalBootstrap.show();
    }

    function fecharFormulario() {
        modalBootstrap.hide();
    }

    // --- CÓDIGO DO PROFESSOR: FLUXO DE LEITURA ---
    async function carregarPessoas() {
        try {
            const dados = AtendeLabApi.toList(await AtendeLabApi.get('pessoas', 'listar'));
            const tbody = document.getElementById('tabelaPessoas');
            if (!dados.length) { 
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4">Nenhuma pessoa cadastrada.</td></tr>'; 
                return; 
            }
            tbody.innerHTML = dados.map(p => `<tr>
                <td>${AtendeLabApi.escape(p.nome)}</td>
                <td>${AtendeLabApi.escape(p.documento)}</td>
                <td>${AtendeLabApi.escape(p.email)}</td>
                <td>${AtendeLabApi.escape(p.curso || '')}</td>
                <td>${AtendeLabApi.escape(p.periodo || '')}</td>
                <td><span class="badge ${p.status === 'ativo' ? 'text-bg-success' : 'text-bg-secondary'}">${AtendeLabApi.escape(p.status)}</span></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary" onclick="editarPessoa(${Number(p.id)})">Editar</button> 
                    <button class="btn btn-sm btn-outline-danger" onclick="inativarPessoa(${Number(p.id)})">Inativar</button>
                </td>
            </tr>`).join('');
        } catch (error) { 
            AtendeLabApi.showAlert('alerta', error.message, 'danger'); 
        }
    }

    // --- CÓDIGO DO PROFESSOR: FLUXO DE GRAVAÇÃO ---
    formPessoa.addEventListener('submit', async event => {
        event.preventDefault();
        const id = document.getElementById('pessoaId').value;
        try {
            await AtendeLabApi.post('pessoas', id ? 'atualizar' : 'criar', new FormData(formPessoa));
            AtendeLabApi.showAlert('alerta', id ? 'Pessoa atualizada com sucesso.' : 'Pessoa cadastrada com sucesso.');
            fecharFormulario(); 
            await carregarPessoas();
        } catch (error) { 
            AtendeLabApi.showAlert('alerta', error.message, 'danger'); 
        }
    });

    // Funções complementares para os botões da tabela funcionarem
    async function editarPessoa(id) {
        try {
            // Busca os dados da pessoa específica no back-end
            const pessoa = AtendeLabApi.toObject(await AtendeLabApi.get('pessoas', 'buscarPorId', { id }));
            
            // Preenche o formulário com os dados retornados
            document.getElementById('pessoaId').value = p.id || id;
            document.getElementById('nome').value = pessoa.nome;
            document.getElementById('documento').value = pessoa.documento;
            document.getElementById('email').value = ...