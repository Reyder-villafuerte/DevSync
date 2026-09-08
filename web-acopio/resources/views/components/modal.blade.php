@props(['id','title'])
<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content mf-card">
            <div class="modal-header">
                <h2 class="modal-title" id="{{ $id }}-title">{{ $title }}</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">{{ $slot }}</div>
        </div>
    </div>
</div>
