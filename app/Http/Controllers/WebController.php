<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class WebController extends Controller
{
    public $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    public function login_attempt(Request $request)
    {
        if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
            return redirect('/dashboard');
        } else {
            Session::flash('failed');
            return redirect()->back()->withInput($request->all());
        }
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }

    public function dashboard()
    {
        $dataPenjualan = Penjualan::orderBy('periode')->get();
        $periode = [];
        foreach ($dataPenjualan as $dp) {
            $periodeDp = date('Y', strtotime($dp->periode));
            $cek = array_search($periodeDp, $periode);
            if ($cek === false) {
                $periode[] = $periodeDp;
            }
        }

        $terjual = 0;
        $penjualan = Penjualan::whereYear('periode', end($periode))->get();
        foreach ($penjualan as $p) {
            $terjual += $p->terjual;
        }

        return view('dashboard', compact('periode', 'terjual'));
    }

    public function chart_penjualan(Request $request)
    {
        $penjualan = Penjualan::whereYear('periode', $request->tahun)->orderBy('periode')->get();
        $periode = [];
        foreach ($penjualan as $p) {
            $convertedPeriode = date('Y-m', strtotime($p->periode));
            $check = array_search($convertedPeriode, $periode);
            if ($check === false) {
                $periode[] = $convertedPeriode;
            }
        }

        $chartReal = [];
        foreach ($periode as $p) {
            $transaksi = Penjualan::whereYear('periode', date('Y', strtotime($p)))->whereMonth('periode', date('m', strtotime($p)))->get();
            $terjual = 0;
            foreach ($transaksi as $t) {
                $terjual += $t->terjual;
            }
            $chartReal[] = [
                "periode" => $this->bulan[intval(date('m', strtotime($p))) - 1],
                "terjual" => $terjual
            ];
        }

        $chartPredict = [];
        foreach ($periode as $p) {
            $wmaTo = date('F Y', strtotime($p . "-01"));
            $createWma = $this->createWma($wmaTo);
            if ($createWma["response"] == "success") {
                foreach ($createWma["data"] as $wma) {
                    if ($wma["type"] == "wma") {
                        $chartPredict[] = [
                            "periode" => $this->bulan[intval(date('m', strtotime($wma["periode"]))) - 1],
                            "terjual" => intval(str_replace(',', '', $wma["terjual"]))
                        ];
                    }
                }
            }
        }

        $response = [
            "chartReal" => $chartReal,
            "chartPredict" => $chartPredict
        ];

        return response()->json($response);
    }

    public function chart_penjualan_harian(Request $request)
    {
        $from = date('Y-m', strtotime($request->periode)) . "-" . "01";
        $from = date('Y-m-d', strtotime($from));
        $to = date('Y-m-d', strtotime('+1 month', strtotime($from)));

        $date = $this->getDatesFromRange($from, $to);
        unset($date[count($date) - 1]);
        $chartData = [];

        $exist = false;
        foreach ($date as $d) {
            $transaksi = Penjualan::where('periode', $d)->first();
            if ($transaksi) {
                $exist = true;
                $chartData[] = [
                    "tanggal" => date('d', strtotime($d)),
                    "terjual" => $transaksi->terjual
                ];
            } else {
                $chartData[] = [
                    "tanggal" => date('d', strtotime($d)),
                    "terjual" => ($exist) ? 0 : null
                ];
            }
        }

        return response()->json([
            "response" => "success",
            "periode" => $request->periode,
            "chart_data" => $chartData,
        ]);
    }

    public function transaksi_detail(Request $request)
    {
        $check = Penjualan::where('periode', date('Y-m-d', strtotime($request->tanggal)))->first();
        $penjualan = Penjualan::whereYear('periode', date('Y', strtotime($request->tanggal)))
            ->whereMonth('periode', date('m', strtotime($request->tanggal)))->orderBy('periode')
            ->get();
        $data = [];

        foreach ($penjualan as $p) {
            $data[] = [
                "id" => $p->id,
                "tanggal" => date('d-m-Y', strtotime($p->periode)),
                "terjual" => $p->terjual
            ];
        }

        if ($check) {
            $response = [
                "periode" => $this->bulan[intval(date('m', strtotime($request->tanggal))) - 1] . ' ' . date('Y', strtotime($request->tanggal)),
                "exist" => true,
                "terjual" => $check->terjual,
                "data" => $data,
                "length" => count($penjualan)
            ];
        } else {
            $response = [
                "periode" => $this->bulan[intval(date('m', strtotime($request->tanggal))) - 1] . ' ' . date('Y', strtotime($request->tanggal)),
                "exist" => false,
                "data" => $data,
                "length" => count($penjualan)
            ];
        }
        return response()->json($response);
    }

    public function transaksi_input(Request $request)
    {
        $id = $this->random('mixUppercase', 5);
        while (true) {
            $check = Penjualan::where('id', $id)->first();
            if ($check) {
                $id = $this->random('mixUppercase', 5);
            } else {
                break;
            }
        }

        Penjualan::create([
            "id" => $id,
            "periode" => date('Y-m-d', strtotime($request->tanggal)),
            "terjual" => $request->terjual,
        ]);

        return response()->json([
            "response" => "success",
            "message" => "Data penjualan $request->tanggal berhasil di input !"
        ]);
    }

    public function transaksi_update(Request $request)
    {
        Penjualan::where('id', $request->id)->update([
            "terjual" => $request->terjual
        ]);

        return response()->json([
            "response" => "success",
            "message" => "Transaksi tanggal $request->tanggal berhasil di update !"
        ]);
    }

    public function transaksi_delete($id)
    {
        $transaksi = Penjualan::find($id);
        $message = "Transaksi tanggal $transaksi->periode berhasil dihapus";
        $transaksi->delete();
        return response()->json([
            "response" => "success",
            "message" => $message
        ]);
    }

    public function laporan_bulanan()
    {
        $penjualan = Penjualan::orderBy('periode')->get();
        $periode = [];
        foreach ($penjualan as $p) {
            $convertedPeriode = date('Y-m', strtotime($p->periode));
            $check = array_search($convertedPeriode, $periode);
            if ($check === false) {
                $periode[] = $convertedPeriode;
            }
        }

        $data = [];
        foreach ($periode as $p) {
            $penjualan = Penjualan::whereYear('periode', date('Y', strtotime($p)))->whereMonth('periode', date('m', strtotime($p)))->orderBy('periode')->get();
            $terjual = 0;
            foreach ($penjualan as $pen) {
                $terjual += $pen->terjual;
            }
            $data[] = [
                "periode" => date('F Y', strtotime($p)),
                "terjual" => $terjual
            ];
        }

        return response()->json($data);
    }

    public function wma()
    {
        $dataPenjualanTerakhir = Penjualan::orderBy('periode', 'DESC')->first();
        $prediksiPeriode = date('Y-m', strtotime('+1 months', strtotime(date('Y-m', strtotime($dataPenjualanTerakhir->periode)))));

        return view('wma', compact('dataPenjualanTerakhir', 'prediksiPeriode'));
    }

    public function processWma(Request $request)
    {
        return response()->json($this->createWma($request->wmaPeriode));
    }

    public function createWma($targetWma)
    {
        $wmaTo = date('Y-m', strtotime($targetWma));
        $penjualan = Penjualan::orderBy('periode')->get();

        if (date("Y-m-d", strtotime("$wmaTo-01")) <= date("Y-m-d", strtotime($penjualan[0]["periode"]))) {
            return [
                "response" => "failed",
                "message" => "Gagal memproses prediksi karena tidak dapat menemukan 3 bulan data penjualan terakhir !"
            ];
        }

        $getPeriode = [];
        $dataPenjualanTerakhir = [];

        foreach ($penjualan as $p) {
            $periodeBulan = date('Y-m', strtotime($p->periode));
            $cek = array_search($periodeBulan, $getPeriode);
            if ($cek === false) {
                $getPeriode[] = $periodeBulan;
            }
        }

        $target_key = array_search($wmaTo, $getPeriode);
        if ($target_key) {
            if ($target_key < 3) {
                return [
                    "response" => "failed",
                    "message" => "Gagal memproses prediksi karena tidak dapat menemukan 3 bulan data penjualan terakhir !"
                ];
            }
            for ($i = 3; $i >= 1; $i--) {
                $terjual = 0;
                $penjualan = Penjualan::whereYear('periode', date('Y', strtotime($getPeriode[$target_key - $i])))
                    ->whereMonth('periode', date('m', strtotime($getPeriode[$target_key - $i])))->get();
                foreach ($penjualan as $p) {
                    $terjual += $p->terjual;
                }

                $dataPenjualanTerakhir[] = [
                    "periode" => $getPeriode[$target_key - $i],
                    "terjual" => $terjual
                ];
            }
        } else {
            for ($i = 3; $i >= 1; $i--) {
                $terjual = 0;
                $penjualan = Penjualan::whereYear('periode', date('Y', strtotime($getPeriode[count($getPeriode) - $i])))
                    ->whereMonth('periode', date('m', strtotime($getPeriode[count($getPeriode) - $i])))->get();
                foreach ($penjualan as $p) {
                    $terjual += $p->terjual;
                }

                $dataPenjualanTerakhir[] = [
                    "periode" => $getPeriode[count($getPeriode) - $i],
                    "terjual" => $terjual
                ];
            }
        }

        $predict = [];
        foreach ($dataPenjualanTerakhir as $dpt) {
            $predict["data"][] = [
                "type" => "real",
                "periode" => $dpt["periode"],
                "terjual" => $dpt["terjual"],
                "wma" => null,
                "error" => null,
                "mad" => null,
                "mse" => null,
                "mape" => null
            ];
            $predict["mad"] = 0;
            $predict["mse"] = 0;
            $predict["mape"] = 0;
        }

        foreach ($predict["data"] as $pKey => $pVal) {
            $n1 = 0;
            $n1Period = date('Y-m', strtotime('-1 months', strtotime($pVal["periode"])));
            $n1penjualan = Penjualan::whereYear('periode', date('Y', strtotime($n1Period)))
                ->whereMonth('periode', date('m', strtotime($n1Period)))->get();
            foreach ($n1penjualan as $p) {
                $n1 += $p->terjual;
            }

            $n2 = 0;
            $n2Period = date('Y-m', strtotime('-2 months', strtotime($pVal["periode"])));
            $n2penjualan = Penjualan::whereYear('periode', date('Y', strtotime($n2Period)))
                ->whereMonth('periode', date('m', strtotime($n2Period)))->get();
            foreach ($n2penjualan as $p) {
                $n2 += $p->terjual;
            }

            $n3 = 0;
            $n3Period = date('Y-m', strtotime('-3 months', strtotime($pVal["periode"])));
            $n3penjualan = Penjualan::whereYear('periode', date('Y', strtotime($n3Period)))
                ->whereMonth('periode', date('m', strtotime($n3Period)))->get();
            foreach ($n3penjualan as $p) {
                $n3 += $p->terjual;
            }

            $predict["data"][$pKey]["wma"] = intval((($n1 * 3) + ($n2 * 2) + ($n3 * 1)) / 6);
            $predict["data"][$pKey]["error"] = $predict["data"][$pKey]["terjual"] - $predict["data"][$pKey]["wma"];
            $predict["data"][$pKey]["mad"] = abs($predict["data"][$pKey]["error"]);
            $predict["data"][$pKey]["mse"] = pow($predict["data"][$pKey]["mad"], 2);
            $mape = ($predict["data"][$pKey]["mad"] / $predict["data"][$pKey]["terjual"]) * 100;
            $predict["data"][$pKey]["mape"] = number_format((float)$mape, 2, '.', '');

            $predict["mad"] = $predict["data"][$pKey]["mad"] + $predict["mad"];
            $predict["mse"] = $predict["data"][$pKey]["mse"] + $predict["mse"];
            $predict["mape"] = $predict["data"][$pKey]["mape"] + $predict["mape"];
        }

        $predict["mad"] = intval($predict["mad"] / count($predict["data"]));
        $predict["mse"] = intval($predict["mse"] / count($predict["data"]));
        $predict["mape"] = number_format((float)$predict["mape"] / count($predict["data"]), 2, '.', '');

        if ($target_key) {
            $predict["data"][] = [
                "type" => "wma",
                "periode" => $getPeriode[$target_key],
                "terjual" => null
            ];
        } else {
            $loop = 1;
            for ($i = 0; $i < $loop; $i++) {
                $periode = Penjualan::orderBy('periode', 'DESC')->first();
                $periode = date('Y-m', strtotime('+' . $loop . ' months', strtotime(date('Y-m', strtotime($periode->periode)))));
                if ($periode == $wmaTo) {
                    $predict["data"][] = [
                        "type" => "wma",
                        "periode" => $periode,
                        "terjual" => null
                    ];
                    break;
                } else {
                    $predict["data"][] = [
                        "type" => "wma",
                        "periode" => $periode,
                        "terjual" => null
                    ];
                    $loop = $loop + 1;
                }
            }
        }

        usort($predict["data"], function ($a, $b) {
            return strtotime($a["periode"]) - strtotime($b["periode"]);
        });

        foreach ($predict["data"] as $pKey => $pVal) {
            if ($pVal["terjual"] == null) {
                $terjual = (($predict["data"][$pKey - 3]["terjual"] * 1) + ($predict["data"][$pKey - 2]["terjual"] * 2) + ($predict["data"][$pKey - 1]["terjual"] * 3)) / 6;
                $predict["data"][$pKey]["terjual"] = intval($terjual);
            }
        }

        foreach ($predict["data"] as $pKey => $pVal) {
            $predict["data"][$pKey]["periode"] = date('F Y', strtotime($predict["data"][$pKey]["periode"]));
            $predict["data"][$pKey]["terjual"] = number_format($pVal["terjual"]);
            if ($pVal["type"] == "real") {
                $predict["data"][$pKey]["wma"] = number_format($pVal["wma"]);
                $predict["data"][$pKey]["error"] = number_format($pVal["error"]);
                $predict["data"][$pKey]["mad"] = number_format($pVal["mad"]);
                $predict["data"][$pKey]["mse"] = number_format($pVal["mse"]);
            }
        }

        $predict["mad"] = number_format($predict["mad"]);
        $predict["mse"] = number_format($predict["mse"]);
        $predict["response"] = "success";
        $predict["message"] = "Berhasil membuat prediksi";

        return $predict;
    }
}
