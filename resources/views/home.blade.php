@extends('layouts.app')
<!-- Google Fonts (Modern & Clean Typography) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
@push('styles')
<style>



    /* Global Body & Typography Settings */
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background-color: #0b0f19;
        color: #f3f4f6;
        overflow-x: hidden;
    }

    /* Custom Serif Font for Headings if desired */
    .font-serif {
        font-family: 'Playfair Display', serif;
    }

    /* Gradient Text Effect matching the theme */
    .gradient-text {
        background: linear-gradient(135deg, #c084fc 0%, #e879f9 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* Smooth Section Backgrounds */
    .hero-section, .drops-features-section {
        background-color: #0b0f19;
        background-image: radial-gradient(circle at 10% 20%, rgba(147, 51, 234, 0.08) 0%, transparent 40%),
                          radial-gradient(circle at 90% 80%, rgba(255, 193, 7, 0.04) 0%, transparent 40%);
    }

    /* Custom Scrollbar for sleek UI */
    ::-webkit-scrollbar {
        width: 8px;
    }
    ::-webkit-scrollbar-track {
        background: #0b0f19;
    }
    ::-webkit-scrollbar-thumb {
        background: rgba(192, 132, 252, 0.3);
        border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: rgba(192, 132, 252, 0.5);
    }

    /* Hero Section - Sakura Cyber Theme */
    .hero-section {
        padding-top: 7rem;
        padding-bottom: 6rem;
        position: relative;
        overflow: hidden;
        background: radial-gradient(circle at 70% 30%, #4c1d95 0%, #090314 70%);
    }

    /* Three.js Canvas Container */
    #kanji-rain-container {
        position: absolute;
        top: 0;
        right: 0;
        width: 50%;
        height: 100%;
        z-index: 1;
        pointer-events: none;
    }

    .hero-content {
        position: relative;
        z-index: 2;
    }

    .btn-glow {
        background: linear-gradient(135deg, #9333ea, #db2777);
        border: none;
        color: white;
        transition: 0.3s;
    }
    .btn-glow:hover {
        opacity: 0.9;
        transform: translateY(-2px);
        color: white;
        box-shadow: 0 10px 20px -5px rgba(147, 51, 234, 0.4);
    }

    /* Cards Styling */
    .feature-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 1rem;
        transition: 0.3s;
    }
    .feature-card:hover {
        border-color: rgba(147, 51, 234, 0.5);
        transform: translateY(-4px);
    }

    /* Courses Section Background */
    .courses-section {
        background-color: rgba(147, 51, 234, 0.04);
    }

    /* Default Dark Theme (Current Theme) */
    :root {
        --bg-color: #0f0c1b;
        --card-bg: #130f26;
        --input-bg: #1e1b4b;
        --text-color: #ffffff;
        --text-muted: #9ca3af;
        --border-color: rgba(168, 85, 247, 0.2);
    }

    /* Light Theme Overrides */
    [data-theme="light"] {
        --bg-color: #f8fafc;
        --card-bg: #ffffff;
        --input-bg: #f1f5f9;
        --text-color: #1e293b;
        --text-muted: #64748b;
        --border-color: rgba(168, 85, 247, 0.3);
    }

    body {
        background-color: var(--bg-color) !important;
        color: var(--text-color) !important;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    @keyframes floatAnim {
        0% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-10px) rotate(2deg); }
        100% { transform: translateY(0px) rotate(0deg); }
    }

    .floating-mascot {
        animation: floatAnim 4s ease-in-out infinite;
    }

    /* Button ရဲ့ အခြေခံ ဒီဇိုင်းနှင့် Pink-Violet Gradient */
    .btn-pink-violet-glow {
        background: linear-gradient(135deg, #ff7eb3 0%, #8a2be2 100%);
        color: #ffffff;
        border: none;
        font-weight: 600;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        box-shadow: 0 4px 15px rgba(138, 43, 226, 0.4);
        text-decoration: none;
    }

    .btn-pink-violet-glow:hover {
        background: linear-gradient(135deg, #ff758c 0%, #7b2cbf 100%);
        color: #ffffff;
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 10px 25px rgba(138, 43, 226, 0.6);
    }

    .btn-pink-violet-glow .icon-arrow {
        transition: transform 0.3s ease-in-out;
    }

    .btn-pink-violet-glow:hover .icon-arrow {
        transform: translateX(6px);
    }

    /* Learning Journey Section - Deep Dark Navy Background with Warm Yellow Accents */
    /* Learning Journey Section - Deep Dark Plum Theme (Navy အရောင်အစား ပြောင်းလဲထားသည်) */
    .learning-journey-section {
        background-color: #160f29; /* Deep Dark Plum/Violet ကို ပြောင်းထားပါသည် */
        position: relative;
    }

    .bg-card-dark {
        background: rgba(255, 255, 255, 0.04);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .hover-up:hover {
        transform: translateY(-8px);
        border-color: rgba(236, 72, 153, 0.4) !important; /* Pink glow accent */
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6) !important;
    }

    .font-serif {
        font-family: 'Playfair Display', Georgia, serif;
    }

    .speech-bubble {
        animation: floatBubble 3s ease-in-out infinite;
        z-index: 5;
    }

    @keyframes floatBubble {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-5px); }
        100% { transform: translateY(0px); }
    }
</style>
@endpush

@section('content')
<!-- Hero Component -->
<section class="hero-section position-relative overflow-hidden py-5">
    <!-- Background Kanji -->
    <div id="kanji-rain-container"></div>

    <!-- Ambient Background Glow -->
    <div class="hero-glow hero-glow-1"></div>
    <div class="hero-glow hero-glow-2"></div>

    <div class="container py-lg-5 hero-content position-relative" style="z-index: 2;">
        <div class="row align-items-center g-5">

            <!-- ========================================= -->
            <!-- LEFT SIDE                                -->
            <!-- ========================================= -->
            <div class="col-lg-6 d-flex flex-column align-items-start gap-4">

                <!-- Badge -->
                <div class="hero-badge">
                    <i class="bi bi-stars me-2"></i>
                    New: Visual mnemonics for hiragana
                </div>

                <!-- Heading -->
                <h1 class="hero-title text-white mb-0">
                    Learn Japanese
                    <br>
                    <span class="gradient-text">effortlessly.</span>
                </h1>

                <!-- Description -->
                <p class="hero-description text-light mb-0">
                    Master vocabulary, kanji, and grammar through
                    bite-sized visual lessons that stick —
                    just 5 minutes a day.
                </p>

                <!-- CTA -->
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <a href="{{ route('select.level') }}"
                       class="btn btn-lg rounded-pill px-5 py-3 hero-cta">

                        <span>Get Started Free</span>
                        <i class="bi bi-arrow-right ms-2"></i>

                    </a>
                </div>

                <!-- Trust -->
                <small class="hero-trust text-light">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Trusted by 50,000+ learners worldwide.
                </small>

            </div>


            <!-- ========================================= -->
            <!-- RIGHT SIDE                               -->
            <!-- ========================================= -->
            <div class="col-lg-6 position-relative">

                <div class="hero-visual">

                    <!-- Big Glow -->
                    <div class="visual-glow"></div>

                    <!-- Japanese scenery -->
                    <div class="japan-orb">
                        <div class="mountain"></div>
                        <div class="sun"></div>

                        <div class="pagoda">
                            <div class="pagoda-roof roof-1"></div>
                            <div class="pagoda-roof roof-2"></div>
                            <div class="pagoda-roof roof-3"></div>
                            <div class="pagoda-body"></div>
                        </div>
                    </div>


                    <!-- ================================= -->
                    <!-- FLOATING CARD : SAKANA             -->
                    <!-- ================================= -->
                    <div class="floating-card card-sakana">

                        <div class="hiragana-symbol">あ</div>

                        <div class="mini-illustration fish">
                            🐟
                        </div>

                        <div class="jp-word">
                            さかな
                        </div>

                        <small>
                            sakana · fish
                        </small>

                    </div>


                    <!-- ================================= -->
                    <!-- FLOATING CARD : YAMA               -->
                    <!-- ================================= -->
                    <div class="floating-card card-yama">

                        <div class="hiragana-symbol">か</div>

                        <div class="mini-illustration mountain-icon">
                            🏔️
                        </div>

                        <div class="jp-word">
                            やま
                        </div>

                        <small>
                            yama · mountain
                        </small>

                    </div>


                    <!-- ================================= -->
                    <!-- FLOATING CARD : TATEMONO          -->
                    <!-- ================================= -->
                    <div class="floating-card card-tatemono">

                        <div class="hiragana-symbol">た</div>

                        <div class="mini-illustration">
                            🏯
                        </div>

                        <div class="jp-word">
                            たてもの
                        </div>

                        <small>
                            tatemono · building
                        </small>

                    </div>


                    <!-- ================================= -->
                    <!-- PHONE MOCKUP                       -->
                    <!-- ================================= -->
                    <div class="phone-wrapper">

                        <div class="phone">

                            <!-- Phone Header -->
                            <div class="phone-header">

                                <div class="app-avatar">
                                    🌸
                                </div>

                                <div>
                                    <div class="app-name">
                                        Japanese
                                    </div>

                                    <div class="app-subtitle">
                                        Your daily lesson
                                    </div>
                                </div>

                                <div class="menu-button">
                                    <i class="bi bi-list"></i>
                                </div>

                            </div>


                            <!-- Lesson Card -->
                            <div class="lesson-card">

                                <div class="lesson-label">
                                    今日のレッスン
                                </div>

                                <div class="lesson-word">
                                    こんにちは
                                </div>

                                <div class="lesson-romaji">
                                    konnichiwa
                                </div>

                                <div class="lesson-bottom">

                                    <div class="progress-wrapper">
                                        <div class="progress">
                                            <div class="progress-bar"></div>
                                        </div>
                                    </div>

                                    <span>3/5</span>

                                    <div class="sound-button">
                                        <i class="bi bi-volume-up-fill"></i>
                                    </div>

                                </div>

                            </div>


                            <!-- Categories -->
                            <div class="category-grid">

                                <div class="category-item">
                                    <i class="bi bi-book"></i>
                                    <span>Vocabulary</span>
                                </div>

                                <div class="category-item">
                                    <i class="bi bi-braces"></i>
                                    <span>Kanji</span>
                                </div>

                                <div class="category-item">
                                    <i class="bi bi-file-text"></i>
                                    <span>Grammar</span>
                                </div>

                                <div class="category-item">
                                    <i class="bi bi-lightning-charge"></i>
                                    <span>Practice</span>
                                </div>

                            </div>


                            <!-- Hiragana -->
                            <div class="hiragana-section">

                                <div class="hiragana-header">
                                    <span>Hiragana</span>

                                    <a href="#">
                                        See all →
                                    </a>
                                </div>

                                <div class="hiragana-grid">

                                    <div class="hiragana-tile active">
                                        あ
                                    </div>

                                    <div class="hiragana-tile blue">
                                        か
                                    </div>

                                    <div class="hiragana-tile purple">
                                        さ
                                    </div>

                                    <div class="hiragana-tile pink">
                                        た
                                    </div>

                                    <div class="hiragana-tile blue">
                                        な
                                    </div>

                                    <div class="hiragana-tile purple">
                                        は
                                    </div>

                                    <div class="hiragana-tile pink">
                                        ま
                                    </div>

                                    <div class="hiragana-tile active">
                                        や
                                    </div>

                                </div>

                            </div>


                            <!-- Phone Bottom -->
                            <div class="phone-home-indicator"></div>

                        </div>

                    </div>

                </div>

            </div>

        </div>
    </div>
</section>


<style>

/* ===================================================== */
/* HERO BACKGROUND                                       */
/* ===================================================== */

.hero-section {
    position: relative;
    min-height: 720px;
    display: flex;
    align-items: center;
    background:
        radial-gradient(
            circle at 75% 40%,
            rgba(118, 58, 190, 0.30),
            transparent 30%
        ),
        radial-gradient(
            circle at 20% 30%,
            rgba(89, 35, 160, 0.12),
            transparent 35%
        ),
        linear-gradient(
            135deg,
            #090511 0%,
            #130722 45%,
            #25104d 100%
        );
}

/* Background Glow */

.hero-glow {
    position: absolute;
    border-radius: 50%;
    filter: blur(100px);
    pointer-events: none;
}

.hero-glow-1 {
    width: 450px;
    height: 450px;
    right: 5%;
    top: 15%;
    background: rgba(139, 92, 246, 0.18);
}

.hero-glow-2 {
    width: 300px;
    height: 300px;
    right: 35%;
    bottom: 0;
    background: rgba(236, 72, 153, 0.10);
}


/* ===================================================== */
/* LEFT SIDE                                             */
/* ===================================================== */

.hero-badge {
    display: inline-flex;
    align-items: center;

    padding: 8px 18px;

    border-radius: 999px;

    font-size: 14px;
    font-weight: 600;

    color: #c084fc;

    background: rgba(147, 51, 234, 0.12);

    border: 1px solid rgba(192, 132, 252, 0.35);

    box-shadow:
        0 0 20px rgba(168, 85, 247, 0.08);

    backdrop-filter: blur(10px);
}


.hero-title {
    font-size: clamp(3.2rem, 5vw, 5rem);
    font-weight: 300;

    line-height: 1.05;

    letter-spacing: -2px;
}


.gradient-text {
    background:
        linear-gradient(
            90deg,
            #c084fc,
            #ec4899
        );

    -webkit-background-clip: text;
    background-clip: text;

    -webkit-text-fill-color: transparent;
}


.hero-description {
    max-width: 520px;

    font-size: 1.08rem;
    line-height: 1.8;

    color: rgba(255,255,255,0.68) !important;
}


.hero-cta {
    position: relative;

    color: white;

    font-weight: 700;

    border: none;

    background:
        linear-gradient(
            90deg,
            #ec65b7,
            #9136df
        );

    box-shadow:
        0 12px 35px rgba(168, 55, 221, 0.35);

    transition:
        transform .3s ease,
        box-shadow .3s ease;
}


.hero-cta:hover {
    color: white;

    transform: translateY(-4px);

    box-shadow:
        0 18px 45px rgba(168, 55, 221, 0.48);
}


.hero-cta i {
    transition: transform .3s ease;
}


.hero-cta:hover i {
    transform: translateX(5px);
}


.hero-trust {
    color: rgba(255,255,255,0.42) !important;
}


/* ===================================================== */
/* RIGHT VISUAL                                         */
/* ===================================================== */

.hero-visual {
    position: relative;

    min-height: 590px;

    display: flex;
    justify-content: center;
    align-items: center;
}


/* Main Glow */

.visual-glow {
    position: absolute;

    width: 420px;
    height: 420px;

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(139, 92, 246, 0.30),
            rgba(76, 29, 149, 0.06) 55%,
            transparent 75%
        );

    filter: blur(10px);
}


/* ===================================================== */
/* JAPAN BACKGROUND ORB                                 */
/* ===================================================== */

.japan-orb {
    position: absolute;

    width: 420px;
    height: 420px;

    border-radius: 50%;

    right: 0;

    background:
        radial-gradient(
            circle at 60% 30%,
            rgba(164, 112, 255, 0.42),
            transparent 45%
        ),
        linear-gradient(
            145deg,
            #4b227d,
            #241146
        );

    border: 1px solid rgba(192,132,252,0.35);

    box-shadow:
        0 0 70px rgba(139,92,246,0.25),
        inset 0 0 80px rgba(255,255,255,0.04);

    overflow: hidden;

    opacity: .85;
}


.sun {
    position: absolute;

    width: 90px;
    height: 90px;

    border-radius: 50%;

    top: 55px;
    right: 80px;

    background:
        radial-gradient(
            circle,
            #f4b5dc,
            #bb71ed 60%,
            transparent 72%
        );

    filter: blur(1px);
}


.mountain {
    position: absolute;

    width: 180px;
    height: 180px;

    left: 125px;
    top: 120px;

    background:
        linear-gradient(
            135deg,
            transparent 48%,
            #ded2f8 49%,
            #9f8bd5 60%,
            #60438e 61%
        );

    clip-path: polygon(
        50% 0,
        100% 100%,
        0 100%
    );

    opacity: .9;
}


/* ===================================================== */
/* PAGODA                                               */
/* ===================================================== */

.pagoda {
    position: absolute;

    bottom: 55px;
    right: 80px;

    width: 100px;
    height: 140px;
}


.pagoda-roof {
    position: absolute;

    left: 50%;

    transform: translateX(-50%);

    height: 14px;

    background: #e9b7f6;

    border-radius: 50%;
}


.roof-1 {
    width: 115px;
    top: 15px;
}

.roof-2 {
    width: 90px;
    top: 48px;
}

.roof-3 {
    width: 65px;
    top: 80px;
}


.pagoda-body {
    position: absolute;

    width: 38px;
    height: 92px;

    bottom: 0;
    left: 50%;

    transform: translateX(-50%);

    border-radius: 4px;

    background:
        linear-gradient(
            90deg,
            #43235d,
            #8a4cae,
            #43235d
        );
}


/* ===================================================== */
/* PHONE                                               */
/* ===================================================== */

.phone-wrapper {
    position: relative;

    z-index: 10;

    transform: rotate(1deg);

    filter:
        drop-shadow(
            0 30px 50px rgba(0,0,0,.45)
        );
}


.phone {
    width: 330px;
    min-height: 590px;

    padding: 18px;

    position: relative;

    border-radius: 42px;

    background:
        linear-gradient(
            145deg,
            #191127,
            #08050e
        );

    border: 2px solid rgba(192,132,252,.55);

    box-shadow:
        0 0 0 5px rgba(147,51,234,.08),
        0 0 50px rgba(139,92,246,.30);

    overflow: hidden;
}


/* Phone Header */

.phone-header {
    display: flex;
    align-items: center;

    gap: 10px;

    padding: 5px 4px 20px;
}


.app-avatar {
    width: 38px;
    height: 38px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 12px;

    background:
        linear-gradient(
            135deg,
            #9f5bea,
            #ec6bc0
        );

    font-size: 18px;
}


.app-name {
    color: white;

    font-weight: 700;

    font-size: 14px;
}


.app-subtitle {
    color: rgba(255,255,255,.42);

    font-size: 10px;
}


.menu-button {
    margin-left: auto;

    width: 32px;
    height: 32px;

    display: flex;
    justify-content: center;
    align-items: center;

    border-radius: 10px;

    color: white;

    background: rgba(255,255,255,.06);
}


/* ===================================================== */
/* LESSON CARD                                          */
/* ===================================================== */

.lesson-card {
    padding: 18px;

    border-radius: 20px;

    background:
        linear-gradient(
            145deg,
            rgba(111,45,196,.40),
            rgba(58,22,112,.35)
        );

    border: 1px solid rgba(192,132,252,.18);

    box-shadow:
        inset 0 1px rgba(255,255,255,.05);
}


.lesson-label {
    color: rgba(255,255,255,.52);

    font-size: 11px;
}


.lesson-word {
    margin-top: 8px;

    color: white;

    font-size: 30px;
    font-weight: 600;
}


.lesson-romaji {
    color: #c084fc;

    font-size: 11px;

    margin-top: 2px;
}


.lesson-bottom {
    display: flex;
    align-items: center;

    gap: 9px;

    margin-top: 16px;
}


.lesson-bottom > span {
    color: rgba(255,255,255,.5);

    font-size: 10px;
}


.progress-wrapper {
    flex: 1;
}


.progress {
    height: 7px;

    border-radius: 10px;

    background: rgba(0,0,0,.25);
}


.progress-bar {
    width: 62%;

    height: 100%;

    border-radius: inherit;

    background:
        linear-gradient(
            90deg,
            #ec65b7,
            #9d4edd
        );
}


.sound-button {
    width: 33px;
    height: 33px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    color: white;

    background:
        linear-gradient(
            135deg,
            #8139dc,
            #bd5ce2
        );
}


/* ===================================================== */
/* CATEGORY GRID                                       */
/* ===================================================== */

.category-grid {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 8px;

    margin-top: 15px;
}


.category-item {
    display: flex;
    flex-direction: column;

    align-items: center;
    justify-content: center;

    min-height: 70px;

    border-radius: 14px;

    background: rgba(255,255,255,.045);

    border: 1px solid rgba(255,255,255,.05);

    color: rgba(255,255,255,.65);

    transition: .3s ease;
}


.category-item i {
    font-size: 19px;

    color: #c084fc;

    margin-bottom: 5px;
}


.category-item span {
    font-size: 8px;
}


.category-item:hover {
    transform: translateY(-3px);

    background: rgba(192,132,252,.12);
}


/* ===================================================== */
/* HIRAGANA                                             */
/* ===================================================== */

.hiragana-section {
    margin-top: 18px;
}


.hiragana-header {
    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 10px;
}


.hiragana-header span {
    color: white;

    font-weight: 600;

    font-size: 13px;
}


.hiragana-header a {
    color: #c084fc;

    font-size: 10px;

    text-decoration: none;
}


.hiragana-grid {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 8px;
}


.hiragana-tile {
    height: 43px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 11px;

    color: white;

    font-size: 19px;
    font-weight: 600;

    background:
        linear-gradient(
            145deg,
            #742bd1,
            #4e20a7
        );
}


.hiragana-tile.blue {
    background:
        linear-gradient(
            145deg,
            #5865f2,
            #4035aa
        );
}


.hiragana-tile.purple {
    background:
        linear-gradient(
            145deg,
            #784bd4,
            #4d2a9b
        );
}


.hiragana-tile.pink {
    background:
        linear-gradient(
            145deg,
            #d653b0,
            #923d9c
        );
}


.hiragana-tile.active {
    box-shadow:
        0 0 18px rgba(168,85,247,.28);
}


/* Phone Bottom */

.phone-home-indicator {
    position: absolute;

    bottom: 8px;
    left: 50%;

    transform: translateX(-50%);

    width: 85px;
    height: 4px;

    border-radius: 20px;

    background: rgba(255,255,255,.25);
}


/* ===================================================== */
/* FLOATING CARDS                                      */
/* ===================================================== */

.floating-card {
    position: absolute;

    z-index: 15;

    width: 135px;

    padding: 16px;

    border-radius: 18px;

    background:
        linear-gradient(
            145deg,
            rgba(32,17,59,.92),
            rgba(16,8,30,.88)
        );

    border:
        1px solid rgba(192,132,252,.45);

    backdrop-filter: blur(16px);

    box-shadow:
        0 20px 50px rgba(0,0,0,.35),
        0 0 30px rgba(139,92,246,.12);

    color: white;

    text-align: center;

    animation: floatingCard 5s ease-in-out infinite;
}


.card-sakana {
    top: 95px;
    left: 10px;

    transform: rotate(-7deg);
}


.card-yama {
    bottom: 75px;
    left: 20px;

    transform: rotate(-5deg);

    animation-delay: -2s;
}


.card-tatemono {
    right: -20px;
    bottom: 95px;

    transform: rotate(7deg);

    animation-delay: -3.5s;
}


.hiragana-symbol {
    font-size: 29px;

    color: #e9c4ff;

    font-weight: 700;
}


.mini-illustration {
    font-size: 34px;

    margin: 6px 0;
}


.jp-word {
    font-size: 14px;

    font-weight: 600;
}


.floating-card small {
    display: block;

    margin-top: 2px;

    font-size: 9px;

    color: rgba(255,255,255,.46);
}


/* ===================================================== */
/* FLOATING ANIMATION                                  */
/* ===================================================== */

@keyframes floatingCard {

    0%, 100% {
        translate: 0 0;
    }

    50% {
        translate: 0 -12px;
    }

}


/* ===================================================== */
/* RESPONSIVE                                           */
/* ===================================================== */

@media (max-width: 1199px) {

    .phone {
        width: 300px;
    }

    .japan-orb {
        width: 370px;
        height: 370px;
    }

    .card-sakana {
        left: -5px;
    }

    .card-tatemono {
        right: -5px;
    }

}


@media (max-width: 991px) {

    .hero-section {
        padding-top: 80px;
        padding-bottom: 80px;
    }

    .hero-visual {
        margin-top: 30px;

        min-height: 620px;
    }

    .japan-orb {
        right: 50%;

        transform: translateX(50%);
    }

}


@media (max-width: 575px) {

    .hero-title {
        font-size: 3rem;
    }

    .hero-description {
        font-size: .95rem;
    }

    .hero-visual {
        min-height: 540px;
        transform: scale(.82);
        transform-origin: top center;
        margin-bottom: -80px;
    }

    .card-sakana {
        left: -15px;
    }

    .card-tatemono {
        right: -25px;
    }

}

</style>
<!-- Courses Section -->
<!-- <section id="courses" class="courses-section py-5">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="fw-extrabold display-6 mb-3 text-white">Courses for every level</h2>
            <p class="opacity-75 mx-auto text-light" style="max-width: 600px;">Pick a path and start learning in minutes.</p>
        </div>

        <div class="row g-4"> 
            <div class="col-md-4">
                <div class="feature-card p-4 h-100 text-white">
                    <div class="mb-4 d-flex align-items-center justify-content-center rounded-3 fs-3 fw-bold" style="width: 48px; height: 48px; background: rgba(147, 51, 234, 0.15); color: #c084fc;">
                        あ
                    </div>
                    <h3 class="h5 fw-bold mb-2">Hiragana & Katakana</h3>
                    <p class="opacity-75 small mb-3">Master the two syllabaries with playful memory aids.</p>
                    <span class="small fw-semibold" style="color: #c084fc;">24 lessons</span>
                </div>
            </div> 
            <div class="col-md-4">
                <div class="feature-card p-4 h-100 text-white">
                    <div class="mb-4 d-flex align-items-center justify-content-center rounded-3 fs-3 fw-bold" style="width: 48px; height: 48px; background: rgba(147, 51, 234, 0.15); color: #c084fc;">
                        語
                    </div>
                    <h3 class="h5 fw-bold mb-2">Core Vocabulary</h3>
                    <p class="opacity-75 small mb-3">Build the 2,000 most useful words for daily conversation.</p>
                    <span class="small fw-semibold" style="color: #c084fc;">120 lessons</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card p-4 h-100 text-white">
                    <div class="mb-4 d-flex align-items-center justify-content-center rounded-3 fs-3 fw-bold" style="width: 48px; height: 48px; background: rgba(147, 51, 234, 0.15); color: #c084fc;">
                        文
                    </div>
                    <h3 class="h5 fw-bold mb-2">JLPT Grammar</h3>
                    <p class="opacity-75 small mb-3">Progress from N5 to N3 with crystal-clear explanations.</p>
                    <span class="small fw-semibold" style="color: #c084fc;">86 lessons</span>
                </div>
            </div>
        </div>
    </div>
</section> -->

<!-- Features / Methods Component -->
<!-- <section id="methods" class="py-5"> 
    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="fw-extrabold display-6 mb-3 text-white">
                A learning method that <span class="gradient-text" style="color: #c084fc;">actually sticks.</span>
            </h2>
            <p class="opacity-75 mx-auto text-light" style="max-width: 600px;">
                Science-backed techniques wrapped in a playful, game-like experience.
            </p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card p-4 h-100 text-white">
                    <i class="bi bi-eye fs-3 mb-3 d-block" style="color: #c084fc;"></i>
                    <h5 class="fw-bold mb-2">Visual Mnemonics</h5>
                    <p class="opacity-75 small mb-0">Every word is paired with a vivid illustration so meanings become unforgettable.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card p-4 h-100 text-white">
                    <i class="bi bi-controller fs-3 mb-3 d-block" style="color: #c084fc;"></i>
                    <h5 class="fw-bold mb-2">Game-Like Drills</h5>
                    <p class="opacity-75 small mb-0">Earn streaks, unlock levels, and stay motivated with quick, rewarding challenges.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card p-4 h-100 text-white">
                    <i class="bi bi-headphones fs-3 mb-3 d-block" style="color: #c084fc;"></i>
                    <h5 class="fw-bold mb-2">Native Audio</h5>
                    <p class="opacity-75 small mb-0">Listen to authentic pronunciation and shadow real conversations from day one.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card p-4 h-100 text-white">
                    <i class="bi bi-brain fs-3 mb-3 d-block" style="color: #c084fc;"></i>
                    <h5 class="fw-bold mb-2">Spaced Repetition</h5>
                    <p class="opacity-75 small mb-0">Review words right before you forget them, powered by smart scheduling.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card p-4 h-100 text-white">
                    <i class="bi bi-layers fs-3 mb-3 d-block" style="color: #c084fc;"></i>
                    <h5 class="fw-bold mb-2">Structured Paths</h5>
                    <p class="opacity-75 small mb-0">Follow curated courses from hiragana basics to JLPT-ready grammar.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card p-4 h-100 text-white">
                    <i class="bi bi-stars fs-3 mb-3 d-block" style="color: #c084fc;"></i>
                    <h5 class="fw-bold mb-2">Cultural Context</h5>
                    <p class="opacity-75 small mb-0">Learn the why behind phrases with notes on culture, politeness, and nuance.</p>
                </div>
            </div> 
        </div>
    </div> 
</section>  -->

<!-- Features Zig-Zag Section (Drops Style) -->
<section class="drops-features-section py-5 position-relative overflow-hidden" style="background-color: #0b0f19;">
    <div class="container py-5">
        
        <!-- Section Header -->
        <div class="text-center mx-auto mb-5" style="max-width: 700px;">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-3" style="background: rgba(192, 132, 252, 0.15); border: 1px solid rgba(192, 132, 252, 0.3); color: #c084fc;">
                <i class="bi bi-stars"></i>
                <span class="small fw-semibold">Why Kotoba is Different</span>
            </div>
            <h2 class="display-5 fw-bold text-white font-serif">
                Master Japanese the <span style="color: #c084fc;">fun way</span>
            </h2>
            <p class="text-light opacity-75">
                Designed to make learning effortless, engaging, and tailored for your everyday routine.
            </p>
        </div>

        <!-- Feature 1: Bite-sized Lessons (Text Left, Illustration Right) -->
        <div class="row align-items-center g-5 mb-5 py-4">
            <div class="col-lg-6 text-start">
                <span class="badge rounded-pill px-3 py-1 mb-3" style="background: rgba(255, 193, 7, 0.15); color: #ffc107;">01 / Fast & Easy</span>
                <h3 class="display-6 fw-bold text-white mb-3 font-serif">Bite-sized 5-minute sessions</h3>
                <p class="text-light opacity-75 fs-6 mb-4" style="line-height: 1.8;">
                    No more boring textbooks. Dive into quick, high-energy games and lessons designed to fit your busy schedule and keep your motivation high.
                </p>
                <div class="d-flex align-items-center gap-2 text-white small">
                    <i class="bi bi-check-circle-fill" style="color: #c084fc;"></i> Fits into any daily routine
                </div>
            </div>
            <div class="col-lg-6 text-center position-relative">
                <!-- Borderless Floating Illustration -->
                <div class="position-relative d-flex justify-content-center align-items-center">
                    <div class="position-absolute rounded-circle" style="width: 260px; height: 260px; background: rgba(192, 132, 252, 0.1); filter: blur(50px);"></div>
                    <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=600&q=80" alt="Bite-sized learning" class="img-fluid floating-graphic rounded-4" style="max-width: 380px; object-fit: cover; opacity: 0.95; mask-image: radial-gradient(circle, white 60%, transparent 100%); -webkit-mask-image: radial-gradient(circle, white 60%, transparent 100%);">
                </div>
            </div>
        </div>

        <!-- Feature 2: Visual Mnemonics (Illustration Left, Text Right - Zig-zag) -->
        <div class="row align-items-center g-5 mb-5 py-4 flex-lg-row-reverse">
            <div class="col-lg-6 text-start">
                <span class="badge rounded-pill px-3 py-1 mb-3" style="background: rgba(192, 132, 252, 0.15); color: #c084fc;">02 / Smart Memory</span>
                <h3 class="display-6 fw-bold text-white mb-3 font-serif">Visual mnemonics for Kanji & Kana</h3>
                <p class="text-light opacity-75 fs-6 mb-4" style="line-height: 1.8;">
                    Never forget a character again. Our clever visual association techniques connect Japanese symbols directly to meanings you already know.
                </p>
                <div class="d-flex align-items-center gap-2 text-white small">
                    <i class="bi bi-check-circle-fill" style="color: #ffc107;"></i> Faster long-term retention
                </div>
            </div>
            <div class="col-lg-6 text-center position-relative">
                <!-- Borderless Floating Illustration -->
                <div class="position-relative d-flex justify-content-center align-items-center">
                    <div class="position-absolute rounded-circle" style="width: 260px; height: 260px; background: rgba(255, 193, 7, 0.1); filter: blur(50px);"></div>
                    <img src="https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=600&q=80" alt="Visual mnemonics" class="img-fluid floating-graphic-delayed rounded-4" style="max-width: 380px; object-fit: cover; opacity: 0.95; mask-image: radial-gradient(circle, white 60%, transparent 100%); -webkit-mask-image: radial-gradient(circle, white 60%, transparent 100%);">
                </div>
            </div>
        </div>

        <!-- Feature 3: Real Conversations (Text Left, Illustration Right) -->
        <div class="row align-items-center g-5 py-4">
            <div class="col-lg-6 text-start">
                <span class="badge rounded-pill px-3 py-1 mb-3" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa;">03 / Practical Use</span>
                <h3 class="display-6 fw-bold text-white mb-3 font-serif">Prepare for real-world conversations</h3>
                <p class="text-light opacity-75 fs-6 mb-4" style="line-height: 1.8;">
                    Learn practical vocabulary and natural phrasing used in modern Japan, ensuring you can speak with confidence from day one.
                </p>
                <div class="d-flex align-items-center gap-2 text-white small">
                    <i class="bi bi-check-circle-fill" style="color: #60a5fa;"></i> Speak naturally with locals
                </div>
            </div>
            <div class="col-lg-6 text-center position-relative">
                <!-- Borderless Floating Illustration -->
                <div class="position-relative d-flex justify-content-center align-items-center">
                    <div class="position-absolute rounded-circle" style="width: 260px; height: 260px; background: rgba(59, 130, 246, 0.1); filter: blur(50px);"></div>
                    <img src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=600&q=80" alt="Real conversations" class="img-fluid floating-graphic rounded-4" style="max-width: 380px; object-fit: cover; opacity: 0.95; mask-image: radial-gradient(circle, white 60%, transparent 100%); -webkit-mask-image: radial-gradient(circle, white 60%, transparent 100%);">
                </div>
            </div>
        </div>

    </div>
</section>

<!-- CSS Animations for Smooth Floating Graphics -->
<style>
    @keyframes floatUpDown {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    .floating-graphic {
        animation: floatUpDown 4s ease-in-out infinite;
    }
    .floating-graphic-delayed {
        animation: floatUpDown 4.5s ease-in-out infinite 1s;
    }
</style>

<section class="learning-journey-section py-5 position-relative overflow-hidden" style="background-color: #160f29;">
    <div class="container py-5 position-relative" style="max-width: 1300px;">
        
        <!-- Section Header -->
        <div class="text-center mx-auto mb-5" style="max-width: 750px;">
            <h2 class="display-5 fw-bold font-serif mb-3 text-white">
                SakuraLearn is your <span style="color: #ffc107;">all-in-one</span> Japanese learning platform
            </h2>
            <p class="text-light opacity-75 fs-5">
                Master Japanese naturally with powerful tools designed for your learning journey.
            </p>
        </div>

        <!-- Desktop Wavy Journey Container -->
        <div class="journey-wrapper position-relative d-none d-lg-block" style="height: 750px; width: 100%;">
            
            <!-- SVG Curved Wavy Line -->
            <svg class="position-absolute w-100 h-100 top-0 start-0 pointer-events-none" viewBox="0 0 1200 700" fill="none" xmlns="http://www.w3.org/2000/svg" style="z-index: 1;">
                <path d="M 50 150 C 350 20, 450 550, 650 450 C 850 350, 950 100, 1150 200" stroke="#8a99ad" stroke-width="4" stroke-dasharray="10 10" opacity="0.8" />
            </svg>

            <!-- Node 1: Speak from day one (Top Left) -->
            <div class="position-absolute" style="top: 2%; left: 3%; width: 320px; z-index: 3;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge rounded-circle p-2 d-flex align-items-center justify-content-center shadow" style="width: 32px; height: 32px; background-color: #ffc107; color: #0b0f19;"><i class="bi bi-chat-dots-fill"></i></span>
                    <span style="color: #ffc107;" class="small fw-semibold">話す (Speak)</span>
                </div>
                <div class="card journey-card border-0 p-4 rounded-4 shadow-lg text-white position-relative">
                    
                    <!-- Speech Bubble -->
                    <div class="speech-bubble p-2 rounded-3 bg-light text-dark position-absolute shadow-sm jump-bubble" style="top: 20px; right: 20px; z-index: 5; font-size: 0.75rem; animation-delay: 0s;">
                        <div class="fw-bold">はじめまして</div>
                        <div class="text-muted" style="font-size: 0.65rem;">Nice to meet you</div>
                    </div>

                    <div class="rounded-circle overflow-hidden mb-3 mx-auto shadow img-wrapper" style="width: 120px; height: 120px; border: 3px solid rgba(255,255,255,0.2);">
                        <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=400&q=80" alt="Speak" class="w-100 h-100 object-fit-cover">
                    </div>

                    <h4 class="fw-bold mb-2 font-serif" style="font-size: 1.25rem;">Speak from day one</h4>
                    <p class="text-light opacity-75 small mb-0" style="font-size: 0.85rem;">
                        Short, guided conversations build the rhythm and confidence to use Japanese in everyday moments.
                    </p>
                </div>
            </div>

            <!-- Node 2: Make kana yours (Center / Bottom) -->
            <div class="position-absolute" style="top: 38%; left: 37%; width: 320px; z-index: 3;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge rounded-circle p-2 d-flex align-items-center justify-content-center shadow" style="width: 32px; height: 32px; background-color: #ffc107; color: #0b0f19;"><i class="bi bi-book-fill"></i></span>
                    <span style="color: #ffc107;" class="small fw-semibold">読む (Read)</span>
                </div>
                <div class="card journey-card border-0 p-4 rounded-4 shadow-lg text-white position-relative">
                    
                    <div class="speech-bubble p-2 rounded-3 bg-light text-dark position-absolute shadow-sm jump-bubble" style="top: 20px; right: 20px; z-index: 5; font-size: 0.75rem; animation-delay: 0.3s;">
                        <div class="fw-bold">あいうえお</div>
                        <div class="text-muted" style="font-size: 0.65rem;">Hiragana vowels</div>
                    </div>

                    <div class="rounded-circle overflow-hidden mb-3 mx-auto shadow img-wrapper" style="width: 120px; height: 120px; border: 3px solid rgba(255,255,255,0.2);">
                        <img src="https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=400&q=80" alt="Kana" class="w-100 h-100 object-fit-cover">
                    </div>

                    <h4 class="fw-bold mb-2 font-serif" style="font-size: 1.25rem;">Make kana yours</h4>
                    <p class="text-light opacity-75 small mb-0" style="font-size: 0.85rem;">
                        Interactive flashcards and mnemonic visuals make remembering Japanese characters effortless.
                    </p>
                </div>
            </div>

            <!-- Node 3: Learn through culture (Top Right) -->
            <div class="position-absolute" style="top: 6%; left: 70%; width: 320px; z-index: 3;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge rounded-circle p-2 d-flex align-items-center justify-content-center shadow" style="width: 32px; height: 32px; background-color: #ffc107; color: #0b0f19;"><i class="bi bi-geo-alt-fill"></i></span>
                    <span style="color: #ffc107;" class="small fw-semibold">出会う (Culture)</span>
                </div>
                <div class="card journey-card border-0 p-4 rounded-4 shadow-lg text-white position-relative">
                    
                    <div class="speech-bubble p-2 rounded-3 bg-light text-dark position-absolute shadow-sm jump-bubble" style="top: 20px; right: 20px; z-index: 5; font-size: 0.75rem; animation-delay: 0.6s;">
                        <div class="fw-bold">おすすめは？</div>
                        <div class="text-muted" style="font-size: 0.65rem;">What do you recommend?</div>
                    </div>

                    <div class="rounded-circle overflow-hidden mb-3 mx-auto shadow img-wrapper" style="width: 120px; height: 120px; border: 3px solid rgba(255,255,255,0.2);">
                        <img src="https://images.unsplash.com/photo-1493976040374-85c8e12f0c0e?auto=format&fit=crop&w=400&q=80" alt="Culture" class="w-100 h-100 object-fit-cover">
                    </div>

                    <h4 class="fw-bold mb-2 font-serif" style="font-size: 1.25rem;">Learn through culture</h4>
                    <p class="text-light opacity-75 small mb-0" style="font-size: 0.85rem;">
                        Discover the language inside food, travel, etiquette, and conversations that bring Japan closer.
                    </p>
                </div>
            </div>

        </div>

        <!-- Mobile Responsive View (Vertical Stack) -->
        <div class="d-lg-none d-flex flex-column gap-4">
            @foreach([
                [
                    'title' => 'Speak from day one', 
                    'jp' => '話す (Speak)', 
                    'desc' => 'Short, guided conversations build rhythm and confidence.', 
                    'img' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=400&q=80'
                ],
                [
                    'title' => 'Make kana yours', 
                    'jp' => '読む (Read)', 
                    'desc' => 'Interactive flashcards and mnemonic visuals.', 
                    'img' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=400&q=80'
                ],
                [
                    'title' => 'Learn through culture', 
                    'jp' => '出会う (Culture)', 
                    'desc' => 'Discover the language inside food, travel, and etiquette.', 
                    'img' => 'https://images.unsplash.com/photo-1493976040374-85c8e12f0c0e?auto=format&fit=crop&w=400&q=80'
                ],
            ] as $node)
            <div class="card journey-card border-0 p-4 rounded-4 shadow-lg text-white">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge rounded-circle p-2" style="background-color: #ffc107; color: #0b0f19;"><i class="bi bi-star-fill"></i></span>
                    <span style="color: #ffc107;" class="small fw-semibold">{{ $node['jp'] }}</span>
                </div>
                <div class="rounded-circle overflow-hidden mb-3 mx-auto shadow img-wrapper" style="width: 100px; height: 100px; border: 2px solid #ffc107;">
                    <img src="{{ $node['img'] }}" alt="{{ $node['title'] }}" class="w-100 h-100 object-fit-cover">
                </div>
                <h4 class="fw-bold text-center mb-2 font-serif">{{ $node['title'] }}</h4>
                <p class="text-light opacity-75 small text-center mb-0">
                    {{ $node['desc'] }}
                </p>
            </div>
            @endforeach
        </div>

    </div>
</section>

<!-- CSS Styling for Glassmorphism, Hover & Jump Animation -->
<style>
    @keyframes floatJump {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-8px);
        }
    }

    .jump-bubble {
        animation: floatJump 2.5s ease-in-out infinite;
    }

    .journey-card {
        background: rgba(255, 255, 255, 0.04);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.4s cubic-bezier(0.165, 0.84, 0.44, 1), border-color 0.4s ease;
    }

    .journey-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 0 20px rgba(255, 193, 7, 0.15) !important;
        border-color: rgba(255, 193, 7, 0.4) !important;
    }

    .journey-card:hover .img-wrapper img {
        transform: scale(1.1);
    }

    .img-wrapper img {
        transition: transform 0.4s ease;
    }

    /* Learning Journey Section - Compact Spacing Fix */
.learning-journey-section {
    padding-top: 2rem !important;
    padding-bottom: 2rem !important;
    min-height: auto !important;
    overflow: hidden;
}

/* Wavy Dotted Line & Cards Wrapper Container */
.journey-container {
    position: relative;
    max-width: 1100px;
    margin: 0 auto;
    padding: 1rem 0;
}

/* Reduce vertical gaps between grid rows if applicable */
.journey-grid {
    gap: 1.5rem !important;
}
</style>

<!-- Continuous Scroll / Marquee Section (User Feedback - Testimonials) -->
<section class="py-5 position-relative overflow-hidden" style="background-color: #0b0f19; border-top: 1px solid rgba(192, 132, 252, 0.05);">
    <div class="container-fluid px-0">
        
        <div class="text-center mx-auto mb-4" style="max-width: 600px;">
            <h4 class="h5 fw-bold text-white mb-1 font-serif">Loved by Language Learners</h4>
            <p class="text-muted small mb-0">See what others are saying about their journey to fluency with Kotoba</p>
        </div>

        <!-- Marquee Wrapper -->
        <div class="marquee-wrapper position-relative" style="width: 100%; overflow: hidden; white-space: nowrap; padding: 10px 0;">
            
            <!-- Fades on edges to blend with background -->
            <div class="position-absolute top-0 start-0 h-100" style="width: 150px; background: linear-gradient(to right, #0b0f19, transparent); z-index: 2;"></div>
            <div class="position-absolute top-0 end-0 h-100" style="width: 150px; background: linear-gradient(to left, #0b0f19, transparent); z-index: 2;"></div>

            <!-- Marquee Content (The moving elements) -->
            <div class="marquee-content d-inline-flex align-items-center gap-4" style="animation: marqueeScroll 50s linear infinite;">
                
                <!-- Feedback Item 1 -->
                <div class="marquee-item rounded-4 p-4 shadow-sm" style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(192, 132, 252, 0.2); width: 350px; white-space: normal; vertical-align: top;">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="User 1" class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
                        <div>
                            <div class="text-white fw-bold">Kenjiro Sato</div>
                        </div>
                        <div class="ms-auto text-warning fs-6">
                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                        </div>
                    </div>
                    <p class="text-light opacity-85 small mb-0" style="line-height: 1.6;">"The visual mnemonics are a game-changer! I used to struggle with Kanji, but now I remember them effortlessly. Highly recommended for beginners!"</p>
                </div>

                <!-- Feedback Item 2 -->
                <div class="marquee-item rounded-4 p-4 shadow-sm" style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(192, 132, 252, 0.2); width: 350px; white-space: normal; vertical-align: top;">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="User 2" class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
                        <div>
                            <div class="text-white fw-bold">Aiko Tanaka</div>
                        </div>
                        <div class="ms-auto text-warning fs-6">
                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                        </div>
                    </div>
                    <p class="text-light opacity-85 small mb-0" style="line-height: 1.6;">"I love that I can practice for just 5 minutes a day. It fits perfectly into my lunch break. The speaking exercises have really helped me feel confident."</p>
                </div>

                <!-- Feedback Item 3 -->
                <div class="marquee-item rounded-4 p-4 shadow-sm" style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(192, 132, 252, 0.2); width: 350px; white-space: normal; vertical-align: top;">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img src="https://randomuser.me/api/portraits/men/76.jpg" alt="User 3" class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
                        <div>
                            <div class="text-white fw-bold">Michael Chen</div>
                        </div>
                        <div class="ms-auto text-warning fs-6">
                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i>
                        </div>
                    </div>
                    <p class="text-light opacity-85 small mb-0" style="line-height: 1.6;">"The lessons are structured so well. I've tried other apps, but Kotoba makes the grammar concepts much easier to understand. The daily streak keeps me motivated."</p>
                </div>
                
                <!-- Feedback Item 4 (Duplicate Item 1 for seamless loop) -->
                <div class="marquee-item rounded-4 p-4 shadow-sm" style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(192, 132, 252, 0.2); width: 350px; white-space: normal; vertical-align: top;">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="User 1" class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
                        <div>
                            <div class="text-white fw-bold">Kenjiro Sato</div>
                        </div>
                         <div class="ms-auto text-warning fs-6">
                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                        </div>
                    </div>
                    <p class="text-light opacity-85 small mb-0" style="line-height: 1.6;">"The visual mnemonics are a game-changer! I used to struggle with Kanji, but now I remember them effortlessly. Highly recommended for beginners!"</p>
                </div>
                 <!-- Feedback Item 5 (Duplicate Item 2 for seamless loop) -->
                <div class="marquee-item rounded-4 p-4 shadow-sm" style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(192, 132, 252, 0.2); width: 350px; white-space: normal; vertical-align: top;">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="User 2" class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
                        <div>
                            <div class="text-white fw-bold">Aiko Tanaka</div>
                        </div>
                         <div class="ms-auto text-warning fs-6">
                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                        </div>
                    </div>
                    <p class="text-light opacity-85 small mb-0" style="line-height: 1.6;">"I love that I can practice for just 5 minutes a day. It fits perfectly into my lunch break. The speaking exercises have really helped me feel confident."</p>
                </div>

            </div>
        </div>

    </div>
</section>

<!-- CSS Animation for Continuous Scroll & Pause on Hover -->
<style>
    @keyframes marqueeScroll {
        0% {
            transform: translateX(0);
        }
        100% {
            transform: translateX(-50%); /* Scrolls half the total width */
        }
    }

    /* Pause animation when user hovers over the marquee */
    .marquee-wrapper:hover .marquee-content {
        animation-play-state: paused;
    }
</style>


@endsection

@push('scripts')
<!-- Lottie Animation Player CDN -->
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

<script>
    // Interactive Japanese Character Rain Setup (Sakura Cyber Theme)
        function createCharacterTexture(char) {
            const canvas = document.createElement('canvas');
            canvas.width = 64;
            canvas.height = 64;
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, 64, 64);
            ctx.font = 'Bold 36px sans-serif';
            ctx.fillStyle = '#f472b6';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(char, 32, 32);
            
            const texture = new THREE.CanvasTexture(canvas);
            texture.needsUpdate = true;
            return texture;
        }

        const particleCount = 35;
        const particleGroup = new THREE.Group();
        const particlesData = [];

        for (let i = 0; i < particleCount; i++) {
            const randomChar = characters[Math.floor(Math.random() * characters.length)];
            const texture = createCharacterTexture(randomChar);

            const material = new THREE.SpriteMaterial({
                map: texture,
                transparent: true,
                opacity: Math.random() * 0.6 + 0.3,
                blending: THREE.AdditiveBlending
            });

            const sprite = new THREE.Sprite(material);
            sprite.scale.set(1.5, 1.5, 1.5);

            sprite.position.x = (Math.random() - 0.5) * 18;
            sprite.position.y = Math.random() * 15 - 7.5;
            sprite.position.z = (Math.random() - 0.5) * 8;

            const speed = Math.random() * 0.015 + 0.005;
            const wobbleSpeed = Math.random() * 0.02 + 0.01;

            particleGroup.add(sprite);
            particlesData.push({ sprite, speed, wobbleSpeed, initialX: sprite.position.x });
        }

        scene.add(particleGroup);

        let mouseX = 0;
        let mouseY = 0;
        document.addEventListener('mousemove', (event) => {
            mouseX = (event.clientX / window.innerWidth - 0.5) * 2;
            mouseY = (event.clientY / window.innerHeight - 0.5) * 2;
        });

        function animate() {
            requestAnimationFrame(animate);

            camera.position.x += (mouseX * 1.5 - camera.position.x) * 0.05;
            camera.position.y += (-mouseY * 1.5 - camera.position.y) * 0.05;
            camera.lookAt(scene.position);

            particlesData.forEach((data, index) => {
                data.sprite.position.y -= data.speed;
                data.sprite.position.x = data.initialX + Math.sin(Date.now() * 0.002 + index) * 0.8;

                if (data.sprite.position.y < -8) {
                    data.sprite.position.y = 8;
                    data.initialX = (Math.random() - 0.5) * 18;
                }
            });

            renderer.render(scene, camera);
        }

        animate();

        window.addEventListener('resize', () => {
            const width = container.clientWidth;
            const height = container.clientHeight;
            camera.aspect = width / height;
            camera.updateProjectionMatrix();
            renderer.setSize(width, height);
        });
</script>
@endpush