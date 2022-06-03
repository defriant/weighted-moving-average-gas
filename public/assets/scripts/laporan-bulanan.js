ajaxRequest.get({
    "url": "/laporan-bulanan/get"
}).then(res => {
    let dataTransaksi = ``
    res.forEach(v => {
        dataTransaksi += `<tr>
                            <td>${v.periode}</td>
                            <td>${v.terjual}</td>
                        </tr>`
    });

    let tableHtml = `<table class="table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Terjual</th>
                            </tr>
                        </thead>
                        <tbody id="data-transaksi">
                            ${dataTransaksi}
                        </tbody>
                    </table>`

    $('#panel-laporan-transaksi').html(tableHtml)
})