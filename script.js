document.addEventListener('DOMContentLoaded', () => {

    // ── Hamburger Menu ──────────────────────────────────────
    const hamburger = document.querySelector('.hamburger');
    const navContainer = document.querySelector('.nav-container');

    if (hamburger && navContainer) {
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navContainer.classList.toggle('active');
        });

        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', () => {
                hamburger.classList.remove('active');
                navContainer.classList.remove('active');
            });
        });
    }

    // ── Achievement Intro Rotator ────────────────────────────
    const achievementIntro = document.getElementById('achievement-intro');
    if (achievementIntro) {
        const introLines = [
            'Explore our achievement journey through competitions, awards, and innovation.',
            'Each image shows a proud moment in our club\'s success story.',
            'Browse the gallery to see the achievements that inspire our community.'
        ];
        let currentIntro = 0;

        function updateAchievementIntro() {
            achievementIntro.textContent = introLines[currentIntro];
            currentIntro = (currentIntro + 1) % introLines.length;
        }

        updateAchievementIntro();
        setInterval(updateAchievementIntro, 5000);
    }

    // ── Achievement Gallery Scroll ───────────────────────────
    const gallery = document.getElementById('achievementGallery');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');

    if (gallery && prevBtn && nextBtn) {
        const updateNavButtons = () => {
            prevBtn.disabled = gallery.scrollLeft <= 10;
            nextBtn.disabled = gallery.scrollLeft + gallery.clientWidth >= gallery.scrollWidth - 10;
        };

        prevBtn.addEventListener('click', () => {
            gallery.scrollBy({ left: -gallery.clientWidth * 0.8, behavior: 'smooth' });
        });

        nextBtn.addEventListener('click', () => {
            gallery.scrollBy({ left: gallery.clientWidth * 0.8, behavior: 'smooth' });
        });

        gallery.addEventListener('scroll', updateNavButtons);
        window.addEventListener('resize', updateNavButtons);
        updateNavButtons();
    }

    // ── About Page Typewriter ────────────────────────────────
    const titleEl = document.getElementById('about-header-title');
    const subEl   = document.getElementById('about-header-line');

    if (titleEl && subEl) {
        const titleText = "Building Tomorrow's Innovators Today";
        const subText   = "Kishoreganj University-র Computer & Programming Club — যেখানে শুরু হয় প্রতিটি সম্ভাবনার যাত্রা।";

        function typeText(el, text, speed, onDone) {
            let i = 0;
            el.innerHTML = '';
            const cursor = document.createElement('span');
            cursor.className = 'tw-cursor';
            el.appendChild(cursor);

            const t = setInterval(() => {
                if (i < text.length) {
                    cursor.insertAdjacentText('beforebegin', text[i++]);
                } else {
                    clearInterval(t);
                    if (onDone) onDone(cursor);
                }
            }, speed);
        }

        function startTypewriter() {
            typeText(titleEl, titleText, 55, (c1) => {
                setTimeout(() => {
                    c1.style.opacity = '0';
                    setTimeout(() => {
                        typeText(subEl, subText, 32, () => {});
                    }, 300);
                }, 400);
            });
        }

        setTimeout(startTypewriter, 500);
    }

});