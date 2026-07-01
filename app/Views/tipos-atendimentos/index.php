<?php
$tituloPagina = 'Tipos de atendimento';
require __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Tipos de atendimento</h1>
        <p class="text-secondary mb-0">Categorias e assuntos utilizados para classificar as demandas.</p>
    </div>
    <button class="btn btn-success" type="button" onclick="novoTipo()">Novo tipo</button>
</div>

<div id="alerta"></div>

<div class="card border-0 shadow-sm mb-4 d-none" id="cardFormulario">
    <div class="card-body">
        <h2 class="h5" id="tituloFormulario">Novo tipo</h2>
        <form id="formTipo">
            <input type="hidden" name="id" id="tipoId">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nome *</label>
                    <input class="form-control" name="nome" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Descrição</label>
                    <textarea class="form-control" name="descricao" rows="2"></textarea>
                </div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-success" type="submit">Salvar</button>
                <button class="btn btn-outline-secondary" type="button" onclick="fecharFormulario()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody id="tabelaTipos">
                <tr>
                    <td colspan="4" class="text-center py-4 text-muted">Carregando tipos de atendimento...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    const formTipo = document.getElementById('formTipo');
    const cardFormulario = document.getElementById('cardFormulario');

    function abrirFormulario() { cardFormulario.classList.remove('d-none'); }
    function fecharFormulario() { cardFormulario.classList.add('d-none'); formTipo.reset(); document.getElementById('tipoId').value = ''; }
    function novoTipo() { fecharFormulario(); document.getElementById('tituloFormulario').textContent = 'Novo tipo'; abrirFormulario(); }

    async function carregarTipos() {
        try {
            const dados = AtendeLabApi.toList(await AtendeLabApi.get('tipos', 'listar'));
            const tbody = document.getElementById('tabelaTipos');
            if (!dados.length) { 
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4">Nenhum tipo cadastrado.</td></tr>'; 
                return; 
            }
            tbody.innerHTML = dados.map(t => `<tr>
                <td class="fw-semibold">${AtendeLabApi.escape(t.nome)}</td>
                <td>${AtendeLabApi.escape(t.descricao || '')}</td>
                <td><span class="badge ${t.status === 'ativo' ? 'text-bg-success' : 'text-bg-secondary'}">${AtendeLabApi.escape(t.status)}</span></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary" onclick="editarTipo(${Number(t.id)})">Editar</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="inativarTipo(${Number(t.id)})">Inativar</button>
                </td>
            </tr>`).join('');
        } catch (error) { 
            AtendeLabApi.showAlert('alerta', error.message, 'danger'); 
        }
    }

    async function editarTipo(id) {
        try {
            const resposta = await AtendeLabApi.get('tipos', 'buscarPorId', { id });
            const t = AtendeLabApi.toObject(resposta);
            novoTipo();
            document.getElementById('tituloFormulario').textContent = 'Editar tipo';
            for (const [key, value] of Object.entries(t)) {
                const field = formTipo.elements.namedItem(key);
                if (field) field.value = value ?? '';
            }
        } catch (error) { AtendeLabApi.showAlert('alerta', error.message, 'danger'); }
    }

    formTipo.addEventListener('submit', async event => {
        event.preventDefault();
        const id = document.getElementById('tipoId').value;
        try {
            await AtendeLabApi.post('tipos', id ? 'atualizar' : 'criar', new FormData(formTipo));
            AtendeLabApi.showAlert('alerta', id ? 'Tipo atualizado com sucesso.' : 'Tipo cadastrado com sucesso.');
            fecharFormulario(); 
            await carregarTipos();
        } catch (error) { AtendeLabApi.showAlert('alerta', error.message, 'danger'); }
    });

    async function inativarTipo(id) {
        if (!confirm('Deseja inativar este tipo?')) return;
        try {
            const form = new FormData();
            form.append('id', id);
            await AtendeLabApi.post('tipos', 'inativar', form);
            AtendeLabApi.showAlert('alerta', 'Tipo inativada com sucesso.');
            await carregarTipos();
        } catch (error) { AtendeLabApi.showAlert('alerta', error.message, 'danger'); }
    }

    document.addEventListener('DOMContentLoaded', carregarTipos);
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>