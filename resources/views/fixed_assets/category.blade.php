@extends('template.app')

@section('content')
@php
    function getCategoryStyle($name) {
        $n = strtolower($name);
        if (str_contains($n, 'congeladora')) return ['icon' => 'ti-snowflake', 'color' => 'blue'];
        if (str_contains($n, 'exhibidor')) return ['icon' => 'ti-device-tv', 'color' => 'purple'];
        if (str_contains($n, 'dispensador')) return ['icon' => 'ti-cup', 'color' => 'teal'];
        if (str_contains($n, 'gourmet')) return ['icon' => 'ti-chef-hat', 'color' => 'pink'];
        if (str_contains($n, '1500')) return ['icon' => 'ti-weight', 'color' => 'orange'];
        if (str_contains($n, 'agua')) return ['icon' => 'ti-droplet', 'color' => 'blue'];
        if (str_contains($n, 'selladora')) return ['icon' => 'ti-box-seam', 'color' => 'green'];
        return ['icon' => 'ti-dots', 'color' => 'indigo'];
    }
    $style = getCategoryStyle($subcategory->name);
@endphp

<style>
    /* Hide the default template header to prevent duplication */
    .page-wrapper > .page-header {
        display: none !important;
    }
    
    .header-banner {
        background-color: #ffffff;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        position: relative;
        overflow: hidden;
    }
    .header-illustration {
        position: absolute;
        right: 20px;
        bottom: -40px;
        height: 150%;
        opacity: 0.05; 
        pointer-events: none;
    }
    .premium-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        border: 1px solid rgba(0,0,0,0.03);
        overflow: hidden;
    }
    .table-hoverable tbody tr {
        transition: all 0.2s ease;
        border-bottom: 1px solid rgba(0,0,0,0.02);
    }
    .table-hoverable tbody tr:hover {
        background-color: #f8fafc !important;
        transform: scale(1.001);
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <div class="text-uppercase text-primary small fw-bold mb-1" style="letter-spacing: 1px;">Gestión de Categoría</div>
        <h2 class="page-title fw-bolder fs-1 position-relative" style="padding-left: 15px;">
            Activos: {{ ucfirst($subcategory->name) }}
            <span style="position: absolute; left: 0; top: 10px; height: 60%; width: 4px; background: #4263eb; border-radius: 4px;"></span>
        </h2>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('fixed-assets.index') }}" class="btn btn-outline-secondary shadow-sm rounded-pill">
            <i class="ti ti-arrow-left me-1"></i> Volver
        </a>
        <button class="btn btn-primary shadow-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#createAssetModal">
            <i class="ti ti-plus me-1"></i> Registrar Nuevo
        </button>
    </div>
</div>

<div class="header-banner p-4 mb-5 d-flex align-items-center" style="border-bottom: 4px solid var(--tblr-{{ $style['color'] }});">
    <div class="bg-{{ $style['color'] }}-lt text-{{ $style['color'] }} rounded-3 d-flex justify-content-center align-items-center me-4" style="width: 64px; height: 64px; flex-shrink: 0;">
        <i class="ti {{ $style['icon'] }} fs-1"></i>
    </div>
    <div style="z-index: 1;">
        <h2 class="fw-bolder mb-1 fs-2">Unidades de {{ ucfirst($subcategory->name) }}</h2>
        <div class="text-muted">Total registradas en sistema: <strong>{{ $assets->count() }} unidades</strong></div>
    </div>
    <i class="ti {{ $style['icon'] }} header-illustration text-{{ $style['color'] }}" style="font-size: 160px;"></i>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="card premium-card">
    <div class="table-responsive">
        <table class="table card-table table-vcenter table-hoverable table-borderless align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="text-uppercase text-muted py-3" style="font-size: 0.7rem; letter-spacing: 1px;">Identificador</th>
                    <th class="text-uppercase text-muted py-3" style="font-size: 0.7rem; letter-spacing: 1px;">Estado</th>
                    <th class="text-uppercase text-muted py-3" style="font-size: 0.7rem; letter-spacing: 1px;">Ubicación Actual</th>
                    <th class="text-uppercase text-muted py-3" style="font-size: 0.7rem; letter-spacing: 1px;">Costo Ref.</th>
                    <th class="text-end text-uppercase text-muted py-3" style="font-size: 0.7rem; letter-spacing: 1px;">Opciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assets as $asset)
                <tr>
                    <td>
                        <div class="fw-bold">{{ $asset->name }}</div>
                        <div class="text-muted small">Cod: {{ $asset->internal_code ?: 'N/A' }} | SN: {{ $asset->serial_number ?: 'N/A' }}</div>
                    </td>
                    <td>
                        @if($asset->status == 'available')
                            <span class="badge bg-success-lt">Disponible</span>
                        @elseif($asset->status == 'assigned')
                            <span class="badge bg-warning-lt">Entregado</span>
                        @elseif($asset->status == 'maintenance')
                            <span class="badge bg-danger-lt">Mantenimiento</span>
                        @else
                            <span class="badge bg-secondary-lt">Baja</span>
                        @endif
                    </td>
                    <td>
                        @if($asset->status == 'assigned')
                            @php
                                $activeAssignment = $asset->assignments()->whereNull('returned_date')->latest()->first();
                            @endphp
                            @if($asset->client)
                                <span class="fw-medium text-dark"><i class="ti ti-user me-1"></i>{{ $asset->client->name }}</span>
                                <div class="small text-muted">
                                    @if($activeAssignment && $activeAssignment->assignment_type == 'alquilado')
                                        <span class="text-primary">(Alquilado)</span>
                                    @else
                                        <span class="text-info">(Prestado)</span>
                                    @endif
                                </div>
                            @elseif($activeAssignment && $activeAssignment->assignment_type == 'interno')
                                <span class="fw-medium text-dark"><i class="ti ti-building me-1"></i>Área Interna</span>
                                <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{ $activeAssignment->notes }}">
                                    {{ $activeAssignment->notes }}
                                </div>
                            @endif
                        @elseif($asset->status == 'available')
                            <span class="text-muted small"><i class="ti ti-building me-1"></i>Almacén</span>
                        @endif
                    </td>
                    <td>
                        <div class="small text-muted">
                            @if($asset->purchase_cost > 0)
                            S/ {{ number_format($asset->purchase_cost, 2) }}
                            @else
                            -
                            @endif
                        </div>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-2">
                            @if($asset->status == 'available')
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal{{ $asset->id }}" title="Entregar (Prestar/Alquilar)">
                                    <i class="ti ti-user-plus me-1"></i> Entregar
                                </button>
                            @elseif($asset->status == 'assigned')
                                @php
                                    $activeAssignment = $asset->assignments()->whereNull('returned_date')->latest()->first();
                                @endphp
                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#incomeModal{{ $asset->id }}" title="Registrar Ingreso">
                                    <i class="ti ti-cash me-1"></i> Cobrar
                                </button>
                            @endif
                            
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#expenseModal{{ $asset->id }}" title="Registrar Gasto (Mantenimiento/Reparación)">
                                <i class="ti ti-receipt me-1"></i> Gasto
                            </button>
                            
                            <button class="btn btn-sm btn-outline-secondary btn-icon" data-bs-toggle="modal" data-bs-target="#historyModal{{ $asset->id }}" title="Ver Historial">
                                <i class="ti ti-history"></i>
                            </button>
                            
                            @if($asset->status == 'assigned')
                                <form action="{{ route('fixed-assets.return', $asset->id) }}" method="POST" class="m-0">
                                    @csrf
                                    <input type="hidden" name="returned_date" value="{{ date('Y-m-d') }}">
                                    <button type="submit" class="btn btn-sm btn-outline-info" title="Retornar a Almacén">
                                        <i class="ti ti-arrow-back-up me-1"></i> Devolver
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">No hay unidades en esta categoría.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modals de Acciones -->
@foreach($assets as $asset)
    <!-- Assign Modal -->
    <div class="modal modal-blur fade" id="assignModal{{ $asset->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="{{ route('fixed-assets.assign', $asset->id) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-primary-lt">
                        <h5 class="modal-title"><i class="ti ti-user-plus me-2"></i>Entregar: {{ $asset->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <div class="form-selectgroup">
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="assignment_destination" value="client" class="form-selectgroup-input" checked onchange="toggleAssignmentDestination{{ $asset->id }}(this.value)">
                                    <span class="form-selectgroup-label"><i class="ti ti-user me-1"></i> A Cliente</span>
                                </label>
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="assignment_destination" value="internal" class="form-selectgroup-input" onchange="toggleAssignmentDestination{{ $asset->id }}(this.value)">
                                    <span class="form-selectgroup-label"><i class="ti ti-building me-1"></i> Área Interna</span>
                                </label>
                            </div>
                        </div>

                        <div id="clientFields{{ $asset->id }}">
                            <div class="mb-3">
                                <label class="form-label">Cliente destino</label>
                                <select class="form-select ts-select" name="client_id" required style="width: 100%">
                                    <option value="">Seleccionar cliente...</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Condición</label>
                                    <select class="form-select" name="assignment_type" onchange="document.getElementById('rental_fields_{{ $asset->id }}').style.display = this.value === 'alquilado' ? 'block' : 'none';" required>
                                        <option value="prestado">Prestado</option>
                                        <option value="alquilado">Alquilado</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Fecha de Entrega</label>
                                    <input type="date" class="form-control" name="assigned_date" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                            
                            <div id="rental_fields_{{ $asset->id }}" style="display: none;">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Costo por Cuota (S/)</label>
                                        <input type="number" step="0.01" class="form-control" name="amount" placeholder="Ej: 50.00">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Frecuencia de Pago</label>
                                        <select class="form-select" name="payment_frequency">
                                            <option value="diario">Diario</option>
                                            <option value="semanal">Semanal</option>
                                            <option value="quincenal">Quincenal</option>
                                            <option value="mensual">Mensual</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Modo de Alquiler</label>
                                        <select class="form-select" name="rental_mode" onchange="document.getElementById('installments_{{ $asset->id }}').style.display = this.value === 'fixed' ? 'block' : 'none';">
                                            <option value="indefinite">Indefinido</option>
                                            <option value="fixed">N° Fijo de Cuotas</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="installments_{{ $asset->id }}" style="display: none;">
                                        <label class="form-label">Total de Cuotas</label>
                                        <input type="number" class="form-control" name="total_installments" min="1" placeholder="Ej: 12">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" id="notesLabel{{ $asset->id }}">Notas / Glosa</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Comentarios adicionales..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i> Entregar Equipo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($asset->status == 'assigned')
        @php
            $activeAssignment = $asset->assignments()->whereNull('returned_date')->latest()->first();
        @endphp
        <!-- Income Modal -->
        <div class="modal modal-blur fade" id="incomeModal{{ $asset->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-success-lt">
                        <h5 class="modal-title"><i class="ti ti-cash me-2"></i>Cronograma de Pagos: {{ $asset->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="table-responsive">
                            <table class="table table-vcenter table-hoverable card-table table-borderless">
                                <thead>
                                    <tr>
                                        <th>N°</th>
                                        <th>Vencimiento</th>
                                        <th>Monto</th>
                                        <th>Estado</th>
                                        <th class="text-end" style="min-width: 250px;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($activeAssignment->installments as $installment)
                                    <tr>
                                        <td>Cuota {{ $installment->installment_number }}</td>
                                        <td>{{ \Carbon\Carbon::parse($installment->due_date)->format('d/m/Y') }}</td>
                                        <td class="fw-bold">S/ {{ number_format($installment->amount, 2) }}</td>
                                        <td>
                                            @if($installment->status == 'paid')
                                                <span class="badge bg-success">Pagado el {{ \Carbon\Carbon::parse($installment->paid_date)->format('d/m/y') }}</span>
                                            @else
                                                @if(\Carbon\Carbon::parse($installment->due_date)->isPast())
                                                    <span class="badge bg-danger">Vencido</span>
                                                @else
                                                    <span class="badge bg-warning">Pendiente</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($installment->status == 'pending')
                                            <form action="{{ route('fixed-assets.registerIncome', $installment->id) }}" method="POST" class="d-flex align-items-center justify-content-end gap-2 m-0">
                                                @csrf
                                                <select class="form-select form-select-sm w-auto" name="payment_method_id" required>
                                                    <option value="">Método...</option>
                                                    @foreach($paymentMethods as $pm)
                                                        <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-success"><i class="ti ti-cash me-1"></i> Cobrar</button>
                                            </form>
                                            @else
                                            <button class="btn btn-sm btn-outline-secondary" disabled>Completado</button>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="text-center text-muted py-3">No hay cuotas generadas (Revise si entregó el equipo con monto 0).</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Expense Modal -->
    <div class="modal modal-blur fade" id="expenseModal{{ $asset->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="{{ route('fixed-assets.registerExpense', $asset->id) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-danger-lt">
                        <h5 class="modal-title"><i class="ti ti-receipt me-2"></i>Registrar Gasto: {{ $asset->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">Se registrará un gasto asociado a este activo (ej. mantenimiento, reparación, repuestos).</p>
                        
                        <div class="mb-3">
                            <label class="form-label">Descripción del Gasto</label>
                            <input type="text" class="form-control" name="description" placeholder="Ej: Reparación del motor" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Monto (S/)</label>
                                <input type="number" step="0.01" min="0.01" class="form-control fw-bold text-danger" name="amount" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Método de Pago</label>
                                <select class="form-select" name="payment_method_id" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach($paymentMethods as $pm)
                                        <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha del Gasto</label>
                            <input type="date" class="form-control" name="real_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">N° Comprobante (Opcional)</label>
                            <input type="text" class="form-control" name="receipt_number">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger"><i class="ti ti-receipt me-1"></i> Registrar Gasto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- History Modal -->
    <div class="modal modal-blur fade" id="historyModal{{ $asset->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-secondary-lt">
                    <h5 class="modal-title"><i class="ti ti-history me-2"></i>Historial de Movimientos: {{ $asset->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <ul class="nav nav-tabs nav-fill" data-bs-toggle="tabs">
                        <li class="nav-item">
                            <a href="#tabs-incomes-{{ $asset->id }}" class="nav-link active" data-bs-toggle="tab"><i class="ti ti-cash text-success me-2"></i> Ingresos (Cobros)</a>
                        </li>
                        <li class="nav-item">
                            <a href="#tabs-expenses-{{ $asset->id }}" class="nav-link" data-bs-toggle="tab"><i class="ti ti-receipt text-danger me-2"></i> Egresos (Gastos)</a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <!-- Incomes Tab -->
                        <div class="tab-pane active show" id="tabs-incomes-{{ $asset->id }}">
                            <div class="table-responsive">
                                <table class="table card-table table-vcenter text-nowrap">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Detalle</th>
                                            <th class="text-end">Monto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($asset->history_incomes as $income)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($income->date)->format('d/m/Y') }}</td>
                                            <td>{{ $income->note }}</td>
                                            <td class="text-end fw-bold text-success">S/ {{ number_format($income->amount, 2) }}</td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="3" class="text-center py-4 text-muted">No hay ingresos registrados</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- Expenses Tab -->
                        <div class="tab-pane" id="tabs-expenses-{{ $asset->id }}">
                            <div class="table-responsive">
                                <table class="table card-table table-vcenter text-nowrap">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Detalle</th>
                                            <th class="text-end">Monto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($asset->history_expenses as $expense)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($expense->real_date)->format('d/m/Y') }}</td>
                                            <td>{{ $expense->description }}</td>
                                            <td class="text-end fw-bold text-danger">- S/ {{ number_format($expense->amount, 2) }}</td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="3" class="text-center py-4 text-muted">No hay gastos registrados</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function toggleAssignmentDestination{{ $asset->id }}(type) {
        const clientFields = document.getElementById('clientFields{{ $asset->id }}');
        const clientSelect = clientFields.querySelector('select[name="client_id"]');
        const typeSelect = clientFields.querySelector('select[name="assignment_type"]');
        const notesTextarea = document.querySelector('#assignModal{{ $asset->id }} textarea[name="notes"]');
        const notesLabel = document.getElementById('notesLabel{{ $asset->id }}');
        
        if (type === 'internal') {
            clientFields.style.display = 'none';
            clientSelect.required = false;
            typeSelect.required = false;
            notesTextarea.required = true;
            notesLabel.innerHTML = 'Glosa / Área Destino <span class="text-danger">*</span>';
        } else {
            clientFields.style.display = 'block';
            clientSelect.required = true;
            typeSelect.required = true;
            notesTextarea.required = false;
            notesLabel.innerHTML = 'Notas / Glosa';
        }
    }
    </script>
@endforeach

<!-- Create Modal -->
<div class="modal modal-blur fade" id="createAssetModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <form action="{{ route('fixed-assets.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-plus me-2"></i>Registrar Nuevo Activo (Sumar Stock)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre del Equipo / Marca</label>
                            <input type="text" class="form-control" name="name" required placeholder="Ej: Congeladora Mabe">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Categoría a la que pertenece</label>
                            <input type="text" class="form-control bg-light" value="{{ $subcategory->name }}" readonly>
                            <input type="hidden" name="expense_subcategory_id" value="{{ $subcategory->id }}">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Código Interno (Opcional)</label>
                            <input type="text" class="form-control" name="internal_code" placeholder="Ej: n01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Número de Serie (Opcional)</label>
                            <input type="text" class="form-control" name="serial_number" placeholder="SN del fabricante">
                        </div>
                    </div>
                    <hr>
                    <h4 class="mb-3 text-muted">Datos de Compra (Opcional)</h4>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Compra</label>
                            <input type="date" class="form-control" name="purchase_date" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Costo Unitario (S/)</label>
                            <input type="number" class="form-control text-danger fw-bold" name="purchase_cost" min="0" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="registerExpenseCheckboxCat" onchange="toggleExpenseFields(this, 'expenseFieldsCat')">
                            <span class="form-check-label text-muted small">¿Quieres que este gasto se registre en la compra de stock?</span>
                        </label>
                    </div>
                    
                    <div class="row mb-3" id="expenseFieldsCat" style="display: none;">
                        <div class="col-md-6">
                            <label class="form-label">Método de Pago</label>
                            <select class="form-select" name="payment_method_id">
                                <option value="">Seleccionar...</option>
                                @foreach($paymentMethods as $pm)
                                    <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">N° de Comprobante</label>
                            <input type="text" class="form-control" name="voucher_number" placeholder="Factura o Boleta">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Activo</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        if ($.fn.modal) {
            $.fn.modal.Constructor.prototype._enforceFocus = function() {};
        }

        $('.modal').on('shown.bs.modal', function () {
            var $modal = $(this);
            $modal.find('.ts-select').each(function() {
                if (!$(this).hasClass("select2-hidden-accessible")) {
                    $(this).select2({
                        dropdownParent: $modal,
                        width: '100%'
                    });
                }
            });
        });
    });

    function toggleExpenseFields(checkbox, targetId) {
        if (checkbox.checked) {
            Swal.fire({
                title: 'Atención',
                text: 'Al registrar este gasto, el monto afectará a la caja del día de hoy.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, registrar',
                cancelButtonText: 'Cancelar',
                customClass: {
                    confirmButton: 'btn btn-primary text-white me-2',
                    cancelButton: 'btn btn-secondary text-white'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(targetId).style.display = 'flex';
                } else {
                    checkbox.checked = false;
                    document.getElementById(targetId).style.display = 'none';
                }
            });
        } else {
            document.getElementById(targetId).style.display = 'none';
            // Reset fields
            document.querySelector('#' + targetId + ' select[name="payment_method_id"]').value = '';
            document.querySelector('#' + targetId + ' input[name="voucher_number"]').value = '';
        }
    }
</script>
@endsection
