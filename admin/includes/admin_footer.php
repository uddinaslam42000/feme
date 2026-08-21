<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Admin Master Footer
 */
?>
            </div><!-- /admin-content -->
        </main>
    </div><!-- /admin-wrapper -->

    <!-- Admin JS Sidebar Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleBtn = document.getElementById('sidebarToggleBtn');
            const sidebar = document.getElementById('adminSidebar');

            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html>
