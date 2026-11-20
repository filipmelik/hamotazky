document.addEventListener("DOMContentLoaded", function() {

    document.getElementById("toggle-offcanvas-btn").addEventListener('click',function (e){
        var myOffcanvas = document.getElementById('hamNavbarOffcanvas');
        var bsOffcanvas = new bootstrap.Offcanvas(myOffcanvas);
        e.preventDefault();
        e.stopPropagation();
        bsOffcanvas.toggle();
    });

    // Close the offcanvas menu when user picks different theme or question view mode.
    // Capture phase, so we see whether the option was active before its own click handler changes it.
    document.addEventListener('click', (e) => {
        const option = e.target instanceof Element ? e.target.closest('[data-bs-theme-value], [data-question-view-value]') : null;
        if (!option || option.classList.contains('active')) {
            return;
        }
        const bsOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('hamNavbarOffcanvas'));
        if (bsOffcanvas) {
            bsOffcanvas.hide();
        }
    }, true);

    document.querySelectorAll('.anchored-link').forEach(item => {
        item.addEventListener('click', () => {
            // Close the offcanvas menu on anchor navbar link click
            var bsOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('hamNavbarOffcanvas'));
            bsOffcanvas.hide();
        })
    });
    
});