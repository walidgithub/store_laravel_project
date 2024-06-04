<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!Gate::allows('categories.view')) {
            abort(403);
        }

        $request = request();

        // SELECT a.*, b.name as parent_name
        // FROM categories as a
        // LEFT JOIN categories as b ON b.id = a.parent_id

        $categories = Category::with('parent')
        /*leftJoin('categories as parents', 'parents.id', '=', 'categories.parent_id')
        ->select([
        'categories.*',
        'parents.name as parent_name'
        ])*/
        //->select('categories.*')
        //->selectRaw('(SELECT COUNT(*) FROM products WHERE AND status = 'active' AND category_id = categories.id) as products_count')
        //->addSelect(DB::raw('(SELECT COUNT(*) FROM products WHERE category_id = categories.id) as products_count'))
            ->withCount([
                'products as products_number' => function ($query) {
                    $query->where('status', '=', 'active');
                },
            ])
            ->filter($request->query())
            ->orderBy('categories.name')
            ->paginate(); // Return Collection object

        return view('dashboard.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (Gate::denies('categories.create')) {
            abort(403);
        }

        $parents = Category::all();
        $category = new Category();
        return view('dashboard.categories.create', compact('category', 'parents'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // $request->input('name');
        // $request->post('name');
        // $request->query('name');
        // $request->get('name');
        // $request->name;

        // $request->all();
        // $request->only(['name','age']);
        // $request->except(['name','age']);

        Gate::authorize('categories.create');

        $clean_data = $request->validate(Category::rules(), [
            'required' => 'This field (:attribute) is required',
            'name.unique' => 'This name is already exists!',
        ]);

        // Request merge
        // merge field not found in $request
        // create slug from 'name' field using Str class 
        // slug used to create parts of a URL that identify a page with a human readable slug
        $request->merge([
            'slug' => Str::slug($request->post('name')),
        ]);

        $data = $request->except('image');
        $data['image'] = $this->uploadImgae($request);

        // Mass assignment
        $category = Category::create($data);

        // PRG // to redirect to another view with message stored in session
        return Redirect::route('dashboard.categories.index')
            ->with('success', 'Category created!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        if (Gate::denies('categories.view')) {
            abort(403);
        }
        return view('dashboard.categories.show', [
            'category' => $category,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        Gate::authorize('categories.update');

        try {
            $category = Category::findOrFail($id);
        } catch (Exception $e) {
            return redirect()->route('dashboard.categories.index')
                ->with('info', 'Record not found!');
                // ->with to send data to view with key 'info'
        }

        // SELECT * FROM categories WHERE id <> $id
        // AND (parent_id IS NULL OR parent_id <> $id)
        $parents = Category::where('id', '<>', $id)
        // use here to use parameter 'id'
            ->where(function ($query) use ($id) {
                // check if field is null
                $query->whereNull('parent_id')
                    // Or
                    ->orWhere('parent_id', '<>', $id);
                    // And
                    // ->where('parent_id', '<>', $id);
            })
            ->get();
            // to check the sql query we can use ->dd() instead of ->get()

        return view('dashboard.categories.edit', compact('category', 'parents'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(CategoryRequest $request, $id)
    {
        //$request->validate(Category::rules($id));

        $category = Category::findOrFail($id);

        $old_image = $category->image;

        $data = $request->except('image');
        $new_image = $this->uploadImgae($request);
        if ($new_image) {
            $data['image'] = $new_image;
        }

        // update will update in database but fill will update the object only so we need to call save after that
        $category->update($data);
        //$category->fill($request->all())->save();

        if ($old_image && $new_image) {
            Storage::disk('public')->delete($old_image);
        }

        return Redirect::route('dashboard.categories.index')
            ->with('success', 'Category updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Category $category)
    {
        Gate::authorize('categories.delete');

        // findOrFail (if id not found will return 404 page)
        //$category = Category::findOrFail($id);
        $category->delete();

        //Category::where('id', '=', $id)->delete();
        //Category::destroy($id);

        return Redirect::route('dashboard.categories.index')
            ->with('success', 'Category deleted!');
    }

    protected function uploadImgae(Request $request)
    {
        // check if request has file
        if (!$request->hasFile('image')) {
            return;
        }

        $file = $request->file('image'); // UploadedFile Object

        // we will store image in uploads folder in public folder (public is the disk name) and return this path to store in database
        // you can control in file path and create new disk (local or s3 (amazon)) from config/filesystems.php
        // $path = $file->storeAs('uploads', 'image.jpg' [ // to store with same name and will override
        $path = $file->store('uploads', [ // will create file with random name
            'disk' => 'public',
        ]);
        return $path;
    }

    public function trash()
    {
        $categories = Category::onlyTrashed()->paginate();
        return view('dashboard.categories.trash', compact('categories'));
    }

    public function restore(Request $request, $id)
    {
        $category = Category::onlyTrashed()->findOrFail($id);
        $category->restore();

        return redirect()->route('dashboard.categories.trash')
            ->with('succes', 'Category restored!');
    }

    public function forceDelete($id)
    {
        $category = Category::onlyTrashed()->findOrFail($id);
        $category->forceDelete();

        // delete old image from storage
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        return redirect()->route('dashboard.categories.trash')
            ->with('succes', 'Category deleted forever!');
    }
}