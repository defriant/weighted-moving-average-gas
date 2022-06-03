getTransactionDetail()

function getTransactionDetail() {
    ajaxRequest.post({
        "url": "/transaksi/detail",
        "data": {
            "tanggal": $('#transaksi-tanggal').val()
        }
    }).then(res => {
        if (res.exist) {
            $('#transaksi-terjual').val(res.terjual)
            $('#transaksi-terjual').attr('disabled', true)

            $('#transaksi-footer').html(`<span class="text-note red">Data penjualan ${$('#transaksi-tanggal').val()} sudah di input</span>`)
        } else {
            $('#transaksi-terjual').val('')
            $('#transaksi-terjual').removeAttr('disabled')
            $('#transaksi-footer').html(`<button type="button" class="btn btn-primary" id="submit-transaksi">Submit</button>`)
            submitTransaksi()
        }

        let tableHtml
        if (res.data.length > 0) {
            dataTransaksi = ``
            res.data.forEach(v => {
                dataTransaksi += `<tr>
                                        <td>${v.tanggal}</td>
                                        <td>${v.terjual}</td>
                                        <td><button class="btn-table-action edit" data-toggle="modal" data-target="#modalEditData"
                                                data-id="${v.id}"
                                                data-tanggal="${v.tanggal}"
                                                data-terjual="${v.terjual}">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                        </td>
                                    </tr>`
            })

            tableHtml = `<table class="table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Terjual</th>
                                    <th style="width: 15%"></th>
                                </tr>
                            </thead>
                            <tbody id="data-transaksi">
                                ${dataTransaksi}
                            </tbody>
                        </table>`
        } else {
            tableHtml = `<div class="loader">
                            <i class="fas fa-ban" style="font-size: 5rem; opacity: .5"></i>
                            <h5 style="margin-top: 2.5rem; opacity: .75">Belum ada transaksi</h5>
                        </div>`
        }

        $('#panel-transaksi').html(`<div class="panel-heading">
                                        <h3 class="panel-title" id="transaksi-title">Transaksi Bulan ${res.periode}</h3>
                                    </div>
                                    <div class="panel-body">
                                        ${tableHtml}
                                    </div>`)
        btnUpdateListener()
    })
}

$('#transaksi-tanggal').on('change', () => {
    getTransactionDetail()
})

function submitTransaksi() {
    $('#submit-transaksi').on('click', function(){
        let params = {
            "tanggal": $('#transaksi-tanggal').val(),
            "terjual": parseInt(($('#transaksi-terjual').val().length > 0) ? $('#transaksi-terjual').val() : 0)
        }

        let valid = true
        Object.keys(params).forEach(key => {
            if (key != "tanggal" && params[key] == 0) {
                alert(`Masukkan ${key}`)
                valid = false
                return false
            }

            if (key == "tanggal" && params[key].length == 0) {
                alert(`Masukkan ${key}`)
                valid = false
                return false
            }
        })

        if (valid) {
            $(this).attr('disabled', true)
            ajaxRequest.post({
                "url": "/transaksi/input",
                "data": params
            }).then(res => {
                toastr.option = {
                    "timeout": "5000"
                }
                toastr["success"](res.message)
                getTransactionDetail()
            })
        }
    })
}

function btnUpdateListener() {
    $('.btn-table-action.edit').unbind('click')
    $('.btn-table-action.edit').on('click', function(){
        $('#update-transaksi-id').val($(this).data('id'))
        $('#update-transaksi-tanggal').val($(this).data('tanggal'))
        $('#update-transaksi-terjual').val($(this).data('terjual'))
    })
}

$('#btn-edit-data').on('click', function(){
    if ($('#update-transaksi-terjual').val().length == 0) {
        alert('Masukkan jumlah terjual')
    } else {
        $('#btn-edit-data').attr('disabled', true)
        ajaxRequest.post({
            "url": "/transaksi/update",
            "data": {
                "id": $('#update-transaksi-id').val(),
                "tanggal": $('#update-transaksi-tanggal').val(),
                "terjual": $('#update-transaksi-terjual').val(),
            }
        }).then(res => {
            getTransactionDetail()
            $('#modalEditData').modal('hide')
            $('#btn-edit-data').removeAttr('disabled')
            toastr.option = {
                "timeout": "5000"
            }
            toastr["success"](res.message)
        })
    }
})