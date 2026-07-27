<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
<title><?php echo $__env->yieldContent('title', 'S2P Boiler Dashboard'); ?></title>


<link rel="stylesheet" href="<?php echo e(asset('css/dashboard-shared.css')); ?>">


<?php echo $__env->yieldPushContent('head'); ?>


<?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body class="<?php echo $__env->yieldContent('body-class'); ?>">

<div class="layout">
    <aside class="sidebar">
        <div class="logo-box">
            <img src="<?php echo e(asset('images/logo.png')); ?>" alt="S2P Logo">
        </div>
        <nav class="sidebar-nav">
            <a href="<?php echo e(route('global-view')); ?>" class="nav-item <?php echo e(request()->routeIs('global-view') ? 'active' : ''); ?>">GLOBAL VIEW</a>
            <a href="<?php echo e(route('tube.mapping')); ?>" class="nav-item <?php echo e(request()->routeIs(['tube.mapping', 'tube-mapping.*']) ? 'active' : ''); ?>">TUBE MAPPING</a>
            <a href="<?php echo e(route('rla-analysis')); ?>" class="nav-item <?php echo e(request()->routeIs('rla-analysis') ? 'active' : ''); ?>">RLA ANALYSIS</a>
            <a href="<?php echo e(route('maintenance')); ?>" class="nav-item <?php echo e(request()->routeIs('maintenance') ? 'active' : ''); ?>">MAINTENANCE</a>
            <a href="<?php echo e(route('input-data.index')); ?>" class="nav-item <?php echo e(request()->routeIs('input-data.*') ? 'active' : ''); ?>">INPUT DATA</a>
        </nav>
    </aside>

    <?php echo $__env->yieldContent('content'); ?>
</div>

<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html><?php /**PATH E:\Data D\ProjekS2P_DashboardBoiler\resources\views/layouts/dashboard.blade.php ENDPATH**/ ?>