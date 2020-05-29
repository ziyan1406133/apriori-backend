<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    protected $table = 'tbl_produk';
    
    public function transaksi_detail()
    {
        return $this->hasMany('App\TransaksiDetail', 'id_produk');
    }
}
