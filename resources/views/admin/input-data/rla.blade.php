@extends('admin.input-data.layout')

@section('title', 'Upload RLA Data')
@section('page-title', 'UPLOAD RLA DATA')

@section('content')

  <a href="{{ route('input-data.index') }}"
     class="inline-block text-[11px] text-[#8fb4d6] hover:text-accent font-semibold mb-4">&larr; Kembali ke menu Input Data</a>

  {{-- Form Upload Dokumen RLA --}}
  <div class="bg-panel rounded-lg p-5 mb-6">
    <div class="text-xs font-bold tracking-wide mb-4">UPLOAD DOKUMEN RLA BARU</div>
    <div class="text-[11px] text-slate-400 mb-4">
      Upload hasil RLA (Remaining Life Assessment) per unit. Format file yang diterima: PDF, Excel (.xlsx/.xls), CSV. Maksimal 20 MB.
    </div>

    <form method="POST" action="{{ route('input-data.rla.store') }}" enctype="multipart/form-data"
          class="flex flex-wrap gap-5 items-end">
      @csrf

      <div>
        <label class="field-label" for="unit-rla">UNIT:</label>
        <select id="unit-rla" name="unit" required class="field-input" style="min-width:140px;">
          @foreach ($units as $u)
            <option value="{{ $u }}">{{ strtoupper($u) }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="field-label" for="tanggal-rla">TANGGAL RLA:</label>
        <input id="tanggal-rla" name="tanggal" type="date" required
               class="field-input" style="min-width:160px;"
               value="{{ old('tanggal', now()->toDateString()) }}">
      </div>

      <div>
        <label class="field-label" for="file-rla">FILE DOKUMEN:</label>
        <input id="file-rla" name="file_rla" type="file" required
               accept=".pdf,.xlsx,.xls,.csv"
               class="field-input" style="min-width:280px;padding:6px 10px;">
      </div>

      <button type="submit" class="btn-gold font-bold text-xs px-8 py-2.5 rounded whitespace-nowrap">
        UPLOAD
      </button>
    </form>
  </div>

  {{-- Daftar Dokumen Terupload --}}
  <div class="bg-panel rounded-lg p-5">
    <div class="text-xs font-bold tracking-wide mb-4">
      DOKUMEN RLA TERUPLOAD ({{ $documents->count() }})
    </div>

    @if ($documents->isEmpty())
      <div class="text-xs text-slate-500 py-6 text-center">
        Belum ada dokumen RLA yang diupload.
      </div>
    @else
      <div class="overflow-x-auto">
        <table class="w-full text-[11px] border-separate border-spacing-0">
          <thead class="text-slate-400">
            <tr class="text-left">
              <th class="font-normal py-2 pr-3 border-b border-white/5">UNIT</th>
              <th class="font-normal py-2 pr-3 border-b border-white/5">TANGGAL</th>
              <th class="font-normal py-2 pr-3 border-b border-white/5">NAMA FILE</th>
              <th class="font-normal py-2 pr-3 border-b border-white/5">DIUPLOAD PADA</th>
              <th class="font-normal py-2 border-b border-white/5 text-right">AKSI</th>
            </tr>
          </thead>
          <tbody class="text-slate-200">
            @foreach ($documents as $doc)
              <tr>
                <td class="py-2 pr-3 border-b border-white/5 font-bold text-accent">
                  {{ strtoupper($doc->unit) }}
                </td>
                <td class="py-2 pr-3 border-b border-white/5 font-semibold">
                  {{ $doc->tanggal->format('d M Y') }}
                </td>
                <td class="py-2 pr-3 border-b border-white/5 max-w-[240px] truncate" title="{{ $doc->nama_file }}">
                  {{ $doc->nama_file }}
                </td>
                <td class="py-2 pr-3 border-b border-white/5 text-slate-400">
                  {{ $doc->created_at->format('d M Y H:i') }}
                </td>
                <td class="py-2 border-b border-white/5 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-2">
                    <a href="{{ route('input-data.rla.download', $doc) }}"
                       class="text-[#7fd4e8] hover:text-white font-semibold text-[10px] border border-[#7fd4e8]/40 rounded px-2.5 py-1 hover:bg-[#7fd4e8]/15">
                      DOWNLOAD
                    </a>
                    <form method="POST" action="{{ route('input-data.rla.destroy', $doc) }}"
                          onsubmit="return confirm('Hapus dokumen {{ $doc->nama_file }}?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit"
                              class="text-critical hover:text-red-300 font-semibold text-[10px] border border-critical/40 rounded px-2.5 py-1 hover:bg-critical/15">
                        HAPUS
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

@endsection