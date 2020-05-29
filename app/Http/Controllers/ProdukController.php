<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProdukResource;
use App\Produk;
use Illuminate\Http\Request;

class ProdukController extends Controller
{
    public function index()
    {
        $produk = Produk::orderBy('produk_name', 'asc')->get();
        return ProdukResource::collection($produk);
    }

    public function show($id)
    {
        $produk = Produk::findOrFail($id);
        return new ProdukResource($produk);
    }
}
