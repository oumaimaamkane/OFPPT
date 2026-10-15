<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
class ProductController extends Controller
{
    public function printTicket(Product $product)
    {
        return view('products.print_ticket', compact('product'));
    }
}
