@extends('template.app')

@section('title', 'Facturación')

@section('content')
    <nav class="mb-2">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
            <li class="breadcrumb-item active">Facturación</li>
        </ol>
    </nav>

    <style>
        .ts-dropdown {
            z-index: 2000 !important;
        }

        .card-filter-container {
            overflow: visible !important;
        }
    </style>

    <div class="card card-filter-container">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Comprobantes Emitidos</h3>
            <div class="d-flex gap-2">
                <a href="{{ route('invoices.create') }}" class="btn btn-azure">
                    <i class="ti ti-plus icon"></i> Nueva Factura Manual
                </a>
                @if(auth()->user()->role == 'admin' || auth()->user()->role == 'asistente')
                    <a href="{{ route('reports.deleted_invoices') }}" class="btn btn-azure">
                        <i class="ti ti-trash icon"></i> Ver comprobantes eliminados
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body border-bottom">
            <form class="mb-0">
                <div class="row g-3">
                    <div class="col-lg-4">
                        <label class="form-label">Filtrar por Cliente</label>
                        <select class="form-select ts-clients" name="client_id">
                            <option value="">Todos los clientes</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ request()->client_id == $client->id ? 'selected' : '' }}>
                                    {{ $client->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label">Desde</label>
                        <input type="date" class="form-control" name="start_date" value="{{ request()->start_date }}">
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label">Hasta</label>
                        <input type="date" class="form-control" name="end_date" value="{{ request()->end_date }}">
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" name="type">
                            <option value="">Todos</option>
                            <option value="factura" {{ request()->type == 'factura' ? 'selected' : '' }}>Factura</option>
                            <option value="boleta" {{ request()->type == 'boleta' ? 'selected' : '' }}>Boleta</option>
                        </select>
                    </div>
                    <div class="col-lg-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                    @if(request()->client_id || request()->start_date || request()->end_date || request()->type)
                        <div class="col-lg-2 d-flex align-items-end">
                            <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary w-100">Limpiar</a>
                        </div>
                    @endif
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th>Cliente</th>
                        <th>Fecha Emisión</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Estado SUNAT</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>
                                @php
                                    $isFactura = $invoice->document_type == 'factura';
                                    $badgeClass = $isFactura ? 'bg-purple-lt' : 'bg-azure-lt';
                                    $prefix = $isFactura ? 'FACT' : 'BOL';
                                @endphp
                                <span class="badge {{ $badgeClass }} fw-bold">
                                    {{ $prefix }} #{{ $invoice->number }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $invoice->client->name }}</div>
                                <div class="text-muted small">{{ $invoice->client->document }}</div>
                            </td>
                            <td class="text-muted">{{ $invoice->date->format('d/m/Y') }}</td>
                            <td class="text-end fw-bold">S/ {{ number_format($invoice->total, 2) }}</td>
                            <td class="text-center">
                                @if($invoice->electronic_invoice_status == 'SENT')
                                    <span class="badge bg-green-lt"><i class="ti ti-check me-1"></i> Aceptado</span>
                                @elseif($invoice->electronic_invoice_status == 'ERROR')
                                    <span class="badge bg-red-lt"
                                        title="{{ $invoice->electronic_invoice_response['message'] ?? 'Error desconocido' }}">Error
                                        SUNAT</span>
                                @else
                                    <span class="badge bg-secondary-lt">No emitido</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    @php
                                        $pdfUrl = $invoice->electronic_invoice_pdf_a4_url ?: route('invoices.local_pdf', $invoice);
                                    @endphp
                                    <a href="{{ $pdfUrl }}" target="_blank" class="btn btn-sm btn-primary" title="Ver PDF">
                                        <i class="ti ti-file-text icon-inline me-1"></i> Ver PDF
                                    </a>

                                    @if($invoice->electronic_invoice_status == 'SENT')
                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="visually-hidden">Opciones</span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('invoices.xml', $invoice) }}">
                                                    <i class="ti ti-file-code me-2"></i> Descargar XML
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('invoices.cdr', $invoice) }}">
                                                    <i class="ti ti-file-check me-2"></i> Descargar CDR
                                                </a>
                                            </li>
                                        </ul>
                                    @endif
                                </div>

                                @if($invoice->electronic_invoice_status != 'SENT')
                                    <form action="{{ route('invoices.resend', $invoice) }}" method="POST"
                                        class="d-inline resend-form ms-1">
                                        @csrf
                                        <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                                        <input type="hidden" name="invoice_number" value="{{ $invoice->number }}">
                                        <button type="submit" class="btn btn-sm btn-warning" title="Reenviar a SUNAT">
                                            <i class="ti ti-rotate icon-inline me-1"></i> Reenviar
                                        </button>
                                    </form>
                                @endif
                                @if($invoice->electronic_invoice_status == 'ERROR')
                                    <form action="{{ route('invoices.release_error', $invoice) }}" method="POST"
                                        class="d-inline release-error-form ms-1">
                                        @csrf
                                        <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                            title="Quitar comprobante y devolver ventas a pendientes">
                                            <i class="ti ti-trash icon-inline me-1"></i> Liberar
                                        </button>
                                    </form>
                                @endif
                                @if(auth()->user()->role == 'admin')
                                    <form action="{{ route('invoices.destroy', $invoice) }}" method="POST"
                                        class="d-inline delete-form ms-1">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar comprobante">
                                            <i class="ti ti-trash icon-inline me-1"></i> Eliminar
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted">No se encontraron comprobantes correspondientes a la búsqueda.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $invoices->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof TomSelect !== 'undefined') {
                new TomSelect('.ts-clients', {
                    copyClassesToDropdown: false,
                    dropdownClass: 'dropdown-menu ts-dropdown',
                    optionClass: 'dropdown-item',
                    controlInput: '<input>',
                    dropdownParent: 'body',
                    render: {
                        no_results: function (data, escape) {
                            return '<div class="no-results">No se encontraron resultados</div>';
                        }
                    }
                });
            }

            // Reenvío a SUNAT vía AJAX (misma idea que emitir en pendientes: respuesta JSON, sin 302)
            function subuzEscapeHtml(s) {
                if (s === null || s === undefined) return '';
                return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }
            const resendForms = document.querySelectorAll('.resend-form');
            resendForms.forEach(form => {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const submitResend = function () {
                        const btn = form.querySelector('button[type="submit"]');
                        const originalHtml = btn ? btn.innerHTML : '';
                        if (btn) {
                            btn.disabled = true;
                            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Enviando...';
                        }
                        $.ajax({
                            url: form.getAttribute('action'),
                            method: 'POST',
                            data: $(form).serialize(),
                            success: function (response) {
                                if (response.status) {
                                    Swal.fire({
                                        title: '¡Éxito!',
                                        text: response.message || 'Comprobante reenviado.',
                                        icon: 'success'
                                    }).then(function () { window.location.reload(); });
                                } else {
                                    var errHtml = '<p style="text-align:left">' + subuzEscapeHtml(response.error || 'No se pudo reenviar.') + '</p>';
                                    if (response.local_diagnosis) {
                                        errHtml += '<p style="text-align:left;font-size:0.9em;margin-top:10px;color:#7a0c0c"><strong>Diagnóstico (SUBUZ):</strong> ' + subuzEscapeHtml(response.local_diagnosis) + '</p>';
                                    }
                                    if (response.hint) {
                                        errHtml += '<p style="text-align:left;font-size:0.9em;margin-top:10px;color:#555">' + subuzEscapeHtml(response.hint) + '</p>';
                                    }
                                    if (response.sendBill_http_status != null || response.sendBill_variant) {
                                        errHtml += '<p style="text-align:left;font-size:0.85em;margin-top:8px"><strong>HTTP:</strong> ' + subuzEscapeHtml(String(response.sendBill_http_status)) + ' &nbsp; <strong>variante sendBill:</strong> ' + subuzEscapeHtml(response.sendBill_variant || '') + '</p>';
                                    }
                                    if (response.send_bill_url) {
                                        errHtml += '<p style="text-align:left;font-size:0.8em;margin-top:6px;word-break:break-all"><strong>URL sendBill:</strong> ' + subuzEscapeHtml(response.send_bill_url) + '</p>';
                                    }
                                    if (response.escalation_apisunat) {
                                        errHtml += '<p style="text-align:left;font-size:0.85em;margin-top:10px;color:#1a4d80"><strong>Siguiente paso (escalación):</strong> ' + subuzEscapeHtml(response.escalation_apisunat) + '</p>';
                                    }
                                    if (response.file_name) {
                                        errHtml += '<p style="text-align:left;font-size:0.85em;margin-top:8px"><strong>fileName SUNAT:</strong> ' + subuzEscapeHtml(response.file_name) + '</p>';
                                    }
                                    if (response.debug_file) {
                                        errHtml += '<p style="text-align:left;font-size:0.85em"><strong>Depuración en servidor:</strong> storage/app/apisunat-debug/' + subuzEscapeHtml(response.debug_file) + '</p>';
                                    }
                                    if (response.apisunat_response) {
                                        errHtml += '<p style="text-align:left;font-size:0.85em;margin-top:8px"><strong>Respuesta JSON Apisunat (exacta):</strong></p><pre style="text-align:left;font-size:0.72em;max-height:200px;overflow:auto;background:#f4f4f4;padding:8px;border-radius:4px">' + subuzEscapeHtml(JSON.stringify(response.apisunat_response, null, 2)) + '</pre>';
                                    } else if (response.apisunat_response_raw) {
                                        errHtml += '<p style="text-align:left;font-size:0.85em;margin-top:8px"><strong>Cuerpo crudo Apisunat:</strong></p><pre style="text-align:left;font-size:0.72em;max-height:120px;overflow:auto;background:#f4f4f4;padding:8px;border-radius:4px">' + subuzEscapeHtml(response.apisunat_response_raw) + '</pre>';
                                    }
                                    Swal.fire({ title: 'Error', html: errHtml, icon: 'error', width: '42rem' });
                                    if (btn) {
                                        btn.disabled = false;
                                        btn.innerHTML = originalHtml;
                                    }
                                }
                            },
                            error: function (xhr) {
                                var err = 'Error de servidor';
                                if (xhr.responseJSON) {
                                    if (xhr.responseJSON.error) err = xhr.responseJSON.error;
                                    else if (xhr.responseJSON.errors) {
                                        var firstErr = Object.values(xhr.responseJSON.errors)[0];
                                        err = Array.isArray(firstErr) ? firstErr[0] : firstErr;
                                    } else if (xhr.responseJSON.message) err = xhr.responseJSON.message;
                                }
                                Swal.fire('Error', err, 'error');
                                if (btn) {
                                    btn.disabled = false;
                                    btn.innerHTML = originalHtml;
                                }
                            }
                        });
                    };
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '¿Estás seguro?',
                            text: "Se intentará enviar el comprobante a SUNAT nuevamente.",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#465fff',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Sí, reenviar',
                            cancelButtonText: 'Cancelar'
                        }).then(function (result) {
                            if (result.isConfirmed) {
                                submitResend();
                            }
                        });
                    } else {
                        if (confirm('¿Estás seguro de que deseas reenviar este comprobante a SUNAT?')) {
                            submitResend();
                        }
                    }
                });
            });

            const releaseForms = document.querySelectorAll('.release-error-form');
            releaseForms.forEach(form => {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const submitRelease = function () {
                        const btn = form.querySelector('button[type="submit"]');
                        const originalHtml = btn ? btn.innerHTML : '';
                        if (btn) {
                            btn.disabled = true;
                            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> ...';
                        }
                        $.ajax({
                            url: form.getAttribute('action'),
                            method: 'POST',
                            data: $(form).serialize(),
                            success: function (response) {
                                if (response.status) {
                                    Swal.fire({
                                        title: 'Listo',
                                        text: response.message || 'Comprobante liberado.',
                                        icon: 'success'
                                    }).then(function () { window.location.reload(); });
                                } else {
                                    Swal.fire('Error', response.error || 'No se pudo liberar.', 'error');
                                    if (btn) {
                                        btn.disabled = false;
                                        btn.innerHTML = originalHtml;
                                    }
                                }
                            },
                            error: function (xhr) {
                                var err = 'Error de servidor';
                                if (xhr.responseJSON && xhr.responseJSON.error) err = xhr.responseJSON.error;
                                Swal.fire('Error', err, 'error');
                                if (btn) {
                                    btn.disabled = false;
                                    btn.innerHTML = originalHtml;
                                }
                            }
                        });
                    };
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '¿Liberar este comprobante?',
                            html: 'Se eliminará el registro con error SUNAT y las ventas volverán a <strong>Emitir comprobantes pendientes</strong>. El correlativo local se revierte en uno.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d63939',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Sí, liberar',
                            cancelButtonText: 'Cancelar'
                        }).then(function (result) {
                            if (result.isConfirmed) submitRelease();
                        });
                    } else {
                        if (confirm('¿Eliminar este comprobante con error y devolver las ventas a pendientes?')) {
                            submitRelease();
                        }
                    }
                });
            });
        });
    </script>
@endsection