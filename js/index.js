// passive: false serverve per poter chiamare preventDefault su eventi di scroll/touch, altrimenti js non sa di dover aspettare un preventDefault e inizia a scorrere la pagina
document.addEventListener('DOMContentLoaded', () => {
    const slides = document.querySelectorAll('.slide');
    const totalSlides = slides.length;
    let currentSlide = 0;
    
    let isLocked = false;
    let scrollTimeout = null;
    
    const SCROLL_THRESHOLD = 25;
    const INERTIA_RESET_TIME = 150;

    const updateArrowVisibility = () => {
        const downArrow = document.getElementById('down-arrow');
        if (currentSlide === totalSlides - 1) {
            downArrow.style.visibility = 'hidden';
        } else {
            downArrow.style.visibility = 'visible';
        }
    };

    const goToSlide = (index) => {
        if (index < 0 || index >= totalSlides) return;
        
        currentSlide = index;

        slides[currentSlide].scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });

        updateArrowVisibility();
    };

    window.addEventListener('wheel', (event) => {
        // Blocca sempre lo scroll nativo
        event.preventDefault(); 
        clearTimeout(scrollTimeout);
        
        scrollTimeout = setTimeout(() => {
            isLocked = false;
        }, INERTIA_RESET_TIME);

        if (isLocked) return;

        if (Math.abs(event.deltaY) < SCROLL_THRESHOLD) return;

        if (event.deltaY > 0) {
            goToSlide(currentSlide + 1);
        } else {
            goToSlide(currentSlide - 1);
        }

        isLocked = true;

    }, { passive: false });


    // Gestione della tastiera
    window.addEventListener('keydown', (event) => {
        if (isLocked) return; 

        let changed = false;
        switch (event.key) {
            case 'ArrowDown':
            case 'PageDown':
                if (currentSlide < totalSlides - 1) {
                    goToSlide(currentSlide + 1);
                    changed = true;
                }
                break;
            case 'ArrowUp':
            case 'PageUp':
                if (currentSlide > 0) {
                    goToSlide(currentSlide - 1);
                    changed = true;
                }
                break;
        }
        
        if(changed) {
            isLocked = true;
            setTimeout(() => isLocked = false, 500);
        }
    });

    // gestione touch
    let touchStartY = 0;
    
    window.addEventListener('touchstart', (e) => {
        touchStartY = e.changedTouches[0].screenY;
    }, { passive: false });

    window.addEventListener('touchmove', (e) => {
        e.preventDefault(); 
    }, { passive: false });

    window.addEventListener('touchend', (e) => {
        const touchEndY = e.changedTouches[0].screenY;
        const diff = touchStartY - touchEndY;

        // threshold per evitare swipe accidentali
        if (Math.abs(diff) > 50) {
            if (diff > 0) {
                goToSlide(currentSlide + 1);
            } else {
                goToSlide(currentSlide - 1);
            }
        }
    });

    const slideNextButtons = document.querySelectorAll('.slide-next-btn');
    slideNextButtons.forEach(button => {
        button.addEventListener('click', () => {
            goToSlide(currentSlide + 1);
        });
    });
    
    window.addEventListener('resize', () => {
        slides[currentSlide].scrollIntoView({ behavior: 'auto', block: 'start' });
    });

    // Inizializza la visibilità della freccia al caricamento della pagina
    updateArrowVisibility();
});

