<div class="container-fluid py-4">
    {{-- MENSAJES DE ÉXITO --}}
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle me-2"></i> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- CABECERA Y FILTROS --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">
                    <i class="bi bi-megaphone text-primary me-2"></i>Promociones y Ofertas
                </h4>
                <button wire:click="openModal" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> Nueva Promoción
                </button>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" wire:model="search" class="form-control border-start-0" placeholder="Buscar promoción...">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLA DE DATOS --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small fw-bold text-uppercase">
                    <tr>
                        <th class="px-4 py-3">Promoción</th>
                        <th class="py-3">Segmentación</th>
                        <th class="py-3 text-center">Reglas Envío</th>
                        <th class="py-3 text-center">Estado</th>
                        <th class="text-end px-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $camp)
                    <tr wire:key="camp-row-{{ $camp->id }}">
                        <td class="px-4">
                            <span class="fw-bold d-block text-dark">{{ $camp->name }}</span>
                        </td>
                        <td>
                            <span class="badge bg-soft-info text-info rounded-pill px-3">
                                {{ strtoupper($camp->target_gender) }} | {{ $camp->ageRange->name ?? 'Cualquier edad' }}
                            </span>
                        </td>
                        <td class="text-center">
                            @php $opts = $camp->options ?? []; @endphp
                            @if($opts['on_connect'] ?? false) <span class="badge bg-light text-dark border small">CONECTAR</span> @endif
                            @if($opts['only_new'] ?? false) <span class="badge bg-light text-primary border small">NUEVOS</span> @endif
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-block">
                                {{-- SWITCH CORREGIDO --}}
                                <input class="form-check-input" type="checkbox" role="switch" 
                                    wire:click="toggleStatus({{ $camp->id }})" {{ $camp->active ? 'checked' : '' }}
                                    style="cursor: pointer;">
                            </div>
                        </td>
                        <td class="text-end px-4">
                            <div class="btn-group shadow-sm rounded-3">
                                <button wire:click="edit({{ $camp->id }})" class="btn btn-sm btn-white border">
                                    <i class="bi bi-pencil text-primary"></i>
                                </button>
                                <button onclick="confirm('¿Estás seguro?') || event.stopImmediatePropagation()" 
                                        wire:click="delete({{ $camp->id }})" 
                                        class="btn btn-sm btn-white border">
                                    <i class="bi bi-trash text-danger"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-5 text-muted">No se encontraron promociones.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0 p-3">
            {{ $campaigns->links() }}
        </div>
    </div>

    {{-- MODAL DINÁMICO --}}
    @if($isModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(5px);">
        {{-- MARGIN TOP 6REM APLICADO AQUÍ --}}
        <div class="modal-dialog modal-lg" style="margin-top: 6rem;">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="fw-bold mb-0 text-dark">{{ $selected_id ? 'Editar Promoción' : 'Nueva Promoción' }}</h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="row g-3">

                        {{-- Selector de Routers --}}
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Router de la Promoción</label>
                            <select wire:model="router_identity" class="form-select @error('router_identity') is-invalid @enderror">
                                <option value="">Seleccione un router...</option>
                                @foreach($routers as $router)
                                    <option value="{{ $router->identity }}">{{ $router->comercio_nombre }} ({{ $router->identity }})</option>
                                @endforeach
                            </select>
                            @error('router_identity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Nombre</label>
                            <input type="text" wire:model="name" class="form-control" placeholder="Ej: Promo Verano">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Descripción (Opcional)</label>
                            <textarea wire:model="description" class="form-control" rows="2" placeholder="Detalles de la promoción..."></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Género</label>
                            <select wire:model="target_gender" class="form-select">
                                <option value="todos">Todos</option>
                                <option value="masculino">Masculino</option>
                                <option value="femenino">Femenino</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Rango de Edad</label>
                            <select wire:model="age_range_id" class="form-select">
                                <option value="0">Cualquier edad</option>
                                @foreach($ageRanges as $range)
                                    <option value="{{ $range->id }}">{{ $range->name }} ({{ $range->min_age }}-{{ $range->max_age }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Multimedia</label>
                            <input type="file" wire:model="media" class="form-control">
                            
                            {{-- VISTA PREVIA --}}
                            <div class="mt-3 p-3 border rounded-4 bg-light text-center" style="border-style: dashed !important;">
                                @if ($media) 
                                    @if($media_type == 'imagen')
                                        <img src="{{ $media->temporaryUrl() }}" class="img-fluid rounded shadow-sm" style="max-height: 200px;">
                                    @else
                                        <video src="{{ $media->temporaryUrl() }}" controls class="img-fluid rounded shadow-sm" style="max-height: 200px;"></video>
                                    @endif
                                @elseif($selected_id && $current_media_path)
                                    @if($media_type == 'imagen')
                                        {{-- Usamos el accesor del modelo para obtener la URL --}}
                                        <img src="{{ \App\Models\AdvertisingCampaign::find($selected_id)->media_url }}" class="img-fluid rounded shadow-sm" style="max-height: 200px;">
                                    @elseif($media_type == 'video')
                                        {{-- Usamos el accesor del modelo también para el video --}}
                                        <video src="{{ \App\Models\AdvertisingCampaign::find($selected_id)->media_url }}" controls class="img-fluid rounded shadow-sm" style="max-height: 200px;"></video>
                                    @endif
                                @else
                                    <span class="text-muted small">Sin archivo seleccionado</span>
                                @endif
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="col-12">
                            <h6 class="fw-bold text-primary">Reglas de Envío Automático</h6>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" wire:model="on_connect" id="on_connect_check">
                                <label class="form-check-label" for="on_connect_check">
                                    Mostrar al conectar al WiFi
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" wire:model="only_new" id="only_new_check">
                                <label class="form-check-label" for="only_new_check">
                                    Mostrar solo a clientes nuevos
                                </label>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="bi bi-info-circle me-1"></i>Estas reglas determinan si la promoción aparece automáticamente en el portal cautivo.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 p-4 pt-0">
                    <button wire:click="closeModal" class="btn btn-light rounded-pill px-4">Cerrar</button>
                    <button wire:click="save" class="btn btn-primary rounded-pill px-5 shadow-sm fw-bold">
                        {{ $selected_id ? 'Guardar Cambios' : 'Crear Promoción' }}
                    </button>
                </div>
            </div>
        </div>
    </div>    
    @endif
</div>

<style>
    .bg-soft-info { background-color: rgba(13, 202, 240, 0.12); }
    .btn-white { background-color: #fff; color: #6c757d; }
    .btn-white:hover { background-color: #f8f9fa; }
    .form-switch .form-check-input:checked { background-color: #198754; border-color: #198754; }
</style>