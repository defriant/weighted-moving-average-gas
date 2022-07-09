let chartReal
let chartPredict
let chartCombined
chartPendapatan()

function chartPendapatan() {
    $.ajax({
        type:'post',
        url:'/chart-penjualan',
        data: {
            "tahun": $('.change-periode.active').attr('data-periode')
        },
        success:function(response){
            $('#chart-real-wrapper').html(`<canvas id="data-penjualan-chart" style="max-height: 350px;"></canvas>`)
            $('#chart-predict-wrapper').html(`<canvas id="chart-predict" style="max-height: 350px;"></canvas>`)
            $('#chart-combined-wrapper').html(`<canvas id="chart-combined" style="max-height: 350px;"></canvas>`)
            
            const createChart = () => {
                let ctx = document.getElementById("data-penjualan-chart").getContext('2d')
                let ctxPredict = document.getElementById("chart-predict").getContext('2d')
                let ctxCombined = document.getElementById("chart-combined").getContext('2d')
                let labels = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
                let chartData = []
                let chartDataPredict = []
                let highest
    
                labels.forEach(month => {
                    let check = response.chartReal.find(p => p.periode === month)
                    check ? chartData.push(check.terjual) : chartData.push(null)
    
                    let checkPredict = response.chartPredict.find(p => p.periode === month)
                    checkPredict ? chartDataPredict.push(checkPredict.terjual) : chartDataPredict.push(null)
                })
    
                highest = 0
                response.chartReal.forEach(data => {
                    if (data.terjual > highest) {
                        highest = data.terjual
                    }
                })
    
                response.chartPredict.forEach(data => {
                    if (data.terjual > highest) {
                        highest = data.terjual
                    }
                })
    
                highest = highest + (highest / 4)
    
                chartReal = new Chart(ctx, {
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
    
                chartPredict = new Chart(ctxPredict, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Terjual',
                                data: chartDataPredict,
                                borderColor: '#ff8080',
                                backgroundColor: '#ffb3b3'
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
    
                chartCombined = new Chart(ctxCombined, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Real',
                                data: chartData,
                                borderColor: '#4dc3ff',
                                backgroundColor: '#80d4ff'
                            },
                            {
                                label: 'Predict',
                                data: chartDataPredict,
                                borderColor: '#ff8080',
                                backgroundColor: '#ffb3b3'
                            }
                        ]
                    },
                    options: {
                        scales: {
                            y: {
                                suggestedMin: 500,
                                suggestedMax: highest
                            }
                        }
                    }
                });
            }

            setTimeout(() => {
                createChart()
            }, 100);
        }
    })

    
    $('.change-periode').on('click', function(){
        $('.change-periode').removeClass('active')
        $(this).addClass('active')
        let periode = $(this).data('periode')
        updateChartPendapatan(periode)
    })
}

function updateChartPendapatan(periode) {
    $.ajax({
        type:'post',
        url:'/chart-penjualan',
        data: {
            "tahun": periode
        },
        success:function(response){
            let terjual = 0
            response.chartReal.forEach(data => {
                terjual += data.terjual
            })
            $('#terjual').html(terjual.toLocaleString('en-US'))

            let labels = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
            let chartData = []
            let chartDataPredict = []

            labels.forEach(month => {
                let check = response.chartReal.find(p => p.periode === month)
                check ? chartData.push(check.terjual) : chartData.push(null)

                let checkPredict = response.chartPredict.find(p => p.periode === month)
                checkPredict ? chartDataPredict.push(checkPredict.terjual) : chartDataPredict.push(null)
            })

            chartReal.data = {
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
            chartReal.update()

            chartPredict.data = {
                labels: labels,
                datasets: [
                    {
                        label: 'Terjual',
                        data: chartDataPredict,
                        borderColor: '#ff8080',
                        backgroundColor: '#ffb3b3'
                    }
                ]
            }
            chartPredict.update()

            chartCombined.data = {
                labels: labels,
                datasets: [
                    {
                        label: 'Real',
                        data: chartData,
                        borderColor: '#4dc3ff',
                        backgroundColor: '#80d4ff'
                    },
                    {
                        label: 'Predict',
                        data: chartDataPredict,
                        borderColor: '#ff8080',
                        backgroundColor: '#ffb3b3'
                    }
                ]
            }
            chartCombined.update()
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