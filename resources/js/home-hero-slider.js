// home hero ka image slider. Ye file sirf decide karti hai kaunsa slide kab dikhe -
// crossfade, zoom, text aur dot ka look sab app.css mein, is-active / is-leaving classes se
const heroSlider = document.querySelector('[data-hero-slider]');

if (heroSlider) {
    const slides = [...heroSlider.querySelectorAll('[data-slide]')];
    const contentBlocks = [...heroSlider.querySelectorAll('[data-slide-content]')];
    const dotsContainer = heroSlider.querySelector('[data-slide-dots]');
    const prevButton = heroSlider.querySelector('[data-slide-prev]');
    const nextButton = heroSlider.querySelector('[data-slide-next]');

    const AUTOPLAY_DELAY_MS = 3000;
    // app.css ke .hero-slide aur .hero-copy transitions se match
    const SLIDE_FADE_MS = 800;
    const COPY_FADE_OUT_MS = 250;

    const visitorPrefersLessMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let activeIndex = 0;
    let mouseIsOverSlider = false;

    // timer beech mein ruk sakta hai (hover, background tab), is liye slide ka bacha hua time yaad rakhte hain
    let autoplayTimer = null;
    let timerStartedAt = 0;
    let timeLeftOnSlide = AUTOPLAY_DELAY_MS;

    let slideCleanupTimer = null;
    let copyCleanupTimer = null;

    heroSlider.style.setProperty('--hero-slide-duration', `${AUTOPLAY_DELAY_MS}ms`);

    const dots = slides.map((_, index) => {
        const dot = document.createElement('button');
        dot.type = 'button';
        dot.setAttribute('aria-label', `Go to slide ${index + 1}`);
        dot.addEventListener('click', () => goToSlide(index));
        dotsContainer.appendChild(dot);
        return dot;
    });

    function paintDots() {
        dots.forEach((dot, index) => {
            const isActiveDot = index === activeIndex;

            dot.className = isActiveDot
                ? 'relative h-2 w-6 overflow-hidden rounded-full bg-white/40 transition-all'
                : 'h-2 w-2 rounded-full bg-white/40 transition-all hover:bg-white/70';
            dot.setAttribute('aria-current', isActiveDot ? 'true' : 'false');

            // har baar naya bar, taake fill khali se shuru ho
            dot.replaceChildren();
            if (isActiveDot) {
                const progressBar = document.createElement('span');
                progressBar.className = 'hero-dot-progress absolute inset-0 rounded-full bg-white';
                dot.appendChild(progressBar);
            }
        });
    }

    function goToSlide(index) {
        const previousIndex = activeIndex;
        activeIndex = (index + slides.length) % slides.length;

        if (activeIndex !== previousIndex) {
            swapSlideImages(previousIndex);
            swapSlideCopy(previousIndex);
        }

        paintDots();
        // arrow ya dot click bhi yahin aata hai, taake chune hue slide ko poore 3 second milen
        timeLeftOnSlide = AUTOPLAY_DELAY_MS;
        startAutoplay();
    }

    function swapSlideImages(previousIndex) {
        // jaldi click karne se do change pehle wala slide abhi bhi leaving reh sakta hai
        clearTimeout(slideCleanupTimer);
        slides.forEach((slide) => slide.classList.remove('is-leaving'));

        slides[previousIndex].classList.replace('is-active', 'is-leaving');
        slides[activeIndex].classList.add('is-active');

        // naya slide poora fade hone tak purana neeche rehta hai
        slideCleanupTimer = setTimeout(() => {
            slides[previousIndex].classList.remove('is-leaving');
        }, SLIDE_FADE_MS);
    }

    function swapSlideCopy(previousIndex) {
        clearTimeout(copyCleanupTimer);
        contentBlocks.forEach((block) => block.classList.remove('is-leaving'));

        contentBlocks[previousIndex].classList.replace('is-active', 'is-leaving');
        contentBlocks[activeIndex].classList.add('is-active');

        copyCleanupTimer = setTimeout(() => {
            contentBlocks[previousIndex].classList.remove('is-leaving');
        }, COPY_FADE_OUT_MS);
    }

    function autoplayShouldRun() {
        return ! visitorPrefersLessMotion && ! mouseIsOverSlider && ! document.hidden;
    }

    function startAutoplay() {
        clearTimeout(autoplayTimer);
        autoplayTimer = null;

        if (! autoplayShouldRun()) {
            heroSlider.classList.add('hero-slider-paused');
            return;
        }

        heroSlider.classList.remove('hero-slider-paused');
        timerStartedAt = performance.now();
        autoplayTimer = setTimeout(() => goToSlide(activeIndex + 1), timeLeftOnSlide);
    }

    // dot ka progress bar aur photo zoom bhi ruk jate hain (.hero-slider-paused)
    function pauseAutoplay() {
        if (autoplayTimer) {
            timeLeftOnSlide = Math.max(0, timeLeftOnSlide - (performance.now() - timerStartedAt));
        }

        clearTimeout(autoplayTimer);
        autoplayTimer = null;
        heroSlider.classList.add('hero-slider-paused');
    }

    prevButton?.addEventListener('click', () => goToSlide(activeIndex - 1));
    nextButton?.addEventListener('click', () => goToSlide(activeIndex + 1));

    heroSlider.addEventListener('mouseenter', () => {
        mouseIsOverSlider = true;
        pauseAutoplay();
    });
    heroSlider.addEventListener('mouseleave', () => {
        mouseIsOverSlider = false;
        startAutoplay();
    });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            pauseAutoplay();
        } else {
            startAutoplay();
        }
    });

    paintDots();
    startAutoplay();
}
