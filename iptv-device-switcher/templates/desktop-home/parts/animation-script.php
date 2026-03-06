<!-- ANIMATION SCRIPT -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    
    // 1. SCROLL REVEAL (Intersection Observer)
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if(entry.isIntersecting) {
                entry.target.classList.add('active');
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
    
    document.querySelectorAll('.reveal, .fade-in').forEach(el => revealObserver.observe(el));

    // 2. STATS COUNTER
    const countObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if(entry.isIntersecting) {
                const target = +entry.target.getAttribute('data-target');
                const duration = 2000; // 2 seconds
                const start = performance.now();
                
                const step = (timestamp) => {
                    const progress = Math.min((timestamp - start) / duration, 1);
                    const val = Math.floor(progress * target);
                    entry.target.innerText = val.toLocaleString() + "+";
                    if(progress < 1) window.requestAnimationFrame(step);
                };
                
                window.requestAnimationFrame(step);
                countObserver.unobserve(entry.target);
            }
        });
    });
    document.querySelectorAll('.count-up').forEach(el => countObserver.observe(el));

    // 3. OPTIMIZED SCROLL LOOP (60FPS Parallax & Nav)
    let lastScrollY = window.scrollY;
    let ticking = false;

    function updateScroll() {
        const nav = document.getElementById('navbar');
        const bg = document.getElementById('parallax-bg');
        
        // Navbar Glass
        if (lastScrollY > 50) nav.classList.add('scrolled');
        else nav.classList.remove('scrolled');

        // Parallax (Limit calculation to hero area for performance)
        if (bg && lastScrollY < 1200) {
            // FIX: Use translate3d for hardware acceleration
            bg.style.transform = `scale(1.1) translate3d(0, ${lastScrollY * 0.4}px, 0)`;
        }

        ticking = false;
    }

    window.addEventListener('scroll', () => {
        lastScrollY = window.scrollY;
        if (!ticking) {
            window.requestAnimationFrame(updateScroll);
            ticking = true;
        }
    }, { passive: true });

    // 4. SIDEBAR LOGIC (NEW)
    const trigger = document.getElementById('sidebar-trigger');
    const drawer = document.getElementById('sidebar-drawer');
    const backdrop = document.getElementById('sidebar-backdrop');
    const closeBtn = document.getElementById('sidebar-close');

    function toggleSidebar(open) {
        if (open) {
            drawer.classList.add('open');
            backdrop.classList.add('open');
            document.body.style.overflow = 'hidden'; // Lock scroll
        } else {
            drawer.classList.remove('open');
            backdrop.classList.remove('open');
            document.body.style.overflow = '';
        }
    }

    if(trigger) trigger.addEventListener('click', () => toggleSidebar(true));
    if(closeBtn) closeBtn.addEventListener('click', () => toggleSidebar(false));
    if(backdrop) backdrop.addEventListener('click', () => toggleSidebar(false));

});
</script>
