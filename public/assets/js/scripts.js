document.addEventListener("DOMContentLoaded", function() {

    document.getElementById("toggle-offcanvas-btn").addEventListener('click',function (e){
        var myOffcanvas = document.getElementById('hamNavbarOffcanvas');
        var bsOffcanvas = new bootstrap.Offcanvas(myOffcanvas);
        e.preventDefault();
        e.stopPropagation();
        bsOffcanvas.toggle();
    });

    document.querySelectorAll('.anchored-link').forEach(item => {
        item.addEventListener('click', () => {
            // Close the offcanvas menu on anchor navbar link click
            var bsOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('hamNavbarOffcanvas'));
            bsOffcanvas.hide();
        })
    });
    
});