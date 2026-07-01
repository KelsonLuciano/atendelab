<?php
$tituloPagina = 'Atendimentos';
require __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Atendimentos</h1>
        <p class="text-secondary mb-0">Registro e acompanhamento de chamados e solicitações acadêmicas.</p>
    </div>
    <button class="btn btn-success" type="button" onclick="novoAtendimento()">Novo atendimento</button>
</div>

<div id="alerta"></div>

<div class="card border-0 shadow-sm mb-4 d-none" id="cardFormulario">
    <div class="card-body">
        <h2 class="h5" id="tituloFormulario">Novo atendimento</h2>
        <form id="formAtendimento">
            <input type="hidden" name="id" id="atendimentoId">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Pessoa Atendida *</label>
                    <select class="form-select" name="pessoa_id" id="selectPessoas" required>
                        </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tipo de Demanda *</label>
                    <select class="form-select" name="tipo_atendimento_id" id="selectTipos" required>
                        </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Descrição / Relato do Atendimento *</label>
                    <textarea class="form-control" name="descricao" rows="4" required></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status da Demanda</label>
                    <select class="form-select" name="status">
                        <option value="aberto">Aberto</option>
                        <option value="em_andamento">Em Andamento</option>
                        <option value="fechado">Fechado</option>
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-success" type="submit">Registrar</button>
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
                    <th>ID</th>
                    <th>Pessoa</th>
                    <th>Tipo</th>
                    <th>Descrição</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody id="tabelaAtendimentos">
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">Carregando registros...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    const formAtendimento = document.getElementById('formAtendimento');
    const cardFormulario = document.getElementById('cardFormulario');

    function abrirFormulario() { cardFormulario.classList.remove('d-none'); }
    function fecharFormulario() { cardFormulario.classList.add('d-none'); formAtendimento.reset(); document.getElementById('atendimentoId').value = ''; }
    
    async function novoAtendimento() { 
        fecharFormulario(); 
        document.getElementById('tituloFormulario').textContent = 'Novo atendimento'; 
        await carregarCombos();
        abrirFormulario(); 
    }

    // Alimenta os SELECTS dinamicamente com informações ativas do Banco
    async function carregarCombos() {
        try {
            const [pRaw, tRaw] = await Promise.all([
                AtendeLabApi.get('pessoas', 'listar'),
                AtendeLabApi.get('tipos', 'listar')
            ]);
            
            const pessoas = AtendeLabApi.toList(pRaw).filter(p => p.status === 'ativo');
            const tipos = AtendeLabApi.toList(tRaw).filter(t => t.status === 'ativo');

            document.getElementById('selectPessoas').innerHTML = '<option value="">Selecione...</option>' + 
                pessoas.map(p => `<option value="${p.id}">${AtendeLabApi.escape(p.nome)}</option>`).join('');

            document.getElementById('selectTipos').innerHTML = '<option value="">Selecione...</option>' + 
                tipos.map(t => `<option value="${t.id}">${AtendeLabApi.escape(t.nome)}</option>`).join('');
        } catch (error) {
            AtendeLabApi.showAlert('alerta', 'Falha ao carregar formulários auxiliares: ' + error.message, 'danger');
        }
    }

    async function carregarAtendimentos() {
        try {
            const dados = AtendeLabApi.toList(await AtendeLabApi.get('atendimentos', 'listar'));
            const tbody = document.getElementById('tabelaAtendimentos');
            if (!dados.length) { 
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Nenhum atendimento registrado.</td></tr>'; 
                return; 
            }
            
            const statusBadges = {
                'aberto': 'text-bg-warning',
                'em_andamento': 'text-bg-info text-white',
                'fechado': 'text-bg-success'
            };

            tbody.innerHTML = dados.map(a => `<tr>
                <td>#${a.id}</td>
                <td class="fw-semibold">${AtendeLabApi.escape(a.pessoa_nome || 'Não identificado')}</td>
                <td><span class="badge text-bg-light border">${AtendeLabApi.escape(a.tipo_nome || 'Geral')}</span></td>
                <td class="text-truncate" style="max-width: 250px;">${AtendeLabApi.escape(a.descricao)}</td>
                <td><span class="badge ${statusBadges[a.status] || 'text-bg-secondary'}">${AtendeLabApi.escape(a.status.replace('_', ' '))}</span></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary" onclick="editarAtendimento(${Number(a.id)})">Editar</button>
                </td>
            </tr>`).join('');
        } catch (error) { 
            AtendeLabApi.showAlert('alerta', error.message, 'danger'); 
        }
    }

    async function editarAtendimento(id) {
        try {
            await carregarCombos();
            const resposta = await AtendeLabApi.get('atendimentos', 'buscarPorId', { id });
            const a = AtendeLabApi.toObject(resposta);
            
            fecharFormulario();
            document.getElementById('tituloFormulario').textContent = 'Editar atendimento';
            abrirFormulario();

            for (const [key, value] of Object.entries(a)) {
                const field = formAtendimento.elements.namedItem(key);
                if (field) field.value = value ?? '';
            }
        } catch (error) { AtendeLabApi.showAlert('alerta', error.message, 'danger'); }
    }

    formAtendimento.addEventListener('submit', async event => {
        event.preventDefault();
        const id = document.getElementById('atendimentoId').value;
        try {
            await AtendeLabApi.post('atendimentos', id ? 'atualizar' : 'criar', new FormData(formAtendimento));
            AtendeLabApi.showAlert('alerta', id ? 'Registro atualizado com sucesso.' : 'Atendimento registrado com sucesso.');
            fecharFormulario(); 
            await carregarAtendimentos();
        } catch (error) { AtendeLabApi.showAlert('alerta', error.message, 'danger'); }
    });

    document.addEventListener('DOMContentLoaded', carregarAtendimentos);
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>