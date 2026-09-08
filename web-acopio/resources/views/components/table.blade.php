@props(['headers'=>[]])
<div class="table-wrap">
    <table {{ $attributes }}>
        <thead>
            <tr>@foreach($headers as $header)<th scope="col">{{ $header }}</th>@endforeach</tr>
        </thead>
        <tbody>{{ $slot }}</tbody>
    </table>
</div>
