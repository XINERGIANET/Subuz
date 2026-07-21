@extends('template.app')

@section('title', 'Activos Fijos (Control de Stock)')

@section('content')
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
        bottom: -20px;
        height: 120%;
        opacity: 0.1; 
        pointer-events: none;
    }
    .premium-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        transition: all 0.3s ease;
        border: 1px solid rgba(0,0,0,0.03);
        border-bottom: 4px solid #e2e8f0;
    }
    .premium-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.06);
    }
    
    /* Dynamic bottom borders */
    .border-blue { border-bottom-color: #4263eb !important; }
    .border-purple { border-bottom-color: #ae3ec9 !important; }
    .border-teal { border-bottom-color: #0ca678 !important; }
    .border-pink { border-bottom-color: #d6336c !important; }
    .border-orange { border-bottom-color: #f76707 !important; }
    .border-green { border-bottom-color: #2fb344 !important; }
    .border-indigo { border-bottom-color: #6610f2 !important; }
    
    /* Stat boxes */
    .stat-box {
        border: 1px solid #f1f5f9;
        border-radius: 12px;
        background: #ffffff;
        padding: 12px;
    }
    
    .cat-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    .page-title-box {
        position: relative;
        padding-left: 15px;
    }
    .page-title-box::before {
        content: '';
        position: absolute;
        left: 0;
        top: 10px;
        height: 60%;
        width: 4px;
        background: #4263eb;
        border-radius: 4px;
    }
</style>

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
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <div class="text-uppercase text-primary small fw-bold mb-1" style="letter-spacing: 1px;">Sistema de Gestión</div>
        <h2 class="page-title fw-bolder fs-1 page-title-box">
            Activos Fijos (Control de Stock)
        </h2>
    </div>
    <div>
        <button class="btn btn-primary shadow-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#createAssetModal">
            <i class="ti ti-plus me-1"></i> Registrar Nuevo
        </button>
    </div>
</div>

<nav class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Activos Fijos</li>
  </ol>
</nav>

<div class="header-banner p-4 mb-5 d-flex align-items-center">
    <div class="bg-blue-lt text-blue rounded-3 d-flex justify-content-center align-items-center me-4" style="width: 64px; height: 64px; flex-shrink: 0;">
        <i class="ti ti-box fs-1"></i>
    </div>
    <div style="z-index: 1;">
        <h2 class="fw-bolder mb-1 fs-2">Inventario de Activos y Stock</h2>
        <div class="text-muted">Control de existencias, préstamos, alquileres y gastos</div>
    </div>
    <i class="ti ti-building-warehouse header-illustration text-primary" style="font-size: 140px;"></i>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row row-cards mb-4">
    <div class="row w-100" style="row-gap: 20px;">
    @forelse($groupedAssets as $index => $group)
        @php
            $style = getCategoryStyle($group->subcategory->name);
        @endphp
        <div class="col-sm-6 col-lg-3 flex-fill">
            <a href="{{ route('fixed-assets.category', $group->subcategory->id) }}" class="text-decoration-none">
                <div class="card premium-card border-{{ $style['color'] }} h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="cat-icon-box bg-{{ $style['color'] }}-lt text-{{ $style['color'] }} me-3 flex-shrink-0">
                                <i class="ti {{ $style['icon'] }}"></i>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold text-dark fs-3 lh-1">{{ ucfirst($group->subcategory->name) }}</h3>
                                <span class="badge bg-primary-lt text-primary px-2 py-1 rounded-pill fw-medium">Total: {{ $group->count }} unidades</span>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <div class="flex-fill text-center stat-box">
                                <div class="text-success fw-bold mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">DISPONIBLE</div>
                                <div class="fs-2 fw-bolder text-dark lh-1">{{ $group->available_count }}</div>
                            </div>
                            <div class="flex-fill text-center stat-box">
                                <div class="text-warning fw-bold mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">ENTREGADO</div>
                                <div class="fs-2 fw-bolder text-dark lh-1">{{ $group->assigned_count }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    @empty
        <div class="col-12 text-muted text-center py-4">
            No hay activos registrados en las categorías correspondientes.
        </div>
    @endforelse
    </div>
</div>




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
                            <div class="input-group">
                                <select class="form-select" name="expense_subcategory_id" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach($assetSubcategories as $sub)
                                        <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#createSubcategoryModal" title="Añadir nueva categoría">
                                    <i class="ti ti-plus"></i>
                                </button>
                            </div>
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
                            <input class="form-check-input" type="checkbox" id="registerExpenseCheckbox" onchange="toggleExpenseFields(this, 'expenseFields')">
                            <span class="form-check-label text-muted small">¿Quieres que este gasto se registre en la compra de stock?</span>
                        </label>
                    </div>
                    
                    <div class="row mb-3" id="expenseFields" style="display: none;">
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
                    <button type="submit" class="btn btn-brand">Guardar Activo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Subcategory Modal -->
<div class="modal modal-blur fade" id="createSubcategoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
        <div class="modal-content">
            @if(isset($assetCategory))
            <form action="{{ route('expense-categories.subcategories.store', $assetCategory->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre de Categoría</label>
                        <input type="text" class="form-control" name="name" required placeholder="Ej: Herramientas">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
            @endif
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

        // Handle subcategory creation via AJAX
        $('#createSubcategoryModal form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var url = form.attr('action');
            var btn = form.find('button[type="submit"]');
            
            btn.prop('disabled', true);
            
            $.ajax({
                type: "POST",
                url: url,
                data: form.serialize(),
                success: function(response) {
                    if(response.status) {
                        $('#createSubcategoryModal').modal('hide');
                        
                        // Add new option to the select and select it
                        var newOption = new Option(response.subcategory.name, response.subcategory.id, true, true);
                        $('select[name="expense_subcategory_id"]').append(newOption).trigger('change');
                        
                        form[0].reset();
                        ToastMessage.fire({
                            title: 'Categoría creada exitosamente'
                        });
                        
                        // Re-open original modal
                        $('#createAssetModal').modal('show');
                    }
                },
                error: function() {
                    ToastError.fire({
                        title: 'Error al crear categoría'
                    });
                },
                complete: function() {
                    btn.prop('disabled', false);
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
