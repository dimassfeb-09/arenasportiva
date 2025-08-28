    </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function(){
            // Clone the sidebar navigation for the offcanvas menu
            const sidebarNav = document.querySelector('.sidebar-nav');
            const offcanvasBody = document.querySelector('#sidebarOffcanvas .offcanvas-body');
            if(sidebarNav && offcanvasBody) {
                offcanvasBody.innerHTML = sidebarNav.innerHTML;
            }
        });
    </script>
</body>
</html>