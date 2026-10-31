@extends('layouts.app')

@section('content')
<div id="mainContent">
    <style>
    /* Methods Page Custom Glassmorphism Styling */
    .methods-container {
        min-height: 80vh;
        padding-top: 2rem;
        padding-bottom: 4rem;
    }

    .glass-card {
        background: rgba(25, 15, 45, 0.75);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(192, 132, 252, 0.2);
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        transition: transform 0.3s ease, border-color 0.3s ease;
    }

    .glass-card:hover {
        border-color: rgba(192, 132, 252, 0.4);
        transform: translateY(-4px);
    }

    .gradient-text {
        background: linear-gradient(135deg, #f472b6 0%, #c084fc 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 800;
    }

    .back-link {
        color: rgba(255, 255, 255, 0.75);
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .back-link:hover {
        color: #f472b6;
    }

    .method-badge {
        background: rgba(244, 114, 182, 0.2);
        color: #f472b6;
        border: 1px solid rgba(244, 114, 182, 0.4);
        padding: 4px 12px;
        border-radius: 50rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
</style>

<div class="container methods-container">
    <!-- Back Navigation -->
    <div class="mb-4">
        <a href="{{ url('/') }}" class="back-link d-inline-flex align-items-center gap-2 fw-semibold">
            <i class="bi bi-arrow-left"></i> Back to Home
        </a>
    </div>

    <!-- Hero Header Box -->
    <div class="glass-card p-5 mb-5 text-center">
        <span class="method-badge mb-3 d-inline-block">Pedagogical Approach</span>
        <h1 class="gradient-text display-4 mb-3">Our Learning Methods</h1>
        <p class="text-light opacity-75 lead mx-auto" style="max-width: 650px;">
            Master Japanese vocabulary, kanji, and grammar through bite-sized visual lessons designed to stick in just 5 minutes a day.
        </p>
    </div>

    <!-- Primary Learning Method Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="glass-card p-4 h-100">
                <div class="fs-1 mb-3">🌸</div>
                <h4 class="text-white fw-bold mb-2">Visual Mnemonics</h4>
                <p class="text-light opacity-75 small mb-0">
                    Learn hiragana, katakana, and kanji easily by connecting characters with memorable visual illustrations and stories.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card p-4 h-100">
                <div class="fs-1 mb-3">📖</div>
                <h4 class="text-white fw-bold mb-2">Bite-sized Lessons</h4>
                <p class="text-light opacity-75 small mb-0">
                    Short, focused daily modules that fit right into your busy schedule without feeling overwhelmed.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card p-4 h-100">
                <div class="fs-1 mb-3">⚡</div>
                <h4 class="text-white fw-bold mb-2">Active Practice & Reviews</h4>
                <p class="text-light opacity-75 small mb-0">
                    Reinforce your knowledge with interactive quizzes, mistake reviews, and targeted exam question practice.
                </p>
            </div>
        </div>
    </div>

    <!-- Additional Advanced Section (More Content) -->
    <div class="glass-card p-5 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <span class="method-badge mb-2 d-inline-block">Advanced Tech</span>
                <h3 class="text-white fw-bold mb-3">Smart Spaced Repetition System (SRS)</h3>
                <p class="text-light opacity-75 mb-4">
                    Our platform automatically tracks words or kanji you struggle with and schedules review sessions right before you are about to forget them, ensuring long-term memory retention.
                </p>
                <div class="d-flex gap-3 text-light small">
                    <div><i class="bi bi-check-circle-fill text-pink me-1"></i> Personalized Reviews</div>
                    <div><i class="bi bi-check-circle-fill text-pink me-1"></i> Error Tracking</div>
                </div>
            </div>
            <div class="col-lg-5 text-center">
                <div class="fs-1 py-4 rounded-4" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08);">
                    🧠 ⇄ 📈
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection