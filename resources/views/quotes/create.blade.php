@extends('template.app')

@section('title', 'Cotizaciones')

@section('content')
<nav class="mb-3 d-print-none">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('quotes.index') }}">Cotizaciones</a></li>
        <li class="breadcrumb-item active">Nueva Cotización</li>
    </ol>
</nav>

<style>
	.ts-dropdown { z-index: 2000 !important; background: var(--bg-primary) !important; }
	.ts-dropdown .dropdown-item { color: var(--text-main) !important; }
	.ts-dropdown .dropdown-item:hover { background-color: rgba(0, 0, 0, 0.05) !important; }
	[data-bs-theme='dark'] .ts-dropdown .dropdown-item:hover { background-color: rgba(255, 255, 255, 0.1) !important; }
</style>

<div class="row">
    <div class="col-12 col-md-8 mx-auto">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-brand-lt py-3">
                <h3 class="card-title text-brand fw-bold mb-0">
                    <i class="ti ti-file-text me-2"></i> Generar Cotización
                </h3>
            </div>
            <div class="card-body">
                <form id="form-quote">
                    @csrf
                    
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                        <h4 class="fw-bold text-muted mb-0">Datos del Cliente</h4>
                        <div style="width: 300px;">
                            <select class="form-select ts-clients" id="search_client">
                                <option value="">Buscar cliente guardado...</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Fecha</label>
                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Señor(es) / Razón Social</label>
                            <input type="text" name="client_name" class="form-control" placeholder="Ej. Empresa SAC" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">RUC / DNI</label>
                            <div class="input-group">
                                <input type="text" name="client_ruc" class="form-control" placeholder="Ej. 20123456789" required>
                                <button class="btn btn-primary" type="button" id="btnSearchDocument" title="Buscar en RENIEC/SUNAT">
                                    <i class="ti ti-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Dirección</label>
                            <input type="text" name="client_address" class="form-control" placeholder="Ej. Av. Principal 123" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                        <h4 class="fw-bold text-muted mb-0">Productos a Cotizar</h4>
                        <button type="button" class="btn btn-sm btn-outline-brand" id="btn-add-product">
                            <i class="ti ti-plus me-1"></i> Agregar Fila
                        </button>
                    </div>

                    <div class="table-responsive mb-4">
                        <table class="table table-vcenter">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th style="width: 120px;">Cantidad</th>
                                    <th style="width: 150px;">Precio Unit. (S/)</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="products-container">
                                <tr>
                                    <td>
                                        <select name="products[0][id]" class="form-select product-select" required>
                                            <option value="">Seleccione un producto</option>
                                            @foreach($products as $p)
                                                <option value="{{ $p->id }}" data-price="{{ $p->calculated_price }}">{{ $p->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" min="1" step="1" name="products[0][quantity]" class="form-control" value="1" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="products[0][price]" class="form-control product-price" required>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-icon btn-outline-danger btn-remove-row" title="Quitar">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('form-quote').reset();">
                            Limpiar
                        </button>
                        <button type="submit" class="btn btn-brand">
                            <i class="ti ti-file-type-pdf me-2"></i> Generar PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let rowIdx = 1;
    const productsData = @json($products);

    $(document).on('change', '.product-select', function() {
        const selectedOption = $(this).find('option:selected');
        const price = selectedOption.data('price');
        if(price) {
            $(this).closest('tr').find('.product-price').val(price);
        }
    });

    $('#btn-add-product').on('click', function() {
        let options = '<option value="">Seleccione un producto</option>';
        productsData.forEach(p => {
            options += `<option value="${p.id}" data-price="${p.price}">${p.name}</option>`;
        });

        const html = `
            <tr>
                <td>
                    <select name="products[${rowIdx}][id]" class="form-select product-select" required>
                        ${options}
                    </select>
                </td>
                <td>
                    <input type="number" min="1" step="1" name="products[${rowIdx}][quantity]" class="form-control" value="1" required>
                </td>
                <td>
                    <input type="number" step="0.01" name="products[${rowIdx}][price]" class="form-control product-price" required>
                </td>
                <td>
                    <button type="button" class="btn btn-icon btn-outline-danger btn-remove-row" title="Quitar">
                        <i class="ti ti-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#products-container').append(html);
        rowIdx++;
    });

    $(document).on('click', '.btn-remove-row', function() {
        if ($('#products-container tr').length > 1) {
            $(this).closest('tr').remove();
        } else {
            ToastError.fire({ title: 'No puedes eliminar la única fila.' });
        }
    });

    $('#btnSearchDocument').click(function() {
        let documentNumber = $('input[name="client_ruc"]').val().trim();
        if (documentNumber.length === 8) {
            searchDocument(documentNumber, 'reniec');
        } else if (documentNumber.length === 11) {
            searchDocument(documentNumber, 'ruc');
        } else {
            ToastError.fire({ text: 'El documento debe tener 8 (DNI) o 11 (RUC) dígitos.' });
        }
    });

    function searchDocument(documentNumber, type) {
        let url = type === 'reniec' ? '/api/reniec?dni=' + documentNumber : '/api/ruc?ruc=' + documentNumber;
        let btn = $('#btnSearchDocument');
        let originalIcon = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>').prop('disabled', true);

        $.ajax({
            url: url,
            method: 'GET',
            success: function(data) {
                btn.html(originalIcon).prop('disabled', false);
                if (data.status) {
                    if (type === 'reniec') {
                        $('input[name="client_name"]').val(data.nombre_completo);
                        $('input[name="client_address"]').val('-');
                        ToastMessage.fire({ text: 'DNI encontrado' });
                    } else {
                        $('input[name="client_name"]').val(data.trade_name || data.legal_name);
                        $('input[name="client_address"]').val(data.address);
                        ToastMessage.fire({ text: 'RUC encontrado' });
                    }
                } else {
                    ToastError.fire({ text: data.message || 'No se encontró información' });
                }
            },
            error: function(err) {
                btn.html(originalIcon).prop('disabled', false);
                ToastError.fire({ text: err.responseJSON?.message || 'Error al buscar el documento' });
            }
        });
    }

    var tsClients = new TomSelect('.ts-clients', {
        valueField: 'id',
        labelField: 'name',
        searchField: ['name', 'document'],
        copyClassesToDropdown: false,
        dropdownClass: 'dropdown-menu ts-dropdown',
        optionClass:'dropdown-item',
        load: function(query, callback){
            $.ajax({
                url: '{{ route('clients.api') }}?q=' + encodeURIComponent(query),
                method: 'GET',
                success: function(data){
                    callback(data.items);
                },
                error: function(err){
                    console.log(err);
                }
            })
        },
        render: {
            option: function(data, escape) {
                return `<div>${escape(data.name)} <span class="small text-muted">(${escape(data.document || '')})</span></div>`;
            },
            item: function(data, escape) {
                return `<div>${escape(data.name)}</div>`;
            },
            no_results: function(data, escape){
                return '<div class="no-results">No se encontraron resultados</div>'
            }
        }
    });

    tsClients.on('change', function(value){
        if(value){
            let client = tsClients.options[value];
            $('input[name="client_name"]').val(client.name);
            $('input[name="client_ruc"]').val(client.document);
            
            let fullAddress = client.address ? client.address : '';
            if (client.district) {
                fullAddress += fullAddress ? ' - ' + client.district : client.district;
            }
            $('input[name="client_address"]').val(fullAddress || '-');
        }
    });

    $('#form-quote').submit(function(e){
        e.preventDefault();
        
        let btn = $(this).find('button[type="submit"]');
        let originalText = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generando...').prop('disabled', true);

        $.ajax({
            url: '{{ route('quotes.store') }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(data){
                if(data.status){
                    window.open(data.url, '_blank');
                    window.location.href = '{{ route('quotes.index') }}';
                }
            },
            error: function(err){
                btn.html(originalText).prop('disabled', false);
                ToastError.fire({ text: 'Ocurrió un error al generar la cotización' });
            }
        });
    });
</script>
@endsection
