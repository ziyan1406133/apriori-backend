<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProdukResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // return parent::toArray($request);
        
        return [
            'id' => $this->id,
            'kode' => $this->produk_kode,
            'nama' => $this->produk_name,
            'harga' => $this->produk_harga,
            'image' => $this->produk_image,
        ];
    }
}
