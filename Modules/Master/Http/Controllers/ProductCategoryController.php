<?php

namespace Modules\Master\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Master\Entities\ProductCategory;
use PhpOffice\PhpSpreadsheet\Calculation\Category;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Auth;
use Maatwebsite\Excel\Concerns\FromArray;
use \Exception;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Facades\Excel;

class ProductCategoryController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('category.index')) {
            return $denied;
        }

        $categories = ProductCategory::query()
            ->select(['id', 'name', 'description'])
            ->orderBy('name')
            ->get();

        return view('master::category.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('category.create')) {
            return $denied;
        }

        return redirect()->route('category.index', ['create' => 1]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('category.store')) {
            return $denied;
        }

        // Validasi input
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:products_category,name',
            'description' => 'nullable|string|max:1000',
        ]);

        // Simpan data ke database
        try {
            DB::beginTransaction();
            $category = new ProductCategory();
            $category->name = $validated['name'];
            $category->description = $validated['description'] ?? null;
            $category->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Kategori gagal disimpan.',
                'data' => $category
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Kategori berhasil disimpan.',
            'data' => $category
        ], 201);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('master::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        if ($denied = $this->requireAccess('category.edit')) {
            return $denied;
        }

        $category = ProductCategory::findOrFail($id);
        return response()->json($category);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('category.update')) {
            return $denied;
        }

        // Validasi input
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:products_category,name,' . $id,
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();
            $category = ProductCategory::findOrFail($id);
            $category->name = $validated['name'];
            $category->description = $validated['description'] ?? null;
            $category->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Kategori gagal diperbarui.'], 500);
        }

        return response()->json(['message' => 'Category updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('category.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $category = ProductCategory::findOrFail($id);
            $category->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getCategory(Request $request)
    {
        $search = $request->get('search');
        $data = ProductCategory::where('name', 'like', '%' . $search . '%')
            ->select('id', 'name')
            ->limit(10)
            ->get();

        return response()->json($data);
    }

    public function get_data(Request $request)
    {
        $data = ProductCategory::all();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                $name = e($row->name);

                $html = '
                <div class="dropstart">
                    <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi ' . $name . '">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">';
                if (check_access('category.edit')) {
                    $html .= '
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" onclick="editProduct(' . $row->id . ')">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                        </li>';
                }

                if (check_access('category.delete')) {
                    $html .= '
                        <li>
                            <a class="dropdown-item text-primary d-flex justify-content-center" href="javascript:void(0)" onclick="deleteProduct(' . $row->id . ')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </li>';
                }

                $html .= '
                    </ul>
                </div>';
                return $html;
            })
            ->rawColumns(['name', 'action'])
            ->make(true);
    }

    public function excel()
    {
        if ($denied = $this->requireAccess('category.export')) {
            return $denied;
        }

        $category = ProductCategory::all();

        $dataExport = [];

        // Konversi data menjadi array
        foreach ($category as $key => $value) {
            $dataExport[] = [
                'no' => $key + 1,
                'name' => $value->name,
                'description' => $value->description
            ];
        }

        // Header untuk data
        $headings = [
            'No',
            'Name',
            'Description'
        ];

        // Gunakan anonymous class untuk ekspor Excel
        return Excel::download(new class($dataExport, $headings) implements FromArray, WithHeadings, WithEvents {
            private $data;
            private $headings;

            public function __construct(array $data, array $headings)
            {
                $this->data = $data;
                $this->headings = $headings;
            }

            public function array(): array
            {
                return $this->data;
            }

            public function headings(): array
            {
                return $this->headings;
            }

            public function registerEvents(): array
            {
                return [
                    AfterSheet::class => function (AfterSheet $event) {
                        $sheet = $event->sheet->getDelegate();

                        // Styling header
                        $sheet->getStyle('A1:C1')->applyFromArray([
                            'font' => [
                                'bold' => true,
                            ],
                        ]);

                        // Tambahkan filter untuk seluruh kolom
                        $highestColumn = $sheet->getHighestColumn();
                        $sheet->setAutoFilter("A1:{$highestColumn}1");

                        foreach (range('A', $highestColumn) as $column) {
                            $sheet->getColumnDimension($column)->setAutoSize(true);
                        }
                    },
                ];
            }
        }, 'category.xlsx');
    }
}
