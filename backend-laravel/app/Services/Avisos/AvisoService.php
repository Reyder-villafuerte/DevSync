<?php

namespace App\Services\Avisos;

use App\Models\Aviso;
use App\Models\Usuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Use Case: gestión de avisos del mural.
 *
 * La imagen se guarda como ARCHIVO en el disco público (storage/app/public/avisos)
 * y en la fila sólo queda su URL — nunca un blob en la base (restricción del
 * enunciado).
 */
class AvisoService
{
    private const DIRECTORIO = 'avisos';

    private const DISCO = 'public';

    /**
     * @param  array{titulo:string,mensaje:string,fecha_publicacion:string,fecha_expiracion?:?string,obligatorio?:bool}  $datos
     */
    public function crear(array $datos, ?UploadedFile $imagen, Usuario $autor): Aviso
    {
        $imagenUrl = null;
        if ($imagen !== null) {
            $ruta = $imagen->store(self::DIRECTORIO, self::DISCO);
            $imagenUrl = Storage::disk(self::DISCO)->url($ruta);
        }

        return Aviso::create([
            'titulo' => $datos['titulo'],
            'contenido' => $datos['mensaje'],
            'imagen_url' => $imagenUrl,
            'fecha_publicacion' => $datos['fecha_publicacion'],
            'fecha_expiracion' => $datos['fecha_expiracion'] ?? null,
            'obligatorio' => $datos['obligatorio'] ?? false,
            'publicado' => true,
            'creado_por' => $autor->id,
        ]);
    }

    /** Retirar del mural: se despublica, no se borra (queda su histórico). */
    public function retirar(Aviso $aviso): void
    {
        $aviso->forceFill(['publicado' => false])->save();
    }
}
