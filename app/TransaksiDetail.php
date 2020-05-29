<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TransaksiDetail extends Model
{
    protected $table = 'tbl_transaksi_detail';
    
    public function transaksi()
    {
        return $this->belongsTo('App\Transaksi', 'id_transaksi');
    }

    public function produk()
    {
        return $this->belongsTo('App\Produk', 'id_produk');
    }
}
