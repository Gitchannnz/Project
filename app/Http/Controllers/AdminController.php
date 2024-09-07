<?php

namespace App\Http\Controllers;


use App\Models\Brand;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Order;
use Carbon\Carbon;
use App\Models\Slide;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Laravel\Facades\Image;



class AdminController extends Controller
{
 public function index()
{
    // Fetch the 10 most recent orders
    $orders = Order::orderBy('created_at', 'DESC')->limit(10)->get();

    // Fetch summarized dashboard data for total orders
    $dashboardDatas = DB::select("
        SELECT 
            sum(total) AS TotalAmount,
            sum(IF(status = 'ordered', total, 0)) AS TotalOrderedAmount,
            sum(IF(status = 'delivered', total, 0)) AS TotalDeliveredAmount,
            sum(IF(status = 'canceled', total, 0)) AS TotalCanceledAmount,
            COUNT(*) AS Total,
            sum(IF(status = 'ordered', 1, 0)) AS TotalOrdered,
            sum(IF(status = 'delivered', 1, 0)) AS TotalDelivered,
            sum(IF(status = 'canceled', 1, 0)) AS TotalCanceled
        FROM orders
    ");

    // Fetch monthly data for the current year
    $monthlyDatas = DB::select("
        SELECT 
            M.id AS MonthNo, 
            M.name AS MonthName,
            IFNULL(D.TotalAmount, 0) AS TotalAmount,
            IFNULL(D.TotalOrderedAmount, 0) AS TotalOrderedAmount,
            IFNULL(D.TotalDeliveredAmount, 0) AS TotalDeliveredAmount,
            IFNULL(D.TotalCanceledAmount, 0) AS TotalCanceledAmount
        FROM month_names M
        LEFT JOIN (
            SELECT 
                MONTH(created_at) AS MonthNo,
                SUM(total) AS TotalAmount,
                SUM(CASE WHEN status = 'ordered' THEN total ELSE 0 END) AS TotalOrderedAmount,
                SUM(CASE WHEN status = 'delivered' THEN total ELSE 0 END) AS TotalDeliveredAmount,
                SUM(CASE WHEN status = 'canceled' THEN total ELSE 0 END) AS TotalCanceledAmount
            FROM orders
            WHERE YEAR(created_at) = YEAR(NOW())
            GROUP BY MONTH(created_at)
        ) D 
        ON D.MonthNo = M.id
        ORDER BY M.id
    ");

    // Convert monthly data to comma-separated values for charts
    $AmountM = implode(',', collect($monthlyDatas)->pluck('TotalAmount')->toArray());
    $OrderedAmountM = implode(',', collect($monthlyDatas)->pluck('TotalOrderedAmount')->toArray());
    $DeliveredAmountM = implode(',', collect($monthlyDatas)->pluck('TotalDeliveredAmount')->toArray());
    $CanceledAmountM = implode(',', collect($monthlyDatas)->pluck('TotalCanceledAmount')->toArray());

    // Calculate the total sums for each status
    $TotalAmount = collect($monthlyDatas)->sum('TotalAmount');
    $TotalOrderedAmount = collect($monthlyDatas)->sum('TotalOrderedAmount');
    $TotalDeliveredAmount = collect($monthlyDatas)->sum('TotalDeliveredAmount');
    $TotalCanceledAmount = collect($monthlyDatas)->sum('TotalCanceledAmount');

    // Return the data to the view
    return view('admin.index', compact(
        'orders', 
        'dashboardDatas', 
        'AmountM', 
        'OrderedAmountM', 
        'DeliveredAmountM', 
        'CanceledAmountM', 
        'TotalAmount', 
        'TotalOrderedAmount', 
        'TotalDeliveredAmount', 
        'TotalCanceledAmount'
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
        $products =  Product::orderBy('created_at', 'DESC')->paginate(10);
        return view('admin.products', compact('products'));
    }

    public function product_add()
    {
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        $brands = Brand::select('id', 'name')->orderBy('name')->get();
        return view('admin.product-add', compact('categories','brands'));
    }

    public function product_store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:products,slug',
            'short_description' => 'required',
            'description' => 'required',
            'regular_price' => 'required',
            'sale_price' => 'required',
            'SKU' => 'required',
            'stock_status' => 'required',
            'featured' => 'required',
            'quantity' => 'required',
            'image' => 'required|mimes:png,jpg,jpeg|max:2048',
            'category_id' => 'required',
            'brand_id' => 'required',
        ]);

        $product = new Product();
        $product->name = $request->name;
        $product->slug = Str::slug($request->name);
        $product->short_description = $request->short_description;
        $product->description = $request->description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = $request->sale_price;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->featured;
        $product->quantity = $request->quantity;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;

        $current_timestamp = Carbon::now()->timestamp;

        if ($request->hasFile('image'))
        {
            $image = $request->file('image');
            $imageName = $current_timestamp.'.'.$image->extension();
            $this->GenerateProductThumbnailsImage($image,$imageName);
            $product->image = $imageName;
        }

        $gallery_arr = [];
        $gallery_images = "";
        $counter = 1;

        if($request->hasFile('images'))
        {
            $allowedfileExtension = ['jpg','png','jpeg'];
            $files = $request->file('images');
            foreach($files as $file)
            {
                $gextension = $file->getClientOriginalExtension();
                $gcheck = in_array($gextension,$allowedfileExtension);
                if($gcheck)
                {
                    $gfileName = $current_timestamp."-". $counter.".". $gextension;
                    $this->GenerateProductThumbnailsImage($file,$gfileName);
                    array_push($gallery_arr,$gfileName);
                    $counter = $counter + 1;
                }
            }
            $gallery_images = implode(',',$gallery_arr);
        }
        $product->images = $gallery_images;
        $product->save();
        return redirect()->route('admin.products')->with('status','New product has been added successfully!');
    }

    public function GenerateProductThumbnailsImage($image, $imageName)
    {
        $destinationPathThumbnail = public_path('uploads/products/thumbnails');
        $destinationPath = public_path('uploads/products');
        $img = Image::read($image->path());

        $img->cover(540,689, "top");
        $img->resize(540, 689, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath.'/'.$imageName);

        $img->resize(104, 104, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPathThumbnail.'/'.$imageName);
    }

    public function product_edit($id)
    {
        $product = Product::find($id);
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        $brands = Brand::select('id', 'name')->orderBy('name')->get();
        return view('admin.product-edit',compact('product','categories','brands')); 
    }
    public function product_update(Request $request)
    {

        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:products,slug,'.$request->id,
            'short_description' => 'required',
            'description' => 'required',
            'regular_price' => 'required',
            'sale_price' => 'required',
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
        $product->sale_price = $request->sale_price;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->featured;
        $product->quantity = $request->quantity;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;

        $current_timestamp = Carbon::now()->timestamp;

        if ($request->hasFile('image'))
        {
            if(File::exists(public_path('uploads/products').'/'.$product->image))
            {
                File::delete(public_path('uploads/products').'/'.$product->image);
            }
            if(File::exists(public_path('uploads/products/thumbnails').'/'.$product->image))
            {
                File::delete(public_path('uploads/products/thumbnails').'/'.$product->image);
            }
            $image = $request->file('image');
            $imageName = $current_timestamp.'.'.$image->extension();
            $this->GenerateProductThumbnailsImage($image,$imageName);
            $product->image = $imageName;
        }

        $gallery_arr = [];
        $gallery_images = "";
        $counter = 1;

        if($request->hasFile('images'))
        {
            foreach(explode(',',$product->images) as $ofile)
            {
                if(File::exists(public_path('uploads/products').'/'.$ofile))
                {
                    File::delete(public_path('uploads/products').'/'.$ofile);
                }
                if(File::exists(public_path('uploads/products/thumbnails').'/'.$ofile))
                {
                    File::delete(public_path('uploads/products/thumbnails').'/'.$ofile);
                }
            }

            $allowedfileExtension = ['jpg','png','jpeg'];
            $files = $request->file('images');
            foreach($files as $file)
            {
                $gextension = $file->getClientOriginalExtension();
                $gcheck = in_array($gextension,$allowedfileExtension);
                if($gcheck)
                {
                    $gfileName = $current_timestamp."-". $counter.".". $gextension;
                    $this->GenerateProductThumbnailsImage($file,$gfileName);
                    array_push($gallery_arr,$gfileName);
                    $counter = $counter + 1;
                }
            }
            $gallery_images = implode(',',$gallery_arr);
            $product->images = $gallery_images;
        }
        $product->save();
        return redirect()->route('admin.products')->with('status','Product has been updated successfully!');

    }
    public function product_delete($id)
    {
        $product = Product::find($id);
        if(File::exists(public_path('uploads/products').'/'.$product->image))
        {
            File::delete(public_path('uploads/products').'/'.$product->image);
        }
        if(File::exists(public_path('uploads/products/thumbnails').'/'.$product->image))
        {
            File::delete(public_path('uploads/products/thumbnails').'/'.$product->image);
        }

        foreach(explode(',',$product->images) as $ofile)
        {
            if(File::exists(public_path('uploads/products').'/'.$ofile))
            {
                File::delete(public_path('uploads/products').'/'.$ofile);
            }
            if(File::exists(public_path('uploads/products/thumbnails').'/'.$ofile))
            {
                File::delete(public_path('uploads/products/thumbnails').'/'.$ofile);
            }
        }
        $product->delete();
        return redirect()->route('admin.products')->with('status','Product has been deleted succesfully!');

    }
   public function orders()
        {
            $orders = Order::with('orderItems')->orderBy('created_at', 'DESC')->paginate(12); 
            return view('admin.orders', compact('orders'));
        }

       public function order_details($order_id)
        {
            $order = Order::find($order_id);
            $orderItems = OrderItem::where('order_id', $order_id)->orderBy('id')->paginate(12);
            $transaction = Transaction::where('order_id', $order_id)->first();
            return view('admin.order-details', compact('order', 'orderItems', 'transaction'));
        }

        public function update_order_status(Request $request)
        {
            $order = Order::find($request->order_id);
            $order->status = $request->order_status;
            if($request->order_status == 'delivered')
            {
                $order->delivered_date = Carbon::now();
            }
            else if($request->order_status == 'canceled')
            {
                $order->canceled_date = Carbon::now();
            }
            $order->save();

            if($request->order_status=='delivered')
            {
                $transaction =Transaction::where('order_id',$request->order_id)->first();
                $transaction->status = 'approved';
                $transaction->save();
            }
            return back()->with("status","Status changed successfully!");
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

 }