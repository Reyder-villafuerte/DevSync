<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tipo de cliente y la tarifa que le corresponde.
 *
 * El tipo NO tiene precio propio: el precio siempre sale del producto, y vive
 * en `product_client_type_prices`. Un tipo con tarifa propia cobraría igual la
 * mantequilla que el queso, que es justamente lo que no puede pasar.
 *
 * Un tipo puede reconocer varios roles del padrón: «Proveedor de leche» lleva
 * `productor`, y un «Empleado» llevaría de una vez todos los puestos de la
 * planta. A esa gente se le reconoce su tarifa sola, sin asignarle nada.
 */
class ClientType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'min_quantity',
        'description',
        'is_active',
    ];

    protected $casts = [
        'min_quantity' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductClientTypePrice::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(ClientTypeRole::class);
    }

    /** @return array<int, string> */
    public function rolesReconocidos(): array
    {
        return $this->roles->pluck('role')->all();
    }

    /** Un tipo sin roles se asigna a mano o se gana por cantidad. */
    public function reconoceAlgunRol(): bool
    {
        return $this->roles->isNotEmpty();
    }

    /**
     * Lo que se le cobra a este tipo por una unidad de ese producto.
     *
     * Devuelve null cuando nadie definió un precio: ni para este tipo ni para
     * el público. Vender a ciegas sería vender regalado, así que el mostrador
     * lo rechaza en vez de inventar una cifra.
     */
    public function priceFor(Product $producto): ?float
    {
        $propia = $this->tarifaDe($producto);

        if ($propia !== null) {
            return $propia;
        }

        // Sin tarifa para este tipo se cobra la de público, que es la que todo
        // producto debería tener cargada.
        $publico = static::tipoPublico();

        return $publico && $publico->id !== $this->id ? $publico->tarifaDe($producto) : null;
    }

    /** El precio cargado para este tipo, sin caerse a ningún otro. */
    public function tarifaDe(Product $producto): ?float
    {
        $fila = $this->relationLoaded('productPrices')
            ? $this->productPrices->firstWhere('product_id', $producto->id)
            : $this->productPrices()->where('product_id', $producto->id)->first();

        return $fila ? (float) $fila->price_per_unit : null;
    }

    /**
     * El tipo de público general: el activo de menor mínimo que no esté atado
     * a un rol. Es la tarifa contra la que se cae todo lo demás.
     */
    public static function tipoPublico(): ?self
    {
        return static::where('is_active', true)
            ->doesntHave('roles')
            ->orderBy('min_quantity')
            ->first();
    }

    /** Si este producto tiene una tarifa cargada para este tipo. */
    public function tieneTarifaPropia(Product $producto): bool
    {
        return $this->relationLoaded('productPrices')
            ? $this->productPrices->contains('product_id', $producto->id)
            : $this->productPrices()->where('product_id', $producto->id)->exists();
    }

    /**
     * Qué tarifa le toca a este cliente por comprar esa cantidad.
     *
     * Tres reglas, en orden:
     *
     * 1. Si el cliente está vinculado a alguien del padrón cuyo rol coincide
     *    con el `auto_role` de un tipo, gana ese tipo. Ser productor no depende
     *    de cuánto compre.
     * 2. Si no, se mira el tipo que tenga asignado y todos los tramos por
     *    cantidad que la compra alcance; gana el de mayor `min_quantity`. Así
     *    un cliente local que se lleva 10 accede a la tarifa mayorista, pero
     *    un mayorista que se lleva 1 no pierde la suya.
     * 3. Si no hay nada, el tipo activo de menor `min_quantity`.
     */
    public static function paraCliente(?Customer $cliente, float $cantidad = 1): ?self
    {
        $tipos = static::with('roles')->where('is_active', true)->orderBy('min_quantity')->get();

        if ($tipos->isEmpty()) {
            return null;
        }

        if ($cliente?->linked_user_id) {
            $rol = $cliente->linkedUser?->role;
            $porRol = $rol
                ? $tipos->first(fn (self $tipo) => in_array($rol, $tipo->rolesReconocidos(), true))
                : null;

            if ($porRol) {
                return $porRol;
            }
        }

        $candidatos = static::tramosAlcanzados($tipos, $cantidad);

        $asignado = static::asignadoA($cliente, $tipos);

        if ($asignado) {
            $candidatos->push($asignado);
        }

        // Si nada aplicó —una compra fraccionada por debajo del tramo más bajo—
        // se cobra la de público. Caer en `$tipos->first()` podría darle la
        // tarifa de proveedor a cualquiera que pase por el mostrador.
        return $candidatos->sortByDesc(fn (self $tipo) => (float) $tipo->min_quantity)->first()
            ?? $tipos->first(fn (self $tipo) => ! $tipo->reconoceAlgunRol())
            ?? $tipos->first();
    }

    /**
     * El tipo que el cliente tiene asignado.
     *
     * Si todavía no tiene uno —fila vieja, o cliente dado de alta en el
     * mostrador— se cae a la columna `type` de siempre, cuyos valores
     * («proveedor», «mayorista», «local») coinciden con el slug de los tres
     * tipos sembrados.
     *
     * @param  Collection<int, self>  $tipos
     */
    private static function asignadoA(?Customer $cliente, Collection $tipos): ?self
    {
        if (! $cliente) {
            return null;
        }

        if ($cliente->client_type_id) {
            $porId = $tipos->firstWhere('id', $cliente->client_type_id);

            if ($porId) {
                return $porId;
            }
        }

        return $cliente->type ? $tipos->firstWhere('slug', $cliente->type) : null;
    }

    /**
     * @param  Collection<int, self>  $tipos
     * @return \Illuminate\Support\Collection<int, self>
     */
    private static function tramosAlcanzados(Collection $tipos, float $cantidad): \Illuminate\Support\Collection
    {
        return $tipos
            // Un tipo que reconoce roles es una identidad, no un tramo por
            // volumen: no se le cae encima a cualquiera que compre mucho.
            ->filter(fn (self $tipo) => ! $tipo->reconoceAlgunRol() && (float) $tipo->min_quantity <= $cantidad)
            ->values()
            ->toBase();
    }
}
