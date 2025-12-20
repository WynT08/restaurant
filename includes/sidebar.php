<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-utensils"></i> <?php echo SITE_TITLE; ?></h3>
    </div>
    
    <ul class="sidebar-menu">
        <li class="<?php echo $current_page == 'index' ? 'active' : ''; ?>">
            <a href="<?php echo SITE_URL; ?>/modules/dashboard/index.php">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <?php if (hasPermission('staff')): ?>
        <li class="<?php echo in_array($current_page, ['pos', 'order_list', 'order_details']) ? 'active' : ''; ?>">
            <a href="<?php echo SITE_URL; ?>/modules/orders/pos.php">
                <i class="fas fa-cash-register"></i>
                <span>POS - Bán hàng</span>
            </a>
        </li>
        
        <li class="<?php echo in_array($current_page, ['list', 'create', 'edit', 'calendar']) && strpos($_SERVER['REQUEST_URI'], 'reservations') !== false ? 'active' : ''; ?>">
            <a href="<?php echo SITE_URL; ?>/modules/reservations/list.php">
                <i class="fas fa-calendar-check"></i>
                <span>Đặt bàn</span>
            </a>
        </li>
        
        <li class="<?php echo strpos($_SERVER['REQUEST_URI'], 'tables') !== false ? 'active' : ''; ?>">
            <a href="<?php echo SITE_URL; ?>/modules/tables/manage_tables.php">
                <i class="fas fa-table"></i>
                <span>Quản lý bàn</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (hasPermission('manager')): ?>
        <li class="menu-header">Quản lý</li>
        
        <li class="<?php echo strpos($_SERVER['REQUEST_URI'], 'menu') !== false ? 'active' :  ''; ?>">
            <a href="<?php echo SITE_URL; ?>/modules/menu/categories.php">
                <i class="fas fa-book-open"></i>
                <span>Thực đơn</span>
            </a>
        </li>
        
        <li class="<?php echo strpos($_SERVER['REQUEST_URI'], 'inventory') !== false ? 'active' : ''; ?>">
            <a href="<?php echo SITE_URL; ?>/modules/inventory/ingredients.php">
                <i class="fas fa-warehouse"></i>
                <span>Kho hàng</span>
            </a>
        </li>
        
        <li class="<?php echo strpos($_SERVER['REQUEST_URI'], 'expenses') !== false ? 'active' : ''; ?>">
            <a href="<?php echo SITE_URL; ?>/modules/expenses/expense_list.php">
                <i class="fas fa-money-bill-wave"></i>
                <span>Chi phí</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (hasPermission('manager')): ?>
        <li class="menu-header">Báo cáo</li>
        
        <li class="<?php echo strpos($_SERVER['REQUEST_URI'], 'reports') !== false ? 'active' : ''; ?>">
            <a href="<?php echo SITE_URL; ?>/modules/reports/daily_sales.php">
                <i class="fas fa-chart-line"></i>
                <span>Doanh thu</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (hasPermission('admin')): ?>
        <li class="menu-header">Hệ thống</li>
        
        <li class="<?php echo strpos($_SERVER['REQUEST_URI'], 'users') !== false ? 'active' : ''; ?>">
            <a href="<?php echo SITE_URL; ?>/modules/users/manage_staff.php">
                <i class="fas fa-users"></i>
                <span>Nhân viên</span>
            </a>
        </li>
        
        <li class="<?php echo strpos($_SERVER['REQUEST_URI'], 'settings') !== false ? 'active' : ''; ?>">
            <a href="<?php echo SITE_URL; ?>/modules/settings/general.php">
                <i class="fas fa-cog"></i>
                <span>Cài đặt</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</aside>