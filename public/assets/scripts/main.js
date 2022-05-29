$('.date-picker').datetimepicker({
    timepicker: false,
    format: 'd F Y'
})

$('.date-picker.today').datetimepicker({
    timepicker: false,
    minDate: 'today',
    format: 'Y-m-d'
})

$('.time-picker').datetimepicker({
    datepicker: false,
    timepicker: true,
    format: 'H:i'
})

$('.month-picker').datepicker({
    changeMonth: true,
    changeYear: true,
    showButtonPanel: true,
    dateFormat: 'MM yy',
    onClose: function (dateText, inst) {
        $(this).datepicker('setDate', new Date(inst.selectedYear, inst.selectedMonth, 1));
    }
})

$('.input-number').on('keypress', function(e){
    let charCode = (e.which) ? e.which : e.keyCode;
    if(charCode > 31 && (charCode < 48 || charCode > 57)){
        return false;
    }
    return true;
})

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
})

class requestData {
    post(params){
        let url = params.url
        let data = params.data

        return new Promise((resolve, reject) => {
            $.ajax({
                type: 'POST',
                url: url,
                dataType: "json",
                contentType: 'application/json',
                data: JSON.stringify(data),
                success:function(result){
                    resolve(result)
                },
                error:function(result){
                    alert('Oops! Something went wrong ..')
                }
            })
        })
    }

    get(params){
        let url = params.url

        return new Promise((resolve, reject) => {
            $.ajax({
                type: 'GET',
                url: url,
                dataType: "json",
                contentType: 'application/json',
                success:function(result){
                    resolve(result)
                },
                error:function(result){
                    alert('Oops! Something went wrong ..')
                }
            })
        })
    }
}

const ajaxRequest = new requestData()

if (location.pathname == '/dashboard') {
    chartPendapatan()
}else if(location.pathname == '/kelola-data-penjualan'){
    dataPenjualan()
}

function chartPendapatan() {
    let mychart
    $.ajax({
        type:'get',
        url:'/data-pendapatan',
        success:function(response){
            console.log(response)
            let ctx = document.getElementById("data-penjualan-chart").getContext('2d')
            mychart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                    datasets: [
                        {
                            label: 'Pendapatan',
                            data: response.pendapatan,
                            borderColor: '#4dc3ff',
                            backgroundColor: '#80d4ff'
                        }
                    ]
                },
                options: {
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    })

    
    $('.change-periode').on('click', function(){
        $('.change-periode').removeClass('active')
        $(this).addClass('active')
        let periode = $(this).data('periode')
        updateChartPendapatan(mychart, periode)
    })
}

function updateChartPendapatan(mychart, periode) {
    $.ajax({
        type:'get',
        url:'/data-pendapatan?tahun='+periode,
        success:function(response){
            $('#terjual').html(response.terjual)
            $('#totalPendapatan').html(response.totalPendapatan)
            mychart.data = {
                labels: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                datasets: [
                    {
                        label: 'Pendapatan',
                        data: response.pendapatan,
                        borderColor: '#4dc3ff',
                        backgroundColor: '#80d4ff'
                    }
                ]
            }
            mychart.update()
        }
    })
}

// $('#monthyear').datepicker({
//     changeMonth: true,
//     changeYear: true,
//     showButtonPanel: true,
//     dateFormat: 'MM yy',
//     onClose: function (dateText, inst) {
//         $(this).datepicker('setDate', new Date(inst.selectedYear, inst.selectedMonth, 1));
//     }
// })

function dataPenjualan() {
    let table = $('#table-penjualan').DataTable({
        'ajax' : '/data-penjualan',
        'columns' : [
            {'data' : 'no'},
            {'data' : 'periode'},
            {'data' : 'stok_awal'},
            {'data' : 'stok_akhir'},
            {'data' : 'terjual'},
            {'data' : 'pendapatan'},
            {
                data:null,
                render:function(data, type, row) {
                    return `<button id="editData" class="btn-table-action edit" data-toggle="modal" data-target="#modalEditData"><i class="fas fa-cog"></i></button>`
                }
            }
        ]
    })

    $('#table-penjualan tbody').on('click', '[id*=editData]', function(){
        let data = table.row($(this).parents('tr')).data()
        
        $('#editId').val(data['no'])
        $('#editMonthYear').val(data['periode'])
        $('#editStokAwal').val(data['stok_awal'])
        $('#editStokAkhir').val(data['stok_akhir'])
        $('#editTerjual').val(data['terjual'])
        $('#editPendapatan').val(data['pendapatan'])
    })
}

$('#btn-input-data').on('click', function () {
    if ($('#stokAwal').val().length == 0) {
        alert('Masukkan Stok Awal')
    } else if ($('#stokAkhir').val().length == 0) {
        alert('Masukan Stok Akhir')
    } else if ($('#terjual').val().length == 0) {
        alert('Masukan Barang Terjual')
    } else if ($('#pendapatan').val().length == 0) {
        alert('Masukan Pendapatan')
    } else {
        $('#btn-input-data').attr('disabled', 'disabled')
        $.ajax({
            type: 'post',
            url: '/input-data-penjualan',
            data: {
                monthYear: $('#monthYear').val(),
                stokAwal: $('#stokAwal').val(),
                stokAkhir: $('#stokAkhir').val(),
                terjual: $('#terjual').val(),
                pendapatan: $('#pendapatan').val()
            },
            success: function (response) {
                if (response.response == 'success') {
                    $('#monthYear').val(response.lastPeriod)
                    $('#stokAwal').val('')
                    $('#stokAkhir').val('')
                    $('#terjual').val('')
                    $('#pendapatan').val('')
                    toastr.option = {
                        "timeout": "5000"
                    }
                    toastr["success"]("Data penjualan bulan " + response.monthYear + " berhasil diinput")
                    $('#btn-input-data').removeAttr('disabled')
                    $('#modalInput').modal('toggle')
                    $('#table-penjualan').DataTable().ajax.reload()
                } else if (response.response == 'failed') {
                    toastr.option = {
                        "timeout": "5000"
                    }
                    toastr["error"]("Data penjualan bulan " + response.monthYear + " sudah terdata, <br> Gagal menginput data")
                    $('#btn-input-data').removeAttr('disabled')
                }
            }
        })
    }
})

$('#btn-edit-data').on('click', function(){
    if ($('#editStokAwal').val().length == 0) {
        alert('Masukkan Stok Awal')
    } else if ($('#editStokAkhir').val().length == 0) {
        alert('Masukan Stok Akhir')
    } else if ($('#editTerjual').val().length == 0) {
        alert('Masukan Barang Terjual')
    } else if ($('#editPendapatan').val().length == 0) {
        alert('Masukan Pendapatan')
    } else {
        $('#btn-edit-data').attr('disabled', 'disabled')
        $.ajax({
            type: 'post',
            url: '/edit-data-penjualan',
            data: {
                id: $('#editId').val(),
                stokAwal: $('#editStokAwal').val(),
                stokAkhir: $('#editStokAkhir').val(),
                terjual: $('#editTerjual').val(),
                pendapatan: $('#editPendapatan').val()
            },
            success: function (response) {
                if (response == "success") {
                    toastr.option = {
                        "timeout": "5000"
                    }
                    toastr["success"]("Data penjualan bulan " + $('#editMonthYear').val() + " berhasil di edit")
                    $('#btn-edit-data').removeAttr('disabled')
                    $('#modalEditData').modal('toggle')
                    $('#table-penjualan').DataTable().ajax.reload()
                }else{
                    toastr.option = {
                        "timeout": "5000"
                    }
                    toastr["error"]("Gagal merubah data penjualan periode " + $('#editMonthYear').val())
                }
            }
        })
    }
})

$('#btn-prediksi-data').on('click', function () {
    let wmaPeriode = $('#prediksiPeriode').val()
    $('#prediksiPeriode').attr('disabled', 'disabled')
    $('#btn-prediksi-data').attr('disabled', 'disabled')
    $('#hasil-prediksi').empty()
    $('#hasil-prediksi').append(`<div class="panel panel-headline" id="panel-prediksi-loading">
                                    <div class="loader">
                                        <div class="loader4"></div>
                                        <h5 style="margin-top: 2.5rem">Membuat prediksi</h5>
                                    </div>
                                </div>`)
    $('#hasil-prediksi').append(`<div class="panel panel-headline" id="panel-head-prediksi-terjual" style="display: none;"></div>`)
    $('#hasil-prediksi').append(`<div class="panel panel-headline" id="panel-head-prediksi-pendapatan" style="display: none;"></div>`)
    setTimeout(() => {
        wmaTerjual(wmaPeriode)
    }, 1000);
})

function wmaTerjual(wmaPeriode) {
    $.ajax({
        type: 'post',
        url: '/process-wma',
        data: {
            wmaPeriode: wmaPeriode
        },
        success: function (response) {
            $('#panel-head-prediksi-terjual').empty()
            $('#panel-head-prediksi-terjual').append(`<div class="panel-heading">
                                                            <h3 class="panel-title">Prediksi Stok Akhir</h3>
                                                        </div>
                                                        <div class="panel-body" id="panel-body-prediksi-terjual">
                                                        </div>`)

            $('#panel-body-prediksi-terjual').append(`<p>Tanggal stok habis 3 bulan terakhir :</p>
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <table class="table">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>Periode</th>
                                                                                <th>Stok Akhir</th>
                                                                                <th>WMA</th>
                                                                                <th>Error</th>
                                                                                <th>MAD</th>
                                                                                <th>MSE</th>
                                                                                <th>MAPE</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody id="data-n-terjual">
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                            <hr>`)

            $.each(response.data, function (i, v) {
                if (v.type == 'real') {
                    $('#data-n-terjual').append(`<tr>
                                            <td>${v.periode}</td>
                                            <td>${v.tgl_akhir}</td>
                                            <td>${v.wma}</td>
                                            <td>${v.error}</td>
                                            <td>${v.mad}</td>
                                            <td>${v.mse}</td>
                                            <td>${v.mape} %</td>
                                        </tr>`)
                }
            })

            $('#panel-body-prediksi-terjual').append(`<div class="row">
                                                        <div class="col-md-6 text-right">
                                                            <p>Mean Absolute Deviation (MAD) :</p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p>${response.mad}</p>
                                                        </div>
                                                    </div>`)
            $('#panel-body-prediksi-terjual').append(`<div class="row">
                                                        <div class="col-md-6 text-right">
                                                            <p>Mean Squared Error (MSE) :</p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p>${response.mse}</p>
                                                        </div>
                                                    </div>`)
            $('#panel-body-prediksi-terjual').append(`<div class="row">
                                                        <div class="col-md-6 text-right">
                                                            <p>Mean Absolute Percent Error (MAPE) :</p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p>${response.mape} %</p>
                                                        </div>
                                                    </div>
                                                    <hr>`)

            $('#panel-body-prediksi-terjual').append(`<p>Hasil Prediksi :</p>
                                                    <table class="table table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th style="width: 50%">Periode</th>
                                                                <th style="width: 50%">Prediksi Stok Akhir</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="hasil-prediksi-terjual">
                                                        </tbody>
                                                    </table>`)

            $.each(response.data, function (i, v) {
                if (v.type == 'wma') {
                    $('#hasil-prediksi-terjual').append(`<tr>
                                                                <td>${v.periode}</td>
                                                                <td>${v.tgl_akhir}</td>
                                                            </tr>`)
                }
            })

            $('#btn-prediksi-data').removeAttr('disabled')
            $('#prediksiPeriode').removeAttr('disabled')
            $('#panel-prediksi-loading').remove()
            $('#panel-head-prediksi-terjual').show()
            $('#panel-head-prediksi-pendapatan').show()
        }
    })
}

function wmaPendapatan(wmaPeriode) {
    $.ajax({
        type: 'post',
        url: '/wma-pendapatan',
        data: {
            wmaPeriode: wmaPeriode
        },
        success: function (response) {
            $('#panel-head-prediksi-pendapatan').empty()
            $('#panel-head-prediksi-pendapatan').append(`<div class="panel-heading">
                                                            <h3 class="panel-title">Prediksi Pendapatan</h3>
                                                        </div>
                                                        <div class="panel-body" id="panel-body-prediksi-pendapatan">
                                                        </div>`)

            $('#panel-body-prediksi-pendapatan').append(`<p>Pendapatan 3 bulan terakhir :</p>
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <table class="table">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>Periode</th>
                                                                                <th>Pendapatan</th>
                                                                                <th>WMA</th>
                                                                                <th>Error</th>
                                                                                <th>MAD</th>
                                                                                <th>MSE</th>
                                                                                <th>MAPE</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody id="data-n-pendapatan">
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                            <hr>`)

            $.each(response.data, function (i, v) {
                if (v.type == 'real') {
                    $('#data-n-pendapatan').append(`<tr>
                                            <td>${v.periode}</td>
                                            <td>${v.pendapatan}</td>
                                            <td>${v.wma}</td>
                                            <td>${v.error}</td>
                                            <td>${v.mad}</td>
                                            <td>${v.mse}</td>
                                            <td>${v.mape} %</td>
                                        </tr>`)
                }
            })

            $('#panel-body-prediksi-pendapatan').append(`<div class="row">
                                                        <div class="col-md-6 text-right">
                                                            <p>Mean Absolute Deviation (MAD) :</p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p>${response.mad}</p>
                                                        </div>
                                                    </div>`)
            $('#panel-body-prediksi-pendapatan').append(`<div class="row">
                                                        <div class="col-md-6 text-right">
                                                            <p>Mean Squared Error (MSE) :</p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p>${response.mse}</p>
                                                        </div>
                                                    </div>`)
            $('#panel-body-prediksi-pendapatan').append(`<div class="row">
                                                        <div class="col-md-6 text-right">
                                                            <p>Mean Absolute Percent Error (MAPE) :</p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p>${response.mape} %</p>
                                                        </div>
                                                    </div>
                                                    <hr>`)

            $('#panel-body-prediksi-pendapatan').append(`<p>Hasil Prediksi :</p>
                                                    <table class="table table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>Periode</th>
                                                                <th>Pendapatan</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="hasil-prediksi-pendapatan">
                                                        </tbody>
                                                    </table>`)

            $.each(response.data, function (i, v) {
                if (v.type == 'wma') {
                    $('#hasil-prediksi-pendapatan').append(`<tr>
                                                                <td>${v.periode}</td>
                                                                <td>${v.pendapatan}</td>
                                                            </tr>`)
                }
            })

            $('#btn-prediksi-data').removeAttr('disabled')
            $('#prediksiPeriode').removeAttr('disabled')
            $('#panel-prediksi-loading').remove()
            $('#panel-head-prediksi-terjual').show()
            $('#panel-head-prediksi-pendapatan').show()
        }
    })
}
