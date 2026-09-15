<?php
namespace DomainSystem\Plugins\clinic_pack\Views;

class NursingCockpitView extends AbstractCockpitView
{
    public function renderMainContent(): string
    {
        ob_start();
        extract($this->data);
        ?>
<h1>🏥 CockPIT Enfermagem</h1>
        <p>Bem-vindo à estação de enfermagem. Aqui você acompanha triagens, sinais vitais e leitos.</p>
        
        <div style="margin-top: 30px; padding: 15px; background: #e6f7ff; border-left: 4px solid #1890ff;">
            <strong>Work in Progress:</strong> O Construtor Visual (CockPIT Builder) em breve permitirá editar esta interface sem código!
        </div>
        <?php
        return ob_get_clean();
    }
}
