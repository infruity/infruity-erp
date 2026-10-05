<?php

namespace Modules\Master\Http\Controllers;

use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Modules\Master\Entities\Customer;
use Modules\Master\Entities\CustomerAddress;
use Modules\Master\Entities\Region;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    use \App\Traits\HasAccessControl;

    private function whatsappValidationMessage(): string
    {
        return 'Nomor WhatsApp harus diawali 0, 62, atau +62 dan berisi 10-15 digit.';
    }

    private function isDuplicateWhatsapp(?string $phone, ?int $ignoreId = null): bool
    {
        return $phone !== null && $phone !== '' && Customer::whatsappExists($phone, $ignoreId);
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        if ($denied = $this->requireAccess('customers.index')) {
            return $denied;
        }

        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $records = Customer::query()
            ->select('id', 'name', 'code', 'whatsapp', 'email', 'address', 'gender', 'birth_of_date')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('whatsapp', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('master::customer.index', compact('records'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('customers.create')) {
            return $denied;
        }

        $data['page_plugin_js'] = [
            'assets/plugins/custom/formrepeater/formrepeater.bundle.js',
        ];
        $data['data'] = null;
        $data['customerNumber'] = Customer::getCustomerNumber(); // ['code', 'number']
        return view('master::customer.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('customers.store')) {
            return $denied;
        }

        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'customer_name' => 'required',
            'birth_of_date' => 'nullable|date|date_format:Y-m-d',
            'gender' => 'nullable|in:male,female',
            'province' => 'nullable|exists:reg_provinces,id',
            'city' => 'nullable|exists:reg_regencies,id',
            'district' => 'nullable|exists:reg_districts,id',
            'village' => 'nullable|exists:reg_villages,id',
            'address' => 'nullable',
            'phone' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if ($value !== null && $value !== '' && !Customer::isValidWhatsapp($value)) {
                        $fail($this->whatsappValidationMessage());
                    }
                },
                function ($attribute, $value, $fail) {
                    if ($this->isDuplicateWhatsapp($value)) {
                        $fail('Nomor WhatsApp sudah digunakan oleh customer lain.');
                    }
                },
            ],
            'email' => 'nullable',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();
            $customer = new Customer();
            $customer->name = $request->customer_name;
            $customer->code = Customer::getCustomerNumber();
            $customer->birth_of_date = $request->birth_of_date;
            $customer->gender = $request->gender;
            $customer->province = $request->province;
            $customer->city = $request->city;
            $customer->district = $request->district;
            $customer->village = $request->village;
            $customer->address = $request->address;
            $customer->whatsapp = Customer::sanitizeWhatsapp($request->phone);
            $customer->email = $request->email;
            $customer->created_by = Auth::user()->id_user;
            $customer->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())
                ->with('error', 'Pembuatan Customer gagal' . $e->getMessage());
        }

        return redirect('customers')->with('success', 'Pembuatan Customer berhasil');
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        if ($denied = $this->requireAccess('customers.show')) {
            return $denied;
        }

        $customer = Customer::findOrFail($id);
        $data = [
            'data' => $customer,
            'province' => Region::getProvince($customer->province),
            'city' => Region::getCity($customer->city),
            'district' => Region::getDistrict($customer->district),
            'village' => Region::getVillage($customer->village),
        ];
        // dd($data);
        return view('master::customer.show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        if ($denied = $this->requireAccess('customers.edit')) {
            return $denied;
        }


        $customer = Customer::findOrFail($id);
        $data = [
            'data' => $customer,
            'province' => Region::getProvince($customer->province),
            'city' => Region::getCity($customer->city),
            'district' => Region::getDistrict($customer->district),
            'village' => Region::getVillage($customer->village),
        ];
        $data['page_plugin_js'] = [
            'assets/plugins/custom/formrepeater/formrepeater.bundle.js',
        ];
        // dd($data);
        return view('master::customer.create', $data);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('customers.update')) {
            return $denied;
        }

        $validator = Validator::make($request->all(), [
            'customer_name' => 'required',
            'birth_of_date' => 'nullable|date|date_format:Y-m-d',
            'gender' => 'nullable|in:male,female',
            'province' => 'nullable|exists:reg_provinces,id',
            'city' => 'nullable|exists:reg_regencies,id',
            'district' => 'nullable|exists:reg_districts,id',
            'village' => 'nullable|exists:reg_villages,id',
            'address' => 'nullable',
            'phone' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if ($value !== null && $value !== '' && !Customer::isValidWhatsapp($value)) {
                        $fail($this->whatsappValidationMessage());
                    }
                },
                function ($attribute, $value, $fail) use ($id) {
                    if ($this->isDuplicateWhatsapp($value, (int) $id)) {
                        $fail('Nomor WhatsApp sudah digunakan oleh customer lain.');
                    }
                },
            ],
            'email' => 'nullable',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();
            $customer = Customer::findOrFail($id);
            $customer->name = $request->customer_name;
            $customer->birth_of_date = $request->birth_of_date;
            $customer->gender = $request->gender;
            $customer->province = $request->province;
            $customer->city = $request->city;
            $customer->district = $request->district;
            $customer->village = $request->village;
            $customer->address = $request->address;
            $customer->whatsapp = Customer::sanitizeWhatsapp($request->phone);
            $customer->email = $request->email;
            $customer->updated_by = Auth::user()->id_user;
            $customer->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())
                ->with('error', 'Update Customer gagal' . $e->getMessage());
        }

        return redirect('customers')->with('success', 'Update Customer berhasil');
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('customers.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $customer = Customer::findOrFail($id);
            $customer->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeCustomer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!Customer::isValidWhatsapp($value)) {
                        $fail($this->whatsappValidationMessage());
                    }
                },
                function ($attribute, $value, $fail) {
                    if ($this->isDuplicateWhatsapp($value)) {
                        $fail('Nomor WhatsApp sudah digunakan oleh customer lain.');
                    }
                },
            ],
            'address' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();
            $customer = new Customer();
            $customer->name = $request->name;
            $customer->code = Customer::getCustomerNumber();
            $customer->address = $request->address;
            $customer->whatsapp = Customer::sanitizeWhatsapp($request->phone);
            $customer->created_by = Auth::user()->id_user;
            $customer->save();
            DB::commit();

            return response()->json([
                'success' => true,
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'address' => $customer->address,
                    'phone' => $customer->whatsapp,
                    'whatsapp' => $customer->whatsapp,
                ],
            ]);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getAddress(Request $request)
    {
        $customerId = $request->customer_id;
        $query1 = DB::table('customer')
            ->select('id', 'address')
            ->where('id', $customerId);

        $query2 = DB::table('customer_address')
            ->select('customer_id as id', 'address')
            ->where('customer_id', $customerId);

        $result = $query1->union($query2)->get();
        return response()->json($result);
    }

    public function storeAddress(Request $request)
    {
        // dd($request->all());
        $validated = $request->validate([
            'customer_id' => 'required|exists:customer,id',
            'address' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();
            $customerAddress = new CustomerAddress();
            $customerAddress->customer_id = $request->customer_id;
            $customerAddress->address = $request->address;
            $customerAddress->save();
            DB::commit();
            return response()->json([
                'success' => true,
                'customer' => [
                    'id' => $customerAddress->id,
                    'address' => $customerAddress->address,
                ],
            ]);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function get_data(Request $request)
    {
        $query = Customer::query()->orderBy('name', 'asc');

        // Handle search from custom parameter 'q'
        $searchValue = $request->input('q');
        if (!empty($searchValue)) {
            $searchValue = trim($searchValue);
            if ($searchValue !== '') {
                $query->where('name', 'like', '%' . $searchValue . '%');
            }
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('name', function ($item) {
                return '<div class="d-flex align-items-center">
                            <div class="ms-5">
                                <a href="javascript:void(0)" class="text-gray-800 text-hover-primary fs-5 fw-bold">' . $item->name . '</a><br>
                                <span class="fs-7">' . $item->code . '</span><br>
                                <span class="badge badge-light-success fs-7">' . $item->whatsapp . '</span>
                            </div>
                        </div>';
            })
            ->addColumn('birth_of_date', function ($item) {
                if ($item->birth_of_date) {
                    try {
                        $age = \Carbon\Carbon::parse($item->birth_of_date)->diffInYears();
                        return '<span class="badge badge-light-danger">' . $age . '</span> Thn';
                    } catch (\Exception $e) {
                        // fall through
                    }
                }
                return '<span class="badge badge-light-secondary">-</span>';
            })
            ->addColumn('action', function ($item) {
                $html = '';
                if ($item->status != 'complete') {
                    $html .= '
                    <div class="dropstart">
                        <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">
                            <li>
                                <a class="dropdown-item" href="' . route('customers.show', $item->id) . '">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="' . route('customers.edit', $item->id) . '">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-primary d-flex justify-content-center" href="javascript:void(0)" onclick="deleteProduct(' . $item->id . ')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                    ';
                }
                return $html;
            })
            ->rawColumns(['name', 'action', 'birth_of_date'])
            ->make(true);
    }

    public function getCustomer(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $searchDigits = preg_replace('/\D+/', '', $search);
        $page = $request->get('page', 1); // Select2 akan mengirim page

        $phoneKeywords = [];
        if ($searchDigits !== '') {
            $phoneKeywords[] = $searchDigits;

            if (str_starts_with($searchDigits, '0')) {
                $phoneKeywords[] = '62' . substr($searchDigits, 1);
            }

            if (str_starts_with($searchDigits, '62')) {
                $phoneKeywords[] = '0' . substr($searchDigits, 2);
            }

            $phoneKeywords = array_values(array_unique(array_filter($phoneKeywords)));
        }

        $customer = Customer::when($search !== '' || !empty($phoneKeywords), function ($query) use ($search, $phoneKeywords) {
            $query->where(function ($q) use ($search, $phoneKeywords) {
                if ($search !== '') {
                    $q->where('customer.name', 'like', '%' . $search . '%');
                }

                foreach ($phoneKeywords as $keyword) {
                    $q->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(customer.whatsapp, ''), '+', ''), '-', ''), ' ', ''), '(', ''), ')', '') LIKE ?",
                        ['%' . $keyword . '%']
                    );
                }
            });
        })
            ->leftJoin('vw_customer_tier', 'vw_customer_tier.customer_id', '=', 'customer.id')
            ->select('customer.*', 'tier_name', 'tier_id', 'tier_style', 'minimal_purchase', 'voucher', 'discount')
            ->paginate(10, ['*'], 'page', $page);

        $results = [];

        foreach ($customer as $item) {
            $results[] = [
                "id" => $item->id,
                "text" => $item->name . " (" . $item->whatsapp . ")", // untuk Select2
                "name" => $item->name,
                "address" => $item->address,
                "whatsapp" => $item->whatsapp,
                "tier_name" => $item->tier_name,
                "tier_id" => $item->tier_id,
                "tier_style" => $item->tier_style,
                "minimal_purchase" => $item->minimal_purchase ?? 0,
                "voucher" => $item->voucher ?? 0,
                "discount" => $item->discount ?? 0,
            ];
        }

        return response()->json([
            "results" => $results,
            "pagination" => [
                "more" => $customer->hasMorePages() // Select2 akan load page berikutnya
            ]
        ]);
    }
}
