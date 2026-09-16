from pathlib import Path

def put(path, text):
    p=Path(path); p.parent.mkdir(parents=True,exist_ok=True); p.write_text(text,encoding='utf-8')

enums={
 'Role':{'Administrador':'Administrador','Acopiador':'Acopiador','Supervisor':'Supervisor','Produccion':'Jefe de producción','Despacho':'Personal de despacho de queso','Productor':'Productor'},
 'UserStatus':{x:x for x in ['PENDIENTE','ACTIVO','RECHAZADO','INACTIVO']},
 'TipoEntrega':{x:x for x in ['DIRECTA','RECOGIDA']},
 'EstadoEntrega':{x:x for x in ['PENDIENTE','CONFORME','OBSERVADA','RECHAZADA']},
 'EstadoCalidad':{x:x for x in ['CONFORME','OBSERVADA','RECHAZADA']},
 'TipoCliente':{'Mayorista':'Mayorista','Socio':'Productor / Socio','Publico':'Público General'},
 'EstadoVenta':{'CONFIRMADA':'CONFIRMADA'},
 'EstadoLiquidacion':{x:x for x in ['Pendiente','Calculada','Pagada']},
 'EstadoSincronizacion':{x:x for x in ['PENDIENTE','ENVIADO','ERROR']},
 'TipoProblema':{x:x for x in ['ACIDEZ','ADULTERACION','HIGIENE','OTRO']},
 'EstadoLote':{'TERMINADO':'TERMINADO'},
}
for name, cases in enums.items():
    extra=''
    if name=='Role': extra="\n    public function slug(): string { return match($this) { self::Administrador=>'admin', self::Acopiador=>'acopiador', self::Supervisor=>'supervisor', self::Produccion=>'produccion', self::Despacho=>'despacho', self::Productor=>'productor' }; }\n"
    if name=='TipoCliente': extra="\n    public function precio(): int { return match($this) { self::Mayorista=>20, self::Socio=>18, self::Publico=>21 }; }\n"
    put(f'app/Enums/{name}.php', '<?php\nnamespace App\\Enums;\nenum '+name+': string {\n'+''.join(f"    case {k} = '{v}';\n" for k,v in cases.items())+extra+'}\n')

models={
'Role':('roles',"public function permissions() { return $this->belongsToMany(Permission::class, 'permission_role'); } public function users() { return $this->belongsToMany(User::class); }"),
'Permission':('permissions',"public function roles() { return $this->belongsToMany(Role::class); }"),
'Distrito':('distritos',"public function zonas() { return $this->hasMany(Zona::class); }"),
'Zona':('zonas',"public function distrito() { return $this->belongsTo(Distrito::class); } public function sectores() { return $this->hasMany(Sector::class); }"),
'Sector':('sectores',"public function zona() { return $this->belongsTo(Zona::class); }"),
'Productor':('productores',"use \\Illuminate\\Database\\Eloquent\\SoftDeletes; public function user() { return $this->belongsTo(User::class); } public function entregas() { return $this->hasMany(Entrega::class); } public function sector() { return $this->belongsTo(Sector::class); } public function zona() { return $this->belongsTo(Zona::class); } public function distrito() { return $this->belongsTo(Distrito::class); } public function liquidaciones() { return $this->hasMany(Liquidacion::class); } public function rotaciones() { return $this->hasMany(RotacionProductor::class); }"),
'Acopiador':('acopiadores',"public function user() { return $this->belongsTo(User::class); } public function entregas() { return $this->hasMany(Entrega::class); } public function rutas() { return $this->hasMany(Ruta::class); }"),
'Ruta':('rutas',"public function acopiador() { return $this->belongsTo(Acopiador::class); } public function sectores() { return $this->belongsToMany(Sector::class, 'ruta_sectores'); } public function entregas() { return $this->hasMany(Entrega::class); }"),
'CierreRuta':('cierres_ruta',"public function ruta() { return $this->belongsTo(Ruta::class); }"),
'Entrega':('entregas',"public function productor() { return $this->belongsTo(Productor::class); } public function acopiador() { return $this->belongsTo(Acopiador::class); } public function ruta() { return $this->belongsTo(Ruta::class); } public function pruebaCalidad() { return $this->hasOne(PruebaCalidad::class); } public function problemas() { return $this->hasMany(ProblemaLeche::class); }"),
'PruebaCalidad':('pruebas_calidad',"public function entrega() { return $this->belongsTo(Entrega::class); }"),
'ProblemaLeche':('problemas_leche',"public function entrega() { return $this->belongsTo(Entrega::class); }"),
'Sancion':('sanciones',"public function productor() { return $this->belongsTo(Productor::class); }"),
'Capacitacion':('capacitaciones',"public function productor() { return $this->belongsTo(Productor::class); }"),
'LoteProduccion':('lotes_produccion',"public function entregas() { return $this->belongsToMany(Entrega::class, 'lote_entregas', 'lote_id')->withPivot('litros'); }"),
'StockQueso':('stock_quesos',''),
'StockMovimiento':('stock_movimientos',''),
'Venta':('ventas',"public function detalles() { return $this->hasMany(VentaDetalle::class); }"),
'VentaDetalle':('venta_detalles',"public function venta() { return $this->belongsTo(Venta::class); }"),
'Liquidacion':('liquidaciones',"public function productor() { return $this->belongsTo(Productor::class); } public function detalles() { return $this->hasMany(LiquidacionDetalle::class); }"),
'LiquidacionDetalle':('liquidacion_detalles',"public function entrega() { return $this->belongsTo(Entrega::class); }"),
'Comunicado':('comunicados',''),
'Temporada':('temporadas',''),
'RotacionProductor':('rotaciones_productores',"public function productor() { return $this->belongsTo(Productor::class); }"),
'SyncRecord':('sync_records',''),
}
for name,(table,relations) in models.items():
    put(f'app/Models/{name}.php',f"<?php\nnamespace App\\Models;\nclass {name} extends \\Illuminate\\Database\\Eloquent\\Model {{\n    protected $table = '{table}';\n    protected $guarded = ['id'];\n    {relations}\n}}\n")
put('app/Models/User.php',r'''<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class User extends Authenticatable {
    use HasFactory, Notifiable;
    protected $fillable = ['name','nombres','apellidos','documento','telefono','email','username','password'];
    protected $hidden = ['password','remember_token'];
    protected function casts(): array { return ['password'=>'hashed','approved_at'=>'datetime','rejected_at'=>'datetime','email_verified_at'=>'datetime']; }
    public function roles() { return $this->belongsToMany(Role::class); }
    public function productor() { return $this->hasOne(Productor::class); }
    public function acopiador() { return $this->hasOne(Acopiador::class); }
    public function auditorias() { return $this->hasMany(Auditoria::class,'usuario_id'); }
    public function hasRole(string $slug): bool { return $this->roles->contains('slug',$slug); }
    public function hasPermission(string $permission): bool { return $this->status === 'ACTIVO' && $this->roles()->whereHas('permissions',fn($q)=>$q->where('name',$permission))->exists(); }
    public function dashboard(): string { return '/'.($this->roles->first()?->slug ?? 'registro').'/'.($this->roles->isEmpty()?'pendiente':'dashboard'); }
}
''')
put('app/Models/Auditoria.php',r'''<?php
namespace App\Models;
class Auditoria extends \Illuminate\Database\Eloquent\Model {
    protected $table='auditoria'; public $timestamps=false; protected $guarded=['id'];
    protected function casts(): array { return ['datos_anteriores'=>'array','datos_nuevos'=>'array']; }
    public function usuario() { return $this->belongsTo(User::class,'usuario_id'); }
}
''')
put('app/Services/Audit.php',r'''<?php
namespace App\Services;
use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Model;
class Audit {
    public static function record(string $action, Model $model, array $before=[]): void {
        $clean=fn($data)=>array_diff_key($data,array_flip(['password','remember_token','token']));
        Auditoria::create(['usuario_id'=>auth()->id(),'accion'=>$action,'modelo'=>$model->getTable(),'registro'=>$model->getKey(),'datos_anteriores'=>$clean($before),'datos_nuevos'=>$clean($model->getAttributes()),'fecha'=>now()]);
    }
}
''')
put('app/Services/BusinessWeek.php',r'''<?php
namespace App\Services;
use Carbon\CarbonImmutable;
class BusinessWeek {
    public static function bounds($date=null): array {
        $day=CarbonImmutable::parse($date??now())->startOfDay();
        $start=$day->subDays(($day->dayOfWeek-4+7)%7);
        return [$start,$start->addDays(6)->endOfDay()];
    }
}
''')
put('app/Services/Settings.php',r'''<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class Settings {
    public static function get(string $key, string $default=''): string { return (string)(DB::table('configuraciones')->where('clave',$key)->value('valor')??$default); }
}
''')
put('app/Notifications/MilkFlowNotification.php',r'''<?php
namespace App\Notifications;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
class MilkFlowNotification extends Notification {
    public function __construct(public string $title,public string $message, public bool $email=false) {}
    public function via(object $notifiable): array { return $this->email ? ['database','mail'] : ['database']; }
    public function toArray(object $notifiable): array { return ['titulo'=>$this->title,'mensaje'=>$this->message]; }
    public function toMail(object $notifiable): MailMessage { return (new MailMessage)->subject($this->title)->greeting('Hola, '.$notifiable->name)->line($this->message)->action('Abrir MilkFlow',url('/login')); }
}
''')
