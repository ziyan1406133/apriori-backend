<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $table = 'tbl_transaksi';

    public function detail()
    {
        return $this->hasMany('App\TransaksiDetail', 'id_transaksi');
    }
}
