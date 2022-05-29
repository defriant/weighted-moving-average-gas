<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    use HasFactory;

    protected $table = 'data_penjualan';
    protected $fillable = [
        'id',
        'periode',
        'stok_awal',
        'stok_akhir',
        'terjual',
        'pendapatan'
    ];
}
