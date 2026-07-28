@extends('admin.input-data.layout')

@section('title', 'Upload Gambar Boiler')
@section('page-title', 'UPLOAD GAMBAR BOILER 3D STRUCTURE')

@section('content')

  <a href="{{ route('input-data.index') }}"
     class="inline-block text-[11px] text-[#8fb4d6] hover:text-accent font-semibold mb-4">&larr; Kembali ke menu Input Data</a>

  <div class="bg-panel rounded-lg p-5 mb-6">
    <div class="text-xs font-bold tracking-wide mb-4">UPLOAD GAMBAR BOILER BARU</div>
    <div class="text-[11px] text-slate-400 mb-4">
      Upload gambar struktur 3D boiler per unit. Format yang diterima: JPG, JPEG, PNG, GIF, WEBP, PDF. Maksimal 20 MB.
      Gambar yang diupload akan muncul di panel BOILER 3D STRUCTURE pada halaman Tube Mapping.
    </div>

    <form method="POST" action="{{ route('input-data.image.store') }}" enctype="multipart/form-data"
          class="flex flex-wrap gap-5 items-end">
      @csrf

      <div>
        <label class="field-label" for="unit-image">UNIT:</label>
        <select id="unit-image" name="unit" required class="field-input" style="min-width:140px;">
          @foreach ($units as $u)
            <option value="{{ $u }}" @selected($u === $unit)>{{ strtoupper($u) }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="field-label" for="file-image">FILE GAMBAR:</label>
        <input id="file-image" name="file_image" type="file" required
               accept=".jpg,.jpeg,.png,.gif,.webp,.pdf"
               class="field-input" style="min-width:280px;padding:6px 10px;">
      </div>

      <button type="submit" class="btn-gold font-bold text-xs px-8 py-2.5 rounded whitespace-nowrap">
        UPLOAD
      </button>
    </form>
  </div>

  <div class="bg-panel rounded-lg p-5">
    <div class="flex items-center gap-6 mb-4 flex-wrap">
      <div class="text-xs font-bold tracking-wide">
        GAMBAR TERUPLOAD
        @foreach($units as $u)
          <a href="?unit={{ $u }}"
             class="ml-3 px-3 py-1 rounded text-[10px] font-semibold border {{ $u === $unit ? 'bg-accent/20 text-accent border-accent' : 'border-white/15 text-slate-400 hover:text-white' }}">
            {{ strtoupper($u) }}
          </a>
        @endforeach
      </div>
    </div>

    <div class="text-[11px] text-slate-400 mb-3">
      Menampilkan gambar untuk <span class="text-slate-200 font-bold">{{ strtoupper($unit) }}</span> ({{ $images->count() }} gambar).
    </div>

    @if ($images->isEmpty())
      <div class="text-xs text-slate-500 py-6 text-center">
        Belum ada gambar boiler untuk {{ strtoupper($unit) }}.
      </div>
    @else
      <div class="overflow-x-auto">
        <table class="w-full text-[11px] border-separate border-spacing-0">
          <thead class="text-slate-400">
            <tr class="text-left">
              <th class="font-normal py-2 pr-3 border-b border-white/5">UNIT</th>
              <th class="font-normal py-2 pr-3 border-b border-white/5">NAMA FILE</th>
              <th class="font-normal py-2 pr-3 border-b border-white/5">PREVIEW</th>
              <th class="font-normal py-2 pr-3 border-b border-white/5">DIUPLOAD PADA</th>
              <th class="font-normal py-2 border-b border-white/5 text-right">AKSI</th>
            </tr>
          </thead>
          <tbody class="text-slate-200">
            @foreach ($images as $img)
              <tr>
                <td class="py-2 pr-3 border-b border-white/5 font-bold text-accent">
                  {{ strtoupper($img->unit) }}
                </td>
                <td class="py-2 pr-3 border-b border-white/5 font-semibold max-w-[240px] truncate" title="{{ $img->nama_file }}">
                  {{ $img->nama_file }}
                </td>
                <td class="py-2 pr-3 border-b border-white/5">
                  @php $ext = strtolower(pathinfo($img->nama_file, PATHINFO_EXTENSION)); @endphp
                  @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
                    <img src="{{ asset('storage/' . $img->path) }}" alt="Preview" class="h-8 rounded border border-white/10 object-cover" style="max-width:80px;">
                  @else
                    <span class="text-[10px] text-slate-500">PDF</span>
                  @endif
                </td>
                <td class="py-2 pr-3 border-b border-white/5 text-slate-400">
                  {{ $img->created_at->format('d M Y H:i') }}
                </td>
                <td class="py-2 border-b border-white/5 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-2">
                    <a href="{{ asset('storage/' . $img->path) }}" target="_blank"
                       class="text-[#7fd4e8] hover:text-white font-semibold text-[10px] border border-[#7fd4e8]/40 rounded px-2.5 py-1 hover:bg-[#7fd4e8]/15">
                      LIHAT
                    </a>
                    <form method="POST" action="{{ route('input-data.image.destroy', $img) }}"
                          onsubmit="return confirm('Hapus gambar {{ $img->nama_file }}?')">
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

  {{-- Preview gambar terbaru per unit --}}
  @if($allUnitImages->isNotEmpty())
    <div class="bg-panel rounded-lg p-5 mt-6">
      <div class="text-xs font-bold tracking-wide mb-4">RINGKASAN GAMBAR PER UNIT</div>
      <div class="grid md:grid-cols-3 gap-4">
        @foreach($allUnitImages as $u => $unitImgs)
          @php $latest = $unitImgs->first(); @endphp
          <div class="bg-[#0d1830] border border-white/10 rounded p-3">
            <div class="text-[11px] font-bold text-accent mb-2">{{ strtoupper($u) }}</div>
            @php $ext = strtolower(pathinfo($latest->nama_file, PATHINFO_EXTENSION)); @endphp
            @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
              <img src="{{ asset('storage/' . $latest->path) }}" alt="{{ $u }}" class="w-full rounded border border-white/5 object-cover" style="max-height:200px;">
            @else
              <div class="rounded bg-[#0a1523] flex items-center justify-center text-[10px] text-slate-500" style="height:120px;">
                PDF Document
              </div>
            @endif
            <div class="text-[10px] text-slate-400 mt-2 truncate">{{ $latest->nama_file }}</div>
          </div>
        @endforeach
      </div>
    </div>
  @endif

@endsection