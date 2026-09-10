<div class="tarjeta" wire:poll.15s>
    <h2>Semáforo de rendimiento (RN-08)</h2>

    @php
        $rotulo = [
            'dentro' => 'Dentro de la meta',
            'bajo' => 'Por DEBAJO de la meta',
            'sobre' => 'Por ENCIMA de la meta',
            'sin_datos' => 'Sin sesiones completadas',
        ][$semaforo['estado']] ?? $semaforo['estado'];
    @endphp

    <div class="semaforo {{ $semaforo['estado'] }}">
        <span class="luz"></span>
        <div>
            <strong>{{ $rotulo }}</strong><br>
            <span class="tenue">
                {{ $semaforo['rendimiento'] }} quesos / 100 L
                (meta {{ $semaforo['min'] }}–{{ $semaforo['max'] }}) ·
                {{ $semaforo['unidades'] }} u. sobre {{ number_format($semaforo['litros'], 2) }} L
            </span>
        </div>
    </div>
</div>
