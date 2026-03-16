jQuery(document).ready(function($) {
    
    // 1. SCROLL REVEAL ANIMATIONS
    // Uses IntersectionObserver to trigger animations when elements enter the viewport
    const observerOptions = {
        threshold: 0.1, 
        rootMargin: "0px 0px -50px 0px" 
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target); // Only animate once
            }
        });
    }, observerOptions);

    // Watch all elements with the 'reveal' class
    document.querySelectorAll('.reveal').forEach((el) => {
        observer.observe(el);
    });

    // 2. FAQ ACCORDION LOGIC
    // Toggles the active state on click, closing others
    $('.faq-header').on('click', function() {
        const item = $(this).parent('.faq-item');
        
        // Toggle current item
        item.toggleClass('active');
        
        // Close other items (Accordion style)
        item.siblings().removeClass('active');
    });

});
