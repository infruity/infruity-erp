@extends('template.root')
@section('title', 'Permohonan Saya')
@section('content')
<x-erp.workspace title="Permohonan Saya" subtitle="Tinjau status dan riwayat permohonan."><x-slot:toolbar><a href="{{ url('dashboard') }}" class="erp-action erp-action--primary"><i class="ph ph-plus" aria-hidden="true"></i>Buat Permohonan</a></x-slot:toolbar><div class="erp-application-list">@forelse($data as $item)<article class="erp-application-record"><header><div><h2>{{ $item->type == 'permohonan-sk' ? 'Permohonan Surat Keterangan' : 'Permohonan Pengurangan IPT' }}</h2><p>{{ $item->no_permohonan }}</p></div><details><summary class="erp-action">Aksi</summary><div class="erp-application-actions" aria-labelledby="activityButton">
                                        <a class="dropdown-item"
                                            href="{{ $item->type == 'permohonan-sk' ? url('permohonan') . '/' . $item->id . '/detail' : url('ipt-pengurangan') . '/' . $item->id . '/detail' }}"><i
                                                class="fa-solid fa-eye"></i> Show</a>
                                        <a class="dropdown-item"
                                            href="{{ $item->type == 'permohonan-sk' ? url('permohonan') . '/' . $item->id . '/history' : url('ipt-pengurangan') . '/' . $item->id . '/history' }}"><i
                                                class="fa-solid fa-history"></i> History</a>
                                        @if (in_array($item->id_status, [99, 100]))
                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ $item->type == 'permohonan-sk' ? url('permohonan') . '/' . $item->id . '/edit' : url('ipt-pengurangan') . '/' . $item->id . '/edit' }}"><i
                                                        class="fa-solid fa-edit"></i>
                                                    Edit </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item btn-delete"
                                                    data-url="{{ $item->type == 'permohonan-sk' ? url('permohonan') . '/' . $item->id : url('ipt-pengurangan') . '/' . $item->id }}"
                                                    data-kode="{{ $item->no_permohonan }}">
                                                    <i class="fas fa-trash-alt"></i>
                                                    Hapus</a>
                                            </li>
                                        @endif
                                        @if (check_access('cetak-permohonan'))
                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ $item->type == 'permohonan-sk' ? url('permohonan') . '/' . $item->id . '/cetak-permohonan' : url('ipt-pengurangan') . '/' . $item->id . '/cetak-permohonan' }}"><i
                                                        class="fa-solid fa-print"></i>
                                                    Cetak Permohonan </a>
                                            </li>
                                        @endif
                                        @if (check_access('cetak-formulir'))
                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ $item->type == 'permohonan-sk' ? url('permohonan') . '/' . $item->id . '/cetak-formulir' : url('ipt-pengurangan') . '/' . $item->id . '/cetak-formulir' }}"><i
                                                        class="fa-solid fa-print"></i>
                                                    Cetak Formulir </a>
                                            </li>
                                        @endif
                                        @if ($item->id_status >= 11 && $item->id_status < 99)
                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ url('surat') . '/' . $item->id_surat . '/cetak-surat' }}"><i
                                                        class="fa-solid fa-print"></i>
                                                    Cetak Surat </a>
                                            </li>
                                        @endif
                                    </div></details></header><dl class="erp-detail-grid"><div><dt>Jenis layanan</dt><dd>{{ ucwords(strtolower($item->nm_layanan)) }}</dd></div><div><dt>Status saat ini</dt><dd><span class="badge badge-light-{{ $item->class_color }}">{{ ucwords(strtolower($item->nama_status)) }}</span></dd></div><div><dt>Langkah selanjutnya</dt><dd><span class="badge badge-light-{{ $item->next_status_class_color }}">{{ ucwords(strtolower($item->next_status_name)) }}</span></dd></div></dl></article>@empty<div class="erp-record-empty"><i class="ph ph-files" aria-hidden="true"></i><p>Belum ada permohonan.</p></div>@endforelse</div><x-slot:footer>{{ $data->links() }}</x-slot:footer></x-erp.workspace>
@endsection
