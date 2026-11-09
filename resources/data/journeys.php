<?php

// Data awal journey untuk PerjalananSeeder (situs membaca tabel perjalanan). 'home' => false = tidak tampil di beranda.
return [
    ['region' => null, 'chip' => __('Kustom'), 'name' => 'CUSTOM HIGHLAND JOURNEY', 'tone' => 'custom', 'capacity' => __('Maks. 6 orang'), 'place' => __('Rute sesuai permintaan'), 'img' => '/images/sikunir-sindoro-pagi.webp', 'rating' => '4.88', 'reviews' => 115, 'price' => 'Rp 1.400.000',
        'tagline' => __('Rute dataran tinggi milik Anda — kami yang susun jalannya'),
        'desc' => __('Tidak semua perjalanan terbaik sudah tertulis di brosur. Beberapa lahir dari satu keinginan kecil: melihat matahari terbit di tempat yang Anda impikan sejak lama, mengajak orang tua ke dataran tinggi yang belum sempat mereka lihat, atau sekadar pergi bersama orang-orang tersayang tanpa dikejar waktu.

Ceritakan kepada kami apa yang ada di kepala Anda. Tim lokal kami akan membantu mengubahnya menjadi perjalanan yang terasa seperti milik Anda sendiri.'),
        'highlights' => [__('Destinasi dan durasi Anda tentukan sendiri'), __('Gabungan beberapa kawasan dataran tinggi'), __('Konsultasi rute gratis bersama tim lokal'), __('Sopir lokal yang hafal titik terbaik')],
        'itinerary' => [
            ['t' => __('Langkah 1'), 'x' => __('Konsultasi rute, destinasi, dan durasi bersama tim kami')],
            ['t' => __('Langkah 2'), 'x' => __('Kami susun itinerary privat dan siapkan seluruh perjalanan')],
            ['t' => __('Langkah 3'), 'x' => __('Berangkat dengan ritme dan jeda milik Anda sendiri')],
        ],
        'practical' => [
            'pickup' => __('Titik jemput sesuai permintaan'),
            'start' => __('Fleksibel, sesuai permintaan tamu'),
            'capacity' => __('Maks. 6 tamu per booking (kendaraan disesuaikan), di luar pemandu yang merangkap sopir'),
            'tips' => __('Sampaikan destinasi impian Anda — kami bantu susun rutenya.'),
        ],
        'specs' => [
            ['icon' => 'schedule', 'text' => __('Fleksibel')],
            ['icon' => 'support_agent', 'text' => __('Sopir Privat')],
            ['icon' => 'hotel', 'text' => __('Jemput Hotel')],
        ]],
    ['region' => 'jawa-tengah', 'chip' => __('Esensial'), 'name' => 'DIENG HIGHLAND ESCAPE', 'durasi_hari' => 3, 'maks_orang' => 4, 'tone' => 'dieng', 'capacity' => __('Maks. 4 orang'), 'place' => __('Dieng, Jawa Tengah'), 'img' => '/images/foto-dieng.avif', 'rating' => '4.9', 'reviews' => 142, 'price' => 'Rp 7.700.000',
        'tagline' => __('Menyusuri lautan awan sebelum matahari terbit di dataran 2.000 meter'),
        'desc' => __('Orang Jawa menyebut Dieng sebagai tempat para dewa bersemayam. Ribuan tahun lalu, gunung api purba runtuh dan meninggalkan dataran tinggi yang dingin, berkabut, dan sunyi. Di sana berdiri candi-candi tertua di Jawa, yang dibangun orang-orang yang percaya bahwa langit paling dekat dari tempat setinggi ini.

Kami mengajak Anda ke sana bukan untuk berburu foto. Kami ingin Anda merasakan pagi yang pelan: udara yang menggigit, awan yang mengalir di bawah kaki, dan jeda dari hiruk-pikuk yang sudah lama tidak Anda dapatkan.'),
        'highlights' => [__('Golden sunrise di Bukit Sikunir'), __('Kawah Sikidang yang masih beruap'), __('Telaga Warna & Telaga Pengilon'), __('Candi Arjuna berusia ribuan tahun'), __('Kebun teh Tambi berhawa sejuk')],
        'itinerary' => [
            ['t' => __('Hari 1'), 'x' => __('Jemput di hotel atau titik jemput (Wonosobo, Semarang, atau Yogyakarta), lalu berangkat menuju dataran tinggi Dieng')],
            ['t' => __('Hari 1'), 'x' => __('Kawah Sikidang, Telaga Warna, dan Telaga Pengilon')],
            ['t' => __('Hari 1'), 'x' => __('Candi Arjuna dan kompleks candi Dieng')],
            ['t' => __('Hari 2'), 'x' => __('Golden sunrise di Bukit Sikunir')],
            ['t' => __('Hari 2'), 'x' => __('Kebun teh Tambi dan jalur kabut Kaligua')],
            ['t' => __('Hari 2'), 'x' => __('Belanja oleh-oleh khas Dieng')],
            ['t' => __('Hari 3'), 'x' => __('Antar kembali ke hotel atau titik lanjutan Anda')],
        ],
        'practical' => [
            'pickup' => __('Hotel di Wonosobo, Semarang, atau Yogyakarta'),
            'start' => __('Fleksibel, sesuai permintaan tamu'),
            'capacity' => __('Maks. 4 tamu per booking, di luar pemandu yang merangkap sopir'),
            'vehicle' => __('Toyota Fortuner 2020 atau Kijang Innova Venturer 2020: nyaman di jalan pegunungan berkelok dan bagasi lega.'),
            'tips' => __('Bawa jaket tebal — suhu pagi di puncak bisa mencapai 4°C.'),
        ],
        'specs' => [
            ['icon' => 'schedule', 'text' => __('3 Hari · 2 Malam')],
            ['icon' => 'support_agent', 'text' => __('Sopir Privat')],
            ['icon' => 'hotel', 'text' => __('Jemput Hotel')],
        ]],
    ['region' => 'jawa-timur', 'chip' => __('Petualangan'), 'name' => 'BROMO SUNRISE JOURNEY', 'durasi_hari' => 4, 'maks_orang' => 4, 'tone' => 'bromo', 'capacity' => __('Maks. 4 orang'), 'place' => __('Bromo, Batu & Malang, Jawa Timur'), 'img' => '/images/bromo.avif', 'rating' => '4.95', 'reviews' => 98, 'price' => 'Rp 12.850.000',
        'rekomendasi_penginapan' => '• Malam 1 di Kota Batu, malam 2 di kawasan Bromo, dan malam 3 di Kota Malang: admin merekomendasikan hotel bintang 4 sesuai kawasan dan anggaran Anda',
        'tagline' => __('Empat hari menyusuri Batu, lautan pasir Bromo, Madakaripura, dan kota Malang'),
        'desc' => __('Bagi masyarakat Tengger, Bromo bukan sekadar gunung. Menurut legenda, pasangan Roro Anteng dan Joko Seger meminta keturunan kepada para dewa dengan janji akan mempersembahkan anak bungsu mereka ke kawah. Sampai hari ini, orang Tengger masih naik membawa sesaji setiap tahun sebagai tanda syukur dan janji yang ditepati.

Perjalanan ini mengajak Anda berdiri di tanah yang dijaga turun-temurun itu, menyaksikan matahari terbit di atas lautan pasir, dan pulang dengan cerita yang lebih besar dari sekadar pemandangan.'),
        'highlights' => [__('Sunrise dari titik pandang Penanjakan'), __('Lautan pasir kaldera Tengger & kawah Bromo'), __('Savana Teletubbies & Pasir Berbisik'), __('Air Terjun Madakaripura di tebing melingkar'), __('Museum Angkut & Alun-Alun Kota Batu'), __('Kampung Warna-Warni Jodipan & Kampung Tridi Malang')],
        'itinerary' => [
            ['t' => __('Hari 1'), 'x' => __('Jemput di hotel atau titik jemput (Malang, Surabaya, atau Probolinggo), lalu berangkat menuju Kota Batu')],
            ['t' => __('Hari 1'), 'x' => __('Museum Angkut, koleksi kendaraan dunia dalam satu kompleks')],
            ['t' => __('Hari 1'), 'x' => __('Alun-Alun Kota Batu dan kincir angin ikoniknya')],
            ['t' => __('Hari 2'), 'x' => __('Air Terjun Coban Rondo dan hutan pinus di sekitarnya')],
            ['t' => __('Hari 2'), 'x' => __('Perjalanan menuju kawasan Tengger dan bermalam di sekitar Bromo')],
            ['t' => __('Hari 3'), 'x' => __('Golden sunrise dari Penanjakan')],
            ['t' => __('Hari 3'), 'x' => __('Lautan pasir, Pasir Berbisik, Savana, dan kawah Bromo')],
            ['t' => __('Hari 3'), 'x' => __('Air Terjun Madakaripura, trekking ringan menyusuri tebing melingkar')],
            ['t' => __('Hari 3'), 'x' => __('Perjalanan menuju Kota Malang untuk bermalam')],
            ['t' => __('Hari 4'), 'x' => __('Kampung Warna-Warni Jodipan dan Kampung Tridi')],
            ['t' => __('Hari 4'), 'x' => __('Belanja oleh-oleh khas Malang (keripik tempe, apel, dan lainnya)')],
            ['t' => __('Hari 4'), 'x' => __('Antar kembali ke hotel atau titik lanjutan Anda')],
        ],
        'practical' => [
            'pickup' => __('Hotel di Malang, Surabaya, atau Probolinggo'),
            'start' => __('Fleksibel, sesuai permintaan tamu'),
            'capacity' => __('Maks. 4 tamu per booking, di luar pemandu yang merangkap sopir'),
            'vehicle' => __('Untuk lautan pasir pilih jeep 4x4: Toyota Land Cruiser FJ40 atau Hilux Double Cabin 2020 (muat 4 tamu). Jimny 2020 hanya muat 3 tamu.'),
            'tips' => __('Bawa jaket tebal — angin kaldera dingin bahkan di siang hari — dan sepatu yang nyaman untuk trekking di Madakaripura.'),
        ],
        'specs' => [
            ['icon' => 'schedule', 'text' => __('4 Hari · 3 Malam')],
            ['icon' => 'support_agent', 'text' => __('Sopir Privat')],
            ['icon' => 'hotel', 'text' => __('Jemput Hotel')],
        ]],
    ['region' => 'jawa-timur', 'chip' => __('Petualangan'), 'name' => 'IJEN VOLCANIC ESCAPE', 'durasi_hari' => 3, 'maks_orang' => 4, 'tone' => 'ijen', 'capacity' => __('Maks. 4 orang'), 'place' => __('Baluran, Ijen & Banyuwangi, Jawa Timur'), 'img' => '/images/kawah-ijen.avif', 'rating' => '5.0', 'reviews' => 86, 'price' => 'Rp 7.400.000',
        'tagline' => __('Savana Baluran, api biru Ijen, dan pantai merah Banyuwangi dalam tiga hari'),
        'desc' => __('Sebelum fajar, ketika kebanyakan orang masih tidur, para penambang Ijen sudah berjalan naik. Mereka memikul belerang di bahu, menuruni kawah yang uapnya membuat mata perih, lalu mengulanginya lagi dan lagi. Di kegelapan yang sama, api biru menyala dari celah bumi, hal langka yang hanya bisa dilihat di beberapa tempat di dunia.

Ijen adalah tempat yang jujur. Ia indah sekaligus keras, dan setelah berdiri di bibir kawahnya Anda akan melihat kerja, harapan, dan alam dengan cara yang berbeda.'),
        'highlights' => [__('Api biru di bibir kawah Ijen'), __('Danau kawah hijau yang menakjubkan'), __('Savana Bekol, "Africa van Java" di Baluran'), __('Pantai Bama dan hutan mangrove Baluran'), __('Sunset di Pantai Pulau Merah'), __('Desa adat Osing Kemiren')],
        'itinerary' => [
            ['t' => __('Hari 1'), 'x' => __('Jemput di hotel atau titik jemput (Banyuwangi atau Bondowoso), lalu berangkat menuju Taman Nasional Baluran')],
            ['t' => __('Hari 1'), 'x' => __('Savana Bekol, padang rumput luas dengan Gunung Baluran sebagai latar')],
            ['t' => __('Hari 1'), 'x' => __('Pantai Bama dan jalur mangrove')],
            ['t' => __('Hari 2'), 'x' => __('Trekking privat menuju bibir kawah bersama pemandu')],
            ['t' => __('Hari 2'), 'x' => __('Api biru dan danau kawah hijau')],
            ['t' => __('Hari 2'), 'x' => __('Sunset di Pantai Pulau Merah')],
            ['t' => __('Hari 3'), 'x' => __('Desa adat Osing Kemiren dan belanja oleh-oleh khas Banyuwangi')],
            ['t' => __('Hari 3'), 'x' => __('Antar kembali ke hotel atau pelabuhan Ketapang')],
        ],
        'practical' => [
            'pickup' => __('Hotel di Banyuwangi atau Bondowoso'),
            'start' => __('Fleksibel, sesuai permintaan tamu'),
            'capacity' => __('Maks. 4 tamu per booking, di luar pemandu yang merangkap sopir'),
            'vehicle' => __('Toyota Hilux Double Cabin 2020 atau Fortuner 2020: kuat di tanjakan menuju Paltuding dan perjalanan dini hari.'),
            'tips' => __('Bawa jaket tebal, masker, dan jaket tahan angin — jalur Ijen berdebu belerang. Siapkan tenaga: trekking dimulai dini hari.'),
        ],
        'specs' => [
            ['icon' => 'schedule', 'text' => __('3 Hari · 2 Malam')],
            ['icon' => 'support_agent', 'text' => __('Sopir Privat')],
            ['icon' => 'hotel', 'text' => __('Jemput Hotel')],
        ]],
    ['region' => 'jawa-barat', 'chip' => __('Esensial'), 'name' => 'CIWIDEY & RANCABALI ESCAPE', 'durasi_hari' => 3, 'maks_orang' => 4, 'tone' => 'ciwidey', 'capacity' => __('Maks. 4 orang'), 'place' => __('Ciwidey, Rancabali & Lembang, Jawa Barat'), 'img' => '/images/kawah-putih-ciwidey.webp', 'rating' => null, 'reviews' => null, 'price' => 'Rp 7.650.000',
        'tagline' => __('Kawah putih, kebun teh, danau Patenggang, hingga Tangkuban Parahu dalam tiga hari'),
        'desc' => __('Di selatan Bandung, letusan Gunung Patuha pernah meninggalkan sebuah kawah yang kini berwarna putih kehijauan dan tampak seperti lukisan. Tidak jauh dari sana ada Situ Patenggang, danau yang menurut cerita lahir dari pertemuan dua kekasih yang lama terpisah dan akhirnya saling menemukan.

Ciwidey cocok untuk Anda yang ingin pergi tanpa terburu-buru: udara sejuk, hamparan teh, dan waktu yang cukup untuk duduk diam bersama orang yang Anda ajak.'),
        'highlights' => [__('Danau kawah Kawah Putih'), __('Situ Patenggang dan Batu Cinta'), __('Kebun teh Rancabali tanpa batas pandang'), __('Ranca Upas dan penangkaran rusa'), __('Kawah Gunung Tangkuban Parahu'), __('Floating Market Lembang')],
        'itinerary' => [
            ['t' => __('Hari 1'), 'x' => __('Jemput di hotel atau titik jemput (Bandung), lalu berangkat menuju Ciwidey')],
            ['t' => __('Hari 1'), 'x' => __('Kawah Putih')],
            ['t' => __('Hari 1'), 'x' => __('Situ Patenggang dan Batu Cinta')],
            ['t' => __('Hari 1'), 'x' => __('Kebun teh Rancabali')],
            ['t' => __('Hari 2'), 'x' => __('Ranca Upas, bertemu rusa di penangkaran dan berjalan di hutan pinus')],
            ['t' => __('Hari 2'), 'x' => __('Kebun strawberry Ciwidey (petik sendiri)')],
            ['t' => __('Hari 3'), 'x' => __('Kawah Gunung Tangkuban Parahu')],
            ['t' => __('Hari 3'), 'x' => __('Floating Market Lembang')],
            ['t' => __('Hari 3'), 'x' => __('Antar kembali ke hotel atau titik lanjutan Anda')],
        ],
        'practical' => [
            'pickup' => __('Hotel di Bandung'),
            'start' => __('Fleksibel, sesuai permintaan tamu'),
            'capacity' => __('Maks. 4 tamu per booking, di luar pemandu yang merangkap sopir'),
            'vehicle' => __('Kijang Innova Venturer 2020 atau Fortuner 2020: nyaman di jalur kebun teh yang menanjak dan berkelok.'),
            'tips' => __('Cuaca dataran tinggi berubah cepat: bawa jaket ringan dan payung. Kawah Putih dan Tangkuban Parahu berbau belerang, masker sangat membantu.'),
        ],
        'specs' => [
            ['icon' => 'schedule', 'text' => __('3 Hari · 2 Malam')],
            ['icon' => 'support_agent', 'text' => __('Sopir Privat')],
            ['icon' => 'hotel', 'text' => __('Jemput Hotel')],
        ]],
    ['region' => 'jawa-barat', 'chip' => __('Petualangan'), 'name' => 'GREEN CANYON JOURNEY', 'durasi_hari' => 3, 'maks_orang' => 4, 'tone' => 'green-canyon', 'capacity' => __('Maks. 4 orang'), 'place' => __('Pangandaran & Batu Karas, Jawa Barat'), 'img' => '/images/green-canyon-cukang-taneuh.webp', 'rating' => null, 'reviews' => null, 'price' => 'Rp 7.650.000',
        'tagline' => __('Sungai hijau Green Canyon, pantai-pantai Pangandaran, dan hutan wisata Pananjung'),
        'desc' => __('Sungai Cijulang mengukir tebing batu selama ribuan tahun, dan hasilnya adalah lorong hijau yang kini dikenal sebagai Green Canyon. Penduduk setempat menyebutnya Cukang Taneuh, "jembatan tanah", dan sejak lama mereka menjadi pemandu bagi siapa saja yang ingin melihatnya dari dekat.

Anda akan menyusuri air yang tenang di antara dinding batu yang diselimuti lumut dan akar, lalu berhenti sejenak ketika cahaya matahari jatuh menembus celahnya. Tempat ini mengingatkan bahwa keindahan sering datang dari proses yang sabar.'),
        'highlights' => [__('Sungai berair hijau Cukang Taneuh'), __('Perahu tradisional di antara tebing karang'), __('Pantai Batu Karas yang tenang'), __('Tebing Pantai Batu Hiu'), __('Cagar Alam Pananjung dan Pasir Putih'), __('Sunset di Pantai Pangandaran')],
        'itinerary' => [
            ['t' => __('Hari 1'), 'x' => __('Jemput di hotel atau titik jemput (Pangandaran), lalu berangkat menuju dermaga Green Canyon')],
            ['t' => __('Hari 1'), 'x' => __('Perahu tradisional menyusuri sungai hijau dan tebing karang')],
            ['t' => __('Hari 1'), 'x' => __('Sunset di Pantai Pangandaran')],
            ['t' => __('Hari 2'), 'x' => __('Pantai Batu Karas, berjalan santai atau mencoba selancar pemula')],
            ['t' => __('Hari 2'), 'x' => __('Pantai Batu Hiu dan tebing karangnya')],
            ['t' => __('Hari 2'), 'x' => __('Cagar Alam Pananjung dan Pantai Pasir Putih')],
            ['t' => __('Hari 3'), 'x' => __('Antar kembali ke hotel atau titik lanjutan Anda')],
        ],
        'practical' => [
            'pickup' => __('Hotel di Pangandaran'),
            'start' => __('Fleksibel, sesuai permintaan tamu'),
            'capacity' => __('Maks. 4 tamu per booking, di luar pemandu yang merangkap sopir'),
            'vehicle' => __('Kijang Innova Venturer 2020 atau Fortuner 2020: bagasi lega untuk perlengkapan basah dan jalur pantai selatan yang panjang.'),
            'tips' => __('Bawa sandal, baju ganti, dan tabir surya untuk berenang di kolam alami dan bermain di pantai.'),
        ],
        'specs' => [
            ['icon' => 'schedule', 'text' => __('3 Hari · 2 Malam')],
            ['icon' => 'support_agent', 'text' => __('Sopir Privat')],
            ['icon' => 'hotel', 'text' => __('Jemput Hotel')],
        ]],
    ['region' => 'jawa-barat', 'chip' => __('Petualangan'), 'name' => 'PAPANDAYAN CRATER JOURNEY', 'durasi_hari' => 2, 'maks_orang' => 4, 'tone' => 'papandayan', 'capacity' => __('Maks. 4 orang'), 'place' => __('Garut, Jawa Barat'), 'img' => '/images/gunung-papandayan.avif', 'rating' => null, 'reviews' => null, 'price' => 'Rp 4.550.000',
        'tagline' => __('Uap belerang, hutan mati, dan padang edelweis di 2.665 mdpl'),
        'desc' => __('Papandayan pernah mengamuk. Letusan pada 2002 menumbangkan hutan di lerengnya dan meninggalkan batang-batang pucat yang berdiri seperti penjaga bisu. Dari bekas luka itulah lahir sesuatu yang baru: padang edelweis Tegal Alun yang kini mekar di ketinggian, bunga abadi yang tumbuh di tempat yang dulu hangus.

Perjalanan ini bercerita tentang bangkit kembali. Anda akan berjalan melewati bekas luka gunung itu dan berakhir di tempat yang tenang, tempat alam menunjukkan caranya memulai ulang.'),
        'highlights' => [__('Uap kawah belerang yang masih aktif'), __('Hutan Mati yang fotogenik'), __('Padang edelweis Tegal Alun'), __('Jalur pendakian ringan untuk pemula')],
        'itinerary' => [
            ['t' => __('Hari 1'), 'x' => __('Jemput di hotel atau titik jemput (Garut atau Bandung), lalu berangkat menuju kaki Gunung Papandayan')],
            ['t' => __('Hari 1'), 'x' => __('Kawah belerang dan Hutan Mati')],
            ['t' => __('Hari 1'), 'x' => __('Padang edelweis Tegal Alun')],
            ['t' => __('Hari 2'), 'x' => __('Pemandian air panas Cipanas Garut (opsional)')],
            ['t' => __('Hari 2'), 'x' => __('Antar kembali ke hotel atau titik lanjutan Anda')],
        ],
        'practical' => [
            'pickup' => __('Hotel di Garut atau Bandung'),
            'start' => __('Fleksibel, sesuai permintaan tamu'),
            'capacity' => __('Maks. 4 tamu per booking, di luar pemandu yang merangkap sopir'),
            'vehicle' => __('Hilux Double Cabin 2020 atau Fortuner 2020: jalan menuju area parkir kawah berbatu dan menanjak.'),
            'tips' => __('Bawa jaket tebal — suhu di Tegal Alun bisa turun di bawah 10°C.'),
        ],
        'specs' => [
            ['icon' => 'schedule', 'text' => __('2 Hari · 1 Malam')],
            ['icon' => 'support_agent', 'text' => __('Sopir Privat')],
            ['icon' => 'hotel', 'text' => __('Jemput Hotel')],
        ]],
    ['region' => 'jawa-barat', 'chip' => __('Esensial'), 'name' => 'LEMBANG & TANGKUBAN PARAHU JOURNEY', 'durasi_hari' => 2, 'maks_orang' => 4, 'tone' => 'lembang', 'capacity' => __('Maks. 4 orang'), 'place' => __('Lembang & Bandung Utara, Jawa Barat'), 'img' => '/images/lembang-tangkuban-parahu.webp', 'rating' => null, 'reviews' => null, 'price' => 'Rp 4.850.000',
        'tagline' => __('Kawah gunung berapi, kabut Lembang, dan kebun bunga di utara Bandung'),
        'desc' => __('Tangkuban Parahu mengambil namanya dari legenda Sangkuriang: perahu yang ditelungkupkan menjadi gunung. Dari bibir Kawah Ratu, uap belerang naik di antara tebing berkabut, sementara Lembang di bawahnya menawarkan udara sejuk, kebun bunga, dan sudut-sudut kota yang tenang.

Perjalanan dua hari ini memadukan kawah, pemandian air panas, dan Lembang tanpa terburu-buru. Pemandu MISTBORNE yang merangkap sopir menyesuaikan ritme dengan rombongan Anda.'),
        'highlights' => [__('Kawah Ratu dan Kawah Domas di Tangkuban Parahu'), __('Pemandian air panas alami Ciater'), __('Kebun bunga dan hutan anggrek Lembang'), __('Hotel bintang 4 dengan udara sejuk Lembang')],
        'itinerary' => [
            ['t' => __('Hari 1'), 'x' => __('Jemput di hotel atau titik jemput (Bandung), lalu berangkat menuju Lembang')],
            ['t' => __('Hari 1'), 'x' => __('Kawah Ratu, Kawah Domas, dan Kawah Upas di Tangkuban Parahu (mengikuti status keamanan kawah)')],
            ['t' => __('Hari 1'), 'x' => __('Berendam air panas alami di kawasan Ciater (opsional)')],
            ['t' => __('Hari 1'), 'x' => __('Check-in hotel bintang 4 di Lembang dan istirahat')],
            ['t' => __('Hari 2'), 'x' => __('Sarapan, lalu menjelajah kebun bunga dan hutan anggrek di kawasan Cikole')],
            ['t' => __('Hari 2'), 'x' => __('Antar kembali ke hotel atau titik lanjutan Anda')],
        ],
        'practical' => [
            'pickup' => __('Hotel di Bandung atau Lembang'),
            'start' => __('Fleksibel, sesuai permintaan tamu'),
            'capacity' => __('Maks. 4 tamu per booking, di luar pemandu yang merangkap sopir'),
            'vehicle' => __('Kijang Innova Venturer 2020 atau Fortuner 2020 untuk jalan aspal yang berkelok; Land Cruiser FJ40 bila ingin sensasi jeep terbuka (muat 4 tamu untuk journey ini).'),
            'tips' => __('Bawa jaket dan masker ringan: uap belerang di Kawah Ratu bisa menyengat, dan akses kawah mengikuti status keamanan dari PVMBG pada hari itu.'),
        ],
        'specs' => [
            ['icon' => 'schedule', 'text' => __('2 Hari · 1 Malam')],
            ['icon' => 'support_agent', 'text' => __('Sopir Privat')],
            ['icon' => 'hotel', 'text' => __('Jemput Hotel')],
        ]],
    ['region' => 'jawa-tengah', 'chip' => __('Petualangan'), 'name' => 'BOROBUDUR & MERAPI SUNRISE JOURNEY', 'durasi_hari' => 2, 'maks_orang' => 4, 'tone' => 'borobudur', 'capacity' => __('Maks. 4 orang'), 'place' => __('Magelang & Borobudur, Jawa Tengah'), 'img' => '/images/borobudur-stupa.webp', 'rating' => null, 'reviews' => null, 'price' => 'Rp 5.050.000',
        'tagline' => __('Fajar di atas Borobudur dan siluet Merapi dari perbukitan Magelang'),
        'desc' => __('Sebelum matahari terbit, kabut masih menggantung di lembah Kedu dan stupa Borobudur muncul pelan dari baliknya, dengan Merapi dan Merbabu sebagai latar. Pemandangan ini hanya bisa dinikmati dari bukit-bukit di sekitarnya, jauh dari keramaian pintu masuk candi.

Perjalanan dua hari ini mengajak Anda menikmati candi saat cahayanya paling lembut, lalu naik ke titik pandang yang menghadap Merapi, tanpa harus mengatur apa pun sendiri.'),
        'highlights' => [__('Sunrise Borobudur dari bukit Punthuk Setumbu'), __('Candi Borobudur, Mendut, dan Pawon dalam satu jalur'), __('Titik pandang Merapi dan Merbabu di Ketep Pass'), __('Hotel bintang 4 di Magelang')],
        'itinerary' => [
            ['t' => __('Hari 1'), 'x' => __('Jemput di hotel atau titik jemput (Yogyakarta atau Magelang), lalu berangkat menuju kawasan Borobudur')],
            ['t' => __('Hari 1'), 'x' => __('Candi Mendut dan Candi Pawon, lalu Candi Borobudur (naik ke stupa mengikuti kuota harian pengelola)')],
            ['t' => __('Hari 1'), 'x' => __('Check-in hotel bintang 4 di Magelang dan istirahat')],
            ['t' => __('Hari 2'), 'x' => __('Dini hari menuju Punthuk Setumbu untuk menyaksikan sunrise di atas Borobudur')],
            ['t' => __('Hari 2'), 'x' => __('Sarapan, lalu ke Ketep Pass untuk memandang Merapi dan Merbabu')],
            ['t' => __('Hari 2'), 'x' => __('Antar kembali ke hotel atau titik lanjutan Anda')],
        ],
        'practical' => [
            'pickup' => __('Hotel di Yogyakarta atau Magelang'),
            'start' => __('Fleksibel, sesuai permintaan tamu'),
            'capacity' => __('Maks. 4 tamu per booking, di luar pemandu yang merangkap sopir'),
            'vehicle' => __('Fortuner 2020 atau Kijang Innova Venturer 2020: jalan aspal ke Borobudur dan Ketep Pass, dengan bagasi lega untuk perlengkapan foto.'),
            'tips' => __('Pakai sepatu dengan sol yang tidak licin untuk naik ke Borobudur dan Setumbu, dan bawa jaket tipis: pagi di bukit cukup dingin. Kuota naik candi dan jam buka mengikuti pengelola.'),
        ],
        'specs' => [
            ['icon' => 'schedule', 'text' => __('2 Hari · 1 Malam')],
            ['icon' => 'support_agent', 'text' => __('Sopir Privat')],
            ['icon' => 'hotel', 'text' => __('Jemput Hotel')],
        ]],
];
