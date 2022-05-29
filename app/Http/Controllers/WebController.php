<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class WebController extends Controller
{
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

        $pendapatan = Penjualan::whereYear('periode', end($periode))->sum('pendapatan');
        $terjual = Penjualan::whereYear('periode', end($periode))->sum('terjual');

        return view('dashboard', compact('periode', 'pendapatan', 'terjual'));
    }

    public function data_pendapatan(Request $request)
    {
        if ($request->tahun == null) {
            $periode = Penjualan::orderBy('periode', 'DESC')->first();
            $periode = date('Y', strtotime($periode->periode));
            $penjualan = Penjualan::whereYear('periode', $periode)->get();
            $pendapatan = [];
            foreach ($penjualan as $p) {
                $pendapatan[] = $p->pendapatan;
            }

            $totalPendapatan = Penjualan::whereYear('periode', $periode)->sum('pendapatan');
            $terjual = Penjualan::whereYear('periode', $periode)->sum('terjual');

            $response = [
                "pendapatan" => $pendapatan,
                "totalPendapatan" => number_format($totalPendapatan),
                "terjual" => number_format($terjual)
            ];

            return response()->json($response);
        } else {
            $periode = $request->tahun;
            $penjualan = Penjualan::whereYear('periode', $periode)->get();
            $pendapatan = [];
            foreach ($penjualan as $p) {
                $pendapatan[] = $p->pendapatan;
            }

            $totalPendapatan = Penjualan::whereYear('periode', $periode)->sum('pendapatan');
            $terjual = Penjualan::whereYear('periode', $periode)->sum('terjual');

            $response = [
                "pendapatan" => $pendapatan,
                "totalPendapatan" => number_format($totalPendapatan),
                "terjual" => number_format($terjual)
            ];

            return response()->json($response);
        }
    }

    public function kelola_data_penjualan()
    {
        $lastPeriod = Penjualan::orderBy('periode', 'DESC')->first();
        $lastPeriod = date('F Y', strtotime('+1 months', strtotime($lastPeriod->periode)));
        return view('input', compact('lastPeriod'));
    }

    public function data_penjualan()
    {
        $dataPenjualan = Penjualan::all();
        $data = [];
        foreach ($dataPenjualan as $dp) {
            $data[] = [
                "no" => $dp->id,
                "periode" => date('F Y', strtotime($dp->periode)),
                "stok_awal" => $dp->stok_awal,
                "stok_akhir" => $dp->stok_akhir,
                "terjual" => $dp->terjual,
                "pendapatan" => $dp->pendapatan
            ];
        }
        $response = [
            "data" => $data
        ];
        return response()->json($response);
    }

    public function input(Request $request)
    {
        $parsedMonthYear = date('Y-m-d', strtotime('01 ' . $request->monthYear));
        $cek = Penjualan::where('periode', $parsedMonthYear)->first();
        if (!$cek) {
            $count = Penjualan::all();
            $id = count($count) + 1;
            Penjualan::create([
                'id' => $id,
                'periode' => $parsedMonthYear,
                'stok_awal' => $request->stokAwal,
                'stok_akhir' => $request->stokAkhir,
                'terjual' => $request->terjual,
                'pendapatan' => $request->pendapatan
            ]);

            $lastPeriod = Penjualan::orderBy('periode', 'DESC')->first();
            $lastPeriod = date('F Y', strtotime('+1 months', strtotime($lastPeriod->periode)));

            $response = [
                'response' => 'success',
                'monthYear' => $request->monthYear,
                'lastPeriod' => $lastPeriod
            ];
            return response()->json($response);
        } else {
            $response = [
                'response' => 'failed',
                'monthYear' => $request->monthYear
            ];
            return response()->json($response);
        }
    }

    public function edit(Request $request)
    {
        $update = Penjualan::where('id', $request->id)->update([
            'stok_awal' => $request->stokAwal,
            'stok_akhir' => $request->stokAkhir,
            'terjual' => $request->terjual,
            'pendapatan' => $request->pendapatan
        ]);
        if ($update) {
            return response()->json("success");
        }
    }

    public function transaksi_detail(Request $request)
    {
        $check = Transaksi::where('periode', date('Y-m-d', strtotime($request->tanggal)))->first();
        if ($check) {
            $response = [
                "exist" => true,
                "harga" => $check->harga,
                "terjual" => $check->terjual
            ];
        } else {
            $response = [
                "exist" => false
            ];
        }
        return response()->json($response);
    }

    public function transaksi_input(Request $request)
    {
        $id = $this->random('mixUppercase', 5);
        while (true) {
            $check = Transaksi::where('id', $id)->first();
            if ($check) {
                $id = $this->random('mixUppercase', 5);
            } else {
                break;
            }
        }

        Transaksi::create([
            "id" => $id,
            "periode" => date('Y-m-d', strtotime($request->tanggal)),
            "harga" => $request->harga,
            "terjual" => $request->terjual,
            "total" => $request->harga * $request->terjual
        ]);

        return response()->json([
            "response" => "success",
            "message" => "Data penjualan $request->tanggal berhasil di input !"
        ]);
    }

    public function wma()
    {
        $dataPenjualanTerakhir = Transaksi::orderBy('periode', 'DESC')->first();
        $prediksiPeriode = [];
        for ($i = 1; $i <= 5; $i++) {
            $prediksiPeriode[] = date('F Y', strtotime('+ ' . $i . ' months', strtotime($dataPenjualanTerakhir->periode)));
        }
        return view('wma', compact('dataPenjualanTerakhir', 'prediksiPeriode'));
    }

    public function processWma(Request $request)
    {
        $dataPenjualanTerakhir = [];
        $transaksi = Transaksi::orderBy('periode')->get();
        $periodeBulan = [];

        foreach ($transaksi as $t) {
            $periode = date('Y-m', strtotime($t->periode));
            $cek = array_search($periode, $periodeBulan);
            if ($cek === false) {
                $periodeBulan[] = $periode;
            }
        }

        for ($i = 3; $i >= 1; $i--) {
            $dataPenjualanTerakhir[] = Transaksi::whereYear('periode', date('Y', strtotime($periodeBulan[count($periodeBulan) - $i])))
                ->whereMonth('periode', date('m', strtotime($periodeBulan[count($periodeBulan) - $i])))
                ->orderBy('periode', 'DESC')
                ->first();
        }

        $wmaTo = date('F Y', strtotime($request->wmaPeriode));

        $predict = [];
        foreach ($dataPenjualanTerakhir as $dpt) {
            $predict["data"][] = [
                "type" => "real",
                "periode" => date('F Y', strtotime($dpt->periode)),
                "tgl_akhir" => date('d', strtotime($dpt->periode)),
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
            $n1Period = date('Y-m', strtotime('-1 months', strtotime($pVal["periode"])));
            $n1 = Transaksi::whereYear('periode', date('Y', strtotime($n1Period)))
                ->whereMonth('periode', date('m', strtotime($n1Period)))
                ->orderBy('periode', 'DESC')
                ->first();
            $n1 = date('d', strtotime($n1->periode));

            $n2Period = date('Y-m', strtotime('-2 months', strtotime($pVal["periode"])));
            $n2 = Transaksi::whereYear('periode', date('Y', strtotime($n2Period)))
                ->whereMonth('periode', date('m', strtotime($n2Period)))
                ->orderBy('periode', 'DESC')
                ->first();
            $n2 = date('d', strtotime($n2->periode));

            $n3Period = date('Y-m', strtotime('-2 months', strtotime($pVal["periode"])));
            $n3 = Transaksi::whereYear('periode', date('Y', strtotime($n3Period)))
                ->whereMonth('periode', date('m', strtotime($n3Period)))
                ->orderBy('periode', 'DESC')
                ->first();
            $n3 = date('d', strtotime($n3->periode));

            $predict["data"][$pKey]["wma"] = intval((($n1 * 3) + ($n2 * 2) + ($n3 * 1)) / 6);
            $predict["data"][$pKey]["error"] = $predict["data"][$pKey]["tgl_akhir"] - $predict["data"][$pKey]["wma"];
            $predict["data"][$pKey]["mad"] = abs($predict["data"][$pKey]["error"]);
            $predict["data"][$pKey]["mse"] = pow($predict["data"][$pKey]["mad"], 2);
            $mape = ($predict["data"][$pKey]["mad"] / $predict["data"][$pKey]["tgl_akhir"]) * 100;
            $predict["data"][$pKey]["mape"] = number_format((float)$mape, 2, '.', '');

            $predict["mad"] = $predict["data"][$pKey]["mad"] + $predict["mad"];
            $predict["mse"] = $predict["data"][$pKey]["mse"] + $predict["mse"];
            $predict["mape"] = $predict["data"][$pKey]["mape"] + $predict["mape"];
        }

        $predict["mad"] = intval($predict["mad"] / count($predict["data"]));
        $predict["mse"] = intval($predict["mse"] / count($predict["data"]));
        $predict["mape"] = number_format((float)$predict["mape"] / count($predict["data"]), 2, '.', '');

        $loop = 1;
        for ($i = 0; $i < $loop; $i++) {
            $periode = Transaksi::orderBy('periode', 'DESC')->first();
            $periode = date('F Y', strtotime('+' . $loop . ' months', strtotime($periode->periode)));
            if ($periode == $wmaTo) {
                $predict["data"][] = [
                    "type" => "wma",
                    "periode" => $periode,
                    "tgl_akhir" => null
                ];
                break;
            } else {
                $predict["data"][] = [
                    "type" => "wma",
                    "periode" => $periode,
                    "tgl_akhir" => null
                ];
                $loop = $loop + 1;
            }
        }

        usort($predict["data"], function ($a, $b) {
            return strtotime($a["periode"]) - strtotime($b["periode"]);
        });

        foreach ($predict["data"] as $pKey => $pVal) {
            if ($pVal["tgl_akhir"] == null) {
                $tgl_akhir = (($predict["data"][$pKey - 3]["tgl_akhir"] * 1) + ($predict["data"][$pKey - 2]["tgl_akhir"] * 2) + ($predict["data"][$pKey - 1]["tgl_akhir"] * 3)) / 6;
                $predict["data"][$pKey]["tgl_akhir"] = intval($tgl_akhir);
            }
        }

        foreach ($predict["data"] as $pKey => $pVal) {
            $predict["data"][$pKey]["periode"] = date('F Y', strtotime($predict["data"][$pKey]["periode"]));
            $predict["data"][$pKey]["tgl_akhir"] = date('d F', strtotime($pVal["tgl_akhir"] . ' ' . $predict["data"][$pKey]["periode"]));
            if ($pVal["type"] == "real") {
                $predict["data"][$pKey]["wma"] = number_format($pVal["wma"]);
                $predict["data"][$pKey]["error"] = number_format($pVal["error"]);
                $predict["data"][$pKey]["mad"] = number_format($pVal["mad"]);
                $predict["data"][$pKey]["mse"] = number_format($pVal["mse"]);
            }
        }

        $predict["mad"] = number_format($predict["mad"]);
        $predict["mse"] = number_format($predict["mse"]);

        return response()->json($predict);
    }
}
