@extends('template.app')

@section('title', 'Facturación Manual')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Facturación</a></li>
    <li class="breadcrumb-item active">Nueva Factura Manual</li>
  </ol>
</nav>

<style>
    .ts-dropdown {
        z-index: 2000 !important;
        background: var(--bg-primary) !important;
    }
    .ts-dropdown .dropdown-item {
        color: var(--text-main) !important;
    }
    .card-filter-container {
        overflow: visible !important;
    }
    .glose-toggle {
        cursor: pointer;
    }
</style>

<div class="row">
    <div class="col-lg-12">
        <div class="card mb-3 card-filter-container">
            <div class="card-header">
                <h3 class="card-title">Datos del Comprobante</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-3">
                        <div class="mb-3">
                            <label class="form-label">Tipo de Comprobante</label>
                            <select class="form-select" id="document_type">
                                <option value="boleta" selected>Boleta de Venta</option>
                                <option value="factura">Factura</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <div class="mb-3">
                            <label class="form-label">Próximo Número</label>
                            <input type="text" class="form-control" id="next_number" value="{{ $next_boleta }}" readonly>
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <div class="mb-3">
                            <label class="form-label">Fecha</label>
                            <input type="date" class="form-control" id="date" value="{{ now()->format('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between">
                                Cliente
                                <span id="client-type-badge"></span>
                            </label>
                            <div class="input-group">
                                <select class="form-select ts-clients" id="client_id">
                                    <option value="">Seleccionar Cliente</option>
                                </select>
                                <button class="btn btn-icon" data-bs-toggle="modal" data-bs-target="#createClientModal">
                                    <i class="ti ti-user-plus icon"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-12">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Detalle de Productos / Glose</h3>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="mode_glose">
                    <label class="form-check-label fw-bold text-primary" for="mode_glose">Por consumo</label>
                </div>
            </div>
            <div class="card-body border-bottom bg-light">
                <div class="row align-items-end g-2">
                    <div class="col-lg-6" id="container-product">
                        <label class="form-label">Producto</label>
                        <select class="form-select ts-products" id="product_id">
                            <option value="">Seleccionar Producto</option>
                            @foreach($products as $product)
                            <option value="{{ $product->id }}" data-price="{{ $product->price }}">{{ $product->name }} - S/ {{ number_format($product->price, 2) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-6 d-none" id="container-glose">
                        <label class="form-label">Descripción (Por consumo)</label>
                        <input type="text" class="form-control" id="manual_description" placeholder="Ej. Por consumo de...">
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label">Precio Unit.</label>
                        <input type="number" class="form-control" id="item_price" step="0.01" value="0.00">
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label">Cantidad</label>
                        <input type="number" class="form-control" id="item_quantity" step="0.0001" value="1">
                    </div>
                    <div class="col-lg-2 text-end">
                        <button class="btn btn-azure w-100" id="btn-add-item">
                            <i class="ti ti-plus icon"></i> Agregar
                        </button>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Descripción</th>
                            <th class="text-center">Cant.</th>
                            <th class="text-end">P. Unit.</th>
                            <th class="text-end">Total</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody id="tbl-items">
                        <tr id="empty-row">
                            <td colspan="6" class="text-center text-muted py-4">No hay ítems agregados</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4 offset-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-6 mb-2"><span class="fw-bold">Subtotal</span></div>
                    <div class="col-6 text-end mb-2" id="lbl-subtotal">S/ 0.00</div>
                    
                    <div class="col-6 mb-2"><span class="fw-bold">I.G.V. (18%)</span></div>
                    <div class="col-6 text-end mb-2" id="lbl-igv">S/ 0.00</div>
                    
                    <div class="col-6 mb-3"><span class="h3 fw-bold">TOTAL</span></div>
                    <div class="col-6 text-end mb-3"><span class="h3 fw-bold text-primary" id="lbl-total">S/ 0.00</span></div>
                    
                    <div class="col-12 mb-3">
                        <label class="form-label">Observaciones (Opcional)</label>
                        <textarea class="form-control" id="notes" rows="2"></textarea>
                    </div>

                    <div class="col-12">
                        <button class="btn btn-primary btn-lg w-100" id="btn-save-invoice">
                            <i class="ti ti-device-floppy icon"></i> EMITIR COMPROBANTE
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Crear Cliente --}}
<div class="modal modal-blur fade" id="createClientModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="storeClientForm">
                <div class="modal-header">
                    <h5 class="modal-title">Crear nuevo cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">RUC o DNI</label>
                                <input type="text" class="form-control" name="document" required>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Nombre o Razón Social</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label">Tipo de cliente</label>
                                <select class="form-select" name="type">
                                    <option value="Contado">Contado</option>
                                    <option value="Credito">Crédito</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    let items = [];
    let nextFactura = "{{ $next_factura }}";
    let nextBoleta = "{{ $next_boleta }}";

    $(document).ready(function() {
        // TomSelect Clientes
        const tsClients = new TomSelect('#client_id', {
            valueField: 'id',
            labelField: 'name',
            searchField: ['name', 'document'],
            load: function(query, callback) {
                $.get('{{ route('clients.api') }}?q=' + encodeURIComponent(query), function(data) {
                    callback(data.items);
                });
            },
            render: {
                option: function(data, escape) {
                    return `<div><span class="fw-bold">${escape(data.document)}</span> - ${escape(data.name)}</div>`;
                },
                item: function(data, escape) {
                    return `<div>${escape(data.name)}</div>`;
                }
            }
        });

        // TomSelect Productos
        const tsProducts = new TomSelect('#product_id', {
            onChange: function(value) {
                if (value) {
                    const option = $('#product_id option[value="' + value + '"]');
                    $('#item_price').val(option.data('price'));
                }
            }
        });

        // Cambio de tipo de comprobante
        $('#document_type').change(function() {
            const type = $(this).val();
            $('#next_number').val(type === 'factura' ? nextFactura : nextBoleta);
        });

        // Toggle Glose
        $('#mode_glose').change(function() {
            if ($(this).is(':checked')) {
                $('#container-product').addClass('d-none');
                $('#container-glose').removeClass('d-none');
                tsProducts.clear();
                $('#item_price').val('0.00');
            } else {
                $('#container-product').removeClass('d-none');
                $('#container-glose').addClass('d-none');
            }
        });

        // Agregar Item
        $('#btn-add-item').click(function() {
            const isGlose = $('#mode_glose').is(':checked');
            let description, productId, price, quantity;

            if (isGlose) {
                description = $('#manual_description').val().trim();
                productId = null;
                if (!description) return Swal.fire('Error', 'Ingrese una descripción para el concepto por consumo', 'error');
            } else {
                productId = $('#product_id').val();
                if (!productId) return Swal.fire('Error', 'Seleccione un producto', 'error');
                description = $('#product_id option:selected').text().split(' - S/')[0];
            }

            price = parseFloat($('#item_price').val());
            quantity = parseFloat($('#item_quantity').val());

            if (isNaN(price) || price < 0) return Swal.fire('Error', 'Precio inválido', 'error');
            if (isNaN(quantity) || quantity <= 0) return Swal.fire('Error', 'Cantidad inválida', 'error');

            items.push({
                product_id: productId,
                description: description,
                price: price,
                quantity: quantity,
                subtotal: price * quantity
            });

            renderItems();
            
            // Reset fields
            $('#manual_description').val('');
            tsProducts.clear();
            $('#item_price').val('0.00');
            $('#item_quantity').val('1');
        });

        // Eliminar Item
        $(document).on('click', '.btn-remove-item', function() {
            const index = $(this).data('index');
            items.splice(index, 1);
            renderItems();
        });

        function renderItems() {
            const tbody = $('#tbl-items');
            tbody.empty();

            if (items.length === 0) {
                tbody.append('<tr id="empty-row"><td colspan="6" class="text-center text-muted py-4">No hay ítems agregados</td></tr>');
                updateTotals(0);
                return;
            }

            let grandTotal = 0;
            items.forEach((item, index) => {
                grandTotal += item.subtotal;
                tbody.append(`
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.description}</td>
                        <td class="text-center">${item.quantity}</td>
                        <td class="text-end">S/ ${item.price.toFixed(2)}</td>
                        <td class="text-end">S/ ${item.subtotal.toFixed(2)}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-icon btn-danger btn-remove-item" data-index="${index}">
                                <i class="ti ti-trash"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });

            updateTotals(grandTotal);
        }

        function updateTotals(total) {
            const igvRate = 0.18;
            const subtotal = total / (1 + igvRate);
            const igv = total - subtotal;

            $('#lbl-subtotal').text('S/ ' + subtotal.toFixed(2));
            $('#lbl-igv').text('S/ ' + igv.toFixed(2));
            $('#lbl-total').text('S/ ' + total.toFixed(2));
        }

        // Guardar Comprobante
        $('#btn-save-invoice').click(function() {
            if (items.length === 0) return Swal.fire('Error', 'Debe agregar al menos un ítem', 'error');
            if (!$('#client_id').val()) return Swal.fire('Error', 'Debe seleccionar un cliente', 'error');

            const data = {
                date: $('#date').val(),
                client_id: $('#client_id').val(),
                document_type: $('#document_type').val(),
                notes: $('#notes').val(),
                items: items,
                _token: '{{ csrf_token() }}'
            };

            Swal.fire({
                title: '¿Confirmar emisión?',
                text: "Se generará el comprobante electrónico.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, emitir',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#btn-save-invoice').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Emitiendo...');
                    
                    $.ajax({
                        url: '{{ route('invoices.store_manual') }}',
                        method: 'POST',
                        data: data,
                        success: function(response) {
                            Swal.fire('¡Éxito!', response.message, 'success').then(() => {
                                window.open(response.pdf_url, '_blank');
                                location.href = '{{ route('invoices.index') }}';
                            });
                        },
                        error: function(xhr) {
                            $('#btn-save-invoice').prop('disabled', false).html('<i class="ti ti-device-floppy icon"></i> EMITIR COMPROBANTE');
                            const error = xhr.responseJSON ? xhr.responseJSON.error : 'Ocurrió un error inesperado';
                            Swal.fire('Error', error, 'error');
                        }
                    });
                }
            });
        });

        // Store Client Ajax
        $('#storeClientForm').submit(function(e) {
            e.preventDefault();
            $.post('{{ route('clients.storeInSale') }}', $(this).serialize() + '&_token={{ csrf_token() }}', function(data) {
                if (data.status) {
                    $('#createClientModal').modal('hide');
                    Swal.fire('Listo', 'Cliente creado', 'success');
                    tsClients.load(''); // Refresh search
                } else {
                    Swal.fire('Error', data.error, 'error');
                }
            });
        });
    });
</script>
@endsection
