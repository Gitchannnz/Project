<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- added asset method on style,body,js and imgs --}}
    <title>{{ config('app.name', 'e-IGP') }}</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="author" content="surfside media" />
    <link rel="stylesheet" type="text/css" href="{{ asset('css/animate.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/animation.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/bootstrap-select.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('font/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('icon/style.css') }}">
    <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}">
    <link rel="apple-touch-icon-precomposed" href="{{ asset('images/favicon.ico') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/sweetalert.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/custom.css') }}">
     <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">

    @stack("styles") {{-- to render css --}}

</head>
<body class="body">
    <div id="wrapper">
        <div id="page" class="">
            <div class="layout-wrap">

                <!-- <div id="preload" class="preload-container">
    <div class="preloading">
        <span></span>
    </div>
</div> -->
  <style>
    #header {
      padding-top: 8px;
      padding-bottom: 8px;
    }

    .logo__image {
      max-width: 220px;
    }

    
    .product-item.gap14.mb-10 {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      gap: 15px;
      transition: all 0.3s ease;
      padding-right: 5px;
    }

.product-item .image-no-bg {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 50px;
  height: 50px;
  gap: 10px;
  flex-shrink: 0;
  padding: 5px;
  border-radius: 10px;
  background: #EFF4F8;
}


#box-content-search li {
  list-style: none;
}

#box-content-search .product-item {
  margin-bottom: 10px;
}

    .orders-list {
        max-height: 300px;
        overflow-y: auto;
    }
    .order-notification {
        padding: 10px;
        margin-bottom: 10px;
        border-bottom: 1px solid #eaeaea;
    }
    .notification-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .notification-header h5 {
        margin: 0;
        font-size: 16px;
    }
    .notification-header .order-date {
        font-size: 12px;
        color: #888;
    }
    .notification-body p {
        margin: 5px 0;
    }
    .notification-body ul {
        padding-left: 20px;
        list-style-type: disc;
    }
    .notification-body ul li {
        font-size: 14px;
    }

  </style>
                <div class="section-menu-left">
                    <div class="box-logo">
                        <a href="{{ route('admin.index') }}" id="site-logo-inner">
                            <img class="" id="logo_header_1" alt="" src="{{asset('images/logo/logo.png') }}"
                                data-light="{{asset('images/logo/logo.png') }}" data-dark="{{asset('images/logo/logo.png') }}">
                        </a>
                        <div class="button-show-hide">
                            <i class="icon-menu-left"></i>
                        </div>
                    </div>
                    <div class="center">
                        <div class="center-item">
                            <div class="center-heading">Home</div>
                            <ul class="menu-list">
                                <li class="menu-item">
                                    <a href="{{route('admin.index')}}" class="">
                                        <div class="icon"><i class="icon-grid"></i></div>
                                        <div class="text">Dashboard</div>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="center-item">
                            <ul class="menu-list">
                                <li class="menu-item has-children">
                                    <a href="javascript:void(0);" class="menu-item-button">
                                        <div class="icon"><i class="icon-shopping-cart"></i></div>
                                        <div class="text">Products</div>
                                    </a>
                                    <ul class="sub-menu">
                                        <li class="sub-menu-item">
                                            <a href="{{route('admin.product.add')}}" class="">
                                                <div class="text">Add Product</div>
                                            </a>
                                        </li>
                                        <li class="sub-menu-item">
                                            <a href="{{route('admin.products')}}" class="">
                                                <div class="text">Products</div>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                <li class="menu-item has-children">
                                    <a href="javascript:void(0);" class="menu-item-button">
                                        <div class="icon"><i class="icon-layers"></i></div>
                                        <div class="text">Brand</div>
                                    </a>
                                    <ul class="sub-menu">
                                        <li class="sub-menu-item">
                                            <a href="{{route('admin.brand.add')}}" class="">
                                                <div class="text">New Brand</div>
                                            </a>
                                        </li>
                                        <li class="sub-menu-item">
                                            <a href="{{route('admin.brands')}}" class="">
                                                <div class="text">Brands</div>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                <li class="menu-item has-children">
                                    <a href="javascript:void(0);" class="menu-item-button">
                                        <div class="icon"><i class="icon-layers"></i></div>
                                        <div class="text">Category</div>
                                    </a>
                                    <ul class="sub-menu">
                                        <li class="sub-menu-item">
                                            <a href="{{route('admin.category.add')}}" class="">
                                                <div class="text">New Category</div>
                                            </a>
                                        </li>
                                        <li class="sub-menu-item">
                                            <a href="{{route('admin.categories')}}" class="">
                                                <div class="text">Categories</div>
                                            </a>
                                        </li>
                                    </ul>
                                </li>

                            <li class="menu-item">
                                        <a href="{{ route('admin.orders') }}" class="menu-item-button">
                                            <div class="icon"><i class="icon-file-plus"></i></div>
                                            <div class="text">Orders</div>
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('admin.slides') }}" class="menu-item-button">
                                            <div class="icon"><i class="icon-image"></i></div>
                                            <div class="text">Slides</div>
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <form method="POST" action="{{ route('logout') }}" id="logout-form">
                                            @csrf
                                            <a href="{{ route('logout') }}" class="menu-item-button" 
                                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                                <div class="icon"><i class="icon-log-out"></i></div>
                                                <div class="text">Logout</div>
                                            </a>
                                        </form>
                                    </li>

                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="section-content-right">

                    <div class="header-dashboard">
                        <div class="wrap">
                            <div class="header-left">
                                <a href="index-2.html">
                                    <img class="" id="logo_header_mobile" alt="" src="{{asset('images/logo/logo.png') }}"
                                        data-light="{{asset('images/logo/logo.png') }}" data-dark="{{asset('images/logo/logo.png') }}"
                                        data-width="154px" data-height="52px" data-retina="{{asset('images/logo/logo.png') }}">
                                </a>
                                <div class="button-show-hide">
                                    <i class="icon-menu-left"></i>
                                </div>


                                <form class="form-search flex-grow">
                                    <fieldset class="name">
                                        <input type="text" placeholder="Search here..." class="show-search" name="name" id="search-input" tabindex="2" value="" aria-required="true" required="" autocomplete="off">
                                    </fieldset>
                                    <div class="button-submit">
                                        <button class="" type="submit"><i class="icon-search"></i></button>
                                    </div>
                                    <div class="box-content-search">
                                        <ul id="box-content-search">
                                        </ul>
                                    </div>
                                </form>

                            </div>
        

                             <div class="header-grid">
                         <div class="popup-wrap message type-header">
    <div class="dropdown">
        <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton2" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="header-item">
                <span class="text-tiny">{{ $orders->count() }}</span>
                <i class="icon-bell"></i>
            </span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end has-content" aria-labelledby="dropdownMenuButton2">
            <li>
                <div class="container">
                    <h3>Orders</h3> 
                    @if($orders->isEmpty())
                        <p>You have no orders yet.</p>
                    @else
                        <div class="orders-list">
                            @foreach($orders as $order)
                                <div class="order-notification">
                                    <div class="notification-header">
                                        <h5>Order #{{ $order->id }}</h5>
                                        <span class="order-date">{{ $order->created_at->format('Y-m-d H:i') }}</span>
                                    </div>
                                    <div class="notification-body">
                                        <p>Total: ₱{{ number_format($order->total, 2) }}</p>
                                        <p>Items:</p>
                                        <ul>
                                            @foreach($order->orderItems as $item)
                                                <li>{{ $item->product->name }} ({{ $item->quantity }})</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </li>
             <li><a href="{{route('admin.orders')}}" class="tf-button w-full">View all</a></li>
        </ul>
    </div>
</div>






                                <div class="popup-wrap user type-header">
                                    <div class="dropdown">
                                        <button class="btn btn-secondary dropdown-toggle" type="button"
                                            id="dropdownMenuButton3" data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="header-user wg-user">
                                                
                                                </span>
                                                <span class="flex flex-column">
                                                    <span class="body-title mb-2">{{ Auth::user()->name }}</span>
                                                    <span class="text-tiny uppercase">{{ Auth::user()->usertype }}</span>
                                                </span>
                                            </span>
                                        </button>
                                      <ul class="dropdown-menu dropdown-menu-end has-content" aria-labelledby="dropdownMenuButton3">
                                            <li>
                                                <a href="#" class="user-item">
                                                    <div class="icon">
                                                        <i class="icon-user"></i>
                                                    </div>
                                                    <div class="body-title-2">Account</div>
                                                </a>
                                            </li>

                                            <!-- Other menu items -->

                                            <li>
                                                <form method="POST" action="{{ route('logout') }}" id="logout-form" style="display: none;">
                                                    @csrf
                                                </form>
                                                <a href="{{ route('logout') }}" class="user-item" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                                    <div class="icon">
                                                        <i class="icon-log-out"></i> <!-- Add the logout icon here -->
                                                    </div>
                                                    <div class="body-title-2">Log out</div>
                                                </a>
                                            </li>
                                        </ul>

                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="main-content">
                        @yield('content')

                        <div class="bottom-page">
                            <div class="body-text">Copyright (c) NBSC 2024. All rights reserved.</div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/jquery.min.') }}js"></script>
    <script src="{{ asset('js/bootstrap.min.') }}js"></script>
    <script src="{{ asset('js/bootstrap-select.min.') }}js"></script>   
    <script src="{{ asset('js/sweetalert.min.') }}js"></script>    
    <script src="{{ asset('js/apexcharts/apexcharts.') }}js"></script>
    <script src="{{ asset('js/main.') }}js"></script>

     <script>
    $(function(){
        $("#search-input").on("keyup",function(){
          var searchQuery = $(this).val();
          if(searchQuery.length > 2 )
          {
            $.ajax({
              type: "GET",
              url: "{{ route('admin.search') }}",
              data: {search: searchQuery},
              dataType : 'json',
              success: function(data){
                $("#box-content-search").html('');
                $.each(data,function(index,item){
                  var url = "{{route('admin.product.edit',['id'=>'product_id'])}}";
                  var link = url.replace('product_id',item.id);

                $("#box-content-search").append(`
                  <li>
                    <ul>
                      <li class="product-item gap14 mb-10">
                        <div class="image-no-bg">
                          <img src="{{asset('uploads/products/thumbnails')}}/${item.image}" alt="${item.name}">
                        </div>
                        <div class="flex items-center justify-between gap20 flex-grow">
                          <div class="name">
                            <a href="${link}" class="body-text">${item.name}</a>
                          </div>
                        </div>
                      </li>
                      <li class="mb-10">
                        <div class="divider"></div>
                      </li>
                    </ul>
                  </li>
                `);

                });
              }

            }); 
          }
        })
    });
  </script>
  
        @stack("scripts") {{-- to render javascript --}}
</body>

</html>


