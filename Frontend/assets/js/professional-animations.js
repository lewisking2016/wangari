/**
 * Professional Animations & Interactions
 * Powered by GSAP and Swiper.js
 */

document.addEventListener('DOMContentLoaded', () => {
    
    // 1. Initialize Swiper for Hero Section (if exists)
    const heroSlider = document.querySelector('.swiper-hero');
    if (heroSlider && typeof Swiper !== 'undefined') {
        new Swiper('.swiper-hero', {
            loop: true,
            effect: 'fade',
            autoplay: {
                delay: 6000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            on: {
                init: function () {
                    animateHeroContent(this.slides[this.activeIndex]);
                },
                slideChangeTransitionStart: function () {
                    animateHeroContent(this.slides[this.activeIndex]);
                }
            }
        });
    }

    // Function to animate content inside hero slide using GSAP
    function animateHeroContent(slide) {
        if (typeof gsap === 'undefined') return;
        
        const title = slide.querySelector('h1');
        const text = slide.querySelector('p');
        const buttons = slide.querySelectorAll('.btn');

        // Reset positions
        gsap.set([title, text, buttons], { opacity: 0, y: 30 });

        // Animate
        gsap.to(title, { opacity: 1, y: 0, duration: 0.8, ease: "power3.out", delay: 0.2 });
        gsap.to(text, { opacity: 1, y: 0, duration: 0.8, ease: "power3.out", delay: 0.4 });
        gsap.to(buttons, { opacity: 1, y: 0, duration: 0.8, ease: "power3.out", delay: 0.6, stagger: 0.1 });
    }

    // 1. Initialize Hero Swiper
    const heroSwiper = document.querySelector('.hero-swiper');
    if (heroSwiper && typeof Swiper !== 'undefined') {
        new Swiper('.hero-swiper', {
            effect: 'fade',
            fadeEffect: { crossFade: true },
            loop: true,
            speed: 1500,
            autoplay: {
                delay: 7000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.hero-pagination',
                clickable: true,
            },
            on: {
                slideChangeTransitionStart: function() {
                    const activeSlide = this.slides[this.activeIndex];
                    const content = activeSlide.querySelector('.hero-content');
                    if (content && typeof gsap !== 'undefined') {
                        gsap.fromTo(content.children, 
                            { opacity: 0, y: 30 },
                            { opacity: 1, y: 0, duration: 0.8, stagger: 0.1, ease: "power2.out" }
                        );
                    }
                }
            }
        });
    }

    // 1b. Initialize Trust Slider (Infinite Loop)
    const trustSwiper = document.querySelector('.trust-swiper');
    if (trustSwiper && typeof Swiper !== 'undefined') {
        new Swiper('.trust-swiper', {
            slidesPerView: 2,
            spaceBetween: 30,
            loop: true,
            speed: 5000,
            autoplay: {
                delay: 0,
                disableOnInteraction: false,
            },
            breakpoints: {
                640: { slidesPerView: 3 },
                1024: { slidesPerView: 5 },
            }
        });
    }

    // 2. Initialize Creative Swiper for Featured Products
    const creativeSlider = document.querySelector('.creative-slider');
    if (creativeSlider && typeof Swiper !== 'undefined') {
        const swiper = new Swiper('.creative-slider', {
            slidesPerView: 1,
            spaceBetween: 30,
            centeredSlides: true,
            loop: true,
            speed: 1000,
            autoplay: {
                delay: 6000,
                disableOnInteraction: false,
            },
            breakpoints: {
                768: { slidesPerView: 2 },
                1200: { slidesPerView: 3, spaceBetween: 50 },
            },
            navigation: {
                nextEl: '.creative-nav-next',
                prevEl: '.creative-nav-prev',
            },
            on: {
                init: function() {
                    animateCreativeSlide(this.slides[this.activeIndex]);
                    updateProgressBar(this);
                },
                slideChangeTransitionStart: function() {
                    animateCreativeSlide(this.slides[this.activeIndex]);
                },
                autoplayTimeLeft(s, time, progress) {
                    const progressBar = document.querySelector('.slider-progress-bar');
                    if (progressBar) {
                        progressBar.style.width = `${(1 - progress) * 100}%`;
                    }
                }
            }
        });
    }

    function animateCreativeSlide(slide) {
        if (!slide || typeof gsap === 'undefined') return;
        
        const title = slide.querySelector('[data-gsap="title"]');
        const desc = slide.querySelector('[data-gsap="desc"]');
        const meta = slide.querySelector('[data-gsap="meta"]');
        const btn = slide.querySelector('[data-gsap="btn"]');

        const timeline = gsap.timeline();
        
        // Reset
        gsap.set([title, desc, meta, btn], { opacity: 0, y: 20 });

        // Staggered Entrance
        timeline.to(title, { opacity: 1, y: 0, duration: 0.6, ease: "power3.out" }, "+=0.3")
                .to(desc, { opacity: 1, y: 0, duration: 0.6, ease: "power3.out" }, "-=0.4")
                .to(meta, { opacity: 1, y: 0, duration: 0.6, ease: "power3.out" }, "-=0.4")
                .to(btn, { opacity: 1, y: 0, duration: 0.6, ease: "power3.out" }, "-=0.4");
    }

    function updateProgressBar(swiper) {
        const progressBar = document.querySelector('.slider-progress-bar');
        if (!progressBar) return;
        
        swiper.on('slideChange', () => {
            gsap.set(progressBar, { width: '0%' });
        });
    }

    // 3. GSAP Scroll Animations (Unified)
    if (typeof gsap !== 'undefined') {
        // Counter Animation for Statistics
        const stats = document.querySelectorAll('.stat-counter');
        if (stats.length > 0) {
            const statsObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const target = entry.target;
                        const targetValue = parseInt(target.getAttribute('data-target'));
                        const suffix = target.getAttribute('data-suffix') || '';
                        
                        gsap.to(target, {
                            innerText: targetValue,
                            duration: 2,
                            snap: { innerText: 1 },
                            ease: "power2.out",
                            onUpdate: function() {
                                target.innerText = Math.ceil(target.innerText) + suffix;
                            }
                        });
                        statsObserver.unobserve(target);
                    }
                });
            }, { threshold: 0.5 });

            stats.forEach(stat => statsObserver.observe(stat));
        }

        // High-end section title reveals (IntersectionObserver-based — no plugin needed)
        const sectionHeaders = document.querySelectorAll('.section-header');
        if (sectionHeaders.length > 0) {
            const headerObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        gsap.fromTo(entry.target,
                            { opacity: 0, y: 50 },
                            { opacity: 1, y: 0, duration: 1, ease: "power3.out" }
                        );
                        headerObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15, rootMargin: '0px 0px -50px 0px' });

            sectionHeaders.forEach(header => {
                gsap.set(header, { opacity: 0, y: 50 });
                headerObserver.observe(header);
            });
        }

        // Magnetic Button Effect
        document.querySelectorAll('.btn-primary, .btn-accent').forEach(btn => {
            btn.addEventListener('mousemove', (e) => {
                const rect = btn.getBoundingClientRect();
                const x = e.clientX - rect.left - rect.width / 2;
                const y = e.clientY - rect.top - rect.height / 2;
                
                gsap.to(btn, {
                    x: x * 0.3,
                    y: y * 0.3,
                    duration: 0.3,
                    ease: "power2.out"
                });
            });
            
            btn.addEventListener('mouseleave', () => {
                gsap.to(btn, { x: 0, y: 0, duration: 0.5, ease: "elastic.out(1, 0.3)" });
            });
        });

        // Advanced card staggering using Intersection Observer as fallback 
        // or using pure GSAP if ScrollTrigger was available (we're using simple observer + gsap)
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = entry.target;
                    
                    // Card entry animation
                    gsap.to(target, {
                        opacity: 1,
                        y: 0,
                        scale: 1,
                        duration: 0.8,
                        ease: "back.out(1.7)",
                        delay: target.dataset.delay || 0
                    });
                    
                    observer.unobserve(target);
                }
            });
        }, { threshold: 0.1 });

        // Prepare cards for animation
        document.querySelectorAll('.product-card').forEach((card, index) => {
            gsap.set(card, { opacity: 0, y: 40, scale: 0.95 });
            card.dataset.delay = (index % 4) * 0.1; // Stagger effect
            observer.observe(card);
        });
        
        // Counter Animations
        const counters = document.querySelectorAll('[data-count]');
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = entry.target;
                    const endValue = parseInt(target.getAttribute('data-count'));
                    
                    gsap.to(target, {
                        innerHTML: endValue,
                        duration: 2.5,
                        ease: "power2.out",
                        snap: { innerHTML: 1 },
                        onUpdate: function() {
                            target.innerHTML = Math.round(target.innerHTML) + "+";
                        }
                    });
                    
                    counterObserver.unobserve(target);
                }
            });
        }, { threshold: 0.5 });
        
        counters.forEach(counter => counterObserver.observe(counter));
    }
});
