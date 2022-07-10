@extends('layouts.master')
@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="panel panel-headline">
            <div class="panel-heading">
                <h3 class="panel-title">Prediksi Stok</h3>
            </div>
            <div class="panel-body">
                <p>Periode Data Penjualan Terakhir</p>
                <input type="text" class="form-control" id="lastData" style="width: 75%;" value="{{ date('F Y', strtotime($dataPenjualanTerakhir->periode)) }}" disabled>
                <br>
                <p>Prediksi Untuk Periode</p>
                <input type="text" class="form-control month-picker" id="prediksiPeriode" style="width: 50%;" value="{{ date('F Y', strtotime($prediksiPeriode)) }}" readonly>
                {{-- <select id="prediksiPeriode" class="form-control" style="width: 50%">
                    @foreach ($prediksiPeriode as $p)
                        <option value="{{ $p }}">{{ $p }}</option>
                    @endforeach
                </select> --}}
                <br>
            </div>
            <div class="panel-footer">
                <div class="row">
                    <div class="text-right"><button id="btn-prediksi-data" class="btn btn-primary">Mulai Prediksi</button></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="hasil-prediksi">
    
</div>
@endsection
