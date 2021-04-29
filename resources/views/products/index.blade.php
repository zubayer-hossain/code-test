@extends('layouts.app')

@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Products</h1>
    </div>


    <div class="card">
        <form action="" method="get" class="card-header">
            <div class="form-row justify-content-between">
                <div class="col-md-2">
                    <input type="text" name="product_title" id="product_title" value="{{ $filterData['product_title'] }}" placeholder="Product Title" class="form-control">
                </div>
                <div class="col-md-2">
                    <select name="variant" id="variant" class="form-control">
                        @foreach($variants as $variant)
                            <optgroup label="{{ $variant['name'] }}">
                            @foreach($variant['children'] as $item)
                                <option value="{{ $item }}">{{ $item }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Price Range</span>
                        </div>
                        <input type="text" name="price_from" value="{{ $filterData['price_from'] }}" aria-label="First name" placeholder="From" class="form-control">
                        <input type="text" name="price_to" value="{{ $filterData['price_to'] }}" aria-label="Last name" placeholder="To" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date" value="{{ $filterData['date'] }}" placeholder="Date" class="form-control">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary float-right"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>

        <div class="card-body">
            <div class="table-response">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Variant</th>
                        <th width="150px">Action</th>
                    </tr>
                    </thead>

                    <tbody>
                    @if(!empty($products) && $products->count())
                        @php $count = 0; @endphp
                        @foreach($products as $product)
                        <tr>
                            <td>{{ ++$count }}</td>
                            <td>
                                {{ $product->title }}
                                <br>
                                Created at : {{ $product->created_at->format('d-M-Y') }}
                            </td>
                            <td>
                                {{ $product->description }}
                            </td>
                            <td>
                                @foreach($product->productVariantPrices as $item)
                                <dl class="row mb-0" style="height: 35px; overflow: hidden" id="variant">
                                    <dt class="col-sm-3 pb-0">
                                        {{ \App\Models\ProductVariant::getVariantTitle($item->product_variant_one) }}/
                                        {{ \App\Models\ProductVariant::getVariantTitle($item->product_variant_two) }}/
                                        {{ \App\Models\ProductVariant::getVariantTitle($item->product_variant_three) }}
                                    </dt>
                                    <dd class="col-sm-9">
                                        <dl class="row mb-0">
                                            <dt class="col-sm-4 pb-0">Price : {{ number_format($item->price,2) }}</dt>
                                            <dd class="col-sm-8 pb-0">InStock : {{ number_format($item->stock,2) }}</dd>
                                        </dl>
                                    </dd>
                                </dl>
                                @endforeach
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('product.edit', $product->id) }}" class="btn btn-success">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    @else
                        @php $count = 0; @endphp
                        <tr>
                            <td class="text-center" colspan="5">There are no data.</td>
                        </tr>
                    @endif
                    </tbody>

                </table>
            </div>

        </div>

        <div class="card-footer">
            <div class="row justify-content-between">
                <div class="col-md-6">
                    <p>
                        Showing {{ $count ? 1 : 0 }} to {{ $count ?? 0 }}
                        out of  {{count($products)}}
                    </p>

                </div>
                @if(!$filterData['filtering'])
                <div class="col-md-6">
                    {!! $products->links() !!}
                </div>
                @endif
            </div>
        </div>
    </div>
    <script>
        if ('{{  $filterData['variant'] }}' != ''){
            document.getElementById("variant").value = '{{  $filterData['variant'] }}';
        }
    </script>
@endsection
