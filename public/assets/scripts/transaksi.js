getTransactionDetail()

function getTransactionDetail() {
    ajaxRequest.post({
        "url": "/transaksi/detail",
        "data": {
            "tanggal": $('#transaksi-tanggal').val()
        }
    }).then(res => {
        if (res.exist) {
            $('#transaksi-harga').val(res.harga)
            $('#transaksi-harga').attr('disabled', true)

            $('#transaksi-terjual').val(res.terjual)
            $('#transaksi-terjual').attr('disabled', true)

            $('#transaksi-footer').html(`<span class="text-note red">Data penjualan ${$('#transaksi-tanggal').val()} sudah di input</span>`)
        } else {
            $('#transaksi-harga').val('')
            $('#transaksi-terjual').val('')
            $('#transaksi-harga').removeAttr('disabled')
            $('#transaksi-terjual').removeAttr('disabled')
            $('#transaksi-footer').html(`<button type="button" class="btn btn-primary" id="submit-transaksi">Submit</button>`)
            submitTransaksi()
        }
    })
}

$('#transaksi-tanggal').on('change', () => {
    getTransactionDetail()
})

function submitTransaksi() {
    $('#submit-transaksi').on('click', function(){
        let params = {
            "tanggal": $('#transaksi-tanggal').val(),
            "harga": parseInt(($('#transaksi-harga').val().length > 0) ? $('#transaksi-harga').val() : 0),
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