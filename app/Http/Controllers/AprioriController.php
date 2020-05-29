<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Transaksi;
use App\Produk;

class AprioriController extends Controller
{

    public function stepByStep()
    {
        $iterasi1 = $this->iterasi1();
        // return $iterasi1;

        //unpack $iterasi1
        $transaksi = $iterasi1['0'];
        $products = $iterasi1['1'];
        $data = $iterasi1['2'];
        $st = $iterasi1['3'];
        $tt = $iterasi1['4'];
        $mt = $iterasi1['5'];
        $k = $iterasi1['6'];

        $iterasi_k = $this->iterasi_k($k, $data, $tt, $mt);
        //unpack $iterasi_k
        $data = $iterasi_k['0'] ;
        $latestK = $iterasi_k['1'] - 1; //dikurangi satu karena item set 'terakhir' adalah null
        // return $data;

        $data = $this->getSubset($latestK, $data);
        // return $data;

        $rules = $this->association_rule($data, $tt, $latestK);
        // return $rules;
        
        return view('StepByStep', compact(
            'transaksi', 'products', 'data', 'st', 'mt', 'rules'
        ));

    }

    public function getRulesAndRecommendations(Request $request) //API
    {
        //Rules
        $iterasi1 = $this->iterasi1();
        // return $iterasi1;

        //unpack $iterasi1
        $itemSet = $iterasi1['2'];
        $tt = $iterasi1['4'];
        $mt = $iterasi1['5'];
        $k = $iterasi1['6'];

        $iterasi_k = $this->iterasi_k($k, $itemSet, $tt, $mt);
        //unpack $iterasi_k
        $itemSet = $iterasi_k['0'] ;
        $latestK = $iterasi_k['1'] - 1; //dikurangi satu karena item set 'terakhir' adalah null
        // return $data;

        $itemSet = $this->getSubset($latestK, $itemSet);

        $data['rules'] = $this->association_rule($itemSet, $tt, $latestK);
        // return $data['rules'];
        
        //Recommendations
        $carts = json_decode($request->getContent());
        // return $cart;
        $candidate = array();
        $nama_produk = array();
        foreach ($carts as $cart) {
            $nama_produk[] = $cart->nama;
        }
        foreach ($carts as $cart) {
            // return response()->json($cart);
            for ($int=0; $int < count($data['rules']); $int++) {
                foreach ($data['rules'][$int]['A'] as $ruleA) {
                    // if($ruleA == $cart->nama){
                    //     // return $data['rules'][$int]['A'];
                    //     $candidate[] = $data['rules'][$int];
                    // }
                    $productIntersect = array_intersect($data['rules'][$int]['A'], $nama_produk);
                    if (count($productIntersect) > 0) { //
                        $productIntersect2 = array_intersect($data['rules'][$int]['B'], $nama_produk);
                        if (count($productIntersect2) < 1) { //make sure not to recommend already bought item
                            $candidate[] = $data['rules'][$int];
                        }
                    }
                }
            }
        }

        // return "GAGAL";
        $recommendation = array();
        $trimmed_candidate = array_unique($candidate, SORT_REGULAR);
        $candidates = $trimmed_candidate; 
        // return $candidates;
        $recommendation = array();
        // return $candidates['0']['B'];
        foreach ($candidates as $candidate) {
            foreach ($candidate['B'] as $B) {
                $new_key = $this->getLatestKey($recommendation);
                $cari_produk = Produk::where('produk_name', $B)->first();
                $recommendation[$new_key] = $cari_produk;
                $recommendation[$new_key]['confidence'] = $candidate['confidence'];
                $recommendation[$new_key]['added_to_cart'] = false;
                $recommendation[$new_key]['qty'] = 1;
            }
        }               
        $unique_recommendation = array_unique($recommendation, SORT_REGULAR); //remove duplicate

        $reassigned_key_recommendations = array();
        foreach ($unique_recommendation as $unique) {
            $new_key = $this->getLatestKey($reassigned_key_recommendations);
            $reassigned_key_recommendations[$new_key] = $unique;
        }
        // return $reassigned_key_recommendations;
        // $trimmed_recommendation = array_unique($recommendation, SORT_REGULAR);
        $toDelete = array();
        for ($index=0; $index < count($reassigned_key_recommendations); $index++) { 
            for ($ind=0; $ind < count($reassigned_key_recommendations); $ind++) { 
                if ($reassigned_key_recommendations[$index] 
                > $reassigned_key_recommendations[$ind]) {
                    if($reassigned_key_recommendations[$index]['id'] == $reassigned_key_recommendations[$ind]['id'])
                    {
                        if ($reassigned_key_recommendations[$index]['confidence'] > $reassigned_key_recommendations[$ind]['confidence']) {
                            $toDelete[] = ($reassigned_key_recommendations[$ind]);
                        }
                    }
                }
            }
        }
        // return $toDelete;
        // return $recommendation;
        $trimmed_recommendation = array_diff($reassigned_key_recommendations,$toDelete); //keep the duplicate product with the best confidence

        usort($trimmed_recommendation, function($a, $b) { //Sort by conf
                return $a->confidence > $b->confidence ? -1 : 1;
            });
        
        // if(count($trimmed_recommendation) > 0) { //limit to 3

            $limit3Recommendations = array_splice($trimmed_recommendation, 0, 3);
            $data['recommendations'] = $limit3Recommendations; 

        // } else {
        //     $data['recommendations'] = $trimmed_recommendation; 
        // }


        return response()->json($data);
    }

    public function iterasi1() //iterasi pertama
    {
        
        $transaksi = Transaksi::orderBy('id', 'asc')->get();
        $products = Produk::get();

        $st = 0.024; //Support Treshold
        $tt = count($transaksi); //Total Transaksi
        $mt = round($tt * $st); //Minimum Transaksi
        // $mt = 10; //Minimum Transaksi fixed

        //Json array untuk itemSet
        $data = null;
        $data = json_decode($data, TRUE); 
        $k = 0;

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

        return array($transaksi, $products, $data, $st, $tt, $mt, $k);
        
    }

    public function iterasi_k($k, $data, $tt, $mt) //iterasi ke 2 dan seterusnya
    {
        do {
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

        return array($data, $k);
    }

    public function getSubset($latestK, $data) //menentukan himpunan bagian k-ItemSet terakhir
    {
        $k = $latestK + 1; //dikembalikan untuk mengetahui berapa item yang ada di masing2 item set iterasi terakhir
        for ($x=0; $x < count($data[$latestK.'-ItemSet_pass']); $x++) { //Membuat himpunan bagian
            $dupes = $data[$latestK.'-ItemSet_pass'][$x];
            // return $data[$k.'-ItemSet_pass'];
            // return $dupes;
            $in = array_splice($dupes, 2, $k);
            // return $in;
            $minLength=1;
            $count = count($in); 
            $iterasi=$count;
            $members = pow(2,$count); 

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
            $data[$latestK.'-ItemSet_pass'][$x]['subset'] = $return;
        }
        return $data;
    }

    public function association_rule($data, $tt, $latestK) //menentukan assocciation rule dari subset yang ada
    {
        $rules = array();
        $lastItemSet = $data[$latestK.'-ItemSet_pass'];
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
        
        return $rules;
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


    public function test()
    {
        $k = 2;
        $arr = null;
        $arr = json_decode($arr, TRUE);
        $arr['1-ItemSet'][] = [
                'id0' => '1', 
                'frequency' => '52' 
        ];
        $arr['1-ItemSet'][] = ['id0' => '2', 'frequency' => '45'];
        $arr['1-ItemSet'][] = ['id0' => '3', 'frequency' => '442'];
        $arr[$k.'-ItemSet']= ['id0' => '3', 'id1' => '4', 'id2' => '2'];

        $in = $arr[$k.'-ItemSet'];
        // return $in;
        $minLength=1;
        $iterasi=3;
        $count = count($in); 
        $members = pow(2,$count); 
        $return = array(); 
        for ($i = 1; $i < $members; $i++) { 
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
        return $return; 
        // return response()->json($arr); //HAHA!
        // return $arr[1]["id"];
        // foreach ($arr as $ar) {
        //     return $ar['id0'];
        // }
        // $slice = array_slice($arr[$k.'-ItemSet'][0], 0, 2);
        // return $slice;
    }



    //REFERENSI
    /*
        Istilah
        {
            - E = Produk
            - D = Transaksi
            - Proper Subset = Himpunan Bagian
            - I = Item Set = Himpunan dari E
            - K-Item Set = Set item yang memiliki K item
                Contoh :    1-Item Set adalah set item yang memiliki 1 item, e.g. : {a}, {b}, dst
                            2-Item Set adalah set item yang memiliki 2 item, e.g. : {a,b}, {a,c}, dst
            - Item Set Frekuen = Jumlah munculnya K-Item Set di I 
            - Frekuen Item Set = I (atau K-Item Set(?)) yang frekuensi munculnya lebih dari support treshold
                support treshold disimbolkan Ф
            - Fk = Himpunan dari Frekeun Item Set
        }

        Proses Bisnis
        {
            - Tentukan minimum support.
            - Iterasi 1 : hitung item-item dari support(transaksi yang memuat seluruh item) 
                dengan men-scan database untuk 1-itemset, setelah 1-itemset didapatkan, dari 1-itemset 
                apakah diatas minimum support, apabila telah memenuhi minimum support, 1-itemset tersebut 
                akan menjadi pola frequent tinggi.
            - Iterasi 2 : untuk mendapatkan 2-itemset, harus dilakukan kombinasi dari k-itemset sebelumnya, 
                kemudian scan database lagi untuk hitung item-item yang memuat support. 
                itemset yang memenuhi minimum support akan dipilih sebagai pola frequent tinggi dari kandidat.
            - Tetapkan nilai k-itemset dari support yang telah memenuhi minimum support dari k-itemset.
            - Lakukan proses untuk iterasi selanjutnya hingga tidak ada lagi k-itemset yang memenuhi minimum support.
        }

        Formula
        {
            - Support(A) = Jumlah Transaksi Mengandung <A> / Total Transaksi
            - Support(AUB) = Jumlah Transaksi Mengandung <A> dan <B> / Total Transaksi
            - Confidence = P(B|A) = Jumlah Transaksi Mengandung <A> dan <B> / Jumlah Transaksi Mengandung <A>
        }

        Pseudo-code
        {
            >   foreach ($products as $product) //Mulai iterasi ke-1
                {
                    $count = count($product->transaction_detail)
                    if($count >= $mt) //Masukkan id produk yang memenuhi treshold ke Item Set
                    {
                        $itemSet[] = ['id0' => 'product_id', 'frequency' => '$count'];
                    }
                }
            >   json_encode($itemSet)
            >   declare json array of $two_itemSet with null value to hold product ids and each frequency
                {
                    with structure like so :
                    twoItemSet: {
                        'id0':string (id, id1,),
                        'frequency':integer
                    }
                }
            >   foreach ($itemSet as $is) //Mulai iterasi ke-2
                {
                    for($n = 0; $n<=count($itemSet); $n++) //Menggabungkan Itemset menjadi 2-Item Set
                    {
                        if($is[$n]['id0'] !== $is['id0'])
                        {
                            $id_pertama = $is[$n]['id0'];
                            $id_kedua   = $is['id0'];

                            //Membuat 2-ItemSet yang memenuhi treshold ke itemset
                            $transaction = Transaksi::whereHas('detail', function ($query use $id_pertama) {
                                                $query->where('id_produk', $id_pertama);
                                            })->whereHas('detail', function($query use $id_kedua) {
                                                $query->where('id_produk', $id_pertama);
                                            })->get()
                            if()
                            {
                                $two_itemSet[] = ['id0' => 'product_id', 'frequency' => '$count'];
                            }
                        }
                    }
                }
            
            
            pffft
        }
    */
    
    // public function eh()
    // {
    //     $transaksi = Transaksi::orderBy('id', 'asc')->get();
    //     // $products = Produk::withCount('transaksi_detail')->orderBy('transaksi_detail_count', 'desc')->get();
    //     $products = Produk::get();

    //     $st = 0.025; //Support Treshold
    //     $tt = count($transaksi); //Total Transaksi
    //     $mt = round($tt * $st); //Minimum Transaksi
    //     // $mt = 10; //Minimum Transaksi fixed

    //     //Json array untuk itemSet
    //     $itemSet = null;
    //     $itemSet = json_decode($itemSet, TRUE);

    //     foreach ($products as $product) { //Mulai iterasi ke-1
    //         $count = count($product->transaksi_detail);
    //         $support = $count / $tt;
    //         if($count >= $mt) //Masukkan id produk yang memenuhi treshold ke Item Set
    //         {
    //             $itemSet[] = [
    //                 'id0' => $product->id, 
    //                 'frequency' => $count, 
    //                 'support' => $support
    //             ];
    //         }
    //     }

    //     $twoItemSet = null;
    //     $twoItemSet_all = null;
    //     $twoItemSet = json_decode($twoItemSet, TRUE);
    //     $twoItemSet_all = json_decode($twoItemSet_all, TRUE);
        
    //     $roof = count($itemSet) - 1;

    //     foreach ($itemSet as $is) //Mulai iterasi ke-2
    //     {
    //         for($n = 0; $n <= $roof; $n++) //Menggabungkan Itemset menjadi 2-Item Set
    //         {
    //             if($itemSet[$n]['id0'] !== $is['id0'])
    //             {
    //                 // return $itemSet[$n]['id0'];
    //                 // return $is['id0'];
    //                 $id_pertama = $is['id0'];
    //                 $id_kedua   = $itemSet[$n]['id0'];

    //                 //Membuat 2-ItemSet yang memenuhi treshold ke itemset
    //                 $transaksi_terkait = Transaksi::whereHas('detail', function ($query) use ($id_pertama) {
    //                                     $query->where('id_produk', $id_pertama);
    //                                 })->whereHas('detail', function ($query) use ($id_kedua) {
    //                                     $query->where('id_produk', $id_kedua);
    //                                 })->get();

    //                 // return $transaksi_terkait;
    //                 $count = count($transaksi_terkait);
    //                 $support = $count / $tt;
    //                 if($count >= $mt)
    //                 {
    //                     $twoItemSet[] = [
    //                         'id0' => $id_pertama, 
    //                         'id1' => $id_kedua, 
    //                         'frequency' => $count, 
    //                         'support' => $support
    //                     ];
    //                 }
    //                 $twoItemSet_all[] = [
    //                     'id0' => $id_pertama, 
    //                     'id1' => $id_kedua, 
    //                     'frequency' => $count, 
    //                     'support' => $support
    //                 ];
    //             }
    //         }
    //     }
    //     // return $twoItemSet;

    //     $threeItemSet = null;
    //     $threeItemSet_all = null;
    //     $threeItemSet = json_decode($threeItemSet, TRUE);
    //     $threeItemSet_all = json_decode($threeItemSet_all, TRUE);
    //     // return $twoItemSet;
    //     foreach ($twoItemSet as $tis) { //Iterasi Ke-3

    //         $roof = count($twoItemSet) - 1;

    //         for($n = 0; $n <= $roof; $n++) //Menggabungkan Itemset menjadi 2-Item Set
    //         {
    //             if(($twoItemSet[$n]['id0'] == $tis['id0']) 
    //                 && ($twoItemSet[$n]['id1'] !== $tis['id1']))
    //             {
    //                 // return $twoItemSet[$n];
    //                 // return $itemSet[$n]['id0'];
    //                 // return $is['id0'];
    //                 $id_pertama = $tis['id0'];
    //                 $id_kedua   = $tis['id1'];
    //                 $id_ketiga   = $twoItemSet[$n]['id1'];

    //                 //Membuat 2-ItemSet yang memenuhi treshold ke itemset
    //                 $transaksi_terkait = Transaksi::whereHas('detail', function ($query) use ($id_pertama) {
    //                                     $query->where('id_produk', $id_pertama);
    //                                 })->whereHas('detail', function ($query) use ($id_kedua) {
    //                                     $query->where('id_produk', $id_kedua);
    //                                 })->whereHas('detail', function ($query) use ($id_ketiga) {
    //                                     $query->where('id_produk', $id_ketiga);
    //                                 })->get();

    //                 // return $transaksi_terkait;
    //                 $count = count($transaksi_terkait);
    //                 $support = $count / $tt;
    //                 if($count >= $mt)
    //                 {
    //                     $threeItemSet[] = [
    //                         'id0' => $id_pertama, 
    //                         'id1' => $id_kedua, 
    //                         'id2' => $id_ketiga, 
    //                         'frequency' => $count, 
    //                         'support' => $support
    //                     ];
    //                 }
    //                 $threeItemSet_all[] = [
    //                     'id0' => $id_pertama, 
    //                     'id1' => $id_kedua, 
    //                     'id2' => $id_ketiga, 
    //                     'frequency' => $count, 
    //                     'support' => $support
    //                 ];
    //             }
    //         }
    //     }
    //     $iterasi = 3;
    //     for ($x=0; $x < count($threeItemSet); $x++) { 
    //         $dupes = $threeItemSet[$x];
    //         $in = array_splice($dupes, 0, 3);
    //         // return $in;
    //         $minLength=1;
    //         $iterasi=3;
    //         $count = count($in); 
    //         $members = pow(2,$count); 
    //         $effective_members = $members - 2;
    //         $return = array(); 
    //         for ($i = 0; $i < $members; $i++) { 
    //             $b = sprintf("%0".$count."b",$i); 
    //             $out = array(); 
    //             for ($j = 0; $j < $count; $j++) { 
    //                 if ($b[$j] == '1') {
    //                     $out[] = $in['id'.$j];
    //                 } 
    //             } 
    //             if ((count($out) >= $minLength) && (count($out) < $iterasi)) { 
    //                 $return[] = $out; 
    //             } 
    //         } 
    //         // $threeItemSet[$x]['subset'] = $return;
    //         // // return $threeItemSet; 
    //         // foreach ($threeItemSet[$x]['subset'] as $subset) {
    //         //     //
    //         // }

    //     }

    //     // return '<h1 align="center">DISCONTINUED</h1>';

    //     return view('eh', compact(
    //                 'transaksi', 'products', 'mt', 'itemSet',
    //                 'twoItemSet', 'twoItemSet_all',
    //                 'threeItemSet', 'threeItemSet_all'
    //             ));

    //     /*
    //     gagal, alasan :
    //     - Ada duplikat
    //     - tidak dinamis, terbatas ke 3 iterasi
    //     
    //     fix : stepByStep()
    //     */

    // }
}
