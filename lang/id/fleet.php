<?php

return [
    'fleet' => 'Armada & Driver',
    'drivers' => 'Daftar Driver',
    'vehicles' => 'Daftar Kendaraan',
    'assignments' => 'Penugasan Driver',
    'assignment_calendar' => 'Kalender Penugasan',
    'duty_letter' => 'Surat Tugas',
    'surat_tugas' => 'Surat Tugas Operasional',
    'duty_letter_subtitle' => 'Surat Perintah Perjalanan dan Penugasan Driver',
    'reference_number' => 'Nomor Referensi',
    'issued_date' => 'Tanggal Diterbitkan',

    // Driver
    'driver' => 'Driver',
    'driver_name' => 'Nama Driver',
    'driver_gender' => 'Jenis Kelamin',
    'gender_male' => 'Laki-laki',
    'gender_female' => 'Perempuan',
    'driver_phone' => 'No. Telepon',
    'driver_languages' => 'Bahasa yang Dikuasai',
    'driver_status' => 'Status Driver',
    'status_active' => 'Aktif',
    'status_inactive' => 'Nonaktif',
    'add_driver' => 'Tambah Driver',
    'edit_driver' => 'Edit Driver',

    // Vehicle
    'vehicle' => 'Kendaraan',
    'vehicle_plate' => 'Plat Nomor',
    'vehicle_type' => 'Tipe Kendaraan',
    'vehicle_capacity' => 'Kapasitas Penumpang',
    'capacity_pax' => ':count Penumpang',
    'add_vehicle' => 'Tambah Kendaraan',
    'edit_vehicle' => 'Edit Kendaraan',

    // Assignment & Booking
    'booking_code' => 'Kode Pemesanan',
    'guest_name' => 'Nama Tamu',
    'pax_count' => 'Jumlah Pax',
    'departure_date' => 'Tanggal Berangkat',
    'return_date' => 'Tanggal Selesai',
    'assignment_period' => 'Periode Penugasan',
    'assign_driver' => 'Tugaskan Driver',
    'assign_driver_and_vehicle' => 'Tugaskan Driver & Kendaraan',
    'change_assignment' => 'Ubah Penugasan',
    'cancel_assignment' => 'Batalkan Penugasan',
    'cancel_reason' => 'Alasan Pembatalan',
    'notes' => 'Catatan Khusus',
    'assigned_driver' => 'Driver Bertugas',
    'assigned_vehicle' => 'Kendaraan Bertugas',
    'no_assignment' => 'Belum ada penugasan driver untuk pemesanan ini.',
    'unassigned' => 'Belum Ditugaskan',

    // Gender preference
    'gender_preference' => 'Preferensi Gender Driver',
    'pref_female' => 'Driver Perempuan',
    'pref_male' => 'Driver Laki-laki',
    'pref_any' => 'Tidak Ada Preferensi',
    'warning_no_female' => 'Peringatan: Tidak ada driver perempuan yang tersedia untuk periode pemesanan ini di cabang ini.',
    'warning_no_male' => 'Peringatan: Tidak ada driver laki-laki yang tersedia untuk periode pemesanan ini di cabang ini.',
    'warning_no_driver' => 'Peringatan: Tidak ada driver yang tersedia untuk periode pemesanan ini.',
    'suggested_drivers' => 'Saran Driver yang Tersedia',
    'suggested_badge' => 'Sesuai Preferensi & Tersedia',

    // Statuses
    'status_assigned' => 'Ditugaskan',
    'status_in_progress' => 'Sedang Berjalan',
    'status_completed' => 'Selesai',
    'status_cancelled' => 'Dibatalkan',

    // Duty letter details
    'guest_manifest' => 'Manifest Tamu',
    'operational_instructions' => 'Instruksi Operasional',
    'authorized_by' => 'Diberikan Oleh',
    'duty_signature_note' => 'Dokumen ini sah dan diterbitkan secara digital oleh sistem Indogate Travel Platform.',

    // Feedback
    'saved_successfully' => 'Data berhasil disimpan.',
    'assigned_successfully' => 'Driver dan kendaraan berhasil ditugaskan.',
    'cancelled_successfully' => 'Penugasan berhasil dibatalkan.',
    'deleted_successfully' => 'Data berhasil dihapus.',
    'has_future_assignments' => 'Tidak bisa dinonaktifkan/dihapus: masih ada penugasan aktif. Batalkan penugasan dulu.',

    // i18n sweep
    'all_gender' => 'Semua Gender',
    'search_assignment_ph' => 'Cari driver, plat, booking…',
    'active_date' => 'Tanggal Aktif:',
    'assignment_total' => 'Total :count penugasan aktif pada tanggal ini',
    'cancel_reason_hint' => 'Masukkan alasan pembatalan penugasan driver. Alasan ini akan tercatat dalam audit log.',
    'calendar_lede' => 'Jadwal penugasan driver dan armada di cabang :branch',
    'drivers_lede' => 'Driver di cabang :branch',
    'vehicles_lede' => 'Kendaraan di cabang :branch',
    'no_vehicle_data' => 'Belum ada data kendaraan untuk filter ini.',
];
