chartPendapatan()

function chartPendapatan() {
    let mychart
    $.ajax({
        type:'post',
        url:'/chart-penjualan',
        data: {
            "tahun": $('.change-periode.active').attr('data-periode')
        },
        success:function(response){
            let ctx = document.getElementById("data-penjualan-chart").getContext('2d')
            let labels = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
            let chartData = []

            labels.forEach(month => {
                let check = response.find(p => p.periode === month)
                if (check) {
                    chartData.push(check.terjual)
                } else {
                    chartData.push(null)
                }
            })

            let highest = 0
            response.forEach(data => {
                if (data.terjual > highest) {
                    highest = data.terjual
                }
            })

            highest = highest + (highest / 4)

            mychart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Terjual',
                            data: chartData,
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
                    },
                    scales: {
                        y: {
                            suggestedMin: 500,
                            suggestedMax: highest
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
        type:'post',
        url:'/chart-penjualan',
        data: {
            "tahun": periode
        },
        success:function(response){
            let terjual = 0
            response.forEach(data => {
                terjual += data.terjual
            })
            $('#terjual').html(terjual.toLocaleString('en-US'))

            let labels = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
            let chartData = []

            labels.forEach(month => {
                let check = response.find(p => p.periode === month)
                if (check) {
                    chartData.push(check.terjual)
                } else {
                    chartData.push(null)
                }
            })

            mychart.data = {
                labels: labels,
                datasets: [
                    {
                        label: 'Terjual',
                        data: chartData,
                        borderColor: '#4dc3ff',
                        backgroundColor: '#80d4ff'
                    }
                ]
            }
            mychart.update()
        }
    })
}

getChartDaily().then(res => setChartDaily(res))

function getChartDaily() {
    return new Promise(resolve => {
        ajaxRequest.post({
            "url": "/chart-penjualan/harian",
            "data": {
                "periode": $('#daily-input').val()
            }
        }).then(res => {
            resolve(res)
        })
    })
}

let dailyChart

function setChartDaily(res) {
    let labels = [];
    let chartData = [];

    $.each(res.chart_data, (i, v) => {
        labels.push(v.tanggal)
        chartData.push(v.terjual)
    })

    let dailyCanvas = document.getElementById('data-penjualan-chart-harian').getContext('2d')

    dailyChart = new Chart(dailyCanvas, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: res.periode,
                    data: chartData,
                    borderColor: '#4dc3ff',
                    backgroundColor: '#80d4ff'
                }
            ]
        },
        options: {
            plugins: {
                // legend: {
                //     display: false
                // }
            }
        }
    });
}

function updateChartDaily(res) {
    let labels = [];
    let chartData = [];

    $.each(res.chart_data, (i, v) => {
        labels.push(v.tanggal)
        chartData.push(v.terjual)
    })

    dailyChart.data = {
        labels: labels,
        datasets: [
            {
                label: res.periode,
                data: chartData,
                borderColor: '#4dc3ff',
                backgroundColor: '#80d4ff'
            }
        ]
    }
    dailyChart.update()
}

$('#btn-daily').on('click', function(){
    if ($('#daily-input').val().length == 0) {
        alert('Masukkan periode penjualan')
    } else {
        getChartDaily().then(res => updateChartDaily(res))
    }
})