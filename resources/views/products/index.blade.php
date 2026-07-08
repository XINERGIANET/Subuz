@extends('template.app')

@section('title', 'Productos')

@section('content')
<nav class="mb-2">
	<ol class="breadcrumb">
		<li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
		<li class="breadcrumb-item active">Productos</li>
	</ol>
</nav>

<div class="card">
	<div class="card-header d-flex justify-content-between flex-column flex-sm-row gap-2">
		<div class="d-flex gap-2">
			<button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#createModal">
				<i class="ti ti-plus icon"></i> Crear producto
			</button>
			<button class="btn btn-purple" data-bs-toggle="modal" data-bs-target="#createComboModal">
				<i class="ti ti-box-multiple icon"></i> Crear paquete/combo
			</button>
			<button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createSupplyModal">
				<i class="ti ti-tools icon"></i> Crear insumo
			</button>
		</div>
		<div>
			<form>
				<div class="input-group">
					<input type="text" class="form-control" placeholder="Buscar" name="search" value="{{ request()->search }}">
					<button type="submit" class="btn btn btn-icon">
						<i class="ti ti-search icon"></i>
					</button>
				</div>
			</form>
		</div>
	</div>
	<div class="card-header p-0 border-bottom-0">
		<ul class="nav nav-tabs" data-bs-toggle="tabs">
			<li class="nav-item">
				<a href="#tabs-products" class="nav-link fw-bold active" data-bs-toggle="tab">Productos Individuales</a>
			</li>
			<li class="nav-item">
				<a href="#tabs-combos" class="nav-link fw-bold text-purple" data-bs-toggle="tab">Paquetes / Combos</a>
			</li>
			<li class="nav-item">
				<a href="#tabs-supplies" class="nav-link fw-bold text-success" data-bs-toggle="tab">Insumos</a>
			</li>
		</ul>
	</div>
	<div class="tab-content">
		<div class="tab-pane active show" id="tabs-products">
			<div class="table-responsive">
				<table class="table card-table table-vcenter">
					<thead class="table-corporate-header">
						<tr>
							<th>Nombre</th>
							<th>Precio</th>
							<th>Stock</th>
							<th>¿Reduce stock?</th>
							<th>¿Es prestable?</th>
							<th>Insumos</th>
							<th>Acción</th>
						</tr>
					</thead>
					<tbody>
						@if($products->count() > 0)
						@foreach($products as $product)
						<tr>
							<td>{{ $product->name }}</td>
							<td>S/{{ $product->price }}</td>
							<td>{{ $product->stock ?? 'N/A' }}</td>
							<td>
								@if($product->reduces_stock)
								<span class="badge bg-success text-success-fg">Si</span>
								@else
								<span class="badge bg-secondary text-secondary-fg">No</span>
								@endif
							</td>
							<td>
								@if($product->is_loanable)
								<span class="badge bg-success text-success-fg">Si</span>
								@else
								<span class="badge bg-secondary text-secondary-fg">No</span>
								@endif
							</td>
							<td class="small text-muted">
								@if($product->supplies->count() > 0)
									@foreach($product->supplies as $supply)
										<span class="badge bg-success-lt text-success me-1 mb-1">
											{{ $supply->name }}: {{ rtrim(rtrim(number_format($supply->pivot->quantity, 2, '.', ''), '0'), '.') }} {{ $supply->unit }}
										</span>
									@endforeach
								@else
									<span class="text-muted">Sin insumos</span>
								@endif
							</td>
							<td>
								<div class="d-flex gap-2">
									<div class="d-flex gap-2">
										<button class="btn btn-icon btn-history" data-id="{{ $product->id }}" data-type="product" data-bs-toggle="tooltip" title="Historial de Compras">
											<i class="ti ti-history icon text-blue"></i>
										</button>
										<button class="btn btn-icon btn-edit-corporate btn-edit" data-id="{{ $product->id }}" data-bs-toggle="tooltip" title="Editar">
											<i class="ti ti-pencil icon"></i>
										</button>
										<button class="btn btn-icon btn-delete-corporate btn-delete" data-id="{{ $product->id }}" data-bs-toggle="tooltip" title="Eliminar">
											<i class="ti ti-x icon"></i>
										</button>
									</div>
								</div>
							</td>		
						</tr>
						@endforeach
						@else
						<tr>
							<td colspan="7" align="center" class="py-4 text-muted">No se han encontrado resultados</td>
						</tr>
						@endif
					</tbody>
				</table>
			</div>
			@if($products->hasPages())
			<div class="card-footer d-flex align-items-center">
				{{ $products->withQueryString()->links() }}
			</div>
			@endif
		</div>
		
		<div class="tab-pane" id="tabs-combos">
			<div class="table-responsive">
				<table class="table card-table table-vcenter">
					<thead class="table-corporate-header" style="background: #f4ecf9 !important;">
						<tr>
							<th>Nombre del Paquete</th>
							<th>Productos incluidos</th>
							<th>Acción</th>
						</tr>
					</thead>
					<tbody>
						@if($combos->count() > 0)
						@foreach($combos as $combo)
						<tr>
							<td class="fw-bold">{{ $combo->name }}</td>
							<td class="text-muted small">
								@if(is_array($combo->combo_products))
									@foreach($combo->combo_products as $cp)
										@php $cp_model = $all_products->firstWhere('id', $cp['id']); @endphp
										@if($cp_model)
											<span class="badge bg-purple-lt me-1 mb-1">{{ $cp_model->name }}</span>
										@endif
									@endforeach
								@endif
							</td>
							<td>
								<div class="d-flex gap-2">
									<div class="d-flex gap-2">
										<button class="btn btn-icon btn-edit-corporate btn-edit-combo" data-id="{{ $combo->id }}" data-bs-toggle="tooltip" title="Editar">
											<i class="ti ti-pencil icon"></i>
										</button>
										<button class="btn btn-icon btn-delete-corporate btn-delete" data-id="{{ $combo->id }}" data-bs-toggle="tooltip" title="Eliminar">
											<i class="ti ti-x icon"></i>
										</button>
									</div>
								</div>
							</td>		
						</tr>
						@endforeach
						@else
						<tr>
							<td colspan="3" align="center" class="py-4 text-muted">No se han encontrado paquetes / combos</td>
						</tr>
						@endif
					</tbody>
				</table>
			</div>
			@if($combos->hasPages())
			<div class="card-footer d-flex align-items-center">
				{{ $combos->withQueryString()->links() }}
			</div>
			@endif
		</div>

		<div class="tab-pane" id="tabs-supplies">
			<div class="table-responsive">
				<table class="table card-table table-vcenter">
					<thead class="table-corporate-header" style="background: #eaf7ef !important;">
						<tr>
							<th>Nombre</th>
							<th>Stock</th>
							<th>Unidad</th>
							<th>Acción</th>
						</tr>
					</thead>
					<tbody>
						@if($supplies_list->count() > 0)
						@foreach($supplies_list as $supply)
						<tr>
							<td class="fw-bold">{{ $supply->name }}</td>
							<td>{{ rtrim(rtrim(number_format($supply->stock, 2, '.', ''), '0'), '.') }}</td>
							<td>{{ $supply->unit ?: '-' }}</td>
							<td>
								<div class="d-flex gap-2">
									<button class="btn btn-icon btn-history" data-id="{{ $supply->id }}" data-type="supply" data-bs-toggle="tooltip" title="Historial de Compras">
										<i class="ti ti-history icon text-blue"></i>
									</button>
									<button class="btn btn-icon btn-edit-corporate btn-edit-supply" data-id="{{ $supply->id }}" data-bs-toggle="tooltip" title="Editar">
										<i class="ti ti-pencil icon"></i>
									</button>
									<button class="btn btn-icon btn-delete-corporate btn-delete-supply" data-id="{{ $supply->id }}" data-bs-toggle="tooltip" title="Eliminar">
										<i class="ti ti-x icon"></i>
									</button>
								</div>
							</td>
						</tr>
						@endforeach
						@else
						<tr>
							<td colspan="4" align="center" class="py-4 text-muted">No se han encontrado insumos</td>
						</tr>
						@endif
					</tbody>
				</table>
			</div>
			@if($supplies_list->hasPages())
			<div class="card-footer d-flex align-items-center">
				{{ $supplies_list->withQueryString()->links() }}
			</div>
			@endif
		</div>
	</div>
</div>

<div class="modal modal-blur fade" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered" role="document">
		<div class="modal-content shadow-lg border-0">
			<form id="storeForm" method="POST">
				<div class="modal-header border-0 pb-0">
					<h5 class="modal-title d-flex align-items-center gap-2 fs-2 fw-bold text-main">
                        <i class="ti ti-circle-plus text-primary fs-1"></i>
                        Crear nuevo producto
                    </h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
                <div class="px-3">
                    <p class="text-muted small mb-0 px-1">Completa los datos para registrar un nuevo producto en el catálogo.</p>
                </div>
				<div class="modal-body pt-3">
					<div class="row">
						<div class="col-lg-12">
							<div class="mb-3">
								<label class="form-label fw-bold">Nombre del Producto <span class="text-danger">*</span></label>
								<div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-package text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control" name="name" placeholder="Ej. Filtro de aire Premium" required>
                                </div>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label fw-bold">Precio de Venta <span class="text-danger">*</span></label>
								<div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0 fw-bold">S/</span>
                                    <input type="number" step="0.01" class="form-control" name="price" placeholder="0.00" required>
                                </div>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label fw-bold">Stock Inicial (opcional)</label>
								<div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-database text-muted"></i>
                                    </span>
                                    <input type="number" class="form-control" name="stock" placeholder="0">
                                </div>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3 mt-4">
								<label class="form-check">
									<input class="form-check-input" type="checkbox" name="reduces_stock" value="1">
									<span class="form-check-label fw-bold">¿Este producto reduce stock?</span>
								</label>
								<div class="form-hint small">Si se marca, el stock se descontará en todas las ventas.</div>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3 mt-4">
								<label class="form-check">
									<input class="form-check-input" type="checkbox" name="is_loanable" value="1">
									<span class="form-check-label fw-bold">¿Es prestable?</span>
								</label>
								<div class="form-hint small">Si se marca, se podrá prestar en las ventas con precio 0.</div>
							</div>
						</div>
						<div class="col-lg-12">
							<div class="border rounded p-3 mt-2">
								<div class="d-flex justify-content-between align-items-center gap-2 mb-2">
									<div>
										<label class="form-label fw-bold mb-0">Insumos que consume</label>
										<div class="form-hint small">Ej. por cada bidón: 2 sellos y 1 tapa.</div>
									</div>
									<button type="button" class="btn btn-sm btn-outline-success btn-add-supply-row" data-target="#createProductSupplies">
										<i class="ti ti-plus icon"></i> Agregar insumo
									</button>
								</div>
								<div id="createProductSupplies" class="d-flex flex-column gap-2"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer border-0">
					<button type="button" class="btn btn-ghost-secondary px-4 fw-bold" data-bs-dismiss="modal">
                        <i class="ti ti-x icon me-1"></i> Cancelar
                    </button>
					<button type="submit" class="btn btn-brand px-4 shadow-sm fw-bold">
                        <i class="ti ti-device-floppy icon me-1"></i> Guardar Producto
                    </button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal modal-blur fade" id="createComboModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered" role="document">
		<div class="modal-content shadow-lg border-0">
			<form id="storeComboForm" method="POST">
				<div class="modal-header border-0 pb-0">
					<h5 class="modal-title d-flex align-items-center gap-2 fs-2 fw-bold text-purple">
                        <i class="ti ti-box-multiple text-purple fs-1"></i>
                        Crear nuevo paquete / combo
                    </h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
                <div class="px-3">
                    <p class="text-muted small mb-0 px-1">Un paquete agrupa varios productos. Al venderlo, se agregarán los productos individuales al carrito automáticamente.</p>
                </div>
				<div class="modal-body pt-3">
					<input type="hidden" name="is_combo" value="1">
					<div class="row">
						<div class="col-lg-12">
							<div class="mb-3">
								<label class="form-label fw-bold">Nombre del Paquete <span class="text-danger">*</span></label>
								<div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-box-multiple text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control" name="name" placeholder="Ej. Promoción Bidón + Recarga" required>
                                </div>
							</div>
						</div>
						<div class="col-lg-12">
							<div class="mb-3">
								<label class="form-label fw-bold">Productos incluidos <span class="text-danger">*</span></label>
								<select class="form-select ts-combo" name="combo_products[]" multiple required>
									@foreach($all_products as $p)
									<option value="{{ $p->id }}">{{ $p->name }}</option>
									@endforeach
								</select>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer border-0">
					<button type="button" class="btn btn-ghost-secondary px-4 fw-bold" data-bs-dismiss="modal">
                        <i class="ti ti-x icon me-1"></i> Cancelar
                    </button>
					<button type="submit" class="btn btn-purple px-4 shadow-sm fw-bold">
                        <i class="ti ti-device-floppy icon me-1"></i> Guardar Paquete
                    </button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal modal-blur fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content shadow-lg border-0">
			<form id="editForm" method="POST">
				<div class="modal-header border-0 pb-0">
					<h5 class="modal-title d-flex align-items-center gap-2 fs-2 fw-bold text-main">
                        <i class="ti ti-edit text-warning fs-1"></i>
                        Editar producto
                    </h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
                <div class="px-3">
                    <p class="text-muted small mb-0 px-1">Actualiza la información del producto seleccionado.</p>
                </div>
				<div class="modal-body pt-3">
					<div class="row">
						<div class="col-lg-12">
							<div class="mb-3">
								<label class="form-label fw-bold">Nombre del Producto</label>
								<div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-package text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control" name="name" id="editName" required>
                                </div>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label fw-bold">Precio de Venta</label>
								<div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0 fw-bold">S/</span>
                                    <input type="number" step="0.01" class="form-control" name="price" id="editPrice" required>
                                </div>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label fw-bold">Stock</label>
								<div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-database text-muted"></i>
                                    </span>
                                    <input type="number" class="form-control" name="stock" id="editStock">
                                </div>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-check">
									<input class="form-check-input" type="checkbox" name="reduces_stock" id="editIsBidon" value="1">
									<span class="form-check-label fw-bold">¿Este producto reduce stock?</span>
								</label>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-check">
									<input class="form-check-input" type="checkbox" name="is_loanable" id="editIsLoanable" value="1">
									<span class="form-check-label fw-bold">¿Es prestable?</span>
								</label>
							</div>
						</div>
						<div class="col-lg-12">
							<div class="border rounded p-3 mt-2">
								<div class="d-flex justify-content-between align-items-center gap-2 mb-2">
									<div>
										<label class="form-label fw-bold mb-0">Insumos que consume</label>
										<div class="form-hint small">Se descontarán por cada unidad vendida o prestada.</div>
									</div>
									<button type="button" class="btn btn-sm btn-outline-success btn-add-supply-row" data-target="#editProductSupplies">
										<i class="ti ti-plus icon"></i> Agregar insumo
									</button>
								</div>
								<div id="editProductSupplies" class="d-flex flex-column gap-2"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer border-0">
					<input type="hidden" id="editId">
					<button type="button" class="btn btn-ghost-secondary px-4 fw-bold" data-bs-dismiss="modal">
                        <i class="ti ti-x icon me-1"></i> Cancelar
                    </button>
					<button type="submit" class="btn btn-brand px-4 shadow-sm fw-bold">
                        <i class="ti ti-device-floppy icon me-1"></i> Actualizar Producto
                    </button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal modal-blur fade" id="editComboModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content shadow-lg border-0">
			<form id="editComboForm" method="POST">
				<div class="modal-header border-0 pb-0">
					<h5 class="modal-title d-flex align-items-center gap-2 fs-2 fw-bold text-purple">
                        <i class="ti ti-edit text-purple fs-1"></i>
                        Editar paquete / combo
                    </h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
                <div class="px-3">
                    <p class="text-muted small mb-0 px-1">Actualiza la información del paquete seleccionado.</p>
                </div>
				<div class="modal-body pt-3">
					<input type="hidden" name="is_combo" value="1">
					<div class="row">
						<div class="col-lg-12">
							<div class="mb-3">
								<label class="form-label fw-bold">Nombre del Paquete <span class="text-danger">*</span></label>
								<div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-box-multiple text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control" name="name" id="editComboName" required>
                                </div>
							</div>
						</div>
						<div class="col-lg-12">
							<div class="mb-3">
								<label class="form-label fw-bold">Productos incluidos <span class="text-danger">*</span></label>
								<select class="form-select ts-combo-edit" name="combo_products[]" id="editComboProducts" multiple required>
									@foreach($all_products as $p)
									<option value="{{ $p->id }}">{{ $p->name }}</option>
									@endforeach
								</select>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer border-0">
					<input type="hidden" id="editComboId">
					<button type="button" class="btn btn-ghost-secondary px-4 fw-bold" data-bs-dismiss="modal">
                        <i class="ti ti-x icon me-1"></i> Cancelar
                    </button>
					<button type="submit" class="btn btn-purple px-4 shadow-sm fw-bold">
                        <i class="ti ti-device-floppy icon me-1"></i> Actualizar Paquete
                    </button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal modal-blur fade" id="createSupplyModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content shadow-lg border-0">
			<form id="storeSupplyForm" method="POST">
				<div class="modal-header border-0 pb-0">
					<h5 class="modal-title d-flex align-items-center gap-2 fs-2 fw-bold text-success">
                        <i class="ti ti-tools text-success fs-1"></i>
                        Crear nuevo insumo
                    </h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
                <div class="px-3">
                    <p class="text-muted small mb-0 px-1">Registra consumibles como sellos, tapas, etiquetas o bolsas.</p>
                </div>
				<div class="modal-body pt-3">
					<div class="mb-3">
						<label class="form-label fw-bold">Nombre <span class="text-danger">*</span></label>
						<input type="text" class="form-control" name="name" placeholder="Ej. Sello de seguridad" required>
					</div>
					<div class="row">
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label fw-bold">Stock <span class="text-danger">*</span></label>
								<input type="number" step="0.01" class="form-control" name="stock" value="0" required>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label fw-bold">Unidad</label>
								<input type="text" class="form-control" name="unit" placeholder="Ej. unid, kg, rollo">
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer border-0">
					<button type="button" class="btn btn-ghost-secondary px-4 fw-bold" data-bs-dismiss="modal">
                        <i class="ti ti-x icon me-1"></i> Cancelar
                    </button>
					<button type="submit" class="btn btn-success px-4 shadow-sm fw-bold">
                        <i class="ti ti-device-floppy icon me-1"></i> Guardar Insumo
                    </button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal modal-blur fade" id="editSupplyModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content shadow-lg border-0">
			<form id="editSupplyForm" method="POST">
				<div class="modal-header border-0 pb-0">
					<h5 class="modal-title d-flex align-items-center gap-2 fs-2 fw-bold text-success">
                        <i class="ti ti-edit text-success fs-1"></i>
                        Editar insumo
                    </h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body pt-3">
					<div class="mb-3">
						<label class="form-label fw-bold">Nombre <span class="text-danger">*</span></label>
						<input type="text" class="form-control" name="name" id="editSupplyName" required>
					</div>
					<div class="row">
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label fw-bold">Stock <span class="text-danger">*</span></label>
								<input type="number" step="0.01" class="form-control" name="stock" id="editSupplyStock" required>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label fw-bold">Unidad</label>
								<input type="text" class="form-control" name="unit" id="editSupplyUnit">
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer border-0">
					<input type="hidden" id="editSupplyId">
					<button type="button" class="btn btn-ghost-secondary px-4 fw-bold" data-bs-dismiss="modal">
                        <i class="ti ti-x icon me-1"></i> Cancelar
                    </button>
					<button type="submit" class="btn btn-success px-4 shadow-sm fw-bold">
                        <i class="ti ti-device-floppy icon me-1"></i> Actualizar Insumo
                    </button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal modal-blur fade" id="historyModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered" role="document">
		<div class="modal-content shadow-lg border-0">
			<div class="modal-header border-0 pb-0">
				<h5 class="modal-title d-flex align-items-center gap-2 fs-2 fw-bold text-blue">
					<i class="ti ti-history text-blue fs-1"></i>
					Historial de Compras de Stock
				</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body pt-3">
				<div class="table-responsive">
					<table class="table card-table table-vcenter table-striped">
						<thead>
							<tr>
								<th>Fecha de Registro</th>
								<th>Fecha Real</th>
								<th>Cantidad Comprada</th>
								<th>Monto Invertido (S/)</th>
								<th>Método de Pago</th>
								<th>Comprobante / Operación</th>
							</tr>
						</thead>
						<tbody id="historyTableBody">
							<!-- Populated via AJAX -->
						</tbody>
					</table>
				</div>
			</div>
			<div class="modal-footer border-0">
				<button type="button" class="btn btn-ghost-secondary px-4 fw-bold" data-bs-dismiss="modal">
					<i class="ti ti-x icon me-1"></i> Cerrar
				</button>
			</div>
		</div>
	</div>
</div>

<div class="modal modal-blur fade" id="historyModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered" role="document">
		<div class="modal-content shadow-lg border-0">
			<div class="modal-header border-0 pb-0">
				<h5 class="modal-title d-flex align-items-center gap-2 fs-2 fw-bold text-blue">
					<i class="ti ti-history text-blue fs-1"></i>
					Historial de Compras de Stock
				</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body pt-3">
				<div class="table-responsive">
					<table class="table card-table table-vcenter table-striped">
						<thead>
							<tr>
								<th>Fecha de Registro</th>
								<th>Fecha Real</th>
								<th>Cantidad Comprada</th>
								<th>Monto Invertido (S/)</th>
								<th>M�todo de Pago</th>
								<th>Comprobante / Operaci�n</th>
							</tr>
						</thead>
						<tbody id="historyTableBody">
							<!-- Populated via AJAX -->
						</tbody>
					</table>
				</div>
			</div>
			<div class="modal-footer border-0">
				<button type="button" class="btn btn-ghost-secondary px-4 fw-bold" data-bs-dismiss="modal">
					<i class="ti ti-x icon me-1"></i> Cerrar
				</button>
			</div>
		</div>
	</div>
</div>
@endsection

@section('scripts')
<script>

	const SUPPLIES = @json($supplies_options);

	function formatNumber(value) {
		const number = parseFloat(value);
		return Number.isNaN(number) ? '' : number.toString();
	}

	function escapeHtml(value) {
		return String(value ?? '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function addSupplyRow(target, selectedId = '', quantity = '') {
		if (SUPPLIES.length === 0) {
			ToastError.fire({ text: 'Primero registra al menos un insumo.' });
			return;
		}

		let options = '<option value="">Seleccione insumo</option>';
		SUPPLIES.forEach(function(supply) {
			const selected = String(supply.id) === String(selectedId) ? 'selected' : '';
			const unit = supply.unit ? ` (${escapeHtml(supply.unit)})` : '';
			options += `<option value="${supply.id}" ${selected}>${escapeHtml(supply.name)}${unit}</option>`;
		});

		const row = `
			<div class="row g-2 align-items-end supply-row">
				<div class="col-lg-7">
					<select class="form-select" name="supply_ids[]">
						${options}
					</select>
				</div>
				<div class="col-lg-4">
					<input type="number" step="0.01" min="0" class="form-control" name="supply_quantities[]" value="${formatNumber(quantity)}" placeholder="Cantidad por unidad">
				</div>
				<div class="col-lg-1">
					<button type="button" class="btn btn-icon btn-outline-danger btn-remove-supply-row" title="Quitar">
						<i class="ti ti-x icon"></i>
					</button>
				</div>
			</div>`;

		$(target).append(row);
	}

	var tsCreate = new TomSelect('.ts-combo', {
		placeholder: 'Selecciona los productos...',
	});
	
	var tsEdit = new TomSelect('.ts-combo-edit', {
		placeholder: 'Selecciona los productos...',
	});

	$(document).on('click', '.btn-add-supply-row', function(){
		addSupplyRow($(this).data('target'));
	});

	$(document).on('click', '.btn-remove-supply-row', function(){
		$(this).closest('.supply-row').remove();
	});

	$('#storeForm, #storeComboForm').submit(function(e){
		e.preventDefault();

		$.ajax({
			url: '{{ route('products.store') }}',
			method: 'POST',
			data: $(this).serialize(),
			success: function(data){
				if(data.status){
					$('#createModal, #createComboModal').modal('hide');
					$('#storeForm')[0].reset();
					$('#storeComboForm')[0].reset();
					$('#createProductSupplies').empty();
					tsCreate.clear();
					
					ToastMessage.fire({ text: 'Registro guardado' })
					.then(() => location.reload());
				}else{
					ToastError.fire({ text: data.error ? data.error : 'Ocurrió un error' });
				}
			},
			error: function(err){
				ToastError.fire({ text: 'Ocurrió un error' });
			}
		});

	});

	$('#storeSupplyForm').submit(function(e){
		e.preventDefault();

		$.ajax({
			url: '{{ route('supplies.store') }}',
			method: 'POST',
			data: $(this).serialize(),
			success: function(data){
				if(data.status){
					$('#createSupplyModal').modal('hide');
					$('#storeSupplyForm')[0].reset();

					ToastMessage.fire({ text: 'Insumo guardado' })
					.then(() => location.reload());
				}else{
					ToastError.fire({ text: data.error ? data.error : 'Ocurrió un error' });
				}
			},
			error: function(){
				ToastError.fire({ text: 'Ocurrió un error' });
			}
		});
	});

	$(document).on('click', '.btn-edit, .btn-edit-combo', function(){

		var id = $(this).data('id');
		var isCombo = $(this).hasClass('btn-edit-combo');

		$.ajax({
			url: '{{ route('products.index') }}' + '/' + id + '/edit/',
			method: 'GET',
			success: function(data){
				if(isCombo) {
					$('#editComboName').val(data.name);
					$('#editComboId').val(data.id);
					tsEdit.clear();
					if(data.combo_products) {
						var ids = data.combo_products.map(function(item) { return item.id; });
						tsEdit.setValue(ids);
					}
					$('#editComboModal').modal('show');
				} else {
					$('#editName').val(data.name);
					$('#editPrice').val(data.price);
					$('#editStock').val(data.stock);
					$('#editIsBidon').prop('checked', data.reduces_stock == 1);
					$('#editIsLoanable').prop('checked', data.is_loanable == 1);
					$('#editId').val(data.id);
					$('#editProductSupplies').empty();
					if(data.supplies && data.supplies.length > 0) {
						data.supplies.forEach(function(supply) {
							addSupplyRow('#editProductSupplies', supply.id, supply.pivot.quantity);
						});
					}
					$('#editModal').modal('show');
				}
			},
			error: function(err){
				ToastError.fire({ text: 'Ocurrió un error' });
			}
		});

	});

	$(document).on('click', '.btn-edit-supply', function(){
		var id = $(this).data('id');

		$.ajax({
			url: '{{ route('supplies.store') }}' + '/' + id + '/edit/',
			method: 'GET',
			success: function(data){
				$('#editSupplyName').val(data.name);
				$('#editSupplyStock').val(data.stock);
				$('#editSupplyUnit').val(data.unit);
				$('#editSupplyId').val(data.id);
				$('#editSupplyModal').modal('show');
			},
			error: function(){
				ToastError.fire({ text: 'Ocurrió un error' });
			}
		});
	});

	$('#editForm, #editComboForm').submit(function(e){
		e.preventDefault();

		var id = $(this).attr('id') === 'editComboForm' ? $('#editComboId').val() : $('#editId').val();

		$.ajax({
			url: '{{ route('products.index') }}' + '/' + id + '',
			method: 'PATCH',
			data: $(this).serialize(),
			success: function(data){
				if(data.status){
					$('#editModal, #editComboModal').modal('hide');
					
					ToastMessage.fire({ text: 'Registro actualizado' })
					.then(() => location.reload());

				}else{
					ToastError.fire({ text: data.error ? data.error : 'Ocurrió un error' });
				}
			},
			error: function(err){
				ToastError.fire({ text: 'Ocurrió un error' });
			}
		});

	});

	$('#editSupplyForm').submit(function(e){
		e.preventDefault();
		var id = $('#editSupplyId').val();

		$.ajax({
			url: '{{ route('supplies.store') }}' + '/' + id,
			method: 'PATCH',
			data: $(this).serialize(),
			success: function(data){
				if(data.status){
					$('#editSupplyModal').modal('hide');

					ToastMessage.fire({ text: 'Insumo actualizado' })
					.then(() => location.reload());
				}else{
					ToastError.fire({ text: data.error ? data.error : 'Ocurrió un error' });
				}
			},
			error: function(){
				ToastError.fire({ text: 'Ocurrió un error' });
			}
		});
	});

	$(document).on('click', '.btn-delete', function(){

		var id = $(this).data('id');

		ToastConfirm.fire({
			text: '¿Estás seguro que deseas borrar el registro?',
		}).then((result) => {
			if(result.isConfirmed){
				$.ajax({
					url: '{{ route('products.index') }}' + '/' + id,
					method: 'DELETE',
					success: function(data){
						ToastMessage.fire({ text: 'Registro eliminado' })
							.then(() => location.reload());
					},
					error: function(err){
						ToastError.fire({ text: 'Ocurrió un error' });
					}
				});
			}
		});

	});

	$(document).on('click', '.btn-delete-supply', function(){

		var id = $(this).data('id');

		ToastConfirm.fire({
			text: '¿Estás seguro que deseas borrar este insumo?',
		}).then((result) => {
			if(result.isConfirmed){
				$.ajax({
					url: '{{ route('supplies.store') }}' + '/' + id,
					method: 'DELETE',
					success: function(){
						ToastMessage.fire({ text: 'Insumo eliminado' })
							.then(() => location.reload());
					},
					error: function(){
						ToastError.fire({ text: 'Ocurrió un error' });
					}
				});
			}
		});

	});




	$(document).on('click', '.btn-history', function() {
		var id = $(this).data('id');
		var type = $(this).data('type');
		var url = type === 'product' 
			? '{{ url("products") }}/' + id + '/purchase-history'
			: '{{ url("supplies") }}/' + id + '/purchase-history';

		$('#historyTableBody').html('<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>');
		$('#historyModal').modal('show');

		$.ajax({
			url: url,
			method: 'GET',
			success: function(data) {
				var tbody = $('#historyTableBody');
				tbody.empty();

				if(data.length === 0) {
					tbody.append('<tr><td colspan="6" align="center" class="py-4 text-muted">No se han registrado compras de stock para este �tem.</td></tr>');
				} else {
					data.forEach(function(item) {
						var receipt_op = (item.receipt_number !== '-' ? 'Comp: ' + item.receipt_number : '') + 
										 (item.receipt_number !== '-' && item.operation_number !== '-' ? ' / ' : '') + 
										 (item.operation_number !== '-' ? 'Op: ' + item.operation_number : '');
						if (receipt_op === '') receipt_op = '-';

						var row = `<tr>
							<td>${item.date}</td>
							<td>${item.real_date}</td>
							<td class="fw-bold text-success">+${item.quantity}</td>
							<td class="text-danger fw-bold">S/${item.amount}</td>
							<td><span class="badge bg-blue-lt">${item.payment_method}</span></td>
							<td><span class="text-muted small">${receipt_op}</span></td>
						</tr>`;
						tbody.append(row);
					});
				}
			},
			error: function() {
				$('#historyTableBody').html('<tr><td colspan="6" class="text-center py-4 text-danger">Ocurri� un error al cargar el historial.</td></tr>');
			}
		});
	});

</script>
@endsection



