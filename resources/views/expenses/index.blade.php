@extends('template.app')

@section('title', 'Gastos')

@section('content')
    @php
        $indicatorQuery = [];

        if (request()->from_date) {
            $indicatorQuery['start_date'] = request()->from_date;
        }

        if (request()->to_date) {
            $indicatorQuery['end_date'] = request()->to_date;
        }

        if (!request()->from_date && !request()->to_date && request()->month && request()->year) {
            $indicatorQuery['start_date'] = request()->year . '-' . str_pad(request()->month, 2, '0', STR_PAD_LEFT) . '-01';
            $indicatorQuery['end_date'] = date('Y-m-t', strtotime($indicatorQuery['start_date']));
        }

        if (!request()->from_date && !request()->to_date && request()->year && !request()->month) {
            $indicatorQuery['start_date'] = request()->year . '-01-01';
            $indicatorQuery['end_date'] = request()->year . '-12-31';
        }
    @endphp

    <nav class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
            <li class="breadcrumb-item active">Gastos</li>
        </ol>
    </nav>

    <div class="row row-cards mb-4">
        <div class="col-md-4">
            <div class="card metric-card border-0 shadow-sm overflow-hidden">
                <div class="card-status-start bg-danger"></div>
                <div class="card-body p-3">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="bg-danger-lt text-danger avatar avatar-md">
                                <i class="ti ti-receipt fs-2"></i>
                            </div>
                        </div>
                        <div class="col text-truncate">
                            <div class="text-uppercase text-muted small fw-bold">Total Gastos del Periodo</div>
                            <div class="h2 mb-0 fw-bold text-danger">S/{{ number_format($total_expenses, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header border-0 py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <h3 class="card-title fw-bold mb-0"><i class="ti ti-filter me-2"></i>Filtros de Búsqueda</h3>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-primary btn-pill px-4 shadow-sm"
                    href="{{ route('expenses.indicators', $indicatorQuery) }}">
                    <i class="ti ti-chart-donut-3 me-1 fs-3"></i> Ver gr&aacute;ficos
                </a>
                @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('seller') || auth()->user()->hasRole('asistente') || auth()->user()->hasRole('despachador'))
                    <button class="btn btn-brand btn-pill px-4 shadow-sm" data-bs-toggle="modal"
                        data-bs-target="#createModal">
                        <i class="ti ti-plus me-1 fs-3"></i> Crear nuevo gasto
                    </button>
                @endif
                @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('seller') || auth()->user()->hasRole('asistente'))
                    <button class="btn btn-outline-brand btn-pill px-4 shadow-sm" data-bs-toggle="modal"
                        data-bs-target="#categoryModal">
                        <i class="ti ti-tags me-1 fs-3"></i> Categorías
                    </button>
                    <a class="btn btn-success btn-pill px-4 shadow-sm"
                        href="{{ route('expenses.excel', request()->all()) }}">
                        <i class="ti ti-file-spreadsheet me-1 fs-3"></i> Excel
                    </a>
                    <a class="btn btn-danger btn-pill px-4 shadow-sm" href="{{ route('expenses.pdf', request()->all()) }}">
                        <i class="ti ti-file-type-pdf me-1 fs-3"></i> PDF
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body py-3 border-bottom">
            <form>
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small fw-medium text-muted text-uppercase mb-1">Mes</label>
                        <select class="form-select text-dark" name="month">
                            <option value="">Seleccionar mes</option>
                            @php
                                $names = [
                                    'Enero',
                                    'Febrero',
                                    'Marzo',
                                    'Abril',
                                    'Mayo',
                                    'Junio',
                                    'Julio',
                                    'Agosto',
                                    'Septiembre',
                                    'Octubre',
                                    'Noviembre',
                                    'Diciembre',
                                ];
                            @endphp
                            @foreach ($names as $index => $name)
                                <option value="{{ $index + 1 }}" @if (request()->month == $index + 1) selected @endif>
                                    {{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-medium text-muted text-uppercase mb-1">Año</label>
                        <select class="form-select text-dark" name="year">
                            <option value="">Seleccionar año</option>
                            @for ($i = 2023; $i <= 2030; $i++)
                                <option value="{{ $i }}" @if (request()->year == $i) selected @endif>
                                    {{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-medium text-muted text-uppercase mb-1">Desde</label>
                        <input type="date" class="form-control text-dark" name="from_date"
                            value="{{ request()->from_date }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-medium text-muted text-uppercase mb-1">Hasta</label>
                        <input type="date" class="form-control text-dark" name="to_date"
                            value="{{ request()->to_date }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-medium text-muted text-uppercase mb-1">Método</label>
                        <select class="form-select text-dark" name="payment_method_id">
                            <option value="">Todos</option>
                            @foreach ($payment_methods as $pm)
                                <option value="{{ $pm->id }}" @if(request()->payment_method_id == $pm->id) selected @endif>{{ $pm->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('asistente'))
                    <div class="col-md-2">
                        <label class="form-label small fw-medium text-muted text-uppercase mb-1">Categoría</label>
                        <select class="form-select text-dark" name="expense_category_id" id="filterCategory">
                            <option value="">Todas</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @if(request()->expense_category_id == $cat->id) selected @endif>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-medium text-muted text-uppercase mb-1">Subcategoría</label>
                        <select class="form-select text-dark" name="expense_subcategory_id" id="filterSubcategory" disabled>
                            <option value="">Todas</option>
                        </select>
                    </div>
                    @endif
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-brand w-100 py-2 fw-bold"><i
                                class="ti ti-search me-1 fs-3"></i> Buscar</button>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary w-100 py-2 fw-medium">
                            <i class="ti ti-refresh icon me-1"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header border-0 py-3">
            <h3 class="card-title fw-bold"><i class="ti ti-list me-2"></i>Detalle de Egresos</h3>
        </div>
        <div class="table-responsive">
            <table class="table card-table table-vcenter">
                <thead class="table-corporate-header">
                    <tr>
                        <th>Descripción</th>
                        <th>Detalles Extra</th>
                        <th>Categoría</th>
                        <th class="text-center">Monto Total</th>
                        <th>Desglose de Pago</th>
                        <th class="text-center">F. Registro</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses->groupBy(function($item){ return $item->description.$item->date->format('Y-m-d H:i:s'); }) as $group)
                        @php $first = $group->first(); @endphp
                        <tr>
                            <td class="fw-bold">{{ $first->description }}</td>
                            <td class="small text-muted">
                                @if($first->real_date) <div class="mb-1"><i class="ti ti-calendar-event me-1"></i> F. Real: {{ date('d/m/Y', strtotime($first->real_date)) }}</div> @endif
                                @if($first->receipt_number) <div class="mb-1"><i class="ti ti-file-invoice me-1"></i> Comp: {{ $first->receipt_number }}</div> @endif
                                @if($first->operation_number) <div><i class="ti ti-hash me-1"></i> Op: {{ $first->operation_number }}</div> @endif
                            </td>
                            <td>
                                @if($first->category)
                                    <span class="badge bg-blue-lt">{{ $first->category->name }}</span>
                                    @if($first->subcategory)
                                        <span class="badge bg-azure-lt">{{ $first->subcategory->name }}</span>
                                    @endif
                                @else
                                    <span class="text-muted small">Sin categoría</span>
                                @endif
                            </td>
                            <td class="text-center fw-extrabold text-danger" style="font-size: 1.1rem;">
                                S/{{ number_format($group->sum('amount'), 2) }}</td>
                            <td>
                                <div class="d-flex flex-wrap gap-1 align-items-center">
                                    @foreach ($group as $item)
                                        <span class="badge bg-red-lt fw-normal"
                                            style="text-transform: none; border: 1px solid rgba(214, 51, 108, 0.1);">
                                            <span class="fw-bold">S/{{ number_format($item->amount, 2) }}</span>
                                            <span
                                                class="ms-1 opacity-75">({{ optional($item->payment_method)->name }})</span>
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-center text-muted">{{ $first->date->format('d/m/Y') }}</td>
                            <td class="text-end">
                                @if (auth()->user()->hasRole('admin'))
                                    <div class="d-flex justify-content-end gap-2">
                                        <button class="btn btn-icon btn-edit-corporate btn-edit"
                                            data-id="{{ $first->id }}" data-bs-toggle="tooltip" title="Editar">
                                            <i class="ti ti-pencil fs-2"></i>
                                        </button>
                                        <button class="btn btn-icon btn-delete-corporate btn-delete"
                                            data-id="{{ $first->id }}" data-bs-toggle="tooltip" title="Eliminar">
                                            <i class="ti ti-trash fs-2"></i>
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" align="center" class="py-5 text-muted">
                                <i class="ti ti-mood-empty fs-1 mb-2 d-block"></i>
                                No se han encontrado registros de gastos en este periodo
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($expenses->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center">
                {{ $expenses->withQueryString()->links() }}
            </div>
        @endif
    </div>
    <div class="modal modal-blur fade" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="storeForm" method="POST">
                    @csrf
                    <input type="hidden" name="bank_loan_id" id="createBankLoanId">
                    <input type="hidden" name="installment_number" id="createInstallmentNumber">
                    <div class="modal-header">
                        <h5 class="modal-title">Crear nuevo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <select class="form-select ts-description" name="description" id="createDescription"
                                required>
                                <option value="">Seleccionar o escribir...</option>
                                @foreach ($descriptions as $desc)
                                    <option value="{{ $desc }}">{{ $desc }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Fecha Real (Opcional)</label>
                                <input type="date" class="form-control" name="real_date" id="createRealDate">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">N° Comprobante (Opcional)</label>
                                <input type="text" class="form-control" name="receipt_number" id="createReceiptNumber">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">N° Operación (Opcional)</label>
                                <input type="text" class="form-control" name="operation_number" id="createOperationNumber">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Categoría</label>
                                <select class="form-select" name="expense_category_id" id="createCategory">
                                    <option value="">Ninguna</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Subcategoría</label>
                                <select class="form-select" name="expense_subcategory_id" id="createSubcategory" disabled>
                                    <option value="">Seleccione Categoría Primero</option>
                                </select>
                            </div>
                        </div>

                        <div class="alert bg-blue-lt border border-blue text-blue d-none" id="financePaymentHint">
                            <div class="d-flex align-items-start gap-2">
                                <i class="ti ti-building-bank fs-3 mt-1"></i>
                                <div>
                                    <div class="fw-bold" id="financePaymentTitle">Cuota financiera seleccionada</div>
                                    <div class="small" id="financePaymentText"></div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0 fw-bold text-uppercase small">Pagos / Métodos</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-payment-create">
                                <i class="ti ti-plus me-1"></i> Agregar pago
                            </button>
                        </div>

                        <div id="payment-rows-create">
                            <!-- Payment rows will be injected here -->
                        </div>

                        <div
                            class="mt-3 p-2 rounded bg-primary-lt border border-primary d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Monto Total:</span>
                            <span class="h4 mb-0 fw-extrabold" id="total-amount-create">S/0.00</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-brand">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="editForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Editar</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <select class="form-select ts-description" name="description" id="editDescription" required>
                                <option value="">Seleccionar o escribir...</option>
                                @foreach ($descriptions as $desc)
                                    <option value="{{ $desc }}">{{ $desc }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Fecha Real (Opcional)</label>
                                <input type="date" class="form-control" name="real_date" id="editRealDate">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">N° Comprobante (Opcional)</label>
                                <input type="text" class="form-control" name="receipt_number" id="editReceiptNumber">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">N° Operación (Opcional)</label>
                                <input type="text" class="form-control" name="operation_number" id="editOperationNumber">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Categoría</label>
                                <select class="form-select" name="expense_category_id" id="editCategory">
                                    <option value="">Ninguna</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Subcategoría</label>
                                <select class="form-select" name="expense_subcategory_id" id="editSubcategory" disabled>
                                    <option value="">Seleccione Categoría Primero</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0 fw-bold text-uppercase small">Pagos / Métodos (Modo
                                edición)</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-payment-edit">
                                <i class="ti ti-plus me-1"></i> Agregar pago
                            </button>
                        </div>

                        <div id="payment-rows-edit">
                            <!-- Payment rows will be injected here -->
                        </div>

                        <div
                            class="mt-3 p-2 rounded bg-primary-lt border border-primary d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Monto Total:</span>
                            <span class="h4 mb-0 fw-extrabold" id="total-amount-edit">S/0.00</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" id="editId">
                        <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-brand">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Categorias -->
    <div class="modal modal-blur fade" id="categoryModal" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Categorías y Subcategorías</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addCategoryForm" class="mb-4">
                        @csrf
                        <div class="input-group">
                            <input type="text" class="form-control" name="name" placeholder="Nueva Categoría" required>
                            <button class="btn btn-primary" type="submit">Agregar</button>
                        </div>
                    </form>

                    <div class="accordion" id="categoriesAccordion">
                        @foreach($categories as $category)
                        <div class="accordion-item">
                            <h2 class="accordion-header d-flex" id="heading-{{ $category->id }}">
                                <button class="accordion-button collapsed flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $category->id }}" aria-expanded="false" aria-controls="collapse-{{ $category->id }}">
                                    <span class="category-name-text">{{ $category->name }}</span>
                                </button>
                                @if(strtolower(trim($category->name)) !== 'finanzas' && strtolower(trim($category->name)) !== 'operativos')
                                <button type="button" class="btn btn-icon btn-ghost-primary btn-edit-category align-self-center me-3" data-id="{{ $category->id }}" data-name="{{ $category->name }}" title="Editar Categoría">
                                    <i class="ti ti-pencil"></i>
                                </button>
                                @endif
                            </h2>
                            <div id="collapse-{{ $category->id }}" class="accordion-collapse collapse" aria-labelledby="heading-{{ $category->id }}" data-bs-parent="#categoriesAccordion">
                                <div class="accordion-body">
                                    @if(strtolower(trim($category->name)) !== 'finanzas')
                                    <form class="addSubcategoryForm mb-3" data-category-id="{{ $category->id }}">
                                        @csrf
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control" name="name" placeholder="Nueva Subcategoría" required>
                                            <button class="btn btn-outline-secondary" type="submit">Agregar</button>
                                        </div>
                                    </form>
                                    @else
                                    <div class="alert alert-info alert-sm text-center mb-3">Esta categoría y sus subcategorías son gestionadas automáticamente por el módulo de Finanzas.</div>
                                    @endif
                                    <ul class="list-group list-group-flush subcategories-list-{{ $category->id }}">
                                        @foreach($category->subcategories as $subcategory)
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-1">
                                            <span class="subcategory-name-text">{{ $subcategory->name }}</span>
                                            <div>
                                                @if(strtolower(trim($category->name)) !== 'finanzas')
                                                <button type="button" class="btn btn-sm btn-icon btn-ghost-primary btn-edit-subcategory" data-id="{{ $subcategory->id }}" data-name="{{ $subcategory->name }}" title="Editar Subcategoría">
                                                    <i class="ti ti-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-icon btn-ghost-danger btn-delete-subcategory" data-id="{{ $subcategory->id }}">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                                @endif
                                            </div>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        var paymentMethodsHtml = `
		@foreach ($payment_methods as $pm)
			<option value="{{ $pm->id }}">{{ $pm->name }}</option>
		@endforeach
	`;

        var tsDescriptionCreate;
        var tsDescriptionEdit;

        function addPaymentRowCreate(amount = '') {
            var rowCount = $('#payment-rows-create .payment-row').length;
            var html = `
			<div class="payment-row mb-2 border-bottom pb-2">
				<div class="row g-2 align-items-center">
					<div class="col-7">
						<select class="form-select" name="payments[${rowCount}][method_id]" required>
							<option value="">Seleccionar</option>
							${paymentMethodsHtml}
						</select>
					</div>
					<div class="col-4">
						<div class="input-group input-group-flat">
							<span class="input-group-text ps-2 pe-0">S/</span>
							<input type="number" step="0.01" class="form-control ps-1 txt-payment-amount-create" name="payments[${rowCount}][amount]" value="${amount}" placeholder="0.00" required>
						</div>
					</div>
					<div class="col-1 text-end">
						${rowCount > 0 ? '<button type="button" class="btn btn-ghost-danger btn-icon btn-sm btn-remove-payment-create"><i class="ti ti-trash fs-3"></i></button>' : ''}
					</div>
				</div>
			</div>
		`;
            $('#payment-rows-create').append(html);
            calculateTotalCreate();
        }

        function calculateTotalCreate() {
            var total = 0;
            $('.txt-payment-amount-create').each(function() {
                total += parseFloat($(this).val()) || 0;
            });
            $('#total-amount-create').text('S/' + total.toFixed(2));
        }

        $(document).on('click', '#btn-add-payment-create', function() {
            addPaymentRowCreate();
        });

        $(document).on('click', '.btn-remove-payment-create', function() {
            $(this).closest('.payment-row').remove();
            calculateTotalCreate();
        });

        $(document).on('input', '.txt-payment-amount-create', function() {
            calculateTotalCreate();
        });

        var categoriesData = @json($categories);
        var financeCategoryId = @json($financeCategoryId);
        var financeLoans = @json($financeLoans);
        var financeLoansBySubcategory = {};

        financeLoans.forEach(function(item) {
            financeLoansBySubcategory[item.subcategory_id] = item;
        });

        function isFinanceCategory(categoryId) {
            return financeCategoryId && String(categoryId) === String(financeCategoryId);
        }

        function resetFinanceSelection() {
            $('#createBankLoanId').val('');
            $('#createInstallmentNumber').val('');
            $('#financePaymentHint').addClass('d-none');
            $('#financePaymentTitle').text('Cuota financiera seleccionada');
            $('#financePaymentText').text('');
        }

        function setCreateDescription(description) {
            if (tsDescriptionCreate) {
                if (!tsDescriptionCreate.options[description]) {
                    tsDescriptionCreate.addOption({ value: description, text: description });
                }
                tsDescriptionCreate.setValue(description);
            } else {
                var $description = $('#createDescription');
                var optionExists = false;
                $description.find('option').each(function() {
                    if ($(this).val() === description) {
                        optionExists = true;
                    }
                });
                if (!optionExists) {
                    $description.append(new Option(description, description));
                }
                $description.val(description);
            }
        }

        function applyFinanceSelection() {
            var selectedSubcategoryId = $('#createSubcategory').val();
            var financeData = financeLoansBySubcategory[selectedSubcategoryId];

            if (!isFinanceCategory($('#createCategory').val()) || !financeData) {
                resetFinanceSelection();
                return;
            }

            $('#createBankLoanId').val(financeData.loan_id);
            $('#createInstallmentNumber').val(financeData.installment_number);
            setCreateDescription(financeData.description);

            if ($('#payment-rows-create .payment-row').length === 0) {
                addPaymentRowCreate(financeData.amount);
            } else {
                $('#payment-rows-create .txt-payment-amount-create').first().val(financeData.amount);
                calculateTotalCreate();
            }

            $('#financePaymentTitle').text(financeData.label);
            $('#financePaymentText').text('Vence el ' + financeData.due_date_label + ' · Monto de cuota: ' + financeData.formatted_amount);
            $('#financePaymentHint').removeClass('d-none');
        }

        function updateSubcategories(categoryId, selectElementId, selectedSubcategoryId = null) {
            var $subCatSelect = $('#' + selectElementId);
            $subCatSelect.empty();
            if (!categoryId) {
                $subCatSelect.append('<option value="">Seleccione Categoría Primero</option>');
                $subCatSelect.prop('disabled', true);
                return;
            }

            if (isFinanceCategory(categoryId)) {
                $subCatSelect.append('<option value="">Seleccionar cuota pendiente...</option>');

                if (financeLoans.length > 0) {
                    financeLoans.forEach(function(item) {
                        $subCatSelect.append('<option value="' + item.subcategory_id + '">' + item.label + ' - ' + item.formatted_amount + '</option>');
                    });
                    $subCatSelect.prop('disabled', false);
                    if(selectedSubcategoryId) {
                        $subCatSelect.val(selectedSubcategoryId);
                    }
                } else {
                    $subCatSelect.empty().append('<option value="">Sin cuotas pendientes</option>');
                    $subCatSelect.prop('disabled', true);
                }
                return;
            }
            
            var category = categoriesData.find(c => c.id == categoryId);
            if (category && category.subcategories.length > 0) {
                $subCatSelect.append('<option value="">Ninguna</option>');
                category.subcategories.forEach(function(sub) {
                    $subCatSelect.append('<option value="' + sub.id + '">' + sub.name + '</option>');
                });
                $subCatSelect.prop('disabled', false);
                if(selectedSubcategoryId) {
                    $subCatSelect.val(selectedSubcategoryId);
                }
            } else {
                $subCatSelect.append('<option value="">Sin Subcategorías</option>');
                $subCatSelect.prop('disabled', true);
            }
        }

        $('#createCategory').change(function() {
            resetFinanceSelection();
            updateSubcategories($(this).val(), 'createSubcategory');
        });

        $('#createSubcategory').change(function() {
            applyFinanceSelection();
        });

        $('#editCategory').change(function() {
            updateSubcategories($(this).val(), 'editSubcategory');
        });

        $('#addCategoryForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: '{{ route('expense-categories.store') }}',
                method: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    if (res.status) {
                        ToastMessage.fire({ text: 'Categoría agregada' }).then(() => location.reload());
                    }
                }
            });
        });

        $('.addSubcategoryForm').submit(function(e) {
            e.preventDefault();
            var categoryId = $(this).data('category-id');
            var url = '{{ route("expense-categories.subcategories.store", ":id") }}'.replace(':id', categoryId);
            $.ajax({
                url: url,
                method: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    if (res.status) {
                        ToastMessage.fire({ text: 'Subcategoría agregada' }).then(() => location.reload());
                    }
                }
            });
        });

        $('.btn-delete-subcategory').click(function() {
            var id = $(this).data('id');
            var url = '{{ route("expense-subcategories.destroy", ":id") }}'.replace(':id', id);
            ToastConfirm.fire({ text: '¿Borrar subcategoría?' }).then((result) => {
                if(result.isConfirmed){
                    $.ajax({
                        url: url,
                        method: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function(res) {
                            if (res.status) {
                                ToastMessage.fire({ text: 'Subcategoría borrada' }).then(() => location.reload());
                            }
                        }
                    });
                }
            });
        });

        $('.btn-edit-category').click(function(e) {
            e.stopPropagation();
            var id = $(this).data('id');
            var name = $(this).data('name');
            var url = '{{ route("expense-categories.update", ":id") }}'.replace(':id', id);

            Swal.fire({
                title: 'Editar Categoría',
                input: 'text',
                inputValue: name,
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                target: document.getElementById('categoryModal'),
                inputValidator: (value) => {
                    if (!value) {
                        return 'El nombre es obligatorio'
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        method: 'PUT',
                        data: {
                            _token: '{{ csrf_token() }}',
                            name: result.value
                        },
                        success: function(res) {
                            if (res.status) {
                                ToastMessage.fire({ text: 'Categoría actualizada' }).then(() => location.reload());
                            }
                        }
                    });
                }
            });
        });

        $('.btn-edit-subcategory').click(function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var url = '{{ route("expense-subcategories.update", ":id") }}'.replace(':id', id);

            Swal.fire({
                title: 'Editar Subcategoría',
                input: 'text',
                inputValue: name,
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                target: document.getElementById('categoryModal'),
                inputValidator: (value) => {
                    if (!value) {
                        return 'El nombre es obligatorio'
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        method: 'PUT',
                        data: {
                            _token: '{{ csrf_token() }}',
                            name: result.value
                        },
                        success: function(res) {
                            if (res.status) {
                                ToastMessage.fire({ text: 'Subcategoría actualizada' }).then(() => location.reload());
                            }
                        }
                    });
                }
            });
        });

        $('#createModal').on('show.bs.modal', function() {
            if ($('#payment-rows-create').is(':empty')) {
                addPaymentRowCreate();
            }

            if (!tsDescriptionCreate) {
                tsDescriptionCreate = new TomSelect('#createDescription', {
                    create: true,
                    persist: false,
                });
            }
        });

        $('#storeForm').submit(function(e) {
            e.preventDefault();

            $.ajax({
                url: '{{ route('expenses.store') }}',
                method: 'POST',
                data: $(this).serialize(),
                success: function(data) {
                    if (data.status) {
                        $('#createModal').modal('hide');
                        $('#storeForm')[0].reset();
                        $('#payment-rows-create').empty();
                        resetFinanceSelection();

                        ToastMessage.fire({
                                text: 'Gasto registrado correctamente'
                            })
                            .then(() => location.reload());
                    } else {
                        ToastError.fire({
                            text: data.error ? data.error : 'Ocurrió un error'
                        });
                    }
                },
                error: function(err) {
                    ToastError.fire({
                        text: 'Ocurrió un error al guardar'
                    });
                }
            });

        });

        function addPaymentRowEdit(methodId = '', amount = '') {
            var rowCount = $('#payment-rows-edit .payment-row').length;
            var html = `
			<div class="payment-row mb-2 border-bottom pb-2">
				<div class="row g-2 align-items-center">
					<div class="col-7">
						<select class="form-select" name="payments[${rowCount}][method_id]" required>
							<option value="">Seleccionar</option>
							${paymentMethodsHtml}
						</select>
					</div>
					<div class="col-4">
						<div class="input-group input-group-flat">
							<span class="input-group-text ps-2 pe-0">S/</span>
							<input type="number" step="0.01" class="form-control ps-1 txt-payment-amount-edit" name="payments[${rowCount}][amount]" value="${amount}" placeholder="0.00" required>
						</div>
					</div>
					<div class="col-1 text-end">
						<button type="button" class="btn btn-ghost-danger btn-icon btn-sm btn-remove-payment-edit"><i class="ti ti-trash fs-3"></i></button>
					</div>
				</div>
			</div>
		`;
            var $row = $(html);
            if (methodId) $row.find('select').val(methodId);
            $('#payment-rows-edit').append($row);
            calculateTotalEdit();
        }

        function calculateTotalEdit() {
            var total = 0;
            $('.txt-payment-amount-edit').each(function() {
                total += parseFloat($(this).val()) || 0;
            });
            $('#total-amount-edit').text('S/' + total.toFixed(2));
        }

        $(document).on('click', '#btn-add-payment-edit', function() {
            addPaymentRowEdit();
        });

        $(document).on('click', '.btn-remove-payment-edit', function() {
            $(this).closest('.payment-row').remove();
            calculateTotalEdit();
        });

        $(document).on('input', '.txt-payment-amount-edit', function() {
            calculateTotalEdit();
        });

        $(document).on('click', '.btn-edit', function() {

            var id = $(this).data('id');
            $('#payment-rows-edit').empty();

            $.ajax({
                url: '{{ route('expenses.index') }}' + '/' + id + '/edit/',
                method: 'GET',
                success: function(data) {
                    if (!tsDescriptionEdit) {
                        tsDescriptionEdit = new TomSelect('#editDescription', {
                            create: true,
                            persist: false,
                        });
                    }
                    tsDescriptionEdit.setValue(data.description);
                    $('#editId').val(data.id);
                    $('#editRealDate').val(data.real_date || '');
                    $('#editReceiptNumber').val(data.receipt_number || '');
                    $('#editOperationNumber').val(data.operation_number || '');

                    if (data.expense_category_id) {
                        $('#editCategory').val(data.expense_category_id);
                        updateSubcategories(data.expense_category_id, 'editSubcategory', data.expense_subcategory_id);
                    } else {
                        $('#editCategory').val('');
                        updateSubcategories('', 'editSubcategory');
                    }

                    // Initialize with all payments in the group
                    data.payments.forEach(function(p) {
                        addPaymentRowEdit(p.method_id, p.amount);
                    });

                    $('#editModal').modal('show');
                },
                error: function(err) {
                    ToastError.fire({
                        text: 'Ocurrió un error al cargar datos'
                    });
                }
            });

        });

        $('#editForm').submit(function(e) {
            e.preventDefault();

            var id = $('#editId').val();

            $.ajax({
                url: '{{ route('expenses.index') }}' + '/' + id + '',
                method: 'PATCH',
                data: $(this).serialize(),
                success: function(data) {
                    if (data.status) {
                        $('#editModal').modal('hide');
                        $('#editForm')[0].reset();

                        ToastMessage.fire({
                                text: 'Registro actualizado'
                            })
                            .then(() => location.reload());
                    } else {
                        ToastError.fire({
                            text: data.error ? data.error : 'Ocurrió un error'
                        });
                    }
                },
                error: function(err) {
                    ToastError.fire({
                        text: 'Ocurrió un error'
                    });
                }
            });

        });

        $(document).on('click', '.btn-delete', function() {

            var id = $(this).data('id');

            ToastConfirm.fire({
                text: '¿Estás seguro que deseas borrar el registro?',
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route('expenses.index') }}' + '/' + id,
                        method: 'DELETE',
                        success: function(data) {
                            ToastMessage.fire({
                                    text: 'Registro eliminado'
                                })
                                .then(() => location.reload());
                        },
                        error: function(err) {
                            ToastError.fire({
                                text: 'Ocurrió un error'
                            });
                        }
                    });
                }
            });

        });




        $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('create')) {
                $('#createModal').modal('show');
            }

            // Filtrado de subcategorías
            var allCategories = @json($categories);
            var preselectedSubcategoryId = "{{ request()->expense_subcategory_id }}";

            function updateFilterSubcategories() {
                var catId = $('#filterCategory').val();
                var subcatSelect = $('#filterSubcategory');
                subcatSelect.empty().append('<option value="">Todas</option>');

                if (catId) {
                    subcatSelect.prop('disabled', false);
                    var category = allCategories.find(c => c.id == catId);
                    if (category && category.subcategories) {
                        category.subcategories.forEach(function(sub) {
                            var selected = (sub.id == preselectedSubcategoryId) ? 'selected' : '';
                            subcatSelect.append('<option value="' + sub.id + '" ' + selected + '>' + sub.name + '</option>');
                        });
                    }
                } else {
                    subcatSelect.prop('disabled', true);
                }
            }

            $('#filterCategory').change(updateFilterSubcategories);
            
            if($('#filterCategory').val()) {
                updateFilterSubcategories();
            }
        });
    </script>
@endsection
