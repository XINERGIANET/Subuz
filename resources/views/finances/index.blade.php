@extends('template.app')

@section('title', 'Finanzas')

@section('content')
<nav class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Finanzas</li>
  </ol>
</nav>

<div class="row row-cards mb-4">
    <div class="col-md-4">
        <div class="card metric-card border-0 shadow-sm overflow-hidden">
            <div class="card-status-start bg-primary"></div>
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="bg-primary-lt text-primary avatar avatar-md">
                            <i class="ti ti-building-bank fs-2"></i>
                        </div>
                    </div>
                    <div class="col text-truncate">
                        <div class="text-uppercase text-muted small fw-bold">Saldo Total Pendiente</div>
                        <div class="h2 mb-0 fw-bold text-primary">
                            S/{{ number_format($loans->where('currency', 'PEN')->sum(function($loan){ return $loan->remaining_balance; }), 2) }}
                            <span class="fs-4 text-muted mx-1">|</span>
                            ${{ number_format($loans->where('currency', 'USD')->sum(function($loan){ return $loan->remaining_balance; }), 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header border-0 py-3 d-flex justify-content-between align-items-center">
        <h3 class="card-title fw-bold"><i class="ti ti-activity me-2"></i>Gestión de Créditos</h3>
        <div class="d-flex gap-2">
            <button class="btn btn-brand btn-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#createLoanModal">
                <i class="ti ti-plus me-1 fs-3"></i> Registrar Crédito
            </button>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table card-table table-vcenter">
            <thead class="table-corporate-header">
                <tr>
                    <th>Banco / Entidad</th>
                    <th class="text-center">Monto Total</th>
                    <th class="text-center">Saldo Pendiente</th>
                    <th class="text-center">Cuotas</th>
                    <th class="text-center">Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loans as $loan)
                <tr>
                    <td>
                        <div class="fw-bold">{{ $loan->bank_name }}</div>
                        <div class="text-muted small">{{ $loan->description }}</div>
                    </td>
                    <td class="text-center fw-bold">{{ $loan->currency == 'USD' ? '$' : 'S/' }}{{ number_format($loan->total_amount, 2) }}</td>
                    <td class="text-center fw-bold text-danger">{{ $loan->currency == 'USD' ? '$' : 'S/' }}{{ number_format($loan->remaining_balance, 2) }}</td>
                    <td class="text-center">
                        <span class="badge bg-blue-lt">
                            {{ $loan->payments->count() }} / {{ $loan->installments_total }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $loan->status == 'Activo' ? 'bg-success' : 'bg-secondary' }}">
                            {{ $loan->status }}
                        </span>
                    </td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                            <a href="{{ route('finances.show', $loan->id) }}" class="btn btn-icon btn-outline-primary" data-bs-toggle="tooltip" title="Ver Detalles / Pagos">
                                <i class="ti ti-eye fs-2"></i>
                            </a>
                            <button class="btn btn-icon btn-outline-info btn-edit" data-id="{{ $loan->id }}" data-bs-toggle="tooltip" title="Editar">
                                <i class="ti ti-pencil fs-2"></i>
                            </button>
                            <button class="btn btn-icon btn-outline-danger btn-delete" data-id="{{ $loan->id }}" data-bs-toggle="tooltip" title="Eliminar">
                                <i class="ti ti-trash fs-2"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" align="center" class="py-5 text-muted">
                        <i class="ti ti-mood-empty fs-1 mb-2 d-block"></i>
                        No hay créditos registrados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Create Loan -->
<div class="modal modal-blur fade" id="createLoanModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
        <form action="{{ route('finances.store') }}" method="POST">
            @csrf
            <div class="modal-header">
              <h5 class="modal-title">Registrar Nuevo Crédito</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Banco / Entidad</label>
                <input type="text" class="form-control" name="bank_name" required placeholder="Ej: Banco de Crédito">
              </div>
              <div class="mb-3">
                <label class="form-label">Descripción / Motivo</label>
                <textarea class="form-control" name="description" rows="2" placeholder="Opcional"></textarea>
              </div>
              <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Moneda</label>
                    <select class="form-select" name="currency" required>
                        <option value="PEN">Soles (S/)</option>
                        <option value="USD">Dólares ($)</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Monto Total</label>
                    <input type="number" step="0.01" class="form-control" name="total_amount" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">N° de Cuotas</label>
                    <input type="number" class="form-control" name="installments_total" required min="1">
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Monto Cuota (Estimado)</label>
                    <input type="number" step="0.01" class="form-control" name="monthly_amount">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Fecha de Inicio</label>
                    <input type="date" class="form-control" name="start_date" required value="{{ date('Y-m-d') }}">
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cerrar</button>
              <button type="submit" class="btn btn-brand">Guardar Crédito</button>
            </div>
        </form>
    </div>
  </div>
</div>
<!-- Modal Edit Loan -->
<div class="modal modal-blur fade" id="editLoanModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
        <form id="editLoanForm" method="POST">
            @csrf
            @method('PATCH')
            <div class="modal-header">
              <h5 class="modal-title">Editar Crédito</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Banco / Entidad</label>
                <input type="text" class="form-control" name="bank_name" id="edit_bank_name" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Descripción / Motivo</label>
                <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
              </div>
              <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Moneda</label>
                    <select class="form-select" name="currency" id="edit_currency" required>
                        <option value="PEN">Soles (S/)</option>
                        <option value="USD">Dólares ($)</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Monto Total</label>
                    <input type="number" step="0.01" class="form-control" name="total_amount" id="edit_total_amount" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">N° de Cuotas</label>
                    <input type="number" class="form-control" name="installments_total" id="edit_installments_total" required min="1">
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Monto Cuota (Estimado)</label>
                    <input type="number" step="0.01" class="form-control" name="monthly_amount" id="edit_monthly_amount">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Fecha de Inicio</label>
                    <input type="date" class="form-control" name="start_date" id="edit_start_date" required>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Estado</label>
                <select class="form-select" name="status" id="edit_status">
                    <option value="Activo">Activo</option>
                    <option value="Pagado">Pagado</option>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cerrar</button>
              <button type="submit" class="btn btn-brand">Actualizar Crédito</button>
            </div>
        </form>
    </div>
  </div>
</div>

@section('scripts')
<script>
    $(document).on('click', '.btn-edit', function() {
        let id = $(this).data('id');
        $.get(`/finances/${id}/edit`, function(data) {
            $('#edit_bank_name').val(data.bank_name);
            $('#edit_description').val(data.description);
            $('#edit_currency').val(data.currency);
            $('#edit_total_amount').val(data.total_amount);
            $('#edit_installments_total').val(data.installments_total);
            $('#edit_monthly_amount').val(data.monthly_amount);
            $('#edit_start_date').val(data.start_date.split('T')[0]);
            $('#edit_status').val(data.status);
            
            $('#editLoanForm').attr('action', `/finances/${id}`);
            $('#editLoanModal').modal('show');
        });
    });

    $(document).on('click', '.btn-delete', function() {
        let id = $(this).data('id');
        ToastConfirm.fire({
            text: '¿Estás seguro de eliminar este crédito? Esta acción no se puede deshacer.',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/finances/${id}`,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        if (data.status) {
                            ToastMessage.fire({ text: 'Crédito eliminado correctamente.' }).then(() => {
                                location.reload();
                            });
                        }
                    }
                });
            }
        });
    });
</script>
@endsection
@endsection
