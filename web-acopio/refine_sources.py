from pathlib import Path
Path('resources/views/dashboard.blade.php').write_text("@extends('layouts.app',['title'=>'Inicio'])\n@section('content')\n@include('dashboard-content')\n@endsection\n",encoding='utf-8')
for filename in ['resources/views/modules/index.blade.php','resources/views/reports/index.blade.php','resources/views/reports/pdf.blade.php']:
    p=Path(filename)
    s=p.read_text(encoding='utf-8').replace('{{ $row->$key }}','{{ app(App\\Services\\DisplayValue::class)->get($row,$key) }}').replace('@else{{ $row->$field }}@endif','@else{{ app(App\\Services\\DisplayValue::class)->get($row,$field) }}@endif')
    if 'modules/index' in filename:
        s=s.replace('<td>@if(App\\Services\\Modules::editable', '<td><a class="table-action" href="{{ $base }}/{{ $row->id }}">Detalle</a><br>@if(App\\Services\\Modules::editable')
    p.write_text(s,encoding='utf-8')
import json
p=Path('composer.json'); config=json.loads(p.read_text(encoding='utf-8'))
config['name']='milkflow/web'; config['description']='MilkFlow Web: gestión de acopio, calidad, producción y ventas.'
config['minimum-stability']='stable'
config['scripts']['dev']=['Composer\\Config::disableProcessTimeout','npx concurrently --kill-others "php artisan serve" "php artisan queue:listen --tries=3 --timeout=90" "php artisan schedule:work" "npm run dev"']
p.write_text(json.dumps(config,ensure_ascii=False,indent=4)+'\n',encoding='utf-8')
