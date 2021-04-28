<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;
use phpDocumentor\Reflection\Types\Object_;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index(Request $request)
    {

        if (!empty($request->all()) && !$request->has('page')){
            $filterData['product_title'] = trim($request->query('product_title'));
            $filterData['variant'] = trim($request->query('variant'));
            $filterData['price_from'] = trim($request->query('price_from'));
            $filterData['price_to'] = trim($request->query('price_to'));
            $filterData['date'] = trim($request->query('date'));

            $variants = Variant::with('productVariant')->get();

            $query = Product::query();
            if (!empty($filterData['product_title'])){
                $query->where('products.title','like','%'.$filterData['product_title'].'%');
            }
            if (!empty($filterData['date'])){
                $query->whereBetween('products.created_at',[$filterData['date'].' 00:00:00', $filterData['date'].' 23:59:59']);
            }
            $query->with('productVariantPrices');
            $products = $query->paginate(3);

            return view('products.index',compact('variants','products','filterData'));
        }
        else{
            $filterData['product_title'] = '';
            $filterData['variant'] = '';
            $filterData['price_from'] = '';
            $filterData['price_to'] = '';
            $filterData['date'] = '';

            $variants = Variant::with('productVariant')->get();
            $products = Product::with('productVariantPrices')->paginate(3) ;
            return view('products.index',compact('variants','products','filterData'));
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
//        dd($request->all());
        try {
            // Saving product data to product table
            $product = new Product;
            $product->title = $request->title;
            $product->sku = $request->sku;
            $product->description = $request->description;
            $product->save();
            $product_id = $product->id;

            // Saving product_variant data to product_variant table
            if (count($request->product_variant) > 0){
                array_map(function ($pv) use ($product_id){
                    foreach ($pv['tags'] as $item){
                        $product_variant = new ProductVariant();
                        $product_variant->variant = $item;
                        $product_variant->variant_id = $pv['option'];
                        $product_variant->product_id = $product_id;
                        $product_variant->save();
                    }
                }, $request->product_variant);

            }
            // Saving product_variant_prices data to product_variant_prices table
            if (count($request->product_variant_prices) > 0){
                array_map(function ($pvp) use ($product_id){
                    $product_variant_string_arr = explode("/",$pvp['title']);
                    $product_variant_string_arr = array_values(array_filter($product_variant_string_arr));

                    $product_variant_price = new ProductVariantPrice();
                    if (count($product_variant_string_arr) == 1){
                        $product_variant_price->product_variant_one = ProductVariant::where('variant',$product_variant_string_arr[0])->first()->id;
                    }
                    else if (count($product_variant_string_arr) > 1){
                        $product_variant_price->product_variant_one = ProductVariant::where('variant',$product_variant_string_arr[0])->first()->id;
                        $product_variant_price->product_variant_two = ProductVariant::where('variant',$product_variant_string_arr[1])->first()->id;
                    }
                    else if (count($product_variant_string_arr) > 2){
                        $product_variant_price->product_variant_one = ProductVariant::where('variant',$product_variant_string_arr[0])->first()->id;
                        $product_variant_price->product_variant_two = ProductVariant::where('variant',$product_variant_string_arr[1])->first()->id;
                        $product_variant_price->product_variant_three = ProductVariant::where('variant',$product_variant_string_arr[2])->first()->id;
                    }

                    $product_variant_price->price = $pvp['price'];
                    $product_variant_price->stock = $pvp['stock'];
                    $product_variant_price->product_id = $product_id;
                    $product_variant_price->save();
                }, $request->product_variant_prices);

            }
            return 'success';

        }
        catch (Exception $e){
            return $e->getMessage();
        }

    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $variants = Variant::all();
        $productData = Product::whereId($product->id)->with('productVariantPrices')->first();

        $product_variant_prices = [];
        foreach ($productData->productVariantPrices as $item){
            $data = [];
            $data['title'] = (ProductVariant::getVariantTitle($item->product_variant_one))."/".(ProductVariant::getVariantTitle($item->product_variant_two))."/".(ProductVariant::getVariantTitle($item->product_variant_three));
            $data['price'] = $item->price;
            $data['stock'] = $item->stock;
            array_push($product_variant_prices,$data);
        }
        $productVariantID = DB::table('product_variants')->where('product_id',$product->id)->pluck('variant_id')->toArray();
        $productVariantID  = array_values(array_filter(array_unique($productVariantID)));
        $product_variant = array();
        foreach ($productVariantID as $item){
            $data = [];
            $tags = ProductVariant::where(['variant_id'=>$item, 'product_id'=>$product->id])->pluck('variant')->toArray();
            $data['option'] = $product->id;
            $data['tags'] = $tags;
            array_push($product_variant,$data);
        }
        $productData->product_variant_prices = $product_variant_prices;
        $productData->product_variant = $product_variant;

//        dd($productData);

        return view('products.edit', compact('variants','productData'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        dd($request->all(),$product->id);
        try {
            // Saving product data to product table
            $productData =  Product::find($product->id);
            $productData->title = $request->title;
            $productData->description = $request->description;
            $product->save();
            $product_id = $product->id;

            // Saving product_variant data to product_variant table
            if (count($request->product_variant) > 0){
                array_map(function ($pv) use ($product_id){
                    foreach ($pv['tags'] as $item){
                        $product_variant = new ProductVariant();
                        $product_variant->variant = $item;
                        $product_variant->variant_id = $pv['option'];
                        $product_variant->product_id = $product_id;
                        $product_variant->save();
                    }
                }, $request->product_variant);

            }
            // Saving product_variant_prices data to product_variant_prices table
            if (count($request->product_variant_prices) > 0){
                array_map(function ($pvp) use ($product_id){
                    $product_variant_string_arr = explode("/",$pvp['title']);
                    $product_variant_string_arr = array_values(array_filter($product_variant_string_arr));

                    $product_variant_price = new ProductVariantPrice();
                    if (count($product_variant_string_arr) == 1){
                        $product_variant_price->product_variant_one = ProductVariant::where('variant',$product_variant_string_arr[0])->first()->id;
                    }
                    else if (count($product_variant_string_arr) > 1){
                        $product_variant_price->product_variant_one = ProductVariant::where('variant',$product_variant_string_arr[0])->first()->id;
                        $product_variant_price->product_variant_two = ProductVariant::where('variant',$product_variant_string_arr[1])->first()->id;
                    }
                    else if (count($product_variant_string_arr) > 2){
                        $product_variant_price->product_variant_one = ProductVariant::where('variant',$product_variant_string_arr[0])->first()->id;
                        $product_variant_price->product_variant_two = ProductVariant::where('variant',$product_variant_string_arr[1])->first()->id;
                        $product_variant_price->product_variant_three = ProductVariant::where('variant',$product_variant_string_arr[2])->first()->id;
                    }

                    $product_variant_price->price = $pvp['price'];
                    $product_variant_price->stock = $pvp['stock'];
                    $product_variant_price->product_id = $product_id;
                    $product_variant_price->save();
                }, $request->product_variant_prices);

            }
            return 'success';

        }
        catch (Exception $e){
            return $e->getMessage();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
