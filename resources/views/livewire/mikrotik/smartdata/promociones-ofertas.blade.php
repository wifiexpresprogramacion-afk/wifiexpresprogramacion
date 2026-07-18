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
                        <th class="py-3">Router</th>
                        <th class="py-3">Segmentación</th>
                        <th class="py-3 text-center">Estado</th>
                        <th class="py-3 text-center">Resultados</th>
                        <th class="py-3 text-center">Clientes</th>
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
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-router me-1"></i>{{ $camp->router_identity }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-soft-info text-info rounded-pill px-3">
                                {{ strtoupper($camp->target_gender) }} | {{ $camp->ageRange->name ?? 'Cualquier edad' }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-block">
                                {{-- SWITCH CORREGIDO --}}
                                <input class="form-check-input" type="checkbox" role="switch" 
                                    wire:click="toggleStatus({{ $camp->id }})" {{ $camp->active ? 'checked' : '' }}
                                    style="cursor: pointer;">
                            </div>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('mikrotik.metrica-campana', ['campaign' => $camp->id]) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Ver Resultados">
                                <i class="bi bi-bar-chart-line-fill me-1"></i>
                                <span class="fw-bold">{{ $camp->responses_count }}</span>
                            </a>
                        </td>
                        <td class="text-center">
                            <button wire:click="showUsers({{ $camp->id }})" class="btn btn-sm btn-outline-secondary border-0 rounded-circle" title="Ver Clientes Potenciales">
                                <i class="bi bi-people-fill"></i>
                            </button>
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
                    <tr><td colspan="7" class="text-center py-5 text-muted">No se encontraron promociones.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0 p-3">
            {{ $campaigns->links() }}
        </div>
    </div>

    {{-- SECCIÓN PARA MOSTRAR USUARIOS DE LA CAMPAÑA --}}
    @if($selectedCampaignForUsers)
    <div class="card border-0 shadow-sm rounded-4 mt-5" id="user-list-section">
        <div class="card-header bg-light border-0 p-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-1">
                    <i class="bi bi-person-check-fill text-primary me-2"></i>Clientes para la campaña: "{{ $selectedCampaignForUsers->name }}"
                </h5>
                <p class="text-muted small mb-0">
                    Segmentación: 
                    <span class="badge bg-soft-info text-info rounded-pill px-2">
                        {{ strtoupper($selectedCampaignForUsers->target_gender) }}
                    </span>
                    <span class="badge bg-soft-info text-info rounded-pill px-2">
                        {{ $selectedCampaignForUsers->ageRange->name ?? 'Cualquier edad' }}
                    </span>
                    <span class="badge bg-secondary rounded-pill px-2">
                        <i class="bi bi-router me-1"></i>{{ $selectedCampaignForUsers->router_identity }}
                    </span>
                </p>
            </div>
            <button wire:click="closeUserList" class="btn-close"></button>
        </div>
        <div class="card-body p-0">
            {{-- CAMPO PARA EDITAR EL MENSAJE --}}
            <div class="p-4 border-bottom">
                <label for="messageBodyTextarea" class="form-label fw-bold text-muted small">Contenido del Mensaje (SMS / WhatsApp)</label>
                <textarea wire:model.defer="messageBody" id="messageBodyTextarea" class="form-control" rows="3" placeholder="Escribe aquí el mensaje que se enviará a los clientes seleccionados..."></textarea>
                <div class="text-end mt-2">
                    <button wire:click="saveMessage" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        <span wire:loading.remove wire:target="saveMessage">
                            <i class="bi bi-save me-1"></i> Guardar Mensaje
                        </span>
                        <span wire:loading wire:target="saveMessage">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...
                        </span>
                    </button>
                </div>
            </div>
            <div class="p-4 d-flex justify-content-between align-items-center border-bottom">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" wire:model="selectAll" id="selectAllCheckbox">
                    <label class="form-check-label fw-bold" for="selectAllCheckbox">
                        Seleccionar Todos ({{ count($usersForCampaign) }} encontrados)
                    </label>
                </div>
                <button wire:click="sendPromotion" class="btn btn-success rounded-pill px-4" {{ empty($selectedUsers) ? 'disabled' : '' }}>
                    <i class="bi bi-send me-2"></i> Enviar Promoción a {{ count($selectedUsers) }} Seleccionados
                </button>
            </div>

            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small fw-bold text-uppercase" style="position: sticky; top: 0; z-index: 1;">
                        <tr>
                            <th class="px-4 py-3" width="50"></th>
                            <th class="py-3">Nombre</th>
                            <th class="py-3">Teléfono</th>
                            <th class="py-3">Email</th>
                            <th class="py-3">Género</th>
                            <th class="py-3 text-center">Edad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usersForCampaign as $user)
                        <tr wire:key="user-for-campaign-{{ $user->id }}">
                            <td class="px-4">
                                <input class="form-check-input" type="checkbox" wire:model="selectedUsers" value="{{ $user->id }}">
                            </td>
                            <td><span class="fw-bold text-dark">{{ $user->full_name ?? $user->name }}</span></td>
                            <td>{{ $user->cellphonecode }}{{ $user->cellphone }}</td>
                            <td>{{ $user->email ?? 'N/A' }}</td>
                            <td>
                                <span class="text-capitalize">{{ $user->gender ?? 'N/D' }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark">{{ $user->age ?? 'N/D' }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">No se encontraron clientes que coincidan con la segmentación de esta campaña.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

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

                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-muted">Pregunta de Encuesta</label>
                            <input type="text" wire:model="question_text" class="form-control" placeholder="¿Qué te parece nuestro servicio?">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Tipo de Respuesta</label>
                            <select wire:model="question_type" class="form-select">
                                <option value="simple">Respuesta Abierta</option>
                                <option value="single_choice">Opción Única (Radio)</option>
                                <option value="multiple_choice">Múltiples Opciones (Check)</option>
                            </select>
                        </div>

                        {{-- GESTIÓN DE OPCIONES --}}
                        @if($question_type != 'simple')
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label small fw-bold text-primary mb-0">Opciones de Respuesta</label>
                                <button type="button" wire:click="addOption" class="btn btn-sm btn-outline-primary rounded-pill">
                                    <i class="bi bi-plus"></i> Agregar Opción
                                </label>
                            </div>
                            @foreach($options as $index => $option)
                            <div class="input-group mb-2">
                                <span class="input-group-text">{{ $index + 1 }}</span>
                                <input type="text" wire:model.defer="options.{{ $index }}" class="form-control" placeholder="Texto de la opción">
                                <button type="button" wire:click="removeOption({{ $index }})" class="btn btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                            @endforeach
                            @error('options') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        @endif
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