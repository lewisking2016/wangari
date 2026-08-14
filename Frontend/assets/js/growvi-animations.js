/**
 * Wangari — Growvi animations
 * Replicates the Growvi template's entrance effects:
 *  1. Word-by-word hero title reveal (blur + rise)
 *  2. Scroll-triggered section reveals (.g-reveal / .g-reveal-left / .g-reveal-right)
 *  3. FAQ accordion (.g-faq-item)
 *  4. Navbar turns ink on scroll
 */
(function () {
    'use strict';

    /* ---------- 1. Word-by-word hero title ---------- */
    function splitWords() {
        var title = document.getElementById('gHeroTitle');
        if (!title || title.dataset.gWords === '1') return;
        title.dataset.gWords = '1';

        // Wrap each word in a span, keep .g-serif styling on its words
        var nodes = Array.prototype.slice.call(title.childNodes);
        title.innerHTML = '';
        nodes.forEach(function (node) {
            if (node.nodeType === 3) { // text node
                node.textContent.split(/\s+/).filter(Boolean).forEach(function (word, i) {
                    var span = document.createElement('span');
                    span.className = 'g-word';
                    span.textContent = word;
                    span.style.transitionDelay = (0.15 + i * 0.08) + 's';
                    title.appendChild(span);
                    if (i < word.length) title.appendChild(document.createTextNode(' '));
                });
            } else if (node.nodeType === 1) { // element (the .g-serif span)
                var words = node.textContent.split(/\s+/).filter(Boolean);
                words.forEach(function (word, i) {
                    var span = document.createElement('span');
                    span.className = 'g-word ' + node.className;
                    span.textContent = word;
                    span.style.transitionDelay = (0.15 + i * 0.08) + 's';
                    title.appendChild(span);
                    title.appendChild(document.createTextNode(' '));
                });
            }
        });

        // Trigger the reveal shortly after paint. Use setTimeout directly
        // (rAF can be throttled in background/headless tabs).
        setTimeout(function () {
            title.querySelectorAll('.g-word').forEach(function (w) { w.classList.add('in'); });
        }, 150);
        if (typeof requestAnimationFrame === 'function') {
            requestAnimationFrame(function () {
                title.querySelectorAll('.g-word').forEach(function (w) { w.classList.add('in'); });
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', splitWords);
    } else {
        splitWords();
    }

    /* ---------- 2. Scroll-triggered reveals ---------- */
    var revealEls = document.querySelectorAll('.g-reveal, .g-reveal-left, .g-reveal-right');

    function revealInView() {
        revealEls.forEach(function (el) {
            var rect = el.getBoundingClientRect();
            var vh = window.innerHeight || document.documentElement.clientHeight;
            if (rect.top < vh * 0.92 && rect.bottom > 0) {
                el.classList.add('in');
            }
        });
    }

    // Run immediately (elements already in view), then observe.
    revealInView();
    window.addEventListener('scroll', revealInView, { passive: true });
    window.addEventListener('resize', revealInView, { passive: true });

    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        revealEls.forEach(function (el) { io.observe(el); });
    }

    // Safety net: force-check periodically in case observer callbacks are
    // delayed or scroll events are throttled (embedded/headless browsers).
    [400, 1200, 2400, 4000].forEach(function (t) {
        setTimeout(revealInView, t);
    });
    var revealTimer = setInterval(function () {
        var pending = document.querySelectorAll('.g-reveal:not(.in), .g-reveal-left:not(.in), .g-reveal-right:not(.in)').length;
        if (pending === 0) { clearInterval(revealTimer); return; }
        revealInView();
    }, 600);
    setTimeout(function () { clearInterval(revealTimer); }, 15000); // stop polling after 15s
    document.addEventListener('DOMContentLoaded', revealInView);

    /* ---------- 3. FAQ accordion ---------- */
    document.querySelectorAll('.g-faq-item').forEach(function (item) {
        var q = item.querySelector('.g-faq-q');
        if (!q) return;
        q.addEventListener('click', function () {
            var isOpen = item.classList.contains('open');
            // Close siblings
            item.parentElement.querySelectorAll('.g-faq-item.open').forEach(function (other) {
                other.classList.remove('open');
            });
            if (!isOpen) item.classList.add('open');
        });
    });

    // Legacy accordion (faq.php uses .faq-item/.faq-question)
    document.querySelectorAll('.faq-item').forEach(function (item) {
        var q = item.querySelector('.faq-question');
        if (!q) return;
        q.addEventListener('click', function () {
            var isOpen = item.classList.contains('active');
            item.parentElement.querySelectorAll('.faq-item.active').forEach(function (other) {
                other.classList.remove('active');
            });
            if (!isOpen) item.classList.add('active');
        });
    });

    /* ---------- 4. Navbar ink on scroll ---------- */
    var nav = document.getElementById('gNav');
    if (nav) {
        function onScrollNav() {
            if (window.scrollY > 30) nav.classList.add('scrolled');
            else nav.classList.remove('scrolled');
        }
        window.addEventListener('scroll', onScrollNav, { passive: true });
        onScrollNav();
    }
})();
