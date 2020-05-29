<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link rel="stylesheet" href="{{asset('css/custom.css')}}">
    <link rel="stylesheet" href="{{asset('css/bootstrap.css')}}"> 
</head>
<body>
    <h3>Transaction Breakdown</h3>
    <table class="table table-striped table-sm table-hover">
        <tr>
            <th rowspan="2">Transaction ID</th>
            <th colspan="{{count($products)}}">Produk</th>
        </tr>
        <tr>
            @foreach ($products as $prod)
                <th> {{$prod->produk_name}} </th>
            @endforeach
        </tr>
        @foreach ($transaksi as $trn)
            <tr>
                <td>{{$trn->id}}</td>
                @php
                    $product_id = null;
                    foreach ($trn->detail as $detail) {
                        $product_id[] = $detail->id_produk;
                    } 
                @endphp
                @foreach ($products as $prod)
                    @if (in_array ( $prod->id, $product_id ))
                        <td class="true">1</td>
                    @else
                        <td class="false">0</td>
                    @endif
                @endforeach
            </tr>
        @endforeach
    </table>
    <hr>
    <div class="row">
        <div class="col-md-6">
            <h4>Transaksi Semua Produk</h4>
            <table class="table table-striped table-sm table-hover">
                <tr align="left">
                    <th>ID</th>
                    <th>Nama Produk</th>
                    <th>Jumlah Transaksi</th>
                </tr>
                @foreach ($products as $product)
                    <tr>
                        <td>{{$product->id}}</td>
                        <td>{{$product->produk_name}}</td>
                        <td>{{count($product->transaksi_detail)}}</td>
                    </tr>
                @endforeach
            </table>
        </div>
        <div class="col-md-6">
            <h4>Transaksi Produk >= Minimum Transaksi ({{$mt}})</h4>
            @if ($itemSet !== null)
                <table class="table table-striped table-sm table-hover">
                    <tr align="left">
                        <th>ID</th>
                        <th>Frekuensi Item</th>
                        <th>Nilai Support</th>
                    </tr>
                    @foreach ($itemSet as $item)
                        <tr>
                            <td>{{$item['id0']}}</td>
                            <td>{{$item['frequency']}}</td>
                            <td>{{ round($item['support']*100) }}%</td>
                        </tr>
                    @endforeach
                </table>
            @else
                <p>Tidak ada.</p>
            @endif
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="col-md-6">
            <h4>Trans. 2-Item Set</h4>
            @if ($twoItemSet_all !== null)
                <table class="table table-striped table-sm table-hover">
                    <tr align="left">
                        <th>ID Pertama</th>
                        <th>ID Kedua</th>
                        <th>Jumlah Transaksi</th>
                        <th>Nilai Support</th>
                    </tr>
                    @foreach ($twoItemSet_all as $item)
                        <tr>
                            <td>{{$item['id0']}}</td>
                            <td>{{$item['id1']}}</td>
                            <td>{{$item['frequency']}}</td>
                            <td>{{ round($item['support']*100) }}%</td>
                        </tr>
                    @endforeach
                </table>
            @else
                <p>Tidak ada.</p>
            @endif
        </div>
        <div class="col-md-6">
            <h4>Trans. 2-Item Set >= Minimum Transaksi ({{$mt}})</h4>
            @if ($twoItemSet !== null)
                <table class="table table-striped table-sm table-hover">
                    <tr align="left">
                        <th>ID Pertama</th>
                        <th>ID Kedua</th>
                        <th>Jumlah Transaksi</th>
                        <th>Nilai Support</th>
                    </tr>
                    @foreach ($twoItemSet as $item)
                        <tr>
                            <td>{{$item['id0']}}</td>
                            <td>{{$item['id1']}}</td>
                            <td>{{$item['frequency']}}</td>
                            <td>{{ round($item['support']*100) }}%</td>
                        </tr>
                    @endforeach
                </table>
            @else
                <p>Tidak ada.</p>
            @endif
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="col-md-6">
            <h4>Trans. 3-Item Set</h4>
            @if ($threeItemSet_all !== null)
                <table class="table table-striped table-sm table-hover">
                    <tr align="left">
                        <th>ID Pertama</th>
                        <th>ID Kedua</th>
                        <th>ID Ketiga</th>
                        <th>Jumlah Transaksi</th>
                        <th>Nilai Support</th>
                    </tr>
                    @foreach ($threeItemSet_all as $item)
                        <tr>
                            <td>{{$item['id0']}}</td>
                            <td>{{$item['id1']}}</td>
                            <td>{{$item['id2']}}</td>
                            <td>{{$item['frequency']}}</td>
                            <td>{{ round($item['support']*100) }}%</td>
                        </tr>
                    @endforeach
                </table>
            @else
                <p>Tidak ada.</p>
            @endif
        </div>
        <div class="col-md-6">
            <h4>Trans. 3-Item Set >= Minimum Transaksi ({{$mt}})</h4>
            @if ($threeItemSet !== null)
                <table class="table table-striped table-sm table-hover">
                    <tr align="left">
                        <th>ID Pertama</th>
                        <th>ID Kedua</th>
                        <th>ID Ketiga</th>
                        <th>Jumlah Transaksi</th>
                        <th>Nilai Support</th>
                    </tr>
                    @foreach ($threeItemSet as $item)
                        <tr>
                            <td>{{$item['id0']}}</td>
                            <td>{{$item['id1']}}</td>
                            <td>{{$item['id2']}}</td>
                            <td>{{$item['frequency']}}</td>
                            <td>{{ round($item['support']*100) }}%</td>
                        </tr>
                    @endforeach
                </table>
            @else
                <p>Tidak ada.</p>
            @endif
        </div>
    </div>
    <hr>
    <h3>Association Rule</h3>
    @if ($threeItemSet !== null)
        <table class="table table-striped table-sm table-hover">
            <tr>
                <th>Rule</th>
                <th>Support A⋃B</th>
                <th>Support A</th>
                <th>Confidence</th>
            </tr>
            @foreach ($threeItemSet as $item)
                <tr>
                    <td colspan="4">
                        {{$item['id0']}}, {{$item['id1']}}, {{$item['id2']}}
                    </td>
                </tr>
        
            @endforeach
        </table>
    @else
        <p>Tidak ada.</p>
    @endif
</body>
</html>