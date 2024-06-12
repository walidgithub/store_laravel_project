<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class ProductsController extends Controller
{

    // use middleware of token for all funcions except index and show
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except('index', 'show');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        
        $products = Product::filter($request->query())
        // here we selected specific columns from table in relation
        // id,name in category and id,name in store ...
            ->with('category:id,name', 'store:id,name', 'tags:id,name')
            ->paginate();

            // return response with new form using resource
        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'status' => 'in:active,inactive',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|gt:price',
        ]);

        $user = $request->user();
        if (!$user->tokenCan('products.create')) {
            abort(403, 'Not allowed');
        }

        $product = Product::create($request->all());


        // we use ::json with API
        return Response::json($product, 201, [
            'Location' => route('products.show', $product->id),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        return new ProductResource($product);

        return $product
        // here we can't use ->with because it will return object and you must use ->first after that so
        // we used load instead
            ->load('category:id,name', 'store:id,name', 'tags:id,name');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        // sometimes rule if we don't want update a field if not changed
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:255',
            'category_id' => 'sometimes|required|exists:categories,id',
            'status' => 'in:active,inactive',
            'price' => 'sometimes|required|numeric|min:0',
            'compare_price' => 'nullable|numeric|gt:price',
        ]);

        $user = $request->user();
        if (!$user->tokenCan('products.update')) {
            abort(403, 'Not allowed');
        }

        $product->update($request->all());

        // we use ::json with API
        return Response::json($product);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = Auth::guard('sanctum')->user();
        // if (!$user->tokenCan('products.delete')) {
        //     return response([
        //         'message' => 'Not allowed'
        //     ], 403);
        // }

        Product::destroy($id);
        return [
            'message' => 'Product deleted successfully',
        ];
    }
}
