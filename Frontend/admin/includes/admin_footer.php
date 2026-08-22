<?php
/**
 * Admin footer for admin pages.
 */
declare(strict_types=1);
?>
    </div>
</div>

<?php include __DIR__ . '/system_guide.php'; ?>

    <script src="<?php echo BASE_URL ?? '/Frontend/'; ?>assets/vendor/gsap/gsap.min.js"></script>
    <script src="<?php echo BASE_URL ?? '/Frontend/'; ?>assets/vendor/lucide/lucide.min.js"></script>

    <!-- Wangari Admin V2 — loaded last so it overrides every legacy module stylesheet (admin-stock.css, page-level <style> blocks) -->
    <link rel="stylesheet" href="<?php echo BASE_URL ?? '/Frontend/'; ?>assets/css/wangari-admin-v2.css?v=2.0">
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            // Sidebar V2: collapsible groups
            document.querySelectorAll('.w2-nav-parent').forEach(btn => {
                btn.addEventListener('click', () => {
                    const subs = btn.nextElementSibling;
                    const isOpen = btn.classList.toggle('open');
                    if (subs) subs.style.display = isOpen ? 'block' : 'none';
                    btn.querySelector('.w2-nav-chev').style.transform = isOpen ? 'rotate(180deg)' : '';
                });
            });
            // Auto-open group containing the active sub-item
            document.querySelectorAll('.w2-nav-sub.active').forEach(sub => {
                const parent = sub.closest('.w2-nav-group');
                if (parent) {
                    const btn = parent.querySelector('.w2-nav-parent');
                    const subs = parent.querySelector('.w2-nav-subs');
                    btn.classList.add('open');
                    if (subs) subs.style.display = 'block';
                }
            });
        });
    </script>

    <script>
    /* Sidebar scroll persistence: keep the left nav exactly where the user left it
       when navigating between modules, and bring the active module into view on
       direct loads (bookmark / refresh) instead of resetting to the top. */
    document.addEventListener('DOMContentLoaded', () => {
        const nav = document.getElementById('admin-nav');
        if (!nav) return;

        const SKEY = 'wangariAdminNavScroll';

        const saved = sessionStorage.getItem(SKEY);
        if (saved !== null && saved !== '' && !isNaN(parseInt(saved, 10))) {
            // Same sidebar markup on every admin page, so the saved offset maps 1:1.
            nav.scrollTop = parseInt(saved, 10);
        } else {
            // Direct load: center the highlighted (active) module in the nav so it
            // is visible instead of hidden above the fold.
            const active = nav.querySelector('a.nav-item.active, a.nav-sub.active');
            if (active) {
                const navRect = nav.getBoundingClientRect();
                const itemRect = active.getBoundingClientRect();
                nav.scrollTop += (itemRect.top - navRect.top) - (navRect.height / 2) + (itemRect.height / 2);
            }
        }

        // Remember the offset when a nav link is clicked so the next page restores it.
        nav.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (link && link.href) {
                sessionStorage.setItem(SKEY, String(nav.scrollTop));
                // Mobile drawer: close after choosing a module.
                setNavOpen(false);
            }
        });

        /* Mobile sidebar drawer: hamburger opens/closes the nav as an overlay. */
        const backdrop = document.getElementById('admin-nav-backdrop');
        const toggle = document.getElementById('admin-nav-toggle');
        const setNavOpen = (open) => {
            nav.classList.toggle('open', open);
            document.body.classList.toggle('nav-open', open);
            if (toggle) toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        };
        if (toggle) {
            toggle.addEventListener('click', () => setNavOpen(!nav.classList.contains('open')));
        }
        if (backdrop) {
            backdrop.addEventListener('click', () => setNavOpen(false));
        }
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') setNavOpen(false);
        });

        /* Quick Actions dropdown: toggle on click, close on outside click,
           and "click" shortcuts open the page's own add/edit form. */
        const qaToggle = document.getElementById('quick-actions-toggle');
        const qaMenu = document.getElementById('quick-actions-menu');
        if (qaToggle && qaMenu) {
            const closeQa = () => { qaMenu.style.display = 'none'; const ch = qaToggle.querySelector('svg:last-child'); if (ch) ch.style.transform = ''; };
            qaToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                const open = qaMenu.style.display !== 'none';
                closeQa();
                if (!open) {
                    qaMenu.style.display = 'block';
                    const ch = qaToggle.querySelector('svg:last-child');
                    if (ch) ch.style.transform = 'rotate(180deg)';
                }
            });
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.quick-actions-wrap')) closeQa();
            });
            qaMenu.addEventListener('click', (e) => {
                const t = e.target.closest('[data-quick-click]');
                if (!t) return;
                const text = t.getAttribute('data-quick-click');
                // Try to open the page's own add/edit form button by label.
                // Only real <button> elements open modals — the dropdown's own
                // <a> links also carry data-quick-click, so exclude them.
                const opener = [...document.querySelectorAll('button')].find(b => {
                    if (b === t || t.contains(b)) return false;
                    const label = (b.textContent || '').trim();
                    return label === text || label.includes(text);
                });
                if (opener && opener.closest('form')) {
                    // It's a submit button inside a hidden modal — open the modal first.
                    const modal = opener.closest('[id$="-modal"]');
                    if (modal) modal.style.display = 'flex';
                    e.preventDefault();
                } else if (opener) {
                    e.preventDefault();
                    opener.click();
                }
                closeQa();
            });
        }

        /* Collapsible nav groups: the active section is open by default; other
           sections stay collapsed and remember their open/closed state. */
        nav.querySelectorAll('li.nav-group').forEach(group => {
            const ul = group.querySelector('.nav-subs');
            const chevron = group.querySelector('.nav-chevron');
            if (!ul || !chevron) return;
            const link = group.querySelector('a');
            const key = 'wangariNavOpen:' + (chevron.dataset.navGroupKey || (link ? link.getAttribute('href') : '') || '');
            const isActive = !!group.querySelector('a.nav-item.active');
            const apply = (open) => {
                ul.style.display = open ? 'flex' : 'none';
                group.classList.toggle('nav-group-open', open);
            };
            if (isActive) {
                apply(true);
            } else {
                const stored = sessionStorage.getItem(key);
                if (stored === '1') apply(true);
                else if (stored === '0') apply(false);
            }
            chevron.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const open = ul.style.display !== 'none';
                apply(!open);
                sessionStorage.setItem(key, open ? '0' : '1');
            });
        });
    });
    </script>
</body>
</html>
