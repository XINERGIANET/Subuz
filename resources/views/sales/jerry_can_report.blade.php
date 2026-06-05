@extends('template.app')

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    Reporte de Productos Prestables (Bidones)
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a href="{{ route('reports.jerryCanPdf') }}" class="btn btn-danger">
                    <i class="ti ti-file-type-pdf icon me-1"></i> Descargar PDF
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <form action="{{ url('jerry-can-report') }}" method="GET" class="mb-4">
            <div class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="form-label mb-0">Desde:</label>
                </div>
                <div class="col-auto">
                    <input type="date" name="start_date" class="form-control" value="{{ $request->start_date }}">
                </div>
                <div class="col-auto ms-3">
                    <label class="form-label mb-0">Hasta:</label>
                </div>
                <div class="col-auto">
                    <input type="date" name="end_date" class="form-control" value="{{ $request->end_date }}">
                </div>
                <div class="col-auto ms-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-filter icon me-1"></i> Filtrar
                    </button>
                    @if($request->start_date || $request->end_date)
                        <a href="{{ url('jerry-can-report') }}" class="btn btn-outline-secondary ms-1">Limpiar</a>
                    @endif
                </div>
            </div>
        </form>

        <div class="row row-cards mb-4">
            @foreach($products as $product)
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-3">
                        <div class="row align-items-center">
                            <!-- Left: Icon and Name -->
                            <div class="col-12 col-md-4 d-flex align-items-center mb-3 mb-md-0">
                                <span class="bg-primary text-white avatar avatar-md me-3">
                                    <i class="ti ti-package fs-2"></i>
                                </span>
                                <div>
                                    <h2 class="m-0 fw-bold">{{ $product->name }}</h2>
                                    <button class="btn btn-sm btn-outline-primary mt-1" data-bs-toggle="modal" data-bs-target="#modal-buy-{{ $product->id }}">
                                        <i class="ti ti-shopping-cart icon me-1"></i> Comprar Stock
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Right: Stats -->
                            <div class="col-12 col-md-8">
                                <div class="row text-center g-2 align-items-center">
                                    <div class="col-3 border-end">
                                        <div class="text-muted fw-bold text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Stock Inicial</div>
                                        <div class="h2 m-0 text-secondary">{{ $product->initial_stock !== null ? $product->initial_stock : $product->stock }}</div>
                                    </div>
                                    <div class="col-3 border-end">
                                        <div class="text-muted fw-bold text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Vendidos</div>
                                        <div class="h2 m-0 text-success">{{ $product->total_sold }}</div>
                                    </div>
                                    <div class="col-3 border-end">
                                        <div class="text-muted fw-bold text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Prestados</div>
                                        <div class="h2 m-0 text-warning">{{ $product->total_loaned }}</div>
                                    </div>
                                    <div class="col-3">
                                        <div class="text-muted fw-bold text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Stock Actual</div>
                                        <div class="h2 m-0 text-primary">{{ $product->stock }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach

            @if(count($products) == 0)
            <div class="col-12">
                <div class="alert alert-info border-0 shadow-sm">
                    No hay productos marcados como "Prestables" en el sistema. Puedes configurarlo editando un producto y activando la opción "Es prestable".
                </div>
            </div>
            @endif
        </div>

        @if(count($products) > 0)
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 bg-transparent">
                <h3 class="card-title fw-bold">Detalle por Cliente</h3>
                <div class="card-actions">
                    <form action="{{ url('jerry-can-report') }}" method="GET" class="d-flex">
                        <input type="hidden" name="start_date" value="{{ $request->start_date }}">
                        <input type="hidden" name="end_date" value="{{ $request->end_date }}">
                        <input type="text" name="search" class="form-control form-control-sm me-2" placeholder="Buscar cliente..." value="{{ $request->search }}">
                        <button type="submit" class="btn btn-sm btn-primary">Buscar</button>
                    </form>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap datatable">
                    <thead class="bg-light text-muted small">
                        <tr>
                            <th>Cliente</th>
                            <th>Producto</th>
                            <th class="text-center">Comprados</th>
                            <th class="text-center">Prestados</th>
                            <th class="text-center">Devueltos</th>
                            <th class="text-center">Saldo</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clientBreakdown as $data)
                        <tr>
                            <td class="fw-bold">{{ $data['client_name'] }}</td>
                            <td>{{ $data['product_name'] }}</td>
                            <td class="text-center h3 m-0 text-success">{{ $data['sold'] }}</td>
                            <td class="text-center h3 m-0 text-muted">{{ $data['borrowed'] }}</td>
                            <td class="text-center h3 m-0 text-info">{{ $data['returned'] }}</td>
                            <td class="text-center h3 m-0 text-warning">{{ $data['loaned'] }}</td>
                            <td class="text-end">
                                @if($data['loaned'] > 0 && $data['client_id'] > 0)
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-return-{{ $data['client_id'] }}-{{ $data['product_id'] }}">
                                        Devolver
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No se encontraron movimientos para los filtros seleccionados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>
</div>

@if(count($products) > 0)
    @foreach($clientBreakdown as $data)
        @if($data['loaned'] > 0 && $data['client_id'] > 0)
            <!-- Modal for Return -->
            <div class="modal modal-blur fade text-start" id="modal-return-{{ $data['client_id'] }}-{{ $data['product_id'] }}" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <form action="{{ url('jerry-can-report/return') }}" method="POST">
                            @csrf
                            <div class="modal-body text-center py-4">
                                <i class="ti ti-arrow-back-up text-primary fs-1 mb-2 d-block"></i>
                                <h3>Devolver {{ $data['product_name'] }}</h3>
                                <div class="text-muted mb-3">Cliente: <strong>{{ $data['client_name'] }}</strong><br>Pendientes: <strong>{{ $data['loaned'] }}</strong></div>
                                
                                <input type="hidden" name="client_id" value="{{ $data['client_id'] }}">
                                <input type="hidden" name="product_id" value="{{ $data['product_id'] }}">
                                
                                <div class="form-group mb-3 text-start">
                                    <label class="form-label">Cantidad a devolver</label>
                                    <input type="number" name="quantity" class="form-control" min="1" max="{{ $data['loaned'] }}" value="1" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Registrar Devolución</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endif

@foreach($products as $product)
<!-- Modal for Buy Stock -->
<div class="modal modal-blur fade text-start" id="modal-buy-{{ $product->id }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="{{ url('jerry-can-report/buy') }}" method="POST">
                @csrf
                <div class="modal-body text-center py-4">
                    <i class="ti ti-shopping-cart text-primary fs-1 mb-2 d-block"></i>
                    <h3>Comprar {{ $product->name }}</h3>
                    <div class="text-muted mb-3">Se registrará como un egreso de caja y aumentará el stock actual ({{ $product->stock }}).</div>
                    
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    
                    <div class="form-group mb-3 text-start">
                        <label class="form-label">Cantidad a comprar</label>
                        <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                    </div>
                    <div class="form-group mb-3 text-start">
                        <label class="form-label">Costo Total (S/)</label>
                        <input type="number" name="amount" class="form-control" min="0.01" step="0.01" required>
                    </div>
                    <div class="form-group mb-3 text-start">
                        <label class="form-check">
                            <input class="form-check-input check-external-payment" type="checkbox" name="is_external" value="1" data-target="#payment-method-container-{{ $product->id }}">
                            <span class="form-check-label fw-bold">Pago Externo (No registrar en caja)</span>
                        </label>
                        <div class="form-hint small">Si se marca, la compra sólo aumentará el stock pero no se registrará como egreso en la caja del sistema.</div>
                    </div>
                    <div class="form-group mb-3 text-start" id="payment-method-container-{{ $product->id }}">
                        <label class="form-label">Método de Pago</label>
                        <select name="payment_method_id" class="form-select" required>
                            @foreach($payment_methods as $pm)
                                <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Registrar Compra</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@endsection

@section('scripts')
<script>
    document.querySelectorAll('.check-external-payment').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            var targetContainer = document.querySelector(this.getAttribute('data-target'));
            var select = targetContainer.querySelector('select');
            if (this.checked) {
                targetContainer.style.display = 'none';
                select.removeAttribute('required');
            } else {
                targetContainer.style.display = 'block';
                select.setAttribute('required', 'required');
            }
        });
    });
</script>
@endsection
