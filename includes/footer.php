            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min. js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <? php if ($current_page == 'pos'): ?>
    <script src="<?php echo SITE_URL; ?>/assets/js/pos.js"></script>
    <?php endif; ?>
    
    <?php if (strpos($_SERVER['REQUEST_URI'], 'reservations') !== false): ?>
    <script src="<? php echo SITE_URL; ? >/assets/js/reservation.js"></script>
    <? php endif; ?>
    
    <?php if (strpos($_SERVER['REQUEST_URI'], 'inventory') !== false): ?>
    <script src="<?php echo SITE_URL; ?>/assets/js/inventory.js"></script>
    <?php endif; ?>
</body>
</html>