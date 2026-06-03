<form class="row mb-3">

    <div class="col-md-3">
        <input type="date" name="from" class="form-control">
    </div>

    <div class="col-md-3">
        <input type="date" name="to" class="form-control">
    </div>

    <div class="col-md-3">
        <select name="branch" class="form-control">
            <option value="">-- Cơ sở --</option>
            @foreach($branches as $b)
                <option value="{{ $b }}">{{ $b }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-primary">Lọc</button>

        <a href="{{ route('maintenance.export') }}" class="btn btn-success">
            Export Excel
        </a>
    </div>

</form>