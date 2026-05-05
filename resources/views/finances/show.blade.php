@extends('template.app')

@section('title', 'Detalle de Crédito - ' . $loan->bank_name)

@section('content')
<nav class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finances.index') }}">Finanzas</a></li>
    <li class="breadcrumb-item active">Detalle</li>
  </ol>
</nav>

<div class="row row-cards mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header border-0 py-3">
                <h3 class="card-title fw-bold"><i class="ti ti-calendar-stats me-2"></i>Cronograma de Pagos</h3>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter">
                    <thead class="table-corporate-header">
                        <tr>
                            <th class="text-center">Cuota</th>
                            <th>Vencimiento</th>
                            <th class="text-center">Monto</th>
                            <th class="text-center">Estado</th>
                            <th>Acciones / Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($installments as $ins)
                        <tr class="{{ $ins->status == 'Pagado' ? 'bg-light-success' : ($ins->status == 'Vencido' ? 'bg-light-danger' : '') }}">
                            <td class="text-center">
                                <span class="badge bg-blue-lt fw-bold">{{ $ins->number }}</span>
                            </td>
                            <td class="{{ $ins->status == 'Vencido' ? 'text-danger fw-bold' : '' }}">
                                {{ $ins->due_date->format('d/m/Y') }}
                            </td>
                            <td class="text-center fw-bold">{{ $loan->currency == 'USD' ? '$' : 'S/' }}{{ number_format($ins->amount, 2) }}</td>
                            <td class="text-center">
                                @if($ins->status == 'Pagado')
                                    <span class="badge bg-success">Pagado</span>
                                @elseif($ins->status == 'Vencido')
                                    <span class="badge bg-danger">Vencido</span>
                                @else
                                    <span class="badge bg-secondary">Pendiente</span>
                                @endif
                            </td>
                            <td>
                                @if($ins->status == 'Pagado')
                                    <div class="d-flex flex-column gap-1">
                                        @foreach($ins->payments as $p)
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar avatar-xs bg-green-lt p-1" style="width: 1.2rem; height: 1.2rem;">
                                                    <i class="ti ti-check fs-5"></i>
                                                </div>
                                                <div class="small">
                                                    <span class="fw-bold">{{ $loan->currency == 'USD' ? '$' : 'S/' }}{{ number_format($p->amount, 2) }}</span>
                                                    <span class="text-muted">({{ $p->payment_method->name ?? 'Externo' }})</span>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-icon btn-ghost-info btn-xs btn-edit-payment" data-id="{{ $p->id }}" data-bs-toggle="tooltip" title="Editar Pago">
                                                    <i class="ti ti-pencil fs-4"></i>
                                                </button>
                                                <button class="btn btn-icon btn-ghost-danger btn-xs btn-delete-payment" data-id="{{ $p->id }}" data-bs-toggle="tooltip" title="Eliminar Pago">
                                                    <i class="ti ti-trash fs-4"></i>
                                                </button>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                @else
                                    <button class="btn btn-sm btn-primary btn-pay-installment" 
                                        data-number="{{ $ins->number }}" 
                                        data-amount="{{ $ins->amount }}">
                                        <i class="ti ti-cash me-1"></i> Pagar
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-3">
             <div class="card-status-top bg-primary"></div>
             <div class="card-body">
                 <div class="text-uppercase text-muted small fw-bold mb-3">Información del Crédito</div>
                 <h2 class="mb-1 fw-bold">{{ $loan->bank_name }}</h2>
                 <p class="text-muted mb-3">{{ $loan->description }}</p>
                 
                 <div class="divider mb-3"></div>
                 
                 <div class="d-flex justify-content-between mb-2">
                     <span class="text-muted">Monto Total:</span>
                     <span class="fw-bold">{{ $loan->currency == 'USD' ? '$' : 'S/' }}{{ number_format($loan->total_amount, 2) }}</span>
                 </div>
                 <div class="d-flex justify-content-between mb-2">
                     <span class="text-muted">Monto Pagado:</span>
                     <span class="fw-bold text-success">{{ $loan->currency == 'USD' ? '$' : 'S/' }}{{ number_format($loan->paid_amount, 2) }}</span>
                 </div>
                 <div class="d-flex justify-content-between mb-2">
                     <span class="text-muted">Saldo Pendiente:</span>
                     <span class="h3 mb-0 fw-extrabold text-danger">{{ $loan->currency == 'USD' ? '$' : 'S/' }}{{ number_format($loan->remaining_balance, 2) }}</span>
                 </div>
                 
                 <div class="divider mb-3"></div>
                 
                 <div class="d-flex justify-content-between mb-2">
                     <span class="text-muted">Cuotas:</span>
                     <span class="fw-bold">{{ $loan->payments->unique('installment_number')->count() }} / {{ $loan->installments_total }}</span>
                 </div>
                 <div class="d-flex justify-content-between">
                     <span class="text-muted">Estado:</span>
                     <span class="badge {{ $loan->status == 'Activo' ? 'bg-success' : 'bg-secondary' }}">{{ $loan->status }}</span>
                 </div>
             </div>
        </div>
        
        <div class="card border-0 shadow-sm bg-primary-lt">
            <div class="card-body p-3">
                <div class="d-flex align-items-center">
                    <div class="avatar bg-primary text-white me-3">
                        <i class="ti ti-calendar-event fs-2"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Fecha de Inicio</div>
                        <div class="fw-bold text-primary">{{ $loan->start_date->format('d/m/Y') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pagar Cuota -->
<div class="modal modal-blur fade" id="payInstallmentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="{{ route('finances.payment') }}" method="POST">
                @csrf
                <input type="hidden" name="bank_loan_id" value="{{ $loan->id }}">
                <input type="hidden" name="installment_number" id="modal_installment_number">
                
                <div class="modal-header">
                    <h5 class="modal-title">Pagar Cuota <span id="display_installment_number"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-check form-switch cursor-pointer">
                            <input class="form-check-input" type="checkbox" name="is_external" id="is_external_toggle">
                            <span class="form-check-label fw-bold">Es Pago Externo</span>
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fecha de Pago</label>
                        <input type="date" class="form-control" name="payment_date" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <!-- Pago Interno Section -->
                    <div id="internal_payment_section">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0 fw-bold text-uppercase small text-primary">Métodos de Pago</label>
                            <button type="button" class="btn btn-sm btn-ghost-primary" id="btn-add-payment-modal">
                                <i class="ti ti-plus me-1"></i> Agregar método
                            </button>
                        </div>
                        <div id="payment-rows-modal">
                            <!-- Rows injected here -->
                        </div>
                        <div class="mt-3 p-3 rounded-2 bg-primary-lt border border-primary d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Total a Registrar:</span>
                            <span class="h3 mb-0 fw-extrabold text-primary" id="modal_total_display">{{ $loan->currency == 'USD' ? '$' : 'S/' }}0.00</span>
                        </div>
                    </div>

                    <!-- Pago Externo Section -->
                    <div id="external_payment_section" style="display:none">
                        <div class="mb-3">
                            <label class="form-label">Monto Pagado (Externo)</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ $loan->currency == 'USD' ? '$' : 'S/' }}</span>
                                <input type="number" step="0.01" class="form-control" name="amount" id="external_amount">
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Notas / Observaciones</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Opcional..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand">Confirmar Pago</button>
                </div>
            </form>
    </div>
</div>
</div>

<!-- Modal Editar Pago -->
<div class="modal modal-blur fade" id="editPaymentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="editPaymentForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title">Editar Pago de Cuota</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Monto</label>
                        <div class="input-group">
                            <span class="input-group-text">{{ $loan->currency == 'USD' ? '$' : 'S/' }}</span>
                            <input type="number" step="0.01" class="form-control" name="amount" id="edit_p_amount" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha de Pago</label>
                        <input type="date" class="form-control" name="payment_date" id="edit_p_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Método de Pago</label>
                        <select class="form-select" name="payment_method_id" id="edit_p_method">
                            <option value="">Externo (No afecta caja)</option>
                            @foreach($payment_methods as $pm)
                                <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Si cambias el método o monto, el gasto asociado en caja se actualizará automáticamente.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notas / Observaciones</label>
                        <textarea class="form-control" name="notes" id="edit_p_notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand">Actualizar Pago</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const paymentMethodsOptions = `
        @foreach($payment_methods as $pm)
            <option value="{{ $pm->id }}">{{ $pm->name }}</option>
        @endforeach
    `;

    function addPaymentRowModal(amount = '') {
        const rowCount = $('#payment-rows-modal .payment-row').length;
        const html = `
            <div class="payment-row mb-2 pb-2 border-bottom border-light">
                <div class="row g-2 align-items-center">
                    <div class="col-7">
                        <select class="form-select form-select-sm" name="payments[${rowCount}][method_id]" required>
                            <option value="">Seleccionar método</option>
                            ${paymentMethodsOptions}
                        </select>
                    </div>
                    <div class="col-4">
                        <div class="input-group input-group-sm input-group-flat">
                            <span class="input-group-text ps-2 pe-1">{{ $loan->currency == 'USD' ? '$' : 'S/' }}</span>
                            <input type="number" step="0.01" class="form-control ps-1 txt-payment-amount-modal" name="payments[${rowCount}][amount]" value="${amount}" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="col-1 text-end">
                        <button type="button" class="btn btn-ghost-danger btn-icon btn-sm btn-remove-payment-modal"><i class="ti ti-trash fs-3"></i></button>
                    </div>
                </div>
            </div>
        `;
        $('#payment-rows-modal').append(html);
        calculateModalTotal();
    }

    function calculateModalTotal() {
        let total = 0;
        $('.txt-payment-amount-modal').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $('#modal_total_display').text('{{ $loan->currency == "USD" ? "$" : "S/" }}' + total.toFixed(2));
    }

    $(document).on('click', '.btn-pay-installment', function() {
        const number = $(this).data('number');
        const amount = $(this).data('amount');
        
        $('#modal_installment_number').val(number);
        $('#display_installment_number').text(number);
        $('#external_amount').val(amount);
        
        $('#payment-rows-modal').empty();
        addPaymentRowModal(amount);
        
        $('#payInstallmentModal').modal('show');
    });

    $(document).on('click', '#btn-add-payment-modal', function() {
        addPaymentRowModal();
    });

    $(document).on('click', '.btn-remove-payment-modal', function() {
        $(this).closest('.payment-row').remove();
        calculateModalTotal();
    });

    $(document).on('input', '.txt-payment-amount-modal', function() {
        calculateModalTotal();
    });

    $('#is_external_toggle').change(function() {
        if($(this).is(':checked')) {
            $('#internal_payment_section').hide();
            $('#external_payment_section').show();
            $('#payment-rows-modal input').prop('disabled', true);
            $('#external_amount').prop('disabled', false).prop('required', true);
        } else {
            $('#internal_payment_section').show();
            $('#external_payment_section').hide();
            $('#payment-rows-modal input').prop('disabled', false);
            $('#external_amount').prop('disabled', true).prop('required', false);
        }
    });

    $(document).on('click', '.btn-edit-payment', function() {
        let id = $(this).data('id');
        // Hide tooltip
        $(this).tooltip('hide');
        $.get(`/finances/payment/${id}/edit`, function(data) {
            $('#edit_p_amount').val(data.amount);
            if (data.payment_date) {
                $('#edit_p_date').val(data.payment_date.split('T')[0]);
            }
            $('#edit_p_method').val(data.payment_method_id);
            $('#edit_p_notes').val(data.notes);
            
            $('#editPaymentForm').attr('action', `/finances/payment/${id}`);
            $('#editPaymentModal').modal('show');
        });
    });

    $('#editPaymentForm').submit(function(e) {
        e.preventDefault();
        let url = $(this).attr('action');
        $.ajax({
            url: url,
            type: 'PATCH',
            data: $(this).serialize(),
            success: function(data) {
                if (data.status) {
                    $('#editPaymentModal').modal('hide');
                    ToastMessage.fire({ text: 'Pago actualizado correctamente.' }).then(() => {
                        location.reload();
                    });
                }
            }
        });
    });

    $(document).on('click', '.btn-delete-payment', function() {
        let id = $(this).data('id');
        ToastConfirm.fire({
            text: '¿Estás seguro de eliminar este pago? Si fue un pago interno, el gasto asociado en caja también será eliminado.',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/finances/payment/${id}`,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        if (data.status) {
                            ToastMessage.fire({ text: 'Pago eliminado correctamente.' }).then(() => {
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
