<section class="section skrining" id="skrining">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">📋 Skrining Mandiri</span>
            <h2 class="section-title">Cek Kondisi Kesehatan Jiwa Anda</h2>
            <p class="section-subtitle">Pilih jenis skrining yang sesuai dengan kondisi Anda. Hasil skrining bersifat
                awal dan bukan diagnosis medis.</p>
        </div>

        <!-- Pilihan Jenis Skrining -->
        <div class="skrining-selector" id="skrining-selector">
            <div class="selector-grid selector-grid-4">
                <div class="selector-card" onclick="selectSkriningType('EPDS')">
                    <div class="selector-icon">🤰</div>
                    <h3>EPDS</h3>
                    <p class="selector-subtitle">Edinburgh Postnatal Depression Scale</p>
                    <p class="selector-desc">Untuk ibu hamil atau pasca melahirkan. Mendeteksi risiko depresi perinatal.
                    </p>
                    <span class="selector-meta">10 Pertanyaan • ~5 menit</span>
                </div>
                <div class="selector-card" onclick="selectSkriningType('PHQ2-GAD2')">
                    <div class="selector-icon">💭</div>
                    <h3>PHQ-2 & GAD-2</h3>
                    <p class="selector-subtitle">Patient Health Questionnaire & Generalized Anxiety Disorder</p>
                    <p class="selector-desc">Skrining cepat gejala depresi dan kecemasan untuk usia lebih dari 18 tahun.
                    </p>
                    <span class="selector-meta">4 Pertanyaan • ~2 menit</span>
                </div>
                <div class="selector-card" onclick="selectSkriningType('MMYS-TEEN')">
                    <div class="selector-icon">👦</div>
                    <h3>MMYS 10-18 Tahun</h3>
                    <p class="selector-subtitle">Mini Mindhear Youth Scale V.1</p>
                    <p class="selector-desc">Untuk anak dan remaja usia 10-18 tahun. Diisi mandiri oleh anak.</p>
                    <span class="selector-meta">6 Pertanyaan • ~3 menit</span>
                </div>
                <div class="selector-card" onclick="selectSkriningType('MMYS-CHILD')">
                    <div class="selector-icon">👧</div>
                    <h3>MMYS 7-9 Tahun</h3>
                    <p class="selector-subtitle">Mini Mindhear Youth Scale V.1</p>
                    <p class="selector-desc">Untuk anak usia 7-9 tahun. Diisi oleh orang tua berdasarkan pengamatan.</p>
                    <span class="selector-meta">6 Pertanyaan • ~5 menit</span>
                </div>
            </div>
        </div>

        <!-- Form Biodata Umum -->
        <div class="skrining-biodata" id="skrining-biodata" style="display: none;">
            <div class="biodata-card">
                <button class="back-btn" onclick="backToSelector()">← Kembali</button>
                <h3 id="biodata-title">Data Diri</h3>
                <p>Silakan isi data diri Anda sebelum memulai skrining.</p>
                <form id="biodata-form" onsubmit="startSelectedSkrining(event)">
                    <div class="form-group">
                        <label for="respondent_name">Nama Lengkap <span class="required">*</span></label>
                        <input type="text" id="respondent_name" name="respondent_name" required
                            placeholder="Masukkan nama lengkap">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="respondent_phone">Nomor HP</label>
                            <input type="tel" id="respondent_phone" name="respondent_phone" placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="form-group">
                            <label for="respondent_age">Usia (tahun)</label>
                            <input type="number" id="respondent_age" name="respondent_age" min="7" max="100"
                                placeholder="Usia">
                        </div>
                    </div>
                    <div class="disclaimer">
                        <span class="disclaimer-icon">ℹ️</span>
                        <span>Data Anda akan dijaga kerahasiaannya dan hanya digunakan untuk keperluan skrining.</span>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg">Mulai Skrining →</button>
                </form>
            </div>
        </div>

        <!-- Form Biodata Anak (MMYS 7-9 Tahun) -->
        <div class="skrining-biodata-child" id="skrining-biodata-child" style="display: none;">
            <div class="biodata-card biodata-card-wide">
                <button class="back-btn" onclick="backToSelector()">← Kembali</button>
                <h3>Data Anak & Orang Tua - MMYS 7-9 Tahun</h3>
                <p>Instrumen ini diisi oleh orang tua berdasarkan pengamatan terhadap anak dalam 2 minggu terakhir.</p>

                <form id="biodata-form-child" onsubmit="startChildSkrining(event)">
                    <!-- Data Orang Tua -->
                    <div class="form-section">
                        <h4>👤 Identitas Responden (Orang Tua)</h4>
                        <div class="form-group">
                            <label>Nama Responden (Orang Tua) <span class="required">*</span></label>
                            <input type="text" id="parent_name" required placeholder="Nama orang tua">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Jenis Kelamin <span class="required">*</span></label>
                                <select id="parent_gender" required>
                                    <option value="">Pilih</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Lahir <span class="required">*</span></label>
                                <input type="date" id="parent_dob" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Nomor HP/WA <span class="required">*</span></label>
                            <input type="tel" id="parent_phone" required placeholder="08xxxxxxxxxx">
                        </div>
                    </div>

                    <!-- Data Anak -->
                    <div class="form-section">
                        <h4>👧 Identitas Anak</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nama Anak <span class="required">*</span></label>
                                <input type="text" id="child_name" required placeholder="Nama lengkap anak">
                            </div>
                            <div class="form-group">
                                <label>Jenis Kelamin Anak <span class="required">*</span></label>
                                <select id="child_gender" required>
                                    <option value="">Pilih</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Tanggal Lahir Anak <span class="required">*</span></label>
                                <input type="date" id="child_dob" required>
                            </div>
                            <div class="form-group">
                                <label>NIS/NIK Anak <span class="required">*</span></label>
                                <input type="text" id="child_nik" required placeholder="Nomor identitas">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Asal Sekolah <span class="required">*</span></label>
                                <input type="text" id="child_school" required placeholder="Nama sekolah">
                            </div>
                            <div class="form-group">
                                <label>Kelas <span class="required">*</span></label>
                                <input type="text" id="child_class" required placeholder="Kelas">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Alamat Domisili <span class="required">*</span></label>
                            <input type="text" id="child_address" required placeholder="Alamat lengkap">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Kelurahan <span class="required">*</span></label>
                                <input type="text" id="child_kelurahan" required placeholder="Kelurahan">
                            </div>
                            <div class="form-group">
                                <label>Kecamatan <span class="required">*</span></label>
                                <input type="text" id="child_kecamatan" required placeholder="Kecamatan">
                            </div>
                        </div>
                    </div>

                    <!-- Pertanyaan Awal -->
                    <div class="form-section">
                        <h4>📝 Pertanyaan Awal</h4>
                        <div class="form-group">
                            <label>Apakah sebelumnya sudah pernah mengisi instrumen MINDHEAR 7-9 tahun? <span
                                    class="required">*</span></label>
                            <div class="radio-group">
                                <label class="radio-option"><input type="radio" name="prev_mindhear"
                                        value="Belum Pernah" required> Belum Pernah</label>
                                <label class="radio-option"><input type="radio" name="prev_mindhear"
                                        value="Sudah Pernah"> Sudah Pernah</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Apakah sebelumnya pernah mengisi skrining kesehatan jiwa lainnya? <span
                                    class="required">*</span></label>
                            <div class="radio-group">
                                <label class="radio-option"><input type="radio" name="prev_other" value="Belum Pernah"
                                        required> Belum Pernah</label>
                                <label class="radio-option"><input type="radio" name="prev_other" value="Sudah Pernah">
                                    Sudah Pernah</label>
                            </div>
                        </div>
                    </div>

                    <div class="disclaimer">
                        <span class="disclaimer-icon">ℹ️</span>
                        <span>Pertanyaan harus dijawab dengan jujur dan obyektif berdasarkan pengamatan langsung
                            terhadap anak dalam 2 minggu terakhir.</span>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg">Mulai Skrining →</button>
                </form>
            </div>
        </div>

        <!-- Container Form Skrining -->
        <div class="skrining-container" id="skrining-container" style="display: none;">
            <div class="skrining-form" id="skrining-form">
                <button class="back-btn" onclick="backToBiodata()">← Kembali</button>
                <div class="form-header">
                    <span class="form-badge" id="form-badge">EPDS</span>
                    <h3 id="form-title">Edinburgh Postnatal Depression Scale</h3>
                    <p id="form-instruction">Karena Anda saat ini hamil, kami ingin mengetahui bagaimana perasaan Anda
                        sekarang. Silahkan memilih jawaban yang paling mirip dengan perasaan Anda selama 7 hari
                        terakhir.</p>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <div class="question-counter">Pertanyaan <span id="current-question">1</span> dari <span
                        id="total-questions">10</span></div>
                <div class="question-container" id="question-container"></div>
                <div class="skrining-buttons">
                    <button class="btn btn-outline" id="prev-btn" onclick="prevQuestion()" style="display: none;">←
                        Sebelumnya</button>
                    <button class="btn btn-primary" id="next-btn" onclick="nextQuestion()">Selanjutnya →</button>
                </div>
            </div>
        </div>

        <!-- Hasil Skrining -->
        <div class="skrining-result" id="skrining-result" style="display: none;"></div>
    </div>
</section>