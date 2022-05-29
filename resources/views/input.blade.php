@extends('layouts.master')
@section('content')
<div class="row">
    <div class="col-md-12">
        <!-- RECENT PURCHASES -->
        <div class="panel panel-headline">
            <div class="panel-heading">
                <h3 class="panel-title">Data Penjualan</h3>
                <div class="right">
                    <button type="button" data-toggle="modal" data-target="#modalInput"><i class="far fa-plus"></i>&nbsp; Input Data Penjualan</button>
                </div>
            </div>
            <div class="panel-body">
                <table class="table" id="table-penjualan">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Periode</th>
                            <th>Stok Awal</th>
                            <th>Stok Akhir</th>
                            <th>Terjual</th>
                            <th>Pendapatan</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="loading">1</span></td>
                            <td><span class="loading">January 2020</span></td>
                            <td><span class="loading">977</span></td>
                            <td><span class="loading">292</span></td>
                            <td><span class="loading">685</span></td>
                            <td><span class="loading">54229000</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><span class="loading">1</span></td>
                            <td><span class="loading">January 2020</span></td>
                            <td><span class="loading">977</span></td>
                            <td><span class="loading">292</span></td>
                            <td><span class="loading">685</span></td>
                            <td><span class="loading">54229000</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- END RECENT PURCHASES -->
    </div>
</div>

<div class="modal fade" id="modalInput" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Input Data Penjualan</h4>
            </div>
            <div class="modal-body">
                <p>Periode</p>
                <input type="text" id="monthYear" class="form-control" style="background: transparent;"
                    value="{{ $lastPeriod }}" readonly>
                <br>
                <p>Total Stok Awal</p>
                <input type="number" id="stokAwal" class="form-control">
                <br>
                <p>Total Stok Akhir</p>
                <input type="number" id="stokAkhir" class="form-control">
                <br>
                <p>Total Barang Terjual</p>
                <input type="number" id="terjual" class="form-control">
                <br>
                <p>Total Pendapatan</p>
                <div class="input-group">
                    <span class="input-group-addon">Rp.</span>
                    <input class="form-control" id="pendapatan" type="number">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btn-input-data">Input</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditData" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Edit Data Penjualan</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId">
                <p>Periode a</p>
                <input type="text" id="editMonthYear" class="form-control" style="background: transparent;"
                    value="" readonly>
                <br>
                <p>Total Stok Awal</p>
                <input type="number" id="editStokAwal" class="form-control">
                <br>
                <p>Total Stok Akhir</p>
                <input type="number" id="editStokAkhir" class="form-control">
                <br>
                <p>Total Barang Terjual</p>
                <input type="number" id="editTerjual" class="form-control">
                <br>
                <p>Total Pendapatan</p>
                <div class="input-group">
                    <span class="input-group-addon">Rp.</span>
                    <input class="form-control" id="editPendapatan" type="number">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btn-edit-data">simpan</button>
            </div>
        </div>
    </div>
</div>
@endsection
