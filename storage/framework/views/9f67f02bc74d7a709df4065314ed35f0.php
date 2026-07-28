

<?php $__env->startSection('title', 'Input Data'); ?>
<?php $__env->startSection('page-title', 'INPUT DATA'); ?>

<?php $__env->startSection('content'); ?>

  <div class="text-[11px] text-slate-400 mb-4">
    Pilih salah satu menu di bawah. Unit dan area dipilih di dalam masing-masing menu.
  </div>

  <div class="grid md:grid-cols-3 gap-5">

    <a href="<?php echo e(route('input-data.pipa')); ?>"
       class="bg-panel rounded-lg p-5 block hover:brightness-125 transition">
      <div class="text-accent text-[15px] font-bold tracking-wide mb-2">ADD / DELETE PIPA</div>
      <div class="text-xs text-slate-400 leading-relaxed">
        Menambah atau mengurangi jumlah pipa yang tersedia pada sebuah area.
      </div>
      <div class="text-[11px] text-[#8fb4d6] mt-3 font-semibold">BUKA &rarr;</div>
    </a>

    <a href="<?php echo e(route('input-data.titik')); ?>"
       class="bg-panel rounded-lg p-5 block hover:brightness-125 transition">
      <div class="text-accent text-[15px] font-bold tracking-wide mb-2">ADD / DELETE TITIK</div>
      <div class="text-xs text-slate-400 leading-relaxed">
        Menambah atau menghapus titik ukur pada sebuah area &mdash; berlaku untuk semua pipanya.
        Bawaan 4 titik (A&ndash;D); bisa ditambah (E, F, ...) atau dikurangi.
      </div>
      <div class="text-[11px] text-[#8fb4d6] mt-3 font-semibold">BUKA &rarr;</div>
    </a>

    <a href="<?php echo e(route('input-data.ukur')); ?>"
       class="bg-panel rounded-lg p-5 block hover:brightness-125 transition">
      <div class="text-accent text-[15px] font-bold tracking-wide mb-2">INPUT DATA PENGUKURAN</div>
      <div class="text-xs text-slate-400 leading-relaxed">
        Mengisi nilai awal (ketebalan awal pipa) dan hasil pengetesan ketebalan pada tiap titik ukur pipa.
      </div>
      <div class="text-[11px] text-[#8fb4d6] mt-3 font-semibold">BUKA &rarr;</div>
    </a>

    <a href="<?php echo e(route('input-data.rla')); ?>"
       class="bg-panel rounded-lg p-5 block hover:brightness-125 transition">
      <div class="text-accent text-[15px] font-bold tracking-wide mb-2">UPLOAD RLA DATA</div>
      <div class="text-xs text-slate-400 leading-relaxed">
        Upload dokumen hasil RLA (Remaining Life Assessment) per unit &mdash; PDF, Excel, atau CSV. Admin tinggal upload file, sistem menyimpan beserta tanggal dan unit.
      </div>
      <div class="text-[11px] text-[#8fb4d6] mt-3 font-semibold">BUKA &rarr;</div>
    </a>

  </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.input-data.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Data D\ProjekS2P_DashboardBoiler\resources\views/admin/input-data/index.blade.php ENDPATH**/ ?>