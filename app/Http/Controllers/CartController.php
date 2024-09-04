<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use Surfsidemedia\Shoppingcart\Facades\Cart;

class CartController extends Controller
{
    public function index()
    {
        $items = Cart::instance('cart')->content();
        return view('cart', compact('items'));
    }
    public function add_to_cart(Request $request)
    {
        Cart::instance('cart')->add($request->id,$request->name,$request->quantity,$request->price)->associate('App\Models\Product');
        return redirect()->back();
    }

    public function increase_cart_quantity($rowId)
    {
        $product = Cart::instance('cart')->get($rowId);
        $qty = $product->qty + 1;
        Cart::instance('cart')->update($rowId,$qty);
        return redirect()->back();
    }

    public function decrease_cart_quantity($rowId)
    {
        $product = Cart::instance('cart')->get($rowId);
        $qty = $product->qty - 1;
        Cart::instance('cart')->update($rowId,$qty);
        return redirect()->back();
    }

    public function remove_item($rowId)
    {
        Cart::instance('cart')->remove($rowId);
        return redirect()->back();
    }

    public function empty_cart()
    {
        Cart::instance('cart')->destroy();
        return redirect()->back();
    }

    public function checkout()
    {
        if (!Auth::check()) {
            return redirect()->route("login");
        }
        if (Cart::instance('cart')->count() <= 0) {
            return redirect()->route('cart.index')->with('message', 'Your cart is empty. Please add items to your cart before proceeding to checkout.');
        }
        $user = Auth::user();
        return view('checkout',compact('user'));
    }

    public function place_an_order(Request $request)
    {
        $user_id = Auth::user()->id;
        $name = Auth::user()->name;
        $institutional_id = Auth::user()->institutional_id; // This will be null if the user is an employee without an institutional ID
        
        $this->setAmountForCheckout();
        
        $checkout = session()->get('checkout', []);
    
        $subtotal = isset($checkout['subtotal']) ? str_replace(',', '', $checkout['subtotal']) : 0;
        $total = isset($checkout['total']) ? str_replace(',', '', $checkout['total']) : 0;
    
        $order = new Order();
        $order->user_id = $user_id;
        $order->subtotal = (float) $subtotal;
        $order->total = (float) $total;
        $order->name = $name;
        $order->institutional_id = $request->input('institutional_id') ?: $institutional_id; // Use provided ID or the user's institutional ID if available
        $order->save();                
        
        foreach(Cart::instance('cart')->content() as $item)
        {
            $orderitem = new OrderItem();
            $orderitem->product_id = $item->id;
            $orderitem->order_id = $order->id;
            $orderitem->price = $item->price;
            $orderitem->quantity = $item->qty;
            $orderitem->save();                   
        }
        
        $transaction = new Transaction();
        $transaction->user_id = $user_id;
        $transaction->order_id = $order->id;
        $transaction->status = "pending";
        $transaction->save();
        
        Cart::instance('cart')->destroy();
        Session()->forget('checkout');
        Session::put('order_id', $order->id);
        
        return redirect()->route('cart.order.confirmation');
    }           

    public function setAmountForCheckout()
    { 
        if (Cart::instance('cart')->count() <= 0) {
            Session()->forget('checkout');
            return;
        }    
        $subtotal = Cart::instance('cart')->subtotal();
        $total = $subtotal;
    
        Session()->put('checkout', [
            'subtotal' => $subtotal,
            'total' => $total
        ]);
    }    

    public function order_confirmation()
    {
        if (Session::has('order_id'))
        {
            $order = Order::find(Session::get('order_id'));
            $orderItems = OrderItem::where('order_id', $order->id)->get();

            return view('order-confirmation', compact('order', 'orderItems'));
        }
        return redirect()->route('cart.index');
    }
    
}