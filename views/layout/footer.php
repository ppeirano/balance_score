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
    (function() {
        // Sidebar collapse toggle
        var toggle = document.getElementById('sidebarToggle');
        if (toggle) {
            if (localStorage.getItem('sidebarCollapsed') === '1') {
                document.body.classList.add('sidebar-collapsed');
            }
            toggle.addEventListener('click', function() {
                document.body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed',
                    document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
            });
        }

        // Sidebar sections: persist open/closed state in localStorage
        var sections = ['navPrincipal', 'navGestion', 'navReportes', 'navAdmin'];
        sections.forEach(function(id) {
            var el = document.getElementById(id);
            if (!el) return;
            var key = 'sidebar_' + id;
            var saved = localStorage.getItem(key);

            // Restore saved state (override HTML default)
            if (saved === '1') {
                el.classList.add('show');
                var label = document.querySelector('[data-bs-target="#' + id + '"]');
                if (label) label.classList.remove('collapsed');
            } else if (saved === '0') {
                el.classList.remove('show');
                var label = document.querySelector('[data-bs-target="#' + id + '"]');
                if (label) label.classList.add('collapsed');
            }

            // Save on toggle
            el.addEventListener('shown.bs.collapse', function() {
                localStorage.setItem(key, '1');
            });
            el.addEventListener('hidden.bs.collapse', function() {
                localStorage.setItem(key, '0');
            });
        });
    })();
    </script>
</body>
</html>
