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

        $chartData = [];
        foreach ($periode as $p) {
            $transaksi = Penjualan::whereYear('periode', date('Y', strtotime($p)))->whereMonth('periode', date('m', strtotime($p)))->get();
            $terjual = 0;
            foreach ($transaksi as $t) {
                $terjual += $t->terjual;
            }
            $chartData[] = [
                "periode" => $this->bulan[intval(date('m', strtotime($p))) - 1],
                "terjual" => $terjual
            ];
        }

        return response()->json($chartData);
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
        $wmaTo = date('Y-m', strtotime($request->wmaPeriode));
        $penjualan = Penjualan::orderBy('periode')->get();
        $getPeriode = [];
        $dataPenjualanTerakhir = [];

        foreach ($penjualan as $p) {
            $periodeBulan = date('Y-m', strtotime($p->periode));
            $cek = array_search($periodeBulan, $getPeriode);
            if ($cek === false) {
                $getPeriode[] = $periodeBulan;
            }
        }

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
            $n3Period = date('Y-m', strtotime('-2 months', strtotime($pVal["periode"])));
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

        return response()->json($predict);
    }

    // public function processWma2(Request $request)
    // {
    //     $dataPenjualanTerakhir = [];
    //     $transaksi = Transaksi::orderBy('periode')->get();
    //     $periodeBulan = [];

    //     foreach ($transaksi as $t) {
    //         $periode = date('Y-m', strtotime($t->periode));
    //         $cek = array_search($periode, $periodeBulan);
    //         if ($cek === false) {
    //             $periodeBulan[] = $periode;
    //         }
    //     }

    //     for ($i = 3; $i >= 1; $i--) {
    //         $dataPenjualanTerakhir[] = Transaksi::whereYear('periode', date('Y', strtotime($periodeBulan[count($periodeBulan) - $i])))
    //             ->whereMonth('periode', date('m', strtotime($periodeBulan[count($periodeBulan) - $i])))
    //             ->orderBy('periode', 'DESC')
    //             ->first();
    //     }

    //     $wmaTo = date('F Y', strtotime($request->wmaPeriode));

    //     $predict = [];
    //     foreach ($dataPenjualanTerakhir as $dpt) {
    //         $predict["data"][] = [
    //             "type" => "real",
    //             "periode" => date('F Y', strtotime($dpt->periode)),
    //             "tgl_akhir" => date('d', strtotime($dpt->periode)),
    //             "wma" => null,
    //             "error" => null,
    //             "mad" => null,
    //             "mse" => null,
    //             "mape" => null
    //         ];
    //         $predict["mad"] = 0;
    //         $predict["mse"] = 0;
    //         $predict["mape"] = 0;
    //     }

    //     foreach ($predict["data"] as $pKey => $pVal) {
    //         $n1Period = date('Y-m', strtotime('-1 months', strtotime($pVal["periode"])));
    //         $n1 = Transaksi::whereYear('periode', date('Y', strtotime($n1Period)))
    //             ->whereMonth('periode', date('m', strtotime($n1Period)))
    //             ->orderBy('periode', 'DESC')
    //             ->first();
    //         $n1 = date('d', strtotime($n1->periode));

    //         $n2Period = date('Y-m', strtotime('-2 months', strtotime($pVal["periode"])));
    //         $n2 = Transaksi::whereYear('periode', date('Y', strtotime($n2Period)))
    //             ->whereMonth('periode', date('m', strtotime($n2Period)))
    //             ->orderBy('periode', 'DESC')
    //             ->first();
    //         $n2 = date('d', strtotime($n2->periode));

    //         $n3Period = date('Y-m', strtotime('-2 months', strtotime($pVal["periode"])));
    //         $n3 = Transaksi::whereYear('periode', date('Y', strtotime($n3Period)))
    //             ->whereMonth('periode', date('m', strtotime($n3Period)))
    //             ->orderBy('periode', 'DESC')
    //             ->first();
    //         $n3 = date('d', strtotime($n3->periode));

    //         $predict["data"][$pKey]["wma"] = intval((($n1 * 3) + ($n2 * 2) + ($n3 * 1)) / 6);
    //         $predict["data"][$pKey]["error"] = $predict["data"][$pKey]["tgl_akhir"] - $predict["data"][$pKey]["wma"];
    //         $predict["data"][$pKey]["mad"] = abs($predict["data"][$pKey]["error"]);
    //         $predict["data"][$pKey]["mse"] = pow($predict["data"][$pKey]["mad"], 2);
    //         $mape = ($predict["data"][$pKey]["mad"] / $predict["data"][$pKey]["tgl_akhir"]) * 100;
    //         $predict["data"][$pKey]["mape"] = number_format((float)$mape, 2, '.', '');

    //         $predict["mad"] = $predict["data"][$pKey]["mad"] + $predict["mad"];
    //         $predict["mse"] = $predict["data"][$pKey]["mse"] + $predict["mse"];
    //         $predict["mape"] = $predict["data"][$pKey]["mape"] + $predict["mape"];
    //     }

    //     $predict["mad"] = intval($predict["mad"] / count($predict["data"]));
    //     $predict["mse"] = intval($predict["mse"] / count($predict["data"]));
    //     $predict["mape"] = number_format((float)$predict["mape"] / count($predict["data"]), 2, '.', '');

    //     $loop = 1;
    //     for ($i = 0; $i < $loop; $i++) {
    //         $periode = Transaksi::orderBy('periode', 'DESC')->first();
    //         $periode = date('F Y', strtotime('+' . $loop . ' months', strtotime($periode->periode)));
    //         if ($periode == $wmaTo) {
    //             $predict["data"][] = [
    //                 "type" => "wma",
    //                 "periode" => $periode,
    //                 "tgl_akhir" => null
    //             ];
    //             break;
    //         } else {
    //             $predict["data"][] = [
    //                 "type" => "wma",
    //                 "periode" => $periode,
    //                 "tgl_akhir" => null
    //             ];
    //             $loop = $loop + 1;
    //         }
    //     }

    //     usort($predict["data"], function ($a, $b) {
    //         return strtotime($a["periode"]) - strtotime($b["periode"]);
    //     });

    //     foreach ($predict["data"] as $pKey => $pVal) {
    //         if ($pVal["tgl_akhir"] == null) {
    //             $tgl_akhir = (($predict["data"][$pKey - 3]["tgl_akhir"] * 1) + ($predict["data"][$pKey - 2]["tgl_akhir"] * 2) + ($predict["data"][$pKey - 1]["tgl_akhir"] * 3)) / 6;
    //             $predict["data"][$pKey]["tgl_akhir"] = intval($tgl_akhir);
    //         }
    //     }

    //     foreach ($predict["data"] as $pKey => $pVal) {
    //         $predict["data"][$pKey]["periode"] = date('F Y', strtotime($predict["data"][$pKey]["periode"]));
    //         $predict["data"][$pKey]["tgl_akhir"] = date('d F', strtotime($pVal["tgl_akhir"] . ' ' . $predict["data"][$pKey]["periode"]));
    //         if ($pVal["type"] == "real") {
    //             $predict["data"][$pKey]["wma"] = number_format($pVal["wma"]);
    //             $predict["data"][$pKey]["error"] = number_format($pVal["error"]);
    //             $predict["data"][$pKey]["mad"] = number_format($pVal["mad"]);
    //             $predict["data"][$pKey]["mse"] = number_format($pVal["mse"]);
    //         }
    //     }

    //     $predict["mad"] = number_format($predict["mad"]);
    //     $predict["mse"] = number_format($predict["mse"]);

    //     return response()->json($predict);
    // }

    // public function processWma(Request $request)
    // {
    //     $dataPenjualanTerakhir = [];
    //     $transaksi = Transaksi::orderBy('periode')->get();
    //     $periodeBulan = [];

    //     foreach ($transaksi as $t) {
    //         $periode = date('Y-m', strtotime($t->periode));
    //         $cek = array_search($periode, $periodeBulan);
    //         if ($cek === false) {
    //             $periodeBulan[] = $periode;
    //         }
    //     }

    //     for ($i = 3; $i >= 1; $i--) {
    //         $getTransaction = Transaksi::whereYear('periode', date('Y', strtotime($periodeBulan[count($periodeBulan) - $i])))
    //             ->whereMonth('periode', date('m', strtotime($periodeBulan[count($periodeBulan) - $i])))
    //             ->orderBy('periode')
    //             ->get();
    //         $maxAmount = 1000;
    //         $currentAmount = 0;
    //         $is_fulfilled = false;
    //         $additionalStock = 0;
    //         $data = [];

    //         foreach ($getTransaction as $tKey => $t) {
    //             $currentAmount += $t->terjual;

    //             if ($currentAmount == $maxAmount || $currentAmount >= $maxAmount) {
    //                 if (!$is_fulfilled) {
    //                     $data = $t;
    //                     $is_fulfilled = true;
    //                     $additionalStock += $currentAmount - $maxAmount;
    //                 } else {
    //                     $additionalStock += $currentAmount - $maxAmount;
    //                 }
    //             } else if ($tKey == count($getTransaction) - 1) {
    //                 $data = $t;
    //             }
    //         }

    //         $data["additional_stock"] = $additionalStock;
    //         $dataPenjualanTerakhir[] = $data;
    //     }

    //     $wmaTo = date('F Y', strtotime($request->wmaPeriode));

    //     $predict = [];
    //     foreach ($dataPenjualanTerakhir as $dpt) {
    //         $predict["data"][] = [
    //             "type" => "real",
    //             "periode" => date('F Y', strtotime($dpt->periode)),
    //             "tgl_akhir" => date('d', strtotime($dpt->periode)),
    //             "wma" => null,
    //             "error" => null,
    //             "mad" => null,
    //             "mse" => null,
    //             "mape" => null
    //         ];
    //         $predict["mad"] = 0;
    //         $predict["mse"] = 0;
    //         $predict["mape"] = 0;
    //     }

    //     foreach ($predict["data"] as $pKey => $pVal) {
    //         // n1
    //         $n1Period = date('Y-m', strtotime('-1 months', strtotime($pVal["periode"])));
    //         $n1Transaction = Transaksi::whereYear('periode', date('Y', strtotime($n1Period)))
    //             ->whereMonth('periode', date('m', strtotime($n1Period)))
    //             ->orderBy('periode')
    //             ->get();
    //         $n1maxAmount = 1000;
    //         $n1currentAmount = 0;
    //         $n1_is_fulfilled = false;

    //         foreach ($n1Transaction as $tKey => $t) {
    //             $n1currentAmount += $t->terjual;
    //             if ($n1currentAmount == $n1maxAmount || $n1currentAmount >= $n1maxAmount) {
    //                 if (!$n1_is_fulfilled) {
    //                     $n1 = date('d', strtotime($t->periode));
    //                     $n1_is_fulfilled = true;
    //                     $additionalStock += $n1currentAmount - $n1maxAmount;
    //                 } else {
    //                     $additionalStock += $n1currentAmount - $n1maxAmount;
    //                 }
    //             } else if ($tKey == count($n1Transaction) - 1) {
    //                 $n1 = date('d', strtotime($t->periode));
    //             }
    //         }

    //         // n2
    //         $n2Period = date('Y-m', strtotime('-2 months', strtotime($pVal["periode"])));
    //         $n2Transaction = Transaksi::whereYear('periode', date('Y', strtotime($n2Period)))
    //             ->whereMonth('periode', date('m', strtotime($n2Period)))
    //             ->orderBy('periode')
    //             ->get();
    //         $n2maxAmount = 1000;
    //         $n2currentAmount = 0;
    //         $n2_is_fulfilled = false;

    //         foreach ($n2Transaction as $tKey => $t) {
    //             $n2currentAmount += $t->terjual;
    //             if ($n2currentAmount == $n2maxAmount || $n2currentAmount >= $n2maxAmount) {
    //                 if (!$n2_is_fulfilled) {
    //                     $n2 = date('d', strtotime($t->periode));
    //                     $n2_is_fulfilled = true;
    //                     $additionalStock += $n2currentAmount - $n2maxAmount;
    //                 } else {
    //                     $additionalStock += $n2currentAmount - $n2maxAmount;
    //                 }
    //             } else if ($tKey == count($n2Transaction) - 1) {
    //                 $n2 = date('d', strtotime($t->periode));
    //             }
    //         }

    //         // n3
    //         $n3Period = date('Y-m', strtotime('-2 months', strtotime($pVal["periode"])));
    //         $n3Transaction = Transaksi::whereYear('periode', date('Y', strtotime($n3Period)))
    //             ->whereMonth('periode', date('m', strtotime($n3Period)))
    //             ->orderBy('periode')
    //             ->get();
    //         $n3maxAmount = 1000;
    //         $n3currentAmount = 0;
    //         $n3_is_fulfilled = false;

    //         foreach ($n3Transaction as $tKey => $t) {
    //             $n3currentAmount += $t->terjual;
    //             if ($n3currentAmount == $n3maxAmount || $n3currentAmount >= $n3maxAmount) {
    //                 if (!$n3_is_fulfilled) {
    //                     $n3 = date('d', strtotime($t->periode));
    //                     $n3_is_fulfilled = true;
    //                     $additionalStock += $n3currentAmount - $n2maxAmount;
    //                 } else {
    //                     $additionalStock += $n3currentAmount - $n2maxAmount;
    //                 }
    //             } else if ($tKey == count($n3Transaction) - 1) {
    //                 $n3 = date('d', strtotime($t->periode));
    //             }
    //         }

    //         $predict["data"][$pKey]["wma"] = intval((($n1 * 3) + ($n2 * 2) + ($n3 * 1)) / 6);
    //         $predict["data"][$pKey]["error"] = $predict["data"][$pKey]["tgl_akhir"] - $predict["data"][$pKey]["wma"];
    //         $predict["data"][$pKey]["mad"] = abs($predict["data"][$pKey]["error"]);
    //         $predict["data"][$pKey]["mse"] = pow($predict["data"][$pKey]["mad"], 2);
    //         $mape = ($predict["data"][$pKey]["mad"] / $predict["data"][$pKey]["tgl_akhir"]) * 100;
    //         $predict["data"][$pKey]["mape"] = number_format((float)$mape, 2, '.', '');

    //         $predict["mad"] = $predict["data"][$pKey]["mad"] + $predict["mad"];
    //         $predict["mse"] = $predict["data"][$pKey]["mse"] + $predict["mse"];
    //         $predict["mape"] = $predict["data"][$pKey]["mape"] + $predict["mape"];
    //     }

    //     $predict["mad"] = intval($predict["mad"] / count($predict["data"]));
    //     $predict["mse"] = intval($predict["mse"] / count($predict["data"]));
    //     $predict["mape"] = number_format((float)$predict["mape"] / count($predict["data"]), 2, '.', '');

    //     $loop = 1;
    //     for ($i = 0; $i < $loop; $i++) {
    //         $periode = Transaksi::orderBy('periode', 'DESC')->first();
    //         $periode = date('F Y', strtotime('+' . $loop . ' months', strtotime($periode->periode)));
    //         if ($periode == $wmaTo) {
    //             $predict["data"][] = [
    //                 "type" => "wma",
    //                 "periode" => $periode,
    //                 "tgl_akhir" => null
    //             ];
    //             break;
    //         } else {
    //             $predict["data"][] = [
    //                 "type" => "wma",
    //                 "periode" => $periode,
    //                 "tgl_akhir" => null
    //             ];
    //             $loop = $loop + 1;
    //         }
    //     }

    //     usort($predict["data"], function ($a, $b) {
    //         return strtotime($a["periode"]) - strtotime($b["periode"]);
    //     });

    //     foreach ($predict["data"] as $pKey => $pVal) {
    //         if ($pVal["tgl_akhir"] == null) {
    //             $tgl_akhir = (($predict["data"][$pKey - 3]["tgl_akhir"] * 1) + ($predict["data"][$pKey - 2]["tgl_akhir"] * 2) + ($predict["data"][$pKey - 1]["tgl_akhir"] * 3)) / 6;
    //             $predict["data"][$pKey]["tgl_akhir"] = intval($tgl_akhir);
    //         }
    //     }

    //     foreach ($predict["data"] as $pKey => $pVal) {
    //         $predict["data"][$pKey]["periode"] = date('F Y', strtotime($predict["data"][$pKey]["periode"]));
    //         $predict["data"][$pKey]["tgl_akhir"] = date('d F', strtotime($pVal["tgl_akhir"] . ' ' . $predict["data"][$pKey]["periode"]));
    //         if ($pVal["type"] == "real") {
    //             $predict["data"][$pKey]["wma"] = number_format($pVal["wma"]);
    //             $predict["data"][$pKey]["error"] = number_format($pVal["error"]);
    //             $predict["data"][$pKey]["mad"] = number_format($pVal["mad"]);
    //             $predict["data"][$pKey]["mse"] = number_format($pVal["mse"]);
    //         }
    //     }

    //     $predict["mad"] = number_format($predict["mad"]);
    //     $predict["mse"] = number_format($predict["mse"]);

    //     return response()->json($predict);
    // }
}
