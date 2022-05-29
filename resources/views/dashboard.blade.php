@extends('layouts.master')
@section('content')
<div class="panel panel-headline">
    <div class="panel-heading">
        <h3 class="panel-title">Data Penjualan</h3>
        <p class="panel-subtitle">
            Periode:
            @php
                $pActive = count($periode) - 1;
            @endphp
            @foreach ($periode as $pKey => $pVal)
                <button class="change-periode @if($pKey == $pActive) active @endif" data-periode="{{ $pVal }}">{{ $pVal }}</button>
            @endforeach
        </p>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-4">
                <div class="metric">
                    <span class="icon"><i class="fas fa-exchange"></i></span>
                    <p>
                        <span class="number" style="margin-bottom: .5rem" id="terjual">{{ number_format($terjual) }}</span>
                        <span class="title" style="font-size: 1.4rem;">Produk Terjual</span>
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric">
                    <span class="icon"><i class="fas fa-sack-dollar"></i></span>
                    <p>
                        <span class="number" style="margin-bottom: .5rem" id="totalPendapatan">{{ number_format($pendapatan) }}</span>
                        <span class="title" style="font-size: 1.4rem;">Total Pendapatan</span>
                    </p>
                </div>
            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-md-12">
                <canvas id="data-penjualan-chart" style="max-height: 400px;"></canvas>
            </div>
        </div>
        <br><br>
    </div>
</div>
@endsection