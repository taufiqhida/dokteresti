// ==========================================
// Navigation
// ==========================================
const navbar = document.getElementById('navbar');
const navToggle = document.getElementById('navToggle');
const navMenu = document.getElementById('navMenu');
const navLinks = document.querySelectorAll('.nav-link');

// Scroll effect
window.addEventListener('scroll', () => {
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
    updateActiveNavLink();
});

// Mobile menu toggle
navToggle.addEventListener('click', () => {
    navMenu.classList.toggle('active');
});

// Close mobile menu on link click
navLinks.forEach(link => {
    link.addEventListener('click', () => {
        navMenu.classList.remove('active');
    });
});

// Update active nav link based on scroll position
function updateActiveNavLink() {
    const sections = document.querySelectorAll('section[id]');
    const scrollPos = window.scrollY + 100;

    sections.forEach(section => {
        const top = section.offsetTop;
        const height = section.offsetHeight;
        const id = section.getAttribute('id');
        const link = document.querySelector(`.nav-link[href="#${id}"]`);

        if (link && scrollPos >= top && scrollPos < top + height) {
            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
        }
    });
}

// ==========================================
// Edukasi Tabs
// ==========================================
const tabBtns = document.querySelectorAll('.tab-btn');
const tabContents = document.querySelectorAll('.tab-content');

tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        const tabId = btn.dataset.tab;

        tabBtns.forEach(b => b.classList.remove('active'));
        tabContents.forEach(c => c.classList.remove('active'));

        btn.classList.add('active');
        document.getElementById(tabId).classList.add('active');
    });
});

// ==========================================
// Skrining Questionnaire Data
// ==========================================

// EPDS Questions (Edinburgh Postnatal Depression Scale)
const epdsQuestions = [
    {
        question: "Saya dapat tertawa dan melihat segi kelucuan hal-hal tertentu:",
        options: [
            { text: "Seperti biasanya", score: 0 },
            { text: "Sekarang tidak terlalu sering", score: 1 },
            { text: "Sekarang agak jarang", score: 2 },
            { text: "Tidak sama sekali", score: 3 }
        ]
    },
    {
        question: "Saya menanti-nanti untuk merindukan sesuatu dengan penuh harapan:",
        options: [
            { text: "Sebanyak sebelumnya", score: 0 },
            { text: "Agak sedikit kurang dibandingkan dengan sebelumnya", score: 1 },
            { text: "Kurang dibandingkan dengan sebelumnya", score: 2 },
            { text: "Tidak pernah sama sekali", score: 3 }
        ]
    },
    {
        question: "Saya menyalahkan diri jika ada sesuatu yang tidak sejalan dengan baik:",
        options: [
            { text: "Ya, hampir selalu", score: 3 },
            { text: "Ya, kadang-kadang", score: 2 },
            { text: "Tidak terlalu sering", score: 1 },
            { text: "Tidak, tidak pernah", score: 0 }
        ]
    },
    {
        question: "Saya merasa cemas atau merasa khawatir tanpa alasan:",
        options: [
            { text: "Tidak pernah sama sekali", score: 0 },
            { text: "Hampir tidak pernah", score: 1 },
            { text: "Ya, kadang-kadang", score: 2 },
            { text: "Ya, sering sekali", score: 3 }
        ]
    },
    {
        question: "Saya merasa takut atau panik tanpa alasan:",
        options: [
            { text: "Ya, sering sekali", score: 3 },
            { text: "Ya, kadang-kadang", score: 2 },
            { text: "Tidak terlalu sering", score: 1 },
            { text: "Tidak pernah sama sekali", score: 0 }
        ]
    },
    {
        question: "Banyak hal menjadi beban untuk saya:",
        options: [
            { text: "Ya, seringkali saya sama sekali tidak mengatasinya", score: 3 },
            { text: "Ya, kadang saya tidak dapat mengatasi seperti biasanya", score: 2 },
            { text: "Tidak, biasanya saya dapat mengatasinya dengan baik", score: 1 },
            { text: "Tidak, saya dapat mengatasinya dengan baik seperti biasanya", score: 0 }
        ]
    },
    {
        question: "Saya merasa begitu sedih sampai sulit tidur:",
        options: [
            { text: "Ya, hampir selalu", score: 3 },
            { text: "Ya, kadang-kadang", score: 2 },
            { text: "Tidak, tidak sering", score: 1 },
            { text: "Tidak, tidak pernah", score: 0 }
        ]
    },
    {
        question: "Saya merasa sedih atau susah:",
        options: [
            { text: "Ya, hampir selalu", score: 3 },
            { text: "Ya, sering", score: 2 },
            { text: "Jarang", score: 1 },
            { text: "Tidak pernah", score: 0 }
        ]
    },
    {
        question: "Saya merasa sangat sedih sehingga saya menangis:",
        options: [
            { text: "Ya, hampir selalu", score: 3 },
            { text: "Ya, sering", score: 2 },
            { text: "Hanya sekali-kali", score: 1 },
            { text: "Tidak pernah", score: 0 }
        ]
    },
    {
        question: "Pikiran untuk menyakiti diri saya sendiri sering muncul:",
        options: [
            { text: "Ya, agak sering", score: 3 },
            { text: "Kadang-kadang", score: 2 },
            { text: "Hampir tidak pernah", score: 1 },
            { text: "Tidak pernah", score: 0 }
        ],
        isCritical: true
    }
];

// PHQ-2 & GAD-2 Questions
const phqGadQuestions = [
    {
        section: "PHQ-2 (Gejala Depresi)",
        question: "Dalam 2 minggu terakhir, seberapa sering Anda kurang/tidak bersemangat dalam melakukan kegiatan sehari-hari?",
        type: "PHQ2",
        options: [
            { text: "Tidak sama sekali", score: 0 },
            { text: "Kurang dari 1 minggu", score: 1 },
            { text: "Lebih dari 1 minggu", score: 2 },
            { text: "Hampir setiap hari", score: 3 }
        ]
    },
    {
        section: "PHQ-2 (Gejala Depresi)",
        question: "Dalam 2 minggu terakhir, seberapa sering Anda merasa murung, tertekan, atau putus asa?",
        type: "PHQ2",
        options: [
            { text: "Tidak sama sekali", score: 0 },
            { text: "Kurang dari 1 minggu", score: 1 },
            { text: "Lebih dari 1 minggu", score: 2 },
            { text: "Hampir setiap hari", score: 3 }
        ]
    },
    {
        section: "GAD-2 (Kecemasan)",
        question: "Dalam 2 minggu terakhir, seberapa sering Anda merasa gugup, cemas atau gelisah?",
        type: "GAD2",
        options: [
            { text: "Tidak sama sekali", score: 0 },
            { text: "Kurang dari 1 minggu", score: 1 },
            { text: "Lebih dari 1 minggu", score: 2 },
            { text: "Hampir setiap hari", score: 3 }
        ]
    },
    {
        section: "GAD-2 (Kecemasan)",
        question: "Dalam 2 minggu terakhir, seberapa sering Anda tidak mampu mengendalikan rasa khawatir?",
        type: "GAD2",
        options: [
            { text: "Tidak sama sekali", score: 0 },
            { text: "Kurang dari 1 minggu", score: 1 },
            { text: "Lebih dari 1 minggu", score: 2 },
            { text: "Hampir setiap hari", score: 3 }
        ]
    }
];

// MMYS 10-18 Tahun Questions (Mini Mindhear Youth Scale)
const mmysTeenQuestions = [
    {
        section: "Ansietas (Kecemasan)",
        question: "Dalam 2 minggu terakhir, Saya sering merasa khawatir atau tidak tenang, tegang, deg-degan dan gelisah terutama terhadap hal-hal negatif atau yang belum tentu terjadi",
        type: "ANXIETY",
        options: [
            { text: "Ya", score: 1 },
            { text: "Tidak", score: 0 }
        ]
    },
    {
        section: "Ansietas (Kecemasan)",
        question: "Dalam 2 minggu terakhir, Saya berpikir berlebihan dan tidak bisa mengendalikan diri, terutama terhadap hal-hal negatif atau yang belum tentu terjadi",
        type: "ANXIETY",
        options: [
            { text: "Ya", score: 1 },
            { text: "Tidak", score: 0 }
        ]
    },
    {
        section: "Ansietas (Kecemasan)",
        question: "Dalam 2 minggu terakhir, Saya sulit tidur dan berkonsentrasi terutama saat memikirkan hal-hal negatif yang belum tentu terjadi",
        type: "ANXIETY",
        options: [
            { text: "Ya", score: 1 },
            { text: "Tidak", score: 0 }
        ]
    },
    {
        section: "Depresi",
        question: "Dalam 2 minggu terakhir, Saya sering merasa sedih atau tertekan padahal tidak ada penyebab yang jelas",
        type: "DEPRESSION",
        options: [
            { text: "Ya", score: 1 },
            { text: "Tidak", score: 0 }
        ]
    },
    {
        section: "Depresi",
        question: "Dalam 2 minggu terakhir, Saya tidak tertarik lagi dengan kegiatan atau hal-hal yang biasanya saya suka",
        type: "DEPRESSION",
        options: [
            { text: "Ya", score: 1 },
            { text: "Tidak", score: 0 }
        ]
    },
    {
        section: "Depresi",
        question: "Dalam 2 minggu terakhir, Saya merasa sering capek, sulit tidur dan sulit fokus saat belajar atau melakukan kegiatan",
        type: "DEPRESSION",
        options: [
            { text: "Ya", score: 1 },
            { text: "Tidak", score: 0 }
        ]
    }
];

// MMYS 7-9 Tahun Questions (diisi oleh orang tua)
const mmysChildQuestions = [
    {
        section: "Ansietas (Kecemasan)",
        question: "Dalam 2 minggu terakhir, anak sering merasa khawatir atau tidak tenang, tegang, deg-degan dan gelisah terutama terhadap hal-hal negatif atau yang belum tentu terjadi",
        type: "ANXIETY",
        options: [
            { text: "Ya", score: 1 },
            { text: "Tidak", score: 0 }
        ]
    },
    {
        section: "Ansietas (Kecemasan)",
        question: "Dalam 2 minggu terakhir, anak berpikir berlebihan dan tidak bisa mengendalikan diri, terutama terhadap hal-hal negatif atau yang belum tentu terjadi",
        type: "ANXIETY",
        options: [
            { text: "Ya", score: 1 },
            { text: "Tidak", score: 0 }
        ]
    },
    {
        section: "Ansietas (Kecemasan)",
        question: "Dalam 2 minggu terakhir, anak sulit tidur dan berkonsentrasi terutama saat memikirkan hal-hal negatif yang belum tentu terjadi",
        type: "ANXIETY",
        options: [
            { text: "Ya", score: 1 },
            { text: "Tidak", score: 0 }
        ]
    },
    {
        section: "Depresi",
        question: "Dalam 2 minggu terakhir, anak sering merasa sedih atau tertekan padahal tidak ada penyebab yang jelas",
        type: "DEPRESSION",
        options: [
            { text: "Ya", score: 1 },
            { text: "Tidak", score: 0 }
        ]
    },
    {
        section: "Depresi",
        question: "Dalam 2 minggu terakhir, anak tidak tertarik lagi dengan kegiatan atau hal-hal yang biasanya dia suka",
        type: "DEPRESSION",
        options: [
            { text: "Ya", score: 1 },
            { text: "Tidak", score: 0 }
        ]
    },
    {
        section: "Depresi",
        question: "Dalam 2 minggu terakhir, anak merasa sering capek, sulit tidur, dan sulit fokus saat belajar atau melakukan kegiatan",
        type: "DEPRESSION",
        options: [
            { text: "Ya", score: 1 },
            { text: "Tidak", score: 0 }
        ]
    }
];

// ==========================================
// State Variables
// ==========================================
let selectedSkriningType = null;
let currentQuestions = [];
let currentQuestion = 0;
let answers = [];
let respondentData = {};
let childData = {};

// ==========================================
// Screening Type Selection
// ==========================================
function selectSkriningType(type) {
    selectedSkriningType = type;
    document.getElementById('skrining-selector').style.display = 'none';

    // Show appropriate biodata form
    if (type === 'MMYS-CHILD') {
        document.getElementById('skrining-biodata-child').style.display = 'block';
        document.getElementById('skrining-biodata-child').scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
        document.getElementById('skrining-biodata').style.display = 'block';

        let title = '';
        switch (type) {
            case 'EPDS':
                title = 'Data Diri - EPDS (Edinburgh Postnatal Depression Scale)';
                break;
            case 'PHQ2-GAD2':
                title = 'Data Diri - PHQ-2 & GAD-2';
                break;
            case 'MMYS-TEEN':
                title = 'Data Diri - MMYS 10-18 Tahun';
                break;
        }
        document.getElementById('biodata-title').textContent = title;
        document.getElementById('skrining-biodata').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function backToSelector() {
    document.getElementById('skrining-biodata').style.display = 'none';
    document.getElementById('skrining-biodata-child').style.display = 'none';
    document.getElementById('skrining-selector').style.display = 'block';
    selectedSkriningType = null;
}

function backToBiodata() {
    document.getElementById('skrining-container').style.display = 'none';
    if (selectedSkriningType === 'MMYS-CHILD') {
        document.getElementById('skrining-biodata-child').style.display = 'block';
    } else {
        document.getElementById('skrining-biodata').style.display = 'block';
    }
}

// ==========================================
// Start Screening (Regular Forms)
// ==========================================
function startSelectedSkrining(event) {
    event.preventDefault();

    respondentData = {
        name: document.getElementById('respondent_name').value.trim(),
        phone: document.getElementById('respondent_phone').value.trim(),
        age: document.getElementById('respondent_age').value
    };

    if (!respondentData.name) {
        alert('Silakan masukkan nama Anda');
        return;
    }

    setupQuestions();
    startQuestionForm();
}

// Start Screening (Child Form - MMYS 7-9)
function startChildSkrining(event) {
    event.preventDefault();

    // Get parent data
    respondentData = {
        name: document.getElementById('parent_name').value.trim(),
        phone: document.getElementById('parent_phone').value.trim(),
        gender: document.getElementById('parent_gender').value,
        dob: document.getElementById('parent_dob').value
    };

    // Get child data
    childData = {
        name: document.getElementById('child_name').value.trim(),
        gender: document.getElementById('child_gender').value,
        dob: document.getElementById('child_dob').value,
        nik: document.getElementById('child_nik').value.trim(),
        school: document.getElementById('child_school').value.trim(),
        class: document.getElementById('child_class').value.trim(),
        address: document.getElementById('child_address').value.trim(),
        kelurahan: document.getElementById('child_kelurahan').value.trim(),
        kecamatan: document.getElementById('child_kecamatan').value.trim(),
        prevMindhear: document.querySelector('input[name="prev_mindhear"]:checked')?.value,
        prevOther: document.querySelector('input[name="prev_other"]:checked')?.value
    };

    // Calculate child age
    if (childData.dob) {
        const birthDate = new Date(childData.dob);
        const today = new Date();
        childData.age = today.getFullYear() - birthDate.getFullYear();
    }

    respondentData.age = childData.age;

    setupQuestions();
    document.getElementById('skrining-biodata-child').style.display = 'none';
    startQuestionForm();
}

function setupQuestions() {
    switch (selectedSkriningType) {
        case 'EPDS':
            currentQuestions = epdsQuestions;
            document.getElementById('form-badge').textContent = 'EPDS';
            document.getElementById('form-title').textContent = 'Edinburgh Postnatal Depression Scale';
            document.getElementById('form-instruction').textContent = 'Karena Anda saat ini hamil, kami ingin mengetahui bagaimana perasaan Anda sekarang. Silahkan memilih jawaban yang paling mirip dengan perasaan Anda selama 7 hari terakhir.';
            break;
        case 'PHQ2-GAD2':
            currentQuestions = phqGadQuestions;
            document.getElementById('form-badge').textContent = 'PHQ-2 & GAD-2';
            document.getElementById('form-title').textContent = 'Patient Health Questionnaire & Generalized Anxiety Disorder';
            document.getElementById('form-instruction').textContent = 'Berhubungan dengan perasaan dalam 2 minggu terakhir. Pilihlah salah satu jawaban untuk setiap pertanyaan sesuai dengan perasaan Anda.';
            break;
        case 'MMYS-TEEN':
            currentQuestions = mmysTeenQuestions;
            document.getElementById('form-badge').textContent = 'MMYS 10-18 Tahun';
            document.getElementById('form-title').textContent = 'Mini Mindhear Youth Scale V.1';
            document.getElementById('form-instruction').textContent = 'Untuk setiap pertanyaan, pilih jawaban yang paling sesuai dengan apa yang Anda rasakan dalam 2 minggu terakhir. Jawab dengan jujur dan tanpa dipengaruhi orang lain.';
            break;
        case 'MMYS-CHILD':
            currentQuestions = mmysChildQuestions;
            document.getElementById('form-badge').textContent = 'MMYS 7-9 Tahun';
            document.getElementById('form-title').textContent = 'Mini Mindhear Youth Scale V.1';
            document.getElementById('form-instruction').textContent = 'Jawab setiap pertanyaan berdasarkan pengamatan langsung terhadap anak dalam 2 minggu terakhir. Nama anak: ' + (childData.name || '');
            break;
    }
}

function startQuestionForm() {
    currentQuestion = 0;
    answers = new Array(currentQuestions.length).fill(null);

    document.getElementById('skrining-biodata').style.display = 'none';
    document.getElementById('skrining-container').style.display = 'block';
    document.getElementById('total-questions').textContent = currentQuestions.length;

    renderQuestion();
    document.getElementById('skrining-container').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ==========================================
// Question Rendering
// ==========================================
function renderQuestion() {
    const container = document.getElementById('question-container');
    const q = currentQuestions[currentQuestion];

    let sectionHtml = '';
    if (q.section) {
        sectionHtml = `<div class="question-section">${q.section}</div>`;
    }

    let criticalWarning = '';
    if (q.isCritical) {
        criticalWarning = `
            <div class="critical-warning">
                <span class="warning-icon">⚠️</span>
                <span>Pertanyaan ini sangat penting untuk keselamatan Anda.</span>
            </div>
        `;
    }

    // For MMYS (Yes/No), show simpler options
    const isYesNo = q.options.length === 2 && q.options[0].text === 'Ya';

    let optionsHtml;
    if (isYesNo) {
        optionsHtml = q.options.map((opt, idx) => `
            <label class="option option-yesno ${answers[currentQuestion] === idx ? 'selected' : ''}" onclick="selectOption(${idx})">
                <input type="radio" name="q${currentQuestion}" value="${idx}" ${answers[currentQuestion] === idx ? 'checked' : ''}>
                <span class="option-radio"></span>
                <span class="option-text">${opt.text}</span>
            </label>
        `).join('');
    } else {
        optionsHtml = q.options.map((opt, idx) => `
            <label class="option ${answers[currentQuestion] === idx ? 'selected' : ''}" onclick="selectOption(${idx})">
                <input type="radio" name="q${currentQuestion}" value="${idx}" ${answers[currentQuestion] === idx ? 'checked' : ''}>
                <span class="option-radio"></span>
                <span class="option-text"><span class="option-score">${opt.score}</span> ${opt.text}</span>
            </label>
        `).join('');
    }

    container.innerHTML = `
        ${sectionHtml}
        ${criticalWarning}
        <div class="question">
            <h4>${currentQuestion + 1}. ${q.question}</h4>
            <div class="options ${isYesNo ? 'options-yesno' : ''}">${optionsHtml}</div>
        </div>
    `;

    document.getElementById('current-question').textContent = currentQuestion + 1;
    document.getElementById('progress-fill').style.width = `${((currentQuestion + 1) / currentQuestions.length) * 100}%`;

    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');

    prevBtn.style.display = currentQuestion > 0 ? 'flex' : 'none';
    nextBtn.textContent = currentQuestion === currentQuestions.length - 1 ? 'Lihat Hasil' : 'Selanjutnya →';
}

function selectOption(idx) {
    answers[currentQuestion] = idx;
    document.querySelectorAll('.option').forEach((opt, i) => {
        opt.classList.toggle('selected', i === idx);
    });
}

function prevQuestion() {
    if (currentQuestion > 0) {
        currentQuestion--;
        renderQuestion();
    }
}

function nextQuestion() {
    if (answers[currentQuestion] === null) {
        alert('Silakan pilih salah satu jawaban');
        return;
    }

    if (currentQuestion < currentQuestions.length - 1) {
        currentQuestion++;
        renderQuestion();
    } else {
        calculateAndShowResult();
    }
}

// ==========================================
// Result Calculation
// ==========================================
function calculateAndShowResult() {
    document.getElementById('skrining-container').style.display = 'none';
    const resultContainer = document.getElementById('skrining-result');
    resultContainer.style.display = 'block';

    let resultData = {};

    switch (selectedSkriningType) {
        case 'EPDS':
            resultData = calculateEPDSResult();
            break;
        case 'PHQ2-GAD2':
            resultData = calculatePHQGADResult();
            break;
        case 'MMYS-TEEN':
        case 'MMYS-CHILD':
            resultData = calculateMMYSResult();
            break;
    }

    saveToDatabase(resultData);
    renderResult(resultData);
    resultContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function calculateEPDSResult() {
    let totalScore = 0;
    let isCritical = false;

    answers.forEach((answerIdx, qIdx) => {
        const score = epdsQuestions[qIdx].options[answerIdx].score;
        totalScore += score;

        if (qIdx === 9 && score >= 1) {
            isCritical = true;
        }
    });

    let interpretation, category, icon, message, scoreClass, recommendations;

    if (isCritical) {
        interpretation = 'KRITIS';
        category = 'Butuh Perhatian Segera';
        icon = '🚨';
        scoreClass = 'critical';
        message = 'Jawaban Anda pada pertanyaan terakhir menunjukkan Anda mungkin memiliki pikiran untuk menyakiti diri sendiri. Ini sangat penting dan Anda membutuhkan dukungan segera.';
        recommendations = [
            'SEGERA hubungi keluarga atau orang terdekat Anda',
            'Hubungi hotline kesehatan jiwa: 119 ext 8',
            'Kunjungi fasilitas kesehatan terdekat',
            'Anda tidak sendirian, bantuan tersedia untuk Anda'
        ];
    } else if (totalScore <= 9) {
        interpretation = 'RENDAH';
        category = 'Risiko Rendah';
        icon = '🌟';
        scoreClass = 'good';
        message = 'Hasil skrining menunjukkan risiko depresi yang rendah. Terus jaga kesehatan mental Anda dengan pola hidup sehat.';
        recommendations = [
            'Jaga kesehatan mental dengan kelola stress',
            'Istirahat cukup 7-9 jam/hari',
            'Lakukan aktivitas yang menyenangkan',
            'Konsultasi dengan tenaga kesehatan bila terdapat masalah kesehatan jiwa',
            'Skrining ulang jika ada perubahan perasaan'
        ];
    } else if (totalScore <= 12) {
        interpretation = 'SEDANG';
        category = 'Risiko Sedang';
        icon = '💛';
        scoreClass = 'moderate';
        message = 'Hasil menunjukkan kemungkinan gejala depresi ringan. Disarankan untuk memantau kondisi dan mempertimbangkan konsultasi.';
        recommendations = [
            'Jaga kesehatan mental dengan kelola stress',
            'Istirahat cukup 7-9 jam/hari',
            'Lakukan aktivitas yang menyenangkan',
            'Konsultasi dengan tenaga kesehatan bila terdapat masalah kesehatan jiwa',
            'Skrining ulang jika ada perubahan perasaan'
        ];
    } else {
        interpretation = 'TINGGI';
        category = 'Risiko Tinggi';
        icon = '❤️';
        scoreClass = 'concern';
        message = 'Hasil skrining menunjukkan kemungkinan depresi yang memerlukan evaluasi lebih lanjut oleh tenaga kesehatan.';
        recommendations = [
            'Jaga kesehatan mental dengan kelola stress',
            'Istirahat cukup 7-9 jam/hari',
            'Lakukan aktivitas yang menyenangkan',
            'Konsultasi dengan tenaga kesehatan bila terdapat masalah kesehatan jiwa',
            'Skrining ulang jika ada perubahan perasaan'
        ];
    }

    return {
        screening_type: 'EPDS',
        total_score: totalScore,
        max_score: 30,
        interpretation,
        category,
        icon,
        scoreClass,
        message,
        recommendations,
        is_critical: isCritical ? 1 : 0
    };
}

function calculatePHQGADResult() {
    let phq2Score = 0;
    let gad2Score = 0;

    answers.forEach((answerIdx, qIdx) => {
        const score = phqGadQuestions[qIdx].options[answerIdx].score;
        if (qIdx < 2) {
            phq2Score += score;
        } else {
            gad2Score += score;
        }
    });

    const totalScore = phq2Score + gad2Score;
    let interpretation, category, icon, message, scoreClass, recommendations;
    const phq2Positive = phq2Score >= 3;
    const gad2Positive = gad2Score >= 3;

    if (phq2Positive && gad2Positive) {
        interpretation = 'TINGGI';
        category = 'Skrining Positif (Depresi & Kecemasan)';
        icon = '❤️';
        scoreClass = 'concern';
        message = `Hasil skrining menunjukkan kemungkinan gejala depresi (PHQ-2: ${phq2Score}/6) DAN kecemasan (GAD-2: ${gad2Score}/6). Disarankan untuk evaluasi lebih lanjut.`;
        recommendations = [
            'Jaga kesehatan mental dengan kelola stress',
            'Istirahat cukup 7-9 jam/hari',
            'Lakukan aktivitas yang menyenangkan',
            'Konsultasi dengan tenaga kesehatan bila terdapat masalah kesehatan jiwa',
            'Skrining ulang jika ada perubahan perasaan'
        ];
    } else if (phq2Positive) {
        interpretation = 'SEDANG';
        category = 'Skrining Positif (Gejala Depresi)';
        icon = '💛';
        scoreClass = 'moderate';
        message = `Hasil skrining menunjukkan kemungkinan gejala depresi (PHQ-2: ${phq2Score}/6). Kecemasan dalam batas normal (GAD-2: ${gad2Score}/6).`;
        recommendations = [
            'Jaga kesehatan mental dengan kelola stress',
            'Istirahat cukup 7-9 jam/hari',
            'Lakukan aktivitas yang menyenangkan',
            'Konsultasi dengan tenaga kesehatan bila terdapat masalah kesehatan jiwa',
            'Skrining ulang jika ada perubahan perasaan'
        ];
    } else if (gad2Positive) {
        interpretation = 'SEDANG';
        category = 'Skrining Positif (Gejala Kecemasan)';
        icon = '💛';
        scoreClass = 'moderate';
        message = `Hasil skrining menunjukkan kemungkinan gejala kecemasan (GAD-2: ${gad2Score}/6). Tidak ada indikasi depresi signifikan (PHQ-2: ${phq2Score}/6).`;
        recommendations = [
            'Jaga kesehatan mental dengan kelola stress',
            'Istirahat cukup 7-9 jam/hari',
            'Lakukan aktivitas yang menyenangkan',
            'Konsultasi dengan tenaga kesehatan bila terdapat masalah kesehatan jiwa',
            'Skrining ulang jika ada perubahan perasaan'
        ];
    } else {
        interpretation = 'RENDAH';
        category = 'Skrining Negatif';
        icon = '🌟';
        scoreClass = 'good';
        message = `Hasil skrining tidak menunjukkan gejala signifikan. PHQ-2: ${phq2Score}/6, GAD-2: ${gad2Score}/6. Tetap jaga kesehatan mental Anda.`;
        recommendations = [
            'Jaga kesehatan mental dengan kelola stress',
            'Istirahat cukup 7-9 jam/hari',
            'Lakukan aktivitas yang menyenangkan',
            'Konsultasi dengan tenaga kesehatan bila terdapat masalah kesehatan jiwa',
            'Skrining ulang jika ada perubahan perasaan'
        ];
    }

    return {
        screening_type: 'PHQ2-GAD2',
        total_score: totalScore,
        max_score: 12,
        phq2_score: phq2Score,
        gad2_score: gad2Score,
        interpretation,
        category,
        icon,
        scoreClass,
        message,
        recommendations,
        is_critical: 0
    };
}

function calculateMMYSResult() {
    let anxietyScore = 0;
    let depressionScore = 0;

    const questions = selectedSkriningType === 'MMYS-TEEN' ? mmysTeenQuestions : mmysChildQuestions;

    answers.forEach((answerIdx, qIdx) => {
        const score = questions[qIdx].options[answerIdx].score;
        if (questions[qIdx].type === 'ANXIETY') {
            anxietyScore += score;
        } else {
            depressionScore += score;
        }
    });

    const totalScore = anxietyScore + depressionScore;
    let interpretation, category, icon, message, scoreClass, recommendations;

    // MMYS: 2+ pada masing-masing kategori = perlu perhatian
    const anxietyPositive = anxietyScore >= 2;
    const depressionPositive = depressionScore >= 2;
    const subjectName = selectedSkriningType === 'MMYS-CHILD' ? 'Anak' : 'Anda';

    if (anxietyPositive && depressionPositive) {
        interpretation = 'TINGGI';
        category = 'Perlu Evaluasi Lanjutan';
        icon = '❤️';
        scoreClass = 'concern';
        message = `${subjectName} menunjukkan gejala ansietas (${anxietyScore}/3) dan depresi (${depressionScore}/3). Disarankan untuk evaluasi lebih lanjut oleh tenaga kesehatan.`;
        recommendations = [
            'Jaga kesehatan mental dengan kelola stress',
            'Istirahat cukup 7-9 jam/hari',
            'Lakukan aktivitas yang menyenangkan',
            'Konsultasi dengan tenaga kesehatan bila terdapat masalah kesehatan jiwa',
            'Skrining ulang jika ada perubahan perasaan'
        ];
    } else if (anxietyPositive || depressionPositive) {
        interpretation = 'SEDANG';
        category = anxietyPositive ? 'Gejala Kecemasan Terdeteksi' : 'Gejala Depresi Terdeteksi';
        icon = '💛';
        scoreClass = 'moderate';
        message = anxietyPositive
            ? `${subjectName} menunjukkan gejala kecemasan (${anxietyScore}/3). Gejala depresi dalam batas normal (${depressionScore}/3).`
            : `${subjectName} menunjukkan gejala depresi (${depressionScore}/3). Gejala kecemasan dalam batas normal (${anxietyScore}/3).`;
        recommendations = [
            'Jaga kesehatan mental dengan kelola stress',
            'Istirahat cukup 7-9 jam/hari',
            'Lakukan aktivitas yang menyenangkan',
            'Konsultasi dengan tenaga kesehatan bila terdapat masalah kesehatan jiwa',
            'Skrining ulang jika ada perubahan perasaan'
        ];
    } else {
        interpretation = 'RENDAH';
        category = 'Tidak Ada Gejala Signifikan';
        icon = '🌟';
        scoreClass = 'good';
        message = `Hasil skrining tidak menunjukkan gejala yang signifikan. Ansietas: ${anxietyScore}/3, Depresi: ${depressionScore}/3.`;
        recommendations = [
            'Jaga kesehatan mental dengan kelola stress',
            'Istirahat cukup 7-9 jam/hari',
            'Lakukan aktivitas yang menyenangkan',
            'Konsultasi dengan tenaga kesehatan bila terdapat masalah kesehatan jiwa',
            'Skrining ulang jika ada perubahan perasaan'
        ];
    }

    return {
        screening_type: selectedSkriningType,
        total_score: totalScore,
        max_score: 6,
        anxiety_score: anxietyScore,
        depression_score: depressionScore,
        interpretation,
        category,
        icon,
        scoreClass,
        message,
        recommendations,
        is_critical: 0,
        child_data: selectedSkriningType === 'MMYS-CHILD' ? childData : null
    };
}

// ==========================================
// Render Result
// ==========================================
function renderResult(data) {
    const resultContainer = document.getElementById('skrining-result');
    const recsHtml = data.recommendations.map(r => `<li>${r}</li>`).join('');

    // Score breakdown for PHQ-GAD
    let scoreBreakdown = '';
    if (data.screening_type === 'PHQ2-GAD2') {
        scoreBreakdown = `
            <div class="score-breakdown">
                <div class="score-item">
                    <span class="score-label">PHQ-2 (Depresi)</span>
                    <span class="score-value">${data.phq2_score}/6</span>
                    <span class="score-status ${data.phq2_score >= 3 ? 'positive' : 'negative'}">${data.phq2_score >= 3 ? 'Positif' : 'Negatif'}</span>
                </div>
                <div class="score-item">
                    <span class="score-label">GAD-2 (Kecemasan)</span>
                    <span class="score-value">${data.gad2_score}/6</span>
                    <span class="score-status ${data.gad2_score >= 3 ? 'positive' : 'negative'}">${data.gad2_score >= 3 ? 'Positif' : 'Negatif'}</span>
                </div>
            </div>
        `;
    } else if (data.screening_type === 'MMYS-TEEN' || data.screening_type === 'MMYS-CHILD') {
        scoreBreakdown = `
            <div class="score-breakdown">
                <div class="score-item">
                    <span class="score-label">Ansietas</span>
                    <span class="score-value">${data.anxiety_score}/3</span>
                    <span class="score-status ${data.anxiety_score >= 2 ? 'positive' : 'negative'}">${data.anxiety_score >= 2 ? 'Perlu Perhatian' : 'Normal'}</span>
                </div>
                <div class="score-item">
                    <span class="score-label">Depresi</span>
                    <span class="score-value">${data.depression_score}/3</span>
                    <span class="score-status ${data.depression_score >= 2 ? 'positive' : 'negative'}">${data.depression_score >= 2 ? 'Perlu Perhatian' : 'Normal'}</span>
                </div>
            </div>
        `;
    }

    // Child info for MMYS-CHILD
    let childInfo = '';
    if (data.child_data && data.child_data.name) {
        childInfo = `
            <div class="child-info">
                <strong>Nama Anak:</strong> ${data.child_data.name}<br>
                <strong>Usia:</strong> ${data.child_data.age || '-'} tahun<br>
                <strong>Sekolah:</strong> ${data.child_data.school || '-'}
            </div>
        `;
    }

    // Critical warning
    let criticalAlert = '';
    if (data.is_critical) {
        criticalAlert = `
            <div class="critical-alert">
                <span class="alert-icon">🚨</span>
                <div class="alert-content">
                    <strong>Perhatian Segera Diperlukan</strong>
                    <p>Jika Anda memiliki pikiran untuk menyakiti diri sendiri, segera hubungi:</p>
                    <a href="tel:119" class="emergency-btn">📞 Hotline 119 ext 8</a>
                </div>
            </div>
        `;
    }

    // Display name based on type
    const displayName = data.screening_type === 'MMYS-CHILD'
        ? `Orang Tua: ${respondentData.name}`
        : respondentData.name;

    resultContainer.innerHTML = `
        <div class="result-card">
            <div class="result-header">
                <span class="result-badge">${data.screening_type}</span>
                <span class="result-name">Hasil untuk: ${displayName}</span>
            </div>
            ${criticalAlert}
            ${childInfo}
            <div class="result-icon">${data.icon}</div>
            <h3>Hasil Skrining</h3>
            <div class="result-score ${data.scoreClass}">${data.category}</div>
            <div class="result-total">Total Skor: ${data.total_score}/${data.max_score}</div>
            ${scoreBreakdown}
            <p class="result-message">${data.message}</p>
            <div class="result-recommendations">
                <h4>Rekomendasi:</h4>
                <ul>${recsHtml}</ul>
            </div>
            <div class="disclaimer" style="margin-top: 1.5rem; background: #fef3c7; color: #92400e;">
                <span class="disclaimer-icon">⚠️</span>
                <span>Hasil ini bersifat skrining awal dan bukan diagnosis medis. Untuk hasil yang akurat, silakan konsultasi dengan tenaga profesional.</span>
            </div>
            <div class="result-actions">
                <a href="https://wa.me/628112629888" target="_blank" class="btn btn-primary">
                    💬 Konsultasi via WhatsApp
                </a>
                <button class="btn btn-outline" onclick="resetSkrining()">
                    🔄 Ulangi Skrining
                </button>
            </div>
        </div>
    `;
}

// ==========================================
// Save to Database
// ==========================================
async function saveToDatabase(resultData) {
    try {
        const payload = {
            screening_type: resultData.screening_type,
            respondent_name: selectedSkriningType === 'MMYS-CHILD' ? childData.name : respondentData.name,
            respondent_phone: respondentData.phone || null,
            respondent_age: respondentData.age ? parseInt(respondentData.age) : null,
            answers: answers.map((ansIdx, qIdx) => ({
                question: currentQuestions[qIdx].question,
                answer: currentQuestions[qIdx].options[ansIdx].text,
                score: currentQuestions[qIdx].options[ansIdx].score
            })),
            total_score: resultData.total_score,
            phq2_score: resultData.phq2_score || null,
            gad2_score: resultData.gad2_score || null,
            interpretation: resultData.interpretation,
            is_critical: resultData.is_critical || 0
        };

        // Add child data for MMYS-CHILD
        if (selectedSkriningType === 'MMYS-CHILD' && childData) {
            payload.child_data = childData;
            payload.parent_name = respondentData.name;
        }

        const response = await fetch('api/save_screening.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (!data.success) {
            console.error('Failed to save screening:', data.error);
        } else {
            console.log('Screening saved successfully with ID:', data.id);
        }
    } catch (error) {
        console.error('Error saving screening:', error);
    }
}

// ==========================================
// Reset Screening
// ==========================================
function resetSkrining() {
    currentQuestion = 0;
    answers = [];
    selectedSkriningType = null;
    currentQuestions = [];
    respondentData = {};
    childData = {};

    // Reset forms
    const biodataForm = document.getElementById('biodata-form');
    const childForm = document.getElementById('biodata-form-child');
    if (biodataForm) biodataForm.reset();
    if (childForm) childForm.reset();

    // Show selector
    document.getElementById('skrining-result').style.display = 'none';
    document.getElementById('skrining-container').style.display = 'none';
    document.getElementById('skrining-biodata').style.display = 'none';
    document.getElementById('skrining-biodata-child').style.display = 'none';
    document.getElementById('skrining-selector').style.display = 'block';

    document.getElementById('skrining').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ==========================================
// Smooth scroll for anchor links
// ==========================================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            const offsetTop = target.offsetTop - 80;
            window.scrollTo({
                top: offsetTop,
                behavior: 'smooth'
            });
        }
    });
});

// ==========================================
// Init
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    updateActiveNavLink();
});
