(function(){
    const deleteBtn = document.getElementById('btn-delete-account');
    
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function (e) {
            e.preventDefault();
            
            const conferma = confirm('ATTENZIONE: L\'operazione è definitiva. Perderai tutti i progressi e i dati personali. Vuoi davvero procedere?');
            
            if (conferma) {
                // Reindirizza allo script PHP che gestisce la cancellazione
                window.location.href = 'api/delete_account.php';
            }
        });
    }
})();