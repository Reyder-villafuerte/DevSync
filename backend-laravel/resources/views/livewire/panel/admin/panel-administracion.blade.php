<div>

    @if ($tab === 'tarifas')
        @livewire('panel.admin.tarifas', key('tab-tarifas'))
    @elseif ($tab === 'liquidacion')
        @livewire('panel.admin.liquidacion-viernes', key('tab-liquidacion'))
    @elseif ($tab === 'asamblea')
        @livewire('panel.admin.asistencia-asamblea', key('tab-asamblea'))
    @elseif ($tab === 'avisos')
        @livewire('panel.admin.avisos', key('tab-avisos'))
    @elseif ($tab === 'usuarios')
        @livewire('panel.admin.usuarios', key('tab-usuarios'))
    @elseif ($tab === 'solicitudes')
        @livewire('panel.admin.solicitudes-cambio-ruta', key('tab-solicitudes'))
    @endif
</div>
