<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Algoritma Apriori</title>
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
    <hr>
    <h3>Item Sets</h3>
    <p>Item Set berikut telah difilter dan hanya menampilkan item set yang telah memenuhi treshold</p>
    <p>Support Treshold : {{$st*100}}% ({{$mt}} Transaksi)</p>
    
    {{-- <h4>1-Item Set - All </h4>
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
    </table> --}}
    @php
        // $a = 1.5;
        $k = 1;
    @endphp
    @foreach ($data as $datum)
        @if ($datum !== null)
            {{-- @php
                $which = is_numeric( $a ) && floor( $a ) != $a;
            @endphp --}}
            {{-- <h4>{{floor($a)}}-Item Set - {{$which ? 'Pass' : 'All'}}</h4> --}}
            <h4>{{$k}}-Frequent Item Set</h4>
            <table class="table table-striped table-sm table-hover">
                <tr align="left">
                    {{-- @for ($i = 0; $i < floor($a); $i++) --}}
                    @for ($i = 0; $i < $k; $i++)
                        <th>Item Ke-{{$i+1}}</th>
                    @endfor
                    <th>Jumlah Transaksi</th>
                    <th>Support</th>
                </tr>
                @foreach ($datum as $item)
                {{-- {{dd($item)}} --}}
                    <tr>
                        {{-- @for ($i = 0; $i < floor($a); $i++) --}}
                        @for ($i = 0; $i < $k; $i++)
                            <td>{{$item['id'.$i]}}</td>
                        @endfor
                        <td>{{$item['frequency']}}</td>
                        <td>{{ round($item['support']*100, 2) }}%</td>
                    </tr>
                @endforeach
            </table>
        @endif
        @php
            // $a = $a + 0.5;
            $k++;
        @endphp
    @endforeach
    @php
        $k = $k - 3;
    @endphp
    <hr>
    <h3>Subset</h3>
    <table class="table table-bordered table-striped table-sm table-hover">
        @foreach ($data[$k.'-ItemSet_pass'] as $item)
            <tr>
                <td>
                    Frequent Item Set:
                </td>
                <td colspan="{{ (2^(count($item['subset']))-2) }}">
                    @for ($i = 0; $i <= $k; $i++)
                        {{$item['id'.$i]}} {{ $i !== $k ? '-' : ''}}
                    @endfor
                </td>
            </tr>
            <tr>
                <td>
                    Subset :
                </td>
            @foreach ($item['subset'] as $subset)
                <td>
                    @if(count($subset) > 0)
                        @foreach ($subset as $sub)
                            {{$sub}} {{ $loop->last ? '' : ','}}
                        @endforeach
                    @else
                        {{$subset}}
                    @endif
                </td>
            @endforeach
        </tr>
        @endforeach
    </table>
    <hr>
    <h3>Association Rules</h3>
    <table class="table table-striped table-sm table hover">
        <tr align="left">
            <th>Rule</th>
            <th>Support A⋃B</th>
            <th>Support A</th>
            <th>Confidence</th>
        </tr>
        @foreach ($rules as $rule)
            <tr>
                <td>
                    @php
                        $dupes = $rule;
                        $splice = array_splice($dupes, 0, 2);
                    @endphp
                    @foreach ($splice as $i)
                        {
                            @foreach ($i as $ii)
                                {{$ii}} {{$loop->last ? '' : ','}}
                            @endforeach
                        } {{$loop->last ? '' : '=>'}}
                    @endforeach
                </td>
                <td>
                    {{round($rule['support_aub'], 2)}}%
                </td>
                <td>
                    {{round($rule['support_a'], 2)}}%
                </td>
                <td>
                    {{round($rule['confidence'], 2)}}%
                </td>
            </tr>
        @endforeach
    </table>
</body>
</html>