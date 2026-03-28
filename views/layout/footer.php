                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/app.js"></script>
    <script>
    // Sidebar toggle with localStorage persistence
    (function() {
        const toggle = document.getElementById('sidebarToggle');
        if (!toggle) return;
        // Restore state
        if (localStorage.getItem('sidebarCollapsed') === '1') {
            document.body.classList.add('sidebar-collapsed');
        }
        toggle.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed',
                document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
        });
    })();
    </script>
</body>
</html>
