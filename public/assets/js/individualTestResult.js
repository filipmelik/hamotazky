document.addEventListener("DOMContentLoaded", function() {
    
    // reset test & its progress upon evaluation
    TestStorage.clearTestAndProgress();

    document.querySelector('#show-test-button').addEventListener('click', () => {
        var btn = document.getElementById('show-test-button');
        btn.remove();

        var collapseElementList = [].slice.call(document.querySelectorAll('.collapse'))
        var collapseList = collapseElementList.map(function (collapseEl) {
          return new bootstrap.Collapse(collapseEl)
        })
    });

});
