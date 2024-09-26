<?php

namespace App\Http\Controllers;


use App\Models\Brand;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Order;
use Carbon\Carbon;
use App\Models\Slide;
use App\Models\Notification;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Laravel\Facades\Image;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\TransactionsExport;
use Maatwebsite\Excel\Facades\Excel;



    class AdminController extends Controller
    {
        public $notifications;

        public function __construct()
        {
            // Fetch unread notifications
            $this->notifications = Notification::where('is_read', false)->get();
        }

        public function index()
        {
            $orders = Order::orderBy('created_at', 'DESC')->take(10)->get();

            $dashboardDatas = DB::select("
                SELECT 
                    SUM(total) AS TotalAmount,
                    SUM(IF(status='pending', total, 0)) AS TotalPendingAmount,
                    SUM(IF(status='delivered', total, 0)) AS TotalDeliveredAmount,
                    SUM(IF(status='canceled', total, 0)) AS TotalCanceledAmount,
                    COUNT(*) AS Total,
                    SUM(IF(status='pending', 1, 0)) AS TotalPending,
                    SUM(IF(status='delivered', 1, 0)) AS TotalDelivered,
                    SUM(IF(status='canceled', 1, 0)) AS TotalCanceled
                FROM orders
            ");

            $monthlyDatas = DB::select("
                SELECT 
                    M.id AS MonthNo, 
                    M.name AS MonthName,
                    IFNULL(D.TotalAmount, 0) AS TotalAmount,
                    IFNULL(D.TotalPendingAmount, 0) AS TotalPendingAmount,
                    IFNULL(D.TotalDeliveredAmount, 0) AS TotalDeliveredAmount,
                    IFNULL(D.TotalCanceledAmount, 0) AS TotalCanceledAmount
                FROM month_names M
                LEFT JOIN (
                    SELECT 
                        MONTH(created_at) AS MonthNo,
                        SUM(total) AS TotalAmount,
                        SUM(CASE WHEN status = 'pending' THEN total ELSE 0 END) AS TotalPendingAmount,
                        SUM(CASE WHEN status = 'delivered' THEN total ELSE 0 END) AS TotalDeliveredAmount,
                        SUM(CASE WHEN status = 'canceled' THEN total ELSE 0 END) AS TotalCanceledAmount
                    FROM orders
                    WHERE YEAR(created_at) = YEAR(NOW())
                    GROUP BY MONTH(created_at)
                ) D ON D.MonthNo = M.id
                ORDER BY M.id
            ");

            $AmountM = implode(',', collect($monthlyDatas)->pluck('TotalAmount')->toArray());
            $PendingAmountM = implode(',', collect($monthlyDatas)->pluck('TotalPendingAmount')->toArray());
            $DeliveredAmountM = implode(',', collect($monthlyDatas)->pluck('TotalDeliveredAmount')->toArray());
            $CanceledAmountM = implode(',', collect($monthlyDatas)->pluck('TotalCanceledAmount')->toArray());

            $notifications = Notification::where('is_read', false)->get();

            $TotalAmount = collect($monthlyDatas)->sum('TotalAmount');
            $TotalPendingAmount = collect($monthlyDatas)->sum('TotalPendingAmount');
            $TotalDeliveredAmount = collect($monthlyDatas)->sum('TotalDeliveredAmount');
            $TotalCanceledAmount = collect($monthlyDatas)->sum('TotalCanceledAmount');

            return view('admin.index', compact(
                'orders',
                'dashboardDatas',
                'AmountM',
                'PendingAmountM',
                'DeliveredAmountM',
                'CanceledAmountM',
                'TotalAmount',
                'TotalPendingAmount',
                'TotalDeliveredAmount',
                'TotalCanceledAmount',
                'notifications'
            ));
        }


    public function brands()
    {
        $brands = Brand::orderBy('id', 'DESC')->paginate(10);
        return view('admin.brands', compact('brands'));
    }

    public function add_brand()
    {
        return view('admin.brand-add');
    }

    public function brand_store(Request $request){
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:brands,slug',
            'image'=> 'mimes:png,jpg,jpeg|max:2048'
        ]);

        $brand = new Brand();
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name);
        $image = $request->file('image');
        $file_extension = $request->file('image')->extension();
        $file_name = Carbon::now()->timestamp.'.'.$file_extension;
        $this->GenerateBrandThumbnailsImage($image, $file_name);
        $brand->image = $file_name;
        $brand->save();
        return redirect()->route('admin.brands')->with('status', 'Brand has been added succesfully!');
    }

    public function brand_edit($id)
    {
        $brand = Brand::find($id);
        return view('admin.brand-edit', compact('brand'));
    }

    public function brand_update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:brands,slug,'.$id,
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);

        $brand = Brand::find($id);
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name);
        if ($request->hasFile('image')) {
            $oldImagePath = public_path('uploads/brands/'.$brand->image);
            if (File::exists($oldImagePath)) {
                File::delete($oldImagePath);
            }
            $image = $request->file('image');
            $file_extension = $image->extension();
            $file_name = Carbon::now()->timestamp.'.'.$file_extension;
            $this->GenerateBrandThumbnailsImage($image, $file_name);
            $brand->image = $file_name;
        }
        $brand->save();
        return redirect()->route('admin.brands')->with('status', 'Brand has been updated successfully!');
    }

    public function GenerateBrandThumbnailsImage($image, $imageName)
    {
        $destinationPath = public_path('uploads/brands');
        $img = Image::read($image->path());
        $img->cover(124,124, "top");
        $img->resize(124, 124, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath.'/'.$imageName);
    }

    public function brand_delete($id)
    {
        $brand = Brand::find($id);
        if(File::exists(public_path('uploads/brands').'/'.$brand->image))
        {
            File::delete(public_path('uploads/brands').'/'.$brand->image);
        }
        $brand->delete();
        return redirect()->route('admin.brands')->with('status', 'Brand has been deleted successfully!');
    }

    public function categories()
    {
        $categories = Category::orderBy('id', 'DESC')->paginate(10);
        return view ('admin.categories', compact('categories'));
    }

    public function category_add()
    {
        return view('admin.category-add');
    }

    public function category_store(Request $request){
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:categories,slug',
            'image'=> 'mimes:png,jpg,jpeg|max:2048'
        ]);

        $category = new Category();
        $category->name = $request->name;
        $category->slug = Str::slug($request->name);
        $image = $request->file('image');
        $file_extension = $request->file('image')->extension();
        $file_name = Carbon::now()->timestamp.'.'.$file_extension;
        $this->GenerateCategoryThumbnailsImage($image, $file_name);
        $category->image = $file_name;
        $category->save();
        return redirect()->route('admin.categories')->with('status', 'New category has been added succesfully!');
    }

    public function GenerateCategoryThumbnailsImage($image, $imageName)
    {
        $destinationPath = public_path('uploads/categories');
        $img = Image::read($image->path());
        $img->cover(124,124, "top");
        $img->resize(124, 124, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath.'/'.$imageName);
    }

    public function category_edit($id)
    {
        $category = Category::find($id);
        return view('admin.category-edit', compact('category'));
    }

    public function category_update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:categories,slug,'.$id,
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);

        $category = Category::find($id);
        $category->name = $request->name;
        $category->slug = Str::slug($request->name);
        if ($request->hasFile('image')) {
            $oldImagePath = public_path('uploads/categories/'.$category->image);
            if (File::exists($oldImagePath)) {
                File::delete($oldImagePath);
            }
            $image = $request->file('image');
            $file_extension = $image->extension();
            $file_name = Carbon::now()->timestamp.'.'.$file_extension;
            $this->GenerateCategoryThumbnailsImage($image, $file_name);
            $category->image = $file_name;
        }
        $category->save();
        return redirect()->route('admin.categories')->with('status', 'Category has been updated successfully!');
    }

    public function category_delete($id)
    {
        $category = Category::find($id);
        if(File::exists(public_path('uploads/categories').'/'.$category->image))
        {
            File::delete(public_path('uploads/categories').'/'.$category->image);
        }
        $category->delete();
        return redirect()->route('admin.categories')->with('status', 'Category has been deleted successfully!');
    }

       public function products()
    {
        $products = Product::with(['category', 'brand'])->orderBy('created_at', 'DESC')->paginate(10);
        return view("admin.products", compact('products'));
    }

    public function product_add()
    {
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        $brands = Brand::select('id', 'name')->orderBy('name')->get();
        return view("admin.product-add", compact('categories', 'brands'));
    }

    public function product_store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:products,slug',
            'short_description' => 'required',
            'description' => 'required',
            'regular_price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'SKU' => 'required|unique:products,SKU',
            'stock_status' => 'required|in:instock,outofstock',
            'featured' => 'required|boolean',
            'quantity' => 'required|integer|min:1',
            'image' => 'required|mimes:png,jpg,jpeg|max:2048',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'images.*' => 'mimes:png,jpg,jpeg|max:2048',
        ]);

        $product = new Product();
        $product->name = $request->name;
        $product->slug = Str::slug($request->slug);
        $product->short_description = $request->short_description;
        $product->description = $request->description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = ($request->sale_price && $request->sale_price !== 'N/A') ? $request->sale_price : null;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->featured;
        $product->quantity = $request->quantity;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;

        $current_timestamp = now()->timestamp;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = $current_timestamp . '.' . $image->extension();
            $this->GenerateProductThumbnailsImage($image, $imageName);
            $product->image = $imageName;
        }

        $gallery_arr = [];
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            foreach ($files as $key => $file) {
                $gfileName = $current_timestamp . '-' . ($key + 1) . '.' . $file->getClientOriginalExtension();
                $this->GenerateProductThumbnailsImage($file, $gfileName);
                $gallery_arr[] = $gfileName;
            }
        }
        $product->images = implode(',', $gallery_arr);
        $product->save();

        return redirect()->route('admin.products')->with('status', 'New product has been added successfully!');
    }

    public function GenerateProductThumbnailsImage($image, $imageName)
    {
        $destinationPathThumbnail = public_path('uploads/products/thumbnails');
        $destinationPath = public_path('uploads/products');
        $img = Image::read($image->path());

        $img->cover(540, 689, "top");
        $img->resize(540, 689, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath . '/' . $imageName);

        $img->resize(104, 104, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPathThumbnail . '/' . $imageName);
    }

    public function product_edit($id)
    {
        $product = Product::find($id);
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        $brands = Brand::select('id', 'name')->orderBy('name')->get();
        return view('admin.product-edit', compact('product', 'categories', 'brands'));
    }

    public function product_update(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:products,slug,' . $request->id,
            'short_description' => 'required',
            'description' => 'required',
            'regular_price' => 'required',
            'sale_price' => 'nullable|numeric|min:0',
            'SKU' => 'required',
            'stock_status' => 'required',
            'featured' => 'required',
            'quantity' => 'required',
            'image' => 'mimes:png,jpg,jpeg|max:2048',
            'category_id' => 'required',
            'brand_id' => 'required',
        ]);

        $product = Product::find($request->id);
        $product->name = $request->name;
        $product->slug = Str::slug($request->name);
        $product->short_description = $request->short_description;
        $product->description = $request->description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = ($request->sale_price && $request->sale_price !== 'N/A') ? $request->sale_price : null;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->featured;
        $product->quantity = $request->quantity;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;

        $current_timestamp = Carbon::now()->timestamp;

        if ($request->hasFile('image')) {
            if (File::exists(public_path('uploads/products') . '/' . $product->image)) {
                File::delete(public_path('uploads/products') . '/' . $product->image);
            }
            if (File::exists(public_path('uploads/products/thumbnails') . '/' . $product->image)) {
                File::delete(public_path('uploads/products/thumbnails') . '/' . $product->image);
            }
            $image = $request->file('image');
            $imageName = $current_timestamp . '.' . $image->extension();
            $this->GenerateProductThumbnailsImage($image, $imageName);
            $product->image = $imageName;
        }

        $gallery_arr = [];
        $gallery_images = "";
        $counter = 1;

        if ($request->hasFile('images')) {
            foreach (explode(',', $product->images) as $ofile) {
                if (File::exists(public_path('uploads/products') . '/' . $ofile)) {
                    File::delete(public_path('uploads/products') . '/' . $ofile);
                }
                if (File::exists(public_path('uploads/products/thumbnails') . '/' . $ofile)) {
                    File::delete(public_path('uploads/products/thumbnails') . '/' . $ofile);
                }
            }

            $allowedfileExtension = ['jpg', 'png', 'jpeg'];
            $files = $request->file('images');
            foreach ($files as $file) {
                $gextension = $file->getClientOriginalExtension();
                $gcheck = in_array($gextension, $allowedfileExtension);
                if ($gcheck) {
                    $gfileName = $current_timestamp . "-" . $counter . "." . $gextension;
                    $this->GenerateProductThumbnailsImage($file, $gfileName);
                    array_push($gallery_arr, $gfileName);
                    $counter = $counter + 1;
                }
            }
            $gallery_images = implode(',', $gallery_arr);
            $product->images = $gallery_images;
        }
        $product->save();
        return redirect()->route('admin.products')->with('status', 'Product has been updated successfully!');
    }

    public function product_delete($id)
    {
        $product = Product::find($id);
        if (File::exists(public_path('uploads/products') . '/' . $product->image)) {
            File::delete(public_path('uploads/products') . '/' . $product->image);
        }
        if (File::exists(public_path('uploads/products/thumbnails') . '/' . $product->image)) {
            File::delete(public_path('uploads/products/thumbnails') . '/' . $product->image);
        }

        foreach (explode(',', $product->images) as $ofile) {
            if (File::exists(public_path('uploads/products') . '/' . $ofile)) {
                File::delete(public_path('uploads/products') . '/' . $ofile);
            }
            if (File::exists(public_path('uploads/products/thumbnails') . '/' . $ofile)) {
                File::delete(public_path('uploads/products/thumbnails') . '/' . $ofile);
            }
        }
        $product->delete();
        return redirect()->route('admin.products')->with('status', 'Product has been deleted succesfully!');
    }

   public function orders()
{
    $orders = Order::with('user')
        ->orderBy('created_at', 'DESC')
        ->paginate(12);
        
    return view("admin.orders", compact('orders'));
}


    
    public function order_details($order_id)
    {
        $order = Order::with('user')->find($order_id);
    
        if (!$order) {
            return redirect()->route('admin.orders')->with('error', 'Order not found.');
        }
    
        $orderItems = OrderItem::where('order_id', $order_id)->orderBy('id')->paginate(12);
        $transaction = Transaction::where('order_id', $order_id)->first();
    
        return view("admin.order-details", compact('order', 'orderItems', 'transaction'));
    }    


public function update_order_status(Request $request)
{
    $order = Order::find($request->order_id);

    // Check if the order was found
    if (!$order) {
        return back()->withErrors(['error' => 'Order not found.']);
    }

    if ($request->order_status == 'delivered') {
        // Update order status and delivery date
        $order->status = 'delivered';
        $order->delivered_date = Carbon::now();
        $order->save();

      
        $transaction = Transaction::where('order_id', $order->id)->first();
        if ($transaction) {
            $transaction->status = 'delivered'; 
            $transaction->save();
        } else {
     
            Transaction::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id, 
                'amount' => $order->total,
                'status' => 'delivered',
                'transaction_type' => 'order', 
                'details' => 'Order delivered',
                'created_at' => now(),
            ]);
        }

       
        $deletedCount = Notification::where('related_id', $order->id)
            ->where('type', 'order')
            ->delete();


        \Log::info('Deleted notifications for order ID: ' . $order->id . ', deleted count: ' . $deletedCount);

    } elseif ($request->order_status == 'canceled') {
 
        $order->status = 'canceled';
        $order->canceled_date = Carbon::now();
        $order->save();
    } else {
  
        $order->status = $request->order_status;
        $order->save();
    }

    return back()->with('status', 'Order status updated successfully!');
}





    public function print_order($id)
    {
        $order = Order::find($id);
        if (!$order) {
            return redirect()->back()->with('error', 'Order not found.');
        }

        $user = $order->user;

        $middleNameInitial = !empty($user->middlename) ? strtoupper(substr($user->middlename, 0, 1)) . '.' : '';
        $fullName = strtoupper(trim("{$user->firstname} {$middleNameInitial} {$user->lastname}"));

        $order->fullName = $fullName;

        $orderItems = $order->orderItems;

        $pdf = PDF::loadView('admin.print-order', compact('order', 'orderItems'));

        return $pdf->stream('receipt.pdf');
    }



       public function slides()
        {
         $slides = Slide::orderBy('id', 'DESC')->paginate(12); 
            return view('admin.slides', compact('slides'));
        }

        public function slide_add()
        {
                return view('admin.slide-add');
        }

        public function slide_store(Request $request)
        {
            $request->validate([ 
            'tagline' => 'required',
            'title' => 'required',
            'subtitle' => 'required',
            'link' => 'required',
            'status' => 'required',
            'image' => 'required|mimes:png,jpg,jpeg|max:2048'
            ]);
            $slide = new Slide();
            $slide->tagline = $request->tagline;
            $slide->title = $request->title;
            $slide->subtitle = $request->subtitle;
            $slide->link = $request->link;
            $slide->status = $request->status;

            $image = $request->file('image');
            $file_extension = $request->file('image')->extension();
            $file_name = Carbon::now()->timestamp.'.'.$file_extension;
            $this->GenerateSlideThumbnailsImage($image, $file_name);
            $slide->image = $file_name;
            $slide->save();
            return redirect()->route('admin.slides')->with("status","Slide added successfully!");
        }

         public function GenerateSlideThumbnailsImage($image, $imageName)
        {
            $destinationPath = public_path('uploads/slides');
            $img = Image::read($image->path());
            $img->cover(400,690, "top");
            $img->resize(400, 690, function ($constraint) {
                $constraint->aspectRatio();
            })->save($destinationPath.'/'.$imageName);
        }


        public function slide_edit($id)
        {
            $slide = Slide::find($id);
            return view('admin.slide-edit', compact('slide'));
        }

        public function slide_update(Request $request)
        {
            $request->validate([ 
            'tagline' => 'required',
            'title' => 'required',
            'subtitle' => 'required',
            'link' => 'required',
            'status' => 'required',
            'image' => 'mimes:png,jpg,jpeg|max:2048'
            ]);
            $slide = Slide::find($request->id);
            $slide->tagline = $request->tagline;
            $slide->title = $request->title;
            $slide->subtitle = $request->subtitle;
            $slide->link = $request->link;
            $slide->status = $request->status;

            if($request->hasFile('image'))
             {
                if(File::exists(public_path('uploads/slides').'/'.$slide->image))
                {
                    File::delete(public_path('uploads/slides').'/'.$slide->image);
                }
                $image = $request->file('image');
                $file_extension = $request->file('image')->extension();
                $file_name = Carbon::now()->timestamp.'.'.$file_extension;
                $this->GenerateSlideThumbnailsImage($image, $file_name);
                $slide->image = $file_name;
            }
            $slide->save();
            return redirect()->route('admin.slides')->with("status","Slide updated successfully!");
        }

        public function slide_delete($id)
        {
            $slide = Slide::find($id);

            if ($slide) {
                if (File::exists(public_path('uploads/slides/' . $slide->image))) {
                    File::delete(public_path('uploads/slides/' . $slide->image));
                }
                $slide->delete();
            }

            return redirect()->route('admin.slides')->with("status", "Slide deleted successfully!");
        }

        public function search(Request $request)
        {
            $query = $request->input('query');
            $results = Product::where('name','LIKE',"%{$query}%")->get()->take(8);
            return response()->json($results);
        }



    public function newOrderNotification(Order $order)
    {
        Notification::create([
            'user_id' => 1,
            'url' => route('admin.orders.show', $order->id),
            'message' => 'New order #' . $order->id . ' has been placed.',
            'is_read' => false,
        ]);
    }

    public function createLowStockNotification(Product $product)
    {
        Notification::create([
            'user_id' => 1, 
            'url' => route('admin.products.show', $product->id),
            'message' => 'Low stock alert: ' . $product->name . ' has only ' . $product->stock . ' left.',
            'is_read' => false,
        ]);
    }

    public function markAsRead(Notification $notification)
    {
        $notification->update(['is_read' => true]);
        return redirect()->back();
    }

        public function settings()
    {
        return view('admin.settings');
    }
public function update(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'old_password' => 'required|string',
        'new_password' => 'nullable|string|min:8|confirmed',
    ]);

    $user = auth()->user();

    if (!Hash::check($request->old_password, $user->password)) {
        return back()->withErrors(['old_password' => 'The provided password does not match your current password.']);
    }

    $user->name = $request->name;
    $user->email = $request->email;

    if ($request->new_password) {
        $user->password = Hash::make($request->new_password);
    }

    $user->save();

    return redirect()->back()->with('success', 'Settings updated successfully.');
    }
public function exportTransactions(Request $request)
{
    $search = $request->input('search'); // Capture search input if needed
    return Excel::download(new TransactionsExport($search), 'transaction_history.xlsx');
}


   public function transactions_history(Request $request)
{
    $search = $request->input('search');
    $status = $request->input('status');

    $transactions = Transaction::with('order.user')
        ->when($search, function($query) use ($search) {
            // Search by order number or user name
            return $query->whereHas('order', function($q) use ($search) {
                $q->where('order_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function($u) use ($search) {
                      $u->where('firstname', 'LIKE', "%{$search}%")
                        ->orWhere('lastname', 'LIKE', "%{$search}%");
                  });
            });
        })
        ->when($status, function($query) use ($status) {
            // Filter by status
            return $query->where('status', $status);
        })
        ->paginate(10);

    return view('admin.transactions-history', compact('transactions', 'search', 'status'));
}

}