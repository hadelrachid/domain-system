
<div class="card">
    <div class="card-header">
        <h2>Configurações da Clínica</h2>
    </div>
    <div class="card-body">
        <p>Aqui você pode gerenciar as configurações gerais do pacote Clinic Pack.</p>
        <form method="POST" action="/admin/clinic/settings/save">
            <div class="form-group">
                <label>Nome da Clínica</label>
                <input type="text" name="clinic_name" class="form-control" value="DaherClínica">
            </div>
            <button type="submit" class="btn btn-primary">Salvar Configurações</button>
        </form>
    </div>
</div>
