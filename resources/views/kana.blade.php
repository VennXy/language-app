{{-- resources/views/kana.blade.php --}}
@extends('layouts.app')

@section('content')
<!-- Interactive Kana Table Section -->
<!-- kana.blade.php ထဲရှိ section ကို ဒီလို ပြင်ပါ -->
<section id="kana-section" class="py-5 position-relative overflow-hidden" style="background-color: #0d061a; min-height: 100vh;">    
    <!-- Background Glow Effect -->
    <div class="position-absolute top-0 start-50 translate-middle-x rounded-circle" style="width: 500px; height: 500px; background: rgba(192, 132, 252, 0.07); filter: blur(100px); z-index: 0;"></div>

    <div class="container position-relative" style="z-index: 1; max-width: 1100px;">
        
        <!-- Section Header -->
        <div class="text-center mb-4">
            <span class="badge px-3 py-2 rounded-pill mb-2" style="background: rgba(192, 132, 252, 0.15); color: #c084fc; border: 1px solid rgba(192, 132, 252, 0.3);">
                <i class="bi bi-stars me-1"></i> Complete Kana Charts
            </span>
            <h2 class="text-white fw-bold font-serif">Hiragana & Katakana Master</h2>
            <p class="text-muted small">Select a tab below to switch charts and tap any card to listen to the pronunciation.</p>
        </div>

        <!-- Tab Select Bar (Hiragana / Katakana Switcher) -->
<div class="d-flex justify-content-center mb-5 py-2" style="position: sticky; top: 85px; z-index: 100;">
    <div class="p-1 rounded-pill d-flex gap-2 shadow-lg" style="background: rgba(13, 6, 26, 0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(192, 132, 252, 0.4);">
        <button id="btn-hiragana" onclick="switchTab('hiragana')" class="px-4 py-2 rounded-pill btn text-white fw-semibold btn-sm active-kana-btn" style="background: #c084fc; color: #0b0f19 !important; border: none; transition: all 0.3s;">
            Hiragana
        </button>
        <button id="btn-katakana" onclick="switchTab('katakana')" class="px-4 py-2 rounded-pill btn text-muted fw-semibold btn-sm" style="background: transparent; border: none; transition: all 0.3s;">
            Katakana 
        </button>
    </div>
</div>

        <!-- Kana Grid Container -->
        <div id="kana-grid" class="row row-cols-2 row-cols-sm-3 row-cols-md-5 g-3 justify-content-center">
            <!-- Dynamic JavaScript content will load here -->
        </div>

    </div>
</section>

<!-- Glassmorphism Custom CSS -->
<style>
    .glass-kana-card {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(192, 132, 252, 0.15);
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        overflow: hidden; /* ဒီလိုင်းလေး အသစ်ထည့်ပေးပါ */
    }

    .glass-kana-card:hover {
        transform: translateY(-5px) scale(1.02);
        background: rgba(192, 132, 252, 0.08);
        border-color: rgba(192, 132, 252, 0.5);
        box-shadow: 0 12px 40px 0 rgba(192, 132, 252, 0.2);
    }
</style>

<!-- JavaScript for Data, Tab Switching & Audio -->
<script>
    const hiraganaData = [
        {char: 'あ', romaji: 'a'}, {char: 'い', romaji: 'i'}, {char: 'う', romaji: 'u'}, {char: 'え', romaji: 'e'}, {char: 'お', romaji: 'o'},
        {char: 'か', romaji: 'ka'}, {char: 'き', romaji: 'ki'}, {char: 'く', romaji: 'ku'}, {char: 'け', romaji: 'ke'}, {char: 'こ', romaji: 'ko'},
        {char: 'さ', romaji: 'sa'}, {char: 'し', romaji: 'shi'}, {char: 'す', romaji: 'su'}, {char: 'せ', romaji: 'se'}, {char: 'そ', romaji: 'so'},
        {char: 'た', romaji: 'ta'}, {char: 'ち', romaji: 'chi'}, {char: 'つ', romaji: 'tsu'}, {char: 'て', romaji: 'te'}, {char: 'と', romaji: 'to'},
        {char: 'な', romaji: 'na'}, {char: 'に', romaji: 'ni'}, {char: 'ぬ', romaji: 'nu'}, {char: 'ね', romaji: 'ne'}, {char: 'の', romaji: 'no'},
        {char: 'は', romaji: 'ha'}, {char: 'ひ', romaji: 'hi'}, {char: 'ふ', romaji: 'fu'}, {char: 'へ', romaji: 'he'}, {char: 'ほ', romaji: 'ho'},
        {char: 'ま', romaji: 'ma'}, {char: 'み', romaji: 'mi'}, {char: 'む', romaji: 'mu'}, {char: 'め', romaji: 'me'}, {char: 'も', romaji: 'mo'},
        {char: 'や', romaji: 'ya'}, {char: 'ゆ', romaji: 'yu'}, {char: 'よ', romaji: 'yo'},
        {char: 'ら', romaji: 'ra'}, {char: 'り', romaji: 'ri'}, {char: 'る', romaji: 'ru'}, {char: 'れ', romaji: 're'}, {char: 'ろ', romaji: 'ro'},
        {char: 'わ', romaji: 'wa'}, {char: 'を', romaji: 'wo'}, {char: 'ん', romaji: 'n'}
    ];

    const katakanaData = [
        {char: 'ア', romaji: 'a'}, {char: 'イ', romaji: 'i'}, {char: 'ウ', romaji: 'u'}, {char: 'エ', romaji: 'e'}, {char: 'オ', romaji: 'o'},
        {char: 'カ', romaji: 'ka'}, {char: 'キ', romaji: 'ki'}, {char: 'ク', romaji: 'ku'}, {char: 'ケ', romaji: 'ke'}, {char: 'コ', romaji: 'ko'},
        {char: 'サ', romaji: 'sa'}, {char: 'シ', romaji: 'shi'}, {char: 'ス', romaji: 'su'}, {char: 'セ', romaji: 'se'}, {char: 'ソ', romaji: 'so'},
        {char: 'タ', romaji: 'ta'}, {char: 'チ', romaji: 'chi'}, {char: 'ツ', romaji: 'tsu'}, {char: 'テ', romaji: 'te'}, {char: 'ト', romaji: 'to'},
        {char: 'ナ', romaji: 'na'}, {char: 'ニ', romaji: 'ni'}, {char: 'ヌ', romaji: 'nu'}, {char: 'ネ', romaji: 'ne'}, {char: 'ノ', romaji: 'no'},
        {char: 'ハ', romaji: 'ha'}, {char: 'ヒ', romaji: 'hi'}, {char: 'フ', romaji: 'fu'}, {char: 'ヘ', romaji: 'he'}, {char: 'ホ', romaji: 'ho'},
        {char: 'マ', romaji: 'ma'}, {char: 'ミ', romaji: 'mi'}, {char: 'ム', romaji: 'mu'}, {char: 'メ', romaji: 'me'}, {char: 'モ', romaji: 'mo'},
        {char: 'ヤ', romaji: 'ya'}, {char: 'ユ', romaji: 'yu'}, {char: 'ヨ', romaji: 'yo'},
        {char: 'ら', romaji: 'ra'}, {char: 'り', romaji: 'ri'}, {char: 'る', romaji: 'ru'}, {char: 'れ', romaji: 're'}, {char: 'ろ', romaji: 'ro'},
        {char: 'わ', romaji: 'wa'}, {char: 'ヲ', romaji: 'wo'}, {char: 'ん', romaji: 'n'}
    ];

    let currentType = 'hiragana';

    function renderTable(type) {
        const grid = document.getElementById('kana-grid');
        grid.innerHTML = '';
        const data = type === 'hiragana' ? hiraganaData : katakanaData;

        data.forEach(item => {
            const col = document.createElement('div');
            col.className = 'col';
            col.innerHTML = `
                <div class="glass-kana-card text-center p-3 rounded-4 position-relative" onclick="playKanaSound('${item.char}')">
                    <div class="position-absolute top-0 end-0 m-2 text-muted" style="font-size: 0.75rem;">
                        <i class="bi bi-volume-up-fill" style="color: #c084fc;"></i>
                    </div>
                    <div class="my-2">
                        <span class="fs-3 fw-bold text-white font-serif">${item.char}</span>
                    </div>
                    <div class="small text-muted fw-bold tracking-wider" style="font-size: 0.8rem;">${item.romaji}</div>
                </div>
            `;
            grid.appendChild(col);
        });
    }

    function switchTab(type) {
        currentType = type;
        const btnHiragana = document.getElementById('btn-hiragana');
        const btnKatakana = document.getElementById('btn-katakana');

        if (type === 'hiragana') {
            btnHiragana.style.background = '#c084fc';
            btnHiragana.style.color = '#0b0f19';
            btnHiragana.classList.remove('text-muted');
            
            btnKatakana.style.background = 'transparent';
            btnKatakana.style.color = '#6c757d';
            btnKatakana.classList.add('text-muted');
        } else {
            btnKatakana.style.background = '#c084fc';
            btnKatakana.style.color = '#0b0f19';
            btnKatakana.classList.remove('text-muted');
            
            btnHiragana.style.background = 'transparent';
            btnHiragana.style.color = '#6c757d';
            btnHiragana.classList.add('text-muted');
        }

        renderTable(type);
    }

    function playKanaSound(char) {
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(char);
            utterance.lang = 'ja-JP';
            utterance.rate = 0.9;
            window.speechSynthesis.speak(utterance);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderTable('hiragana');
    });
</script>
@endsection