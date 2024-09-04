@extends('layouts.app')

@section('content')
<main class="pt-90">
    <div class="mb-4 pb-4"></div>
    <section class="shop-checkout container">
        <h2 class="page-title">Order Received</h2>
        <div class="checkout-steps">
            <a href="javascript:void(0)" class="checkout-steps__item active">
                <span class="checkout-steps__item-number">01</span>
                <span class="checkout-steps__item-title">
                    <span>Shopping Bag</span>
                    <em>Manage Your Items List</em>
                </span>
            </a>
            <a href="javascript:void(0)" class="checkout-steps__item active">
                <span class="checkout-steps__item-number">02</span>
                <span class="checkout-steps__item-title">
                    <span>Checkout</span>
                    <em>Checkout Your Items List</em>
                </span>
            </a>
            <a href="javascript:void(0)" class="checkout-steps__item active">
                <span class="checkout-steps__item-number">03</span>
                <span class="checkout-steps__item-title">
                    <span>Confirmation</span>
                    <em>Review And Submit Your Order</em>
                </span>
            </a>
        </div>
        <div class="order-complete">
            <div class="order-complete__message">
                <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="40" cy="40" r="40" fill="#B9A16B" />
                    <path
                        d="M52.9743 35.7612C52.9743 35.3426 52.8069 34.9241 52.5056 34.6228L50.2288 32.346C49.9275 32.0446 49.5089 31.8772 49.0904 31.8772C48.6719 31.8772 48.2533 32.0446 47.952 32.346L36.9699 43.3449L32.048 38.4062C31.7467 38.1049 31.3281 37.9375 30.9096 37.9375C30.4911 37.9375 30.0725 38.1049 29.7712 38.4062L27.4944 40.683C27.1931 40.9844 27.0257 41.4029 27.0257 41.8214C27.0257 42.24 27.1931 42.6585 27.4944 42.9598L33.5547 49.0201L35.8315 51.2969C36.1328 51.5982 36.5513 51.7656 36.9699 51.7656C37.3884 51.7656 37.8069 51.5982 38.1083 51.2969L40.385 49.0201L52.5056 36.8996C52.8069 36.5982 52.9743 36.1797 52.9743 35.7612Z"
                        fill="white" />
                </svg>
                <h3>Your order is completed!</h3>
                    <p>Thank you. Your order has been received.</p>
                    <p>Please visit the IGP office to complete your transaction and pick up your items.</p>
            </div>
            <div class="order-info">
                <div class="order-info__item">
                    <label>Order Number</label>
                    <span>{{$order->id}}</span>
                </div>
                <div class="order-info__item">
                    <label>Date</label>
                    <span>{{$order->created_at->format('d/m/Y')}}</span>
                </div>
                <div class="order-info__item">
                    <label>Total</label>
                    <span>₱{{number_format($order->total,2)}}</span>
                </div>
                <!-- <div class="order-info__item">
                    <label>Payment Method</label>
                    <span>Please pay at the IGP office</span>
                </div> -->
            </div>
            <div class="checkout__totals-wrapper">
                <div class="checkout__totals">
                    <h3>Order Details</h3>
                    <table class="checkout-cart-items">
                        <thead>
                            <tr>
                                <th>PRODUCT</th>
                                <th>SUBTOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orderItems as $item)
                                <tr>
                                    <td>{{$item->product->name}} x {{$item->quantity}}</td>
                                    <td class="text-right">₱{{number_format($item->price * $item->quantity,2)}}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <table class="checkout-totals">
                        <tbody>
                            <!-- <tr>
                                <th>SUBTOTAL</th>
                                <td class="text-right">₱{{number_format($order->subtotal,2)}}</td>
                            </tr> -->
                            <tr>
                                <th>TOTAL</th>
                                <td class="text-right">₱{{number_format($order->total,2)}}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection