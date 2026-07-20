@extends('template.root')
@section('content')
    @foreach ($listPayment as $data)
        <div class="container d-flex flex-column justify-content-center align-items-center text-center mb-3">
            <div class="card shadow p-4 rounded-4">
                <div class="text-success mb-3">
                    <i class="bi bi-check-circle-fill" style="font-size: 4rem;"></i>
                </div>
                <h2 class="mb-3">Pembayaran Berhasil!</h2>
                <p class="mb-4">Terima kasih, pembayaran Anda telah berhasil diproses.</p>
                <p class="mb-4">{{ dateindo($data->date) }}</p>

                <div class="text-start w-100 mb-4">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <strong>Metode Pembayaran:</strong>
                        <span class="text-muted">
                            @foreach (json_decode($data->payment_method) as $method)
                                {{ strtoupper($method) }} @if (!$loop->last)
                                    ,
                                @endif
                            @endforeach
                        </span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <strong>Total Tagihan:</strong>
                        <span class="text-muted">{{ toNumber($data->pos->total ?? 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <strong>Total Dibayarkan:</strong>
                        <span class="text-muted">{{ toNumber($data->total ?? 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <strong>Voucher:</strong>
                        <span class="text-muted">{{ toNumber($data->pos->voucher ?? 0) }}</span>
                    </div>
                    @if (isset($data->return) && $data->return > 0)
                        <div class="d-flex justify-content-between py-1">
                            <strong>Kembalian:</strong>
                            <span class="text-muted">{{ toNumber($data->return ?? 0) }}</span>
                        </div>
                    @else
                        <div class="d-flex justify-content-between py-1">
                            <strong>Kurang:</strong>
                            <span
                                class="text-muted">{{ toNumber(($data->pos->total ?? 0) - ($data->pos->paid ?? 0) - ($data->pos->voucher ?? 0)) }}</span>
                        </div>
                    @endif
                </div>

                <a href="{{ url('pos/printNota') . '/' . $data->id }}" class="btn btn-primary rounded-pill w-100">
                    <i class="fa fa-print"></i> Cetak Struk
                </a>
                @php
                    $url = isset($data->uuid)
                        ? url('/cek-nota/' . $data->uuid)
                        : url('/cek-nota/draft/' . $data->pos->uuid);
                    $message = urlencode("Halo, berikut bukti transaksi Anda:\n{$url}");
                    $phone = $data->pos->customer->whatsapp ?? '';
                    $phone = preg_replace('/[^0-9]/', '', $phone);
                    if (str_starts_with($phone, '0')) {
                        $phone = '62' . substr($phone, 1);
                    } elseif (str_starts_with($phone, '8')) {
                        $phone = '62' . $phone;
                    }
                    $waUrl = "https://wa.me/{$phone}?text={$message}";
                @endphp
                <a href="{{ $waUrl }}" class="btn btn-success rounded-pill w-100 mt-2">
                    <i class="fa fa-comment"></i> Kirim ke WhatsApp
                </a>
            </div>
        </div>
    @endforeach
    <a href="{{ route('pos.index') }}" class="btn btn-warning rounded-pill w-100 mb-2">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
@endsection
