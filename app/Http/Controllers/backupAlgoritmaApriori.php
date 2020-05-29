<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class backupAlgoritmaApriori extends Controller
{
    public function backup()
    {
        $transaksi = Transaksi::orderBy('id', 'asc')->get();
        $products = Produk::get();

        $st = 0.025; //Support Treshold
        $tt = count($transaksi); //Total Transaksi
        $mt = round($tt * $st); //Minimum Transaksi
        // $mt = 10; //Minimum Transaksi fixed

        //Json array untuk itemSet
        $data = null;
        $data = json_decode($data, TRUE); 
        $k = 0; //Inisiasi Iterasi

        foreach ($products as $product) { //Iterasi ke-1
            $count = count($product->transaksi_detail);
            $support = $count / $tt;
            if($count >= $mt) //Masukkan id produk yang memenuhi treshold ke Item Set
            {
                $data[$k.'-ItemSet_pass'][] = [
                    'frequency' => $count, 
                    'support' => $support,
                    'id0' => $product->produk_name 
                ];
            }
        }
        
        do { //Iterasi Ke-2 dan Selanjutnya
            $k++;
            $prevK = $k-1;   
            // $data[$k.'-ItemSet_all'] = null; 
            $data[$k.'-ItemSet_pass'] = null; 
            for ($n=0; $n < count($data[$prevK.'-ItemSet_pass']); $n++) {
            // foreach ($data[$prevK.'-ItemSet_pass'] as $item) {
                // return $data[$prevK.'-ItemSet_pass'];
                for ($i=0; $i < count($data[$prevK.'-ItemSet_pass']); $i++) { 
                    if($k==1) {
                        if($data[$prevK.'-ItemSet_pass'][$i]['id'.$prevK] > $data[$prevK.'-ItemSet_pass'][$n]['id'.$prevK])
                        {
                            $checkArray[] = true;
                        } else {
                            $checkArray[] = false;
                        }
                        // if($i == 2) {
                        //     return $checkArray;
                        //     // return $data[$prevK.'-ItemSet_pass'][$i]['id'.$prevK];
                        // }
                    } else { //jika iterasi ke 3 dst, Cari ItemSet yang memiliki item-item yang sama kecuali item terakhir
                        if($n < $i) { //idk why this works, no time to dbl check <-- Agar tidak ada duplikat
                            for ($c=0; $c < $k-1; $c++) { 
                                if($data[$prevK.'-ItemSet_pass'][$i]['id'.$c] 
                                == $data[$prevK.'-ItemSet_pass'][$n]['id'.$c])
                                {
                                    $checkArray[] = true;
                                } else {
                                    $checkArray[] = false;
                                }
                            }
                            // return $checkArray;
                            if($data[$prevK.'-ItemSet_pass'][$i]['id'.$c] 
                            !== $data[$prevK.'-ItemSet_pass'][$n]['id'.$c])
                            {
                                $checkArray[] = true;
                            } else {
                                $checkArray[] = false;
                            }
                            // if(($k == 2) && ($n = 3)){
                            //     return $checkArray;
                            // }
                        } else {
                            $checkArray[] = false;
                        }
                    }
                    if(!in_array(false, $checkArray))
                    {
                        for ($v=0; $v < $k; $v++) { //menentukan setiap id di item set
                            $idsToCheck[] = $data[$prevK.'-ItemSet_pass'][$n]['id'.$v];
                        }
                        $idsToCheck[] = $data[$prevK.'-ItemSet_pass'][$i]['id'.$prevK];
                        // if($idsToCheck == ['2', '9', '11'])
                        // {
                        //     return $checkArray;
                        // }
                        // return $idsToCheck;

                        //cek transaksi yang memiliki produk2 pada $idsToCheck
                        $transaksi_terkait = Transaksi::where(function ($query) use ($idsToCheck){ 
                                                foreach ($idsToCheck as $id) {
                                                    // $query->whereHas('detail', function($q) use ($id){
                                                    //     $q->where('id_produk', $id);
                                                    // });
                                                    $query->whereHas('detail', function($q) use ($id){
                                                        $q->whereHas('produk', function($p) use ($id){
                                                            $p->where('produk_name', $id);
                                                        });
                                                    });
                                                }
                                            })->get();
                        // return $transaksi_terkait;
                        // if($i == 2)
                        // {
                        //     return $transaksi_terkait;
                        // }
                        $count = count($transaksi_terkait);
                        $support = $count / $tt;

                        $new_key = $this->getLatestKey($data[$k.'-ItemSet_pass']); // check last key

                        if($count >= $mt) //Masukkan id produk yang memenuhi treshold ke Item Set
                        {
                            $data[$k.'-ItemSet_pass'][$new_key] = [
                                'frequency' => $count, 
                                'support' => $support
                            ];
                            for ($number=0; $number < count($idsToCheck); $number++) { 
                                $data[$k.'-ItemSet_pass'][$new_key]['id'.$number] = $idsToCheck[$number];
                            }
                        }
                        // $data[$k.'-ItemSet_all'][$new_key] = [
                        //     'frequency' => $count, 
                        //     'support' => $support
                        // ];
                        // for ($number=0; $number < count($idsToCheck); $number++) { 
                        //     $data[$k.'-ItemSet_all'][$new_key]['id'.$number] = $idsToCheck[$number];
                        // }
                        $idsToCheck = null;
                    }
                    $checkArray = null;
                }
            }
        } while ($data[$k.'-ItemSet_pass'] !== null); //Semua Iterasi Selesai

        // $data[$k.'-ItemSet_pass'] !== null;
        // $k < 2

        
        $kek = $k-1; //k dikurangi 1 karena iterasi 'terakhir' berisi null
        for ($x=0; $x < count($data[$kek.'-ItemSet_pass']); $x++) { //Membuat himpunan bagian
            $dupes = $data[$kek.'-ItemSet_pass'][$x];
            // return $data[$k.'-ItemSet_pass'];
            // return $dupes;
            $in = array_splice($dupes, 2, $k);
            // return $in;
            $minLength=1;
            $count = count($in); 
            $iterasi=$count;
            $members = pow(2,$count); 
            $effective_members = $members - 2;
            $return = array(); 
            for ($i = 0; $i < $members; $i++) { 
                $b = sprintf("%0".$count."b",$i); 
                $out = array(); 
                for ($j = 0; $j < $count; $j++) { 
                    if ($b[$j] == '1') {
                        $out[] = $in['id'.$j];
                    } 
                } 
                if ((count($out) >= $minLength) && (count($out) < $iterasi)) { 
                    $return[] = $out; 
                } 
            } 
            // return $return;
            $data[$kek.'-ItemSet_pass'][$x]['subset'] = $return;
        }
        $rules = array();
        $lastItemSet = $data[$kek.'-ItemSet_pass'];
        // return $lastItemSet;
        for ($sub=0; $sub < count($lastItemSet); $sub++) {  //Membuat association rules
            for ($set=0; $set < count($lastItemSet[$sub]['subset']); $set++) {
                for ($soob=0; $soob < count($lastItemSet[$sub]['subset']); $soob++) {
                    if ($lastItemSet[$sub]['subset'][$set] !== $lastItemSet[$sub]['subset'][$soob]) {
                        // if($set == 2) 
                        // {
                        //     return $lastItemSet[$sub]['subset'][$set];
                        // }

                        //check if there is a duplicate element 
                        foreach ($lastItemSet[$sub]['subset'][$set] as $itemSubSetA) {
                            foreach ($lastItemSet[$sub]['subset'][$soob] as $itemSubSetB) {
                                if ($itemSubSetB == $itemSubSetA) {
                                    $checkArrayForRule[] = true;
                                } else {
                                    $checkArrayForRule[] = false;
                                }
                            }
                        }

                        $new_key = $this->getLatestKey($rules);

                        if (!in_array(true, $checkArrayForRule)) {
                            $produkA = $lastItemSet[$sub]['subset'][$soob];
                            $produkB = $lastItemSet[$sub]['subset'][$set];
                            
                            $rules[$new_key] = [
                                'A' => $produkA,
                                'B' => $produkB
                            ];
                            // return $produkA['0'].", ".$produkB['0'];
                            //Cari transaksi yang memiliki produk A dan Produk B
                            $transaksi_aub  = Transaksi::where(function ($query) use ($produkA, $produkB){
                                                $query->whereHas('detail', function($q) use ($produkA){
                                                    $q->whereHas('produk', function($p) use ($produkA){
                                                        $p->where('produk_name', $produkA['0']);
                                                    });
                                                });
                                                $query->whereHas('detail', function($q) use ($produkB){
                                                    $q->whereHas('produk', function($p) use ($produkB){
                                                        $p->where('produk_name', $produkB['0']);
                                                    });
                                                });
                                            })->get();
                            $count_aub = count($transaksi_aub);
                            $support_aub = ($count_aub / $tt)*100;

                            //Cari transaksi yang memiliki produk A
                            $transaksi_a    = Transaksi::where(function ($query) use ($produkA){
                                                $query->whereHas('detail', function($q) use ($produkA){
                                                    $q->whereHas('produk', function($p) use ($produkA){
                                                        $p->where('produk_name', $produkA['0']);
                                                    });
                                                });
                                            })->get();

                            $count_a = count($transaksi_a);
                            $support_a = ($count_a / $tt)*100;
                            $confidence = ($support_aub/$support_a)*100;

                            $rules[$new_key]['support_aub'] = $support_aub;
                            $rules[$new_key]['support_a'] = $support_a;
                            $rules[$new_key]['confidence'] = $confidence;
                        }
                    }
                }
                $checkArrayForRule = null;
            }
        }
        // return $rules;
        // return $lastItemSet;
        // return $data;


        return view('StepByStep', compact(
                    'transaksi', 'products', 'data', 'st', 'mt', 'rules'
                ));

    }


    public function getLatestKey($arrayToCheck)
    {
        if($arrayToCheck !== null) {
            // return $data[$k.'-ItemSet_pass'];
            $count_keys = count($arrayToCheck);
            $new_key = $count_keys++; 
        } else {
            $new_key = 0;
        }
        return $new_key;
    }

}
