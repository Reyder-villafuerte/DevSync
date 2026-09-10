<?php

namespace App\Livewire\Panel\Admin;

use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Contenedor del panel de Administración: sólo maneja la pestaña activa.
 * Cada pestaña es un componente Livewire independiente que delega en Services.
 */
class PanelAdministracion extends Component
{
    #[Url]
    public string $tab = 'tarifas';

    /** @var array<string,string> */
    public array $pestanas = [
        'tarifas' => 'Tarifas',
        'liquidacion' => 'Liquidación del viernes',
        'asamblea' => 'Asistencia a asamblea',
        'avisos' => 'Avisos',
        'usuarios' => 'Usuarios',
        'solicitudes' => 'Solicitudes de cambio de ruta',
    ];

    public function seleccionar(string $tab): void
    {
        if (array_key_exists($tab, $this->pestanas)) {
            $this->tab = $tab;
        }
    }

    public function render()
    {
        return view('livewire.panel.admin.panel-administracion');
    }
}
