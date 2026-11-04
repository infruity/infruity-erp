<?php

namespace Modules\Pos\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Pos\Entities\OrderBook;
use Modules\Pos\Entities\OrderBookDetail;
use Modules\Master\Entities\Product;
use Modules\Master\Entities\Branch;
use Modules\Master\Entities\UserBranch;
use Modules\Pos\Entities\PosModel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Http;
use App\Helpers\SlackHelper;
use Illuminate\Support\Facades\Log;
use App\Services\FcmService;
use App\Models\UserDevice;


class OrderBookController extends Controller
{
    use \App\Traits\HasAccessControl;

    private function containsParcelKeyword(?string $note): bool
    {
        return str_contains(strtolower((string) $note), 'parcel');
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('order-book.index')) {
            return $denied;
        }

        $data['branches'] = Branch::whereIn('id', UserBranch::getUserBranch())->get();
        return view('pos::order-book.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('order-book.create')) {
            return $denied;
        }

        $data['alpinejs'] = true;
        $data['invoice_number'] = OrderBook::getOrderNumber();
        return view('pos::order-book.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('order-book.store')) {
            return $denied;
        }

        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('pos::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        if ($denied = $this->requireAccess('order-book.edit')) {
            return $denied;
        }

        $data['alpinejs'] = true;
        $data['data'] = OrderBook::with('customer', 'details', 'details.product', 'details.product.unit', 'pos')->findOrFail($id);

        if ($data['data']->status == 'done' && $data['data']->pos) {
            return redirect()->back()->with('error', 'Transaksi yang sudah selesai dan memiliki POS tidak dapat diedit.');
        }

        $data['invoice_number'] = $data['data']->invoice_number;
        return view('pos::order-book.create', $data);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('order-book.update')) {
            return $denied;
        }

        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('order-book.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $pos = OrderBook::with('pos')->findOrFail($id);

            if ($pos->status == 'done' && $pos->pos) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi yang sudah selesai dan memiliki POS tidak dapat dihapus.'
                ], 403);
            }

            $pos->delete();
            OrderBookDetail::where('order_book_id', $id)->delete();

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

    public function saveTransaction(Request $request)
    {
        if ($denied = $this->requireAccess('order-book.save-transaction')) {
            return $denied;
        }

        $data = $request->validate([
            'branch_id' => 'required|exists:branch,id',
            'invoice_number' => 'required',
            'customer_id' => 'nullable',
            'date' => 'required|date',
            'status' => 'nullable|in:draft,posting,process,done',
            'note' => 'required',
        ]);

        try {
            $isParcelOrder = $this->containsParcelKeyword($data['note']);
            $updateAt = null;
            $userId = Auth::id();
            DB::beginTransaction();
            $cek = OrderBook::where('invoice_number', $data['invoice_number'])->first();
            if ($cek) {
                $pos = OrderBook::find($cek->id);
                OrderBookDetail::where('order_book_id', $cek->id)->delete();
                $pos->delete();
                $updateAt = date('Y-m-d H:i:s');
            }
            $orderBook = OrderBook::create([
                'branch_id' => $data['branch_id'],
                'invoice_number' => $data['invoice_number'],
                'customer_id' => $data['customer_id'],
                'date' => $data['date'],
                'status' => $data['status'],
                'note' => $data['note'],
                'created_by' => $userId,
                'updated_at' => $updateAt,
            ]);
            $orderBook->created_at = now();
            $orderBook->save();
            // $products = Product::where('price', '>', 0)->select('id', 'name', 'product_unit')->get();
            // $dataItem = $this->sendToAi($products, $data['note']);
            // // dd($dataItem);
            // Log::info('AI Response: ' . json_encode($dataItem, JSON_UNESCAPED_SLASHES));

            // if (!isset($dataItem['items']) || !is_array($dataItem['items'])) {
            //     return response()->json(['error' => 'Respons AI tidak valid.'], 400);
            // }

            // foreach ($dataItem['items'] as $item) {
            //     if (!isset($item['product_id']) || !isset($item['quantity']))
            //         continue;

            //     // Validasi: pastikan product_id benar-benar ada di database
            //     $productId = (int) $item['product_id'];
            //     if (!$products->contains('id', $productId)) {
            //         continue; // skip jika ID tidak valid
            //     }

            //     OrderBookDetail::create([
            //         'order_book_id' => $orderBook->id,
            //         'product_id' => $productId,
            //         'quantity' => (int) $item['quantity']
            //     ]);
            // }
            DB::commit();
            $tokens = UserDevice::join('user_branch', 'user_devices.user_id', '=', 'user_branch.user_id')
                ->whereNotNull('user_devices.fcm_token')
                ->where('user_branch.branch_id', $data['branch_id'])
                ->pluck('fcm_token')
                ->unique()
                ->values()
                ->toArray();
            $title = "Ada Pesanan Masuk 🍉";
            $body = "List Pesanan : \n" . $data['note'] . "\n\n Semangat Bekerja!";
            // Push is sent after the response so saving does not wait on Firebase.
            (new FcmService())->sendAfterResponse($tokens, $title, $body);
            // dd($data);
            DB::disconnect();
            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil disimpan',
                'order_book_id' => $orderBook->id,
                'is_parcel' => $isParcelOrder
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            DB::disconnect();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan transaksi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function order($id)
    {
        if ($denied = $this->requireAccess('order-book.order')) {
            return $denied;
        }

        $orderBook = OrderBook::with('customer', 'details', 'details.product', 'details.product.unit', 'branch', 'pos')->findOrFail($id);
        
        if ($orderBook->status == 'done' && $orderBook->pos) {
            return redirect()->back()->with('error', 'Transaksi yang sudah selesai dan memiliki POS tidak dapat diproses lagi.');
        }

        $data['order'] = $orderBook;
        $data['alpinejs'] = true;
        $data['data'] = $orderBook;
        $data['detail'] = $orderBook->details ?? [];
        $data['invoice_number'] = $orderBook->invoice_number;
        $data['is_parcel_order'] = $this->containsParcelKeyword($orderBook->note ?? '');
        return view('pos::order-book.order', $data);
    }

    public function sendToAi($products, $note)
    {
        // 1. Validasi produk
        if ($products->isEmpty()) {
            return [
                'error' => 'Tidak ada produk di database.',
                'items' => []
            ];
        }

        // 2. Siapkan data
        $productJson = $products->toJson(JSON_UNESCAPED_UNICODE);

        // $systemPrompt = "You are an intelligent order matcher for an Indonesian grocery store.

        // AVAILABLE PRODUCTS (in valid JSON format):
        // {$productJson}

        // INSTRUCTIONS:
        // - The user will send a shopping list in informal Indonesian, possibly with typos, misspellings, or shorthand (e.g., 'strawberi', 'jruk', 'smngka').
        // - For each line:
        // • Compare the item name to the 'name' field in the JSON list.
        // • Accept close matches based on sound, spelling, or common Indonesian variations.
        //     Examples:
        //     'strawberi', 'stroberi', 'strawbery' → match 'Strawberry'
        //     'jruk', 'jerok' → match 'Jeruk'
        //     'semangkaa', 'smngka' → match 'Semangka'
        // • Ignore differences in capitalization, extra spaces, or minor character substitutions.
        // • ONLY match if the name is reasonably similar.
        // • If NO product is similar (e.g., 'kelengkeng' when not in the list), SKIP the item completely.
        // - Extract for each matched item:
        // • product_id = the 'id' from the matched product
        // • quantity = integer (default to 1 if not specified)
        // • unit = the 'unit' from the matched product (unless user explicitly states a different unit)
        // - NEVER invent a product_id.
        // - NEVER match to a product just because it exists — it must be semantically or phonetically similar.

        // OUTPUT FORMAT:
        // - Return ONLY a valid JSON object with a top-level key \"items\".
        // - Each item must be: {\"product_id\": integer, \"quantity\": integer, \"unit\": string}
        // - Example: {\"items\":[{\"product_id\":1,\"quantity\":2,\"unit\":\"Kg\"}]}

        // CRITICAL:
        // - NO explanations.
        // - NO markdown.
        // - NO text before or after the JSON.
        // - If no items match, return: {\"items\":[]}";

        $systemPrompt = "
            You are an order matcher.
            Output must be valid JSON.

            PRODUCTS (JSON):
            {$productJson}

            RULES:
            - User sends Indonesian shopping items, possibly with typos.
            - Match each line to a product name using fuzzy similarity.
            - Only match if clearly similar; otherwise skip.
            - Extract product_id, quantity (default 1), unit.
            - Never invent product_id or force-match.

            OUTPUT:
            Return ONLY valid JSON:
            {\"items\":[{\"product_id\":int,\"quantity\":int,\"unit\":\"string\"}]}

            If no match:
            {\"items\":[]}

            No explanation. No markdown. No extra text.
            ";

        // 3. Kirim ke Groq
        try {
            // HAPUS SPASI DI URL! (ini sering jadi penyebab 404)
            $url = 'https://api.groq.com/openai/v1/chat/completions';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
                'Content-Type'  => 'application/json',
            ])
                ->withoutVerifying()
                ->timeout(30)
                ->post($url, [
                    // 'model' => 'llama-3.3-70b-versatile',
                    'model' => 'llama-3.1-8b-instant',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $note],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.0,
                ]);

            // 4. Cek apakah request gagal (HTTP error)
            if (!$response->successful()) {
                $errorMessage = 'Groq API error: ' . $response->status();
                if ($response->body()) {
                    $errorMessage .= ' - ' . $response->body();
                }
                return [
                    'error' => $errorMessage,
                    'items' => []
                ];
            }

            // 5. Ambil konten AI
            $aiContent = $response->json()['choices'][0]['message']['content'] ?? '{}';

            // 6. Decode JSON
            $data = json_decode($aiContent, true);

            // 7. Validasi format
            if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
                return [
                    'error' => 'Respons AI tidak valid: format JSON salah.',
                    'items' => []
                ];
            }

            return $data; // ✅ Hanya array, tidak pernah JsonResponse

        } catch (\Exception $e) {
            return [
                'error' => 'Exception: ' . $e->getMessage(),
                'items' => []
            ];
        }
    }

    public function get_data(Request $request)
    {
        $query = OrderBook::with('customer', 'pos', 'branch', 'user:id_user,nm_user')->whereIn('branch_id', UserBranch::getUserBranch());
        if ($request->has('status_filter') && $request->status_filter !== 'all') {
            if ($request->status_filter === 'process') {
                $query = $query->whereIn('status', ['process', 'posting']);
            } else {
                $query = $query->where('status', $request->status_filter);
            }
        }
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }
        if ($request->has('cabang_filter') && $request->cabang_filter !== 'all') {
            $query->where('branch_id', $request->cabang_filter);
        }
        $data = $query->orderBy('id', 'DESC');
        // dd($data);
        return DataTables::of($data)
            ->filter(function ($q) use ($request) {
                $search = trim($request->input('search.value'));

                if (! empty($search)) {
                    $q->where(function ($sub) use ($search) {
                        $sub->whereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'LIKE', "%{$search}%")
                                ->orWhere('whatsapp', 'LIKE', "%{$search}%");
                        });
                        $possibleDates = [];
                        $formats       = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd M Y', 'd F Y', 'd/m/Y H:i', 'd-m-Y H:i'];
                        foreach ($formats as $format) {
                            $date = \DateTime::createFromFormat($format, $search);
                            if ($date) {
                                $possibleDates[] = $date->format('Y-m-d');
                                break;
                            }
                        }
                        if (! empty($possibleDates)) {
                            foreach ($possibleDates as $dateStr) {
                                $sub->orWhereDate('date', $dateStr);
                            }
                        }
                        $sub->orWhere('status', 'LIKE', "%{$search}%");
                        $sub->orWhere('invoice_number', 'LIKE', "%{$search}%");
                        $sub->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('nm_user', 'LIKE', "%{$search}%");
                        });
                    });
                }
            }, true)
            ->addIndexColumn()
            ->addColumn('created_by_name', fn ($item) => $item->user?->nm_user)
            ->addColumn('name', function ($item) {
                $warningIcon = $item->updated_at ? '<span class="text-warning mx-1" title="Terakhir diedit" style="font-size: 0.75rem;">⚠️</span>' : '';
                $parcelBadge = $this->containsParcelKeyword($item->note ?? '')
                    ? '<span class="badge badge-light-success p-1 ms-2" title="Ada Parcel" style="font-size: 1rem;"><i class="bi bi-box text-success" style="font-size: 1rem;"></i></span>'
                    : '';

                $customerName = 'Pelanggan Umum';
                if (isset($item->customer->name)) {
                    $waLast4 = !empty($item->customer->whatsapp) ? ' (' . substr($item->customer->whatsapp, -4) . ')' : '';
                    $customerName = $item->customer->name . $waLast4;
                }

                $posLink = '';
                if ($item->status == 'done' && $item->pos) {
                    $posLink = '<a href="'.route('pos.show', $item->pos->id).'" class="badge badge-info mt-1 px-2 py-1" style="width: fit-content;" title="Lihat POS"><i class="bi bi-box-arrow-up-right text-white me-1"></i> POS</a>';
                }

                $html = '<div class="d-flex flex-column">';
                
                // Row 1: Customer Name + Icons
                $html .= '<div class="d-flex align-items-center mb-1">';
                $html .= '<a href="javascript:void(0)" class="text-gray-800 text-hover-primary fs-6 fw-bold">' . $customerName . '</a>';
                $html .= $parcelBadge;
                $html .= '</div>';

                // Row 2: Metadata (Edit Date and Branch)
                $html .= '<div class="d-flex align-items-center flex-wrap gap-2">';
                if ($item->updated_at != null) {
                    $html .= '<span class="text-muted" style="font-size: 0.7rem;">' . $warningIcon . ' Diedit: ' . $item->updated_at->format('d/m/y H:i') . '</span>';
                }
                $html .= '<span class="badge badge-light-primary px-2 py-1">' . e($item->branch->name) . '</span>';
                $html .= '</div>';

                if ($posLink != '') {
                    $html .= $posLink;
                }

                $html .= '</div>';
                return $html;
            })
            ->addColumn('date', function ($item) {
                $html = '<span class="text-muted d-block fs-8">' . date('d M Y H:i', strtotime($item->created_at)) . '</span>';
                if ($item->status == 'draft') {
                    $html .= '<span class="badge badge-light-danger">Draft</span>';
                } else if ($item->status == 'done') {
                    $html .= '<span class="badge badge-light-success">Selesai</span>';
                } else if ($item->status == 'process') {
                    $html .= '<span class="badge badge-light-warning">Proses</span>';
                } else {
                    $html .= '<span class="badge badge-light-warning">' . $item->status . '</span>';
                }
                return $html;
            })
            ->editColumn('status', function ($item) {
                $html = '';
                if ($item->status == 'pending') {
                    $html .= '<span class="badge badge-light-danger">Proses</span>';
                } else if ($item->status == 'done') {
                    $html .= '<span class="badge badge-light-success">Selesai</span>';
                } else {
                    $html .= '<span class="badge badge-light-warning">' . $item->status . '</span>';
                }
                return $html;
            })
            ->addColumn('action', function ($item) {
                if ($item->status == 'done' && $item->pos) {
                    return '<span class="badge badge-light-secondary"><i class="bi bi-lock-fill"></i> Locked</span>';
                }

                $html = '';
                $html .= '
                    <div class="dropstart">
                        <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">';
                $isRecent = false;
                $timeRef = isset($item->updated_at) ? $item->updated_at : (isset($item->created_at) ? $item->created_at : null);
                if ($timeRef && \Carbon\Carbon::parse($timeRef)->diffInMinutes(now()) <= 30) {
                    $isRecent = true;
                }

                if (in_array($item->status, ['temp', 'draft']) || $isRecent) {
                    $html .= '
                            <li>
                                <a class="dropdown-item" href="' . route('order-book.edit', $item->id) . '">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </li>';
                }
                $html .= '
                            <li>
                                <a class="dropdown-item" href="' . route('order-book.order', $item->id) . '" tooltip="Proses Pesanan" title="Proses Pesanan">
                                    <i class="bi bi-box"></i>
                                </a>
                            </li>';
                $html .= '
                            <li>
                                <a class="dropdown-item text-primary d-flex justify-content-center" href="javascript:void(0)" onclick="deleteProduct(' . $item->id . ')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </li>';

                $html .= '
                        </ul>
                    </div>
                    ';
                return $html;
            })
            ->rawColumns(['name', 'action', 'date'])
            // branch is eager-loaded only for the badge; keep it out of the JSON as before.
            ->removeColumn('branch')
            // The creator is eager loaded only for the name column and search; keep the record out of the JSON.
            ->removeColumn('user')
            ->make(true);
    }
}
