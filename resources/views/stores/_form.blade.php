<div class="row">

    <div class="col-md-4 mb-3">
        <label class="form-label">Mã cửa hàng</label>
        <input type="number"
               name="code"
               class="form-control"
               value="{{ old('code', $store->code ?? '') }}">
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Miền <span class="text-danger">*</span></label>

        <select name="area" class="form-select">
            <option value="">-- Chọn --</option>
            <option value="north" @selected(old('area', $store->area ?? '') == 'north')>Miền Bắc</option>
            <option value="south" @selected(old('area', $store->area ?? '') == 'south')>Miền Nam</option>
        </select>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Khu vực</label>
        <input type="text"
               name="region"
               class="form-control"
               value="{{ old('region', $store->region ?? '') }}">
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-label">Tên cửa hàng <span class="text-danger">*</span></label>
        <input type="text"
               name="name"
               class="form-control"
               value="{{ old('name', $store->name ?? '') }}">
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-label">Email cửa hàng <span class="text-danger">*</span></label>
        <input type="email"
               name="email"
               class="form-control"
               value="{{ old('email', $store->email ?? '') }}">
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">AM</label>
        <input type="text"
               name="am_name"
               class="form-control"
               value="{{ old('am_name', $store->am_name ?? '') }}">
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Email AM</label>
        <input type="email"
               name="am_email"
               class="form-control"
               value="{{ old('am_email', $store->am_email ?? '') }}">
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">OM</label>
        <input type="text"
               name="om_name"
               class="form-control"
               value="{{ old('om_name', $store->om_name ?? '') }}">
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Email OM</label>
        <input type="email"
               name="om_email"
               class="form-control"
               value="{{ old('om_email', $store->om_email ?? '') }}">
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Kỹ thuật viên</label>
        <input type="text"
               name="technician_name"
               class="form-control"
               value="{{ old('technician_name', $store->technician_name ?? '') }}">
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Email mua sắm</label>
        <input type="email"
               name="muasam_email"
               class="form-control"
               value="{{ old('muasam_email', $store->muasam_email ?? '') }}">
    </div>

</div>