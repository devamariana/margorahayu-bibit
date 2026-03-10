-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 10, 2026 at 02:29 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_margorahayu`
--

-- --------------------------------------------------------

--
-- Table structure for table `bibits`
--

CREATE TABLE `bibits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama_bibit` varchar(255) NOT NULL,
  `jenis` varchar(255) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `harga_subsidi` int(11) NOT NULL DEFAULT 0,
  `sumber_pasokan` varchar(255) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'tersedia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bibits`
--

INSERT INTO `bibits` (`id`, `nama_bibit`, `jenis`, `stok`, `deskripsi`, `created_at`, `updated_at`, `harga_subsidi`, `sumber_pasokan`, `gambar`, `status`) VALUES
(3, 'Bibit Padi Unggul', 'Unggul 1', 249870, NULL, '2026-03-10 00:09:26', '2026-03-10 06:21:22', 60000, 'PT. Kucinta Bibit', 'bibit_1773126566.png', 'tersedia');

-- --------------------------------------------------------

--
-- Table structure for table `bibit_petani`
--

CREATE TABLE `bibit_petani` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `bibit_id` bigint(20) UNSIGNED NOT NULL,
  `petani_id` bigint(20) UNSIGNED NOT NULL,
  `kuota_maksimal` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lahans`
--

CREATE TABLE `lahans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `petani_id` bigint(20) UNSIGNED NOT NULL,
  `nama_blok` varchar(255) NOT NULL,
  `luas_lahan` int(11) NOT NULL,
  `rencana_bibit` varchar(255) NOT NULL,
  `jenis_tanah` varchar(255) DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lahans`
--

INSERT INTO `lahans` (`id`, `petani_id`, `nama_blok`, `luas_lahan`, `rencana_bibit`, `jenis_tanah`, `lokasi`, `created_at`, `updated_at`, `status`) VALUES
(14, 11, 'Sawah Blok Barat', 400, 'Bibit Padi Unggul', '-', NULL, '2026-03-10 05:36:56', '2026-03-10 05:50:44', 'disetujui');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_01_21_052942_create_petanis_table', 1),
(5, '2026_03_03_015959_create_pindah_jatahs_table', 1),
(6, '2026_03_06_030920_create_bibits_table', 2),
(7, '2026_03_06_031339_create_transaksis_table', 3),
(8, '2026_03_07_145403_create_lahans_table', 4),
(9, '2026_03_08_034935_create_transaksis_table', 5),
(10, '2026_03_08_175240_create_periodes_table', 6),
(11, '2026_03_08_175954_add_columns_to_bibits_table', 7),
(12, '2026_03_08_181829_add_midtrans_columns_to_transaksis_table', 8),
(13, '2026_03_09_003933_add_status_to_lahans_table', 9),
(14, '2026_03_10_063902_create_bibit_petani_table', 10),
(15, '2026_03_10_120843_create_notifications_table', 11);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) UNSIGNED NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES
('5fa549c7-7d1a-461c-948f-a357a0a72837', 'App\\Notifications\\SistemNotifikasi', 'App\\Models\\User', 10, '{\"judul\":\"Pembayaran Lunas! \\u2705\",\"pesan\":\"Petani mariadi telah melunasi pembayaran untuk bibit \'Bibit Padi Unggul\' sebesar Rp 2.400.000.\",\"tipe\":\"success\",\"url\":\"http:\\/\\/127.0.0.1:8000\\/admin\\/riwayat-transaksi\",\"id_terkait\":18}', '2026-03-10 06:27:55', '2026-03-10 06:27:50', '2026-03-10 06:27:55'),
('63e84719-b53f-479b-b879-786852dd9e67', 'App\\Notifications\\SistemNotifikasi', 'App\\Models\\User', 14, '{\"judul\":\"Pesan Pembelian Bibit\",\"pesan\":\"Permintaan Anda untuk Bibit Padi Unggul telah disetujui! Segera lakukan pembayaran sebelum 7 hari dari sekarang.\",\"tipe\":\"info\",\"url\":\"http:\\/\\/127.0.0.1:8000\\/riwayat-pembelian\"}', '2026-03-10 06:16:00', '2026-03-10 05:56:49', '2026-03-10 06:16:00'),
('955e26f9-da32-479c-9135-4ce169042093', 'App\\Notifications\\SistemNotifikasi', 'App\\Models\\User', 14, '{\"judul\":\"Akun Diverifikasi\",\"pesan\":\"Selamat! Akun Anda telah berhasil diverifikasi oleh Admin. Silakan lengkapi profil Anda.\",\"tipe\":\"success\"}', '2026-03-10 05:45:01', '2026-03-10 05:38:31', '2026-03-10 05:45:01'),
('c3592821-a757-4c21-a1b2-1bf3b435b271', 'App\\Notifications\\SistemNotifikasi', 'App\\Models\\User', 10, '{\"judul\":\"Permintaan Pembelian Bibit\",\"pesan\":\"Petani mariadi meminta 40 qty bibit \'Bibit Padi Unggul\'. Mohon dicek.\",\"tipe\":\"bibit\",\"url\":\"http:\\/\\/127.0.0.1:8000\\/admin\\/riwayat-transaksi\",\"id_terkait\":18}', '2026-03-10 06:25:50', '2026-03-10 06:21:22', '2026-03-10 06:25:50'),
('c844f461-a010-48f3-b4df-2cf0ef675e09', 'App\\Notifications\\SistemNotifikasi', 'App\\Models\\User', 14, '{\"judul\":\"Status Data Lahan\",\"pesan\":\"Data Lahan Anda berlokasi di  telah disetujui.\",\"tipe\":\"success\",\"url\":\"http:\\/\\/127.0.0.1:8000\\/petani\\/lahan\"}', '2026-03-10 05:56:04', '2026-03-10 05:50:44', '2026-03-10 05:56:04'),
('dd0e30b4-7210-4b9d-846d-b283f093219d', 'App\\Notifications\\SistemNotifikasi', 'App\\Models\\User', 10, '{\"judul\":\"Permintaan Pembelian Bibit\",\"pesan\":\"Petani mariadi meminta 40 qty bibit \'Bibit Padi Unggul\'. Mohon dicek.\",\"tipe\":\"bibit\",\"url\":\"http:\\/\\/127.0.0.1:8000\\/admin\\/riwayat-transaksi\"}', '2026-03-10 05:57:00', '2026-03-10 05:56:40', '2026-03-10 05:57:00'),
('f15c3934-d054-4aee-bc8e-37069482ee6c', 'App\\Notifications\\SistemNotifikasi', 'App\\Models\\User', 10, '{\"judul\":\"Permintaan Data Lahan Baru\",\"pesan\":\"Petani mariadi telah menambahkan lahan di Sawah Blok Barat. Mohon segera diverifikasi supaya mereka bisa belanja bibit.\",\"tipe\":\"info\"}', '2026-03-10 05:38:49', '2026-03-10 05:36:56', '2026-03-10 05:38:49');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `periodes`
--

CREATE TABLE `periodes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tahun` varchar(255) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `petanis`
--

CREATE TABLE `petanis` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `nama_lengkap` varchar(255) NOT NULL,
  `no_hp` varchar(255) NOT NULL,
  `nik` varchar(255) NOT NULL DEFAULT '-',
  `alamat` text DEFAULT NULL,
  `luas_lahan` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','disetujui','ditolak') NOT NULL DEFAULT 'pending',
  `foto_ktp` varchar(255) DEFAULT NULL,
  `foto_kk` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `petanis`
--

INSERT INTO `petanis` (`id`, `user_id`, `nama_lengkap`, `no_hp`, `nik`, `alamat`, `luas_lahan`, `status`, `foto_ktp`, `foto_kk`, `created_at`, `updated_at`) VALUES
(11, 14, 'mariadi', '895413793451', '36342763726836283', 'Ds. Mojolangu, Kec. Plesetan, Kab. Nglawak, Jawa Timur', 0.00, 'disetujui', 'KTP_1773145370.png', 'KK_1773145370.png', '2026-03-10 05:15:53', '2026-03-10 05:37:05');

-- --------------------------------------------------------

--
-- Table structure for table `pindah_jatahs`
--

CREATE TABLE `pindah_jatahs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pengirim_id` bigint(20) UNSIGNED NOT NULL,
  `penerima_id` bigint(20) UNSIGNED NOT NULL,
  `jumlah_kg` int(11) NOT NULL,
  `alasan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('diSz7ZQaW47QehvPDaJlBpjXLZt9ZBg2vYSBheAv', 14, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiTFQ1Y0hVeG83YWhVU3hxOWd6U3hUcHpuRFUwMVMyNVdJUFZxMjR3YyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJuZXciO2E6MDp7fXM6Mzoib2xkIjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzk6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9yaXdheWF0LXBlbWJlbGlhbiI7czo1OiJyb3V0ZSI7czoxNDoicGV0YW5pLnJpd2F5YXQiO31zOjEzOiJyZWdpc3Rlcl90aW1lIjtpOjE3NzMxNDQ5NDE7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTQ7fQ==', 1773149270),
('uItNwpimwmTeXV6VbBIFQufVkqNGZboZMyGfp5Oa', 10, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiV0E0UmhYcldMaTZocEpNNEEzYWJzZ293b1hOUHl2TmRKMUhFQWNMbiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDU6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9yaXdheWF0LXRyYW5zYWtzaSI7czo1OiJyb3V0ZSI7czoyMzoiYWRtaW4ucml3YXlhdF90cmFuc2Frc2kiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxMDt9', 1773149279);

-- --------------------------------------------------------

--
-- Table structure for table `transaksis`
--

CREATE TABLE `transaksis` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` varchar(255) DEFAULT NULL,
  `petani_id` bigint(20) UNSIGNED NOT NULL,
  `lahan_id` bigint(20) UNSIGNED NOT NULL,
  `bibit_id` bigint(20) UNSIGNED NOT NULL,
  `jumlah_beli` int(11) NOT NULL,
  `total_harga` bigint(20) NOT NULL,
  `metode_pembayaran` varchar(255) NOT NULL,
  `status_pembayaran` varchar(255) NOT NULL DEFAULT 'pending',
  `snap_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transaksis`
--

INSERT INTO `transaksis` (`id`, `order_id`, `petani_id`, `lahan_id`, `bibit_id`, `jumlah_beli`, `total_harga`, `metode_pembayaran`, `status_pembayaran`, `snap_token`, `created_at`, `updated_at`) VALUES
(17, 'TRX-1773147400-11', 11, 14, 3, 40, 2400000, '-', 'sukses', '50e21bb4-087a-4f68-8123-78d12fd46fab', '2026-03-10 05:56:40', '2026-03-10 06:16:34'),
(18, 'TRX-1773148882-11', 11, 14, 3, 40, 2400000, '-', 'sukses', '22b9fb98-016e-4ef2-9ccd-357229b547f6', '2026-03-10 06:21:22', '2026-03-10 06:27:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','petani','superadmin') DEFAULT 'petani',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `remember_token`, `created_at`, `updated_at`) VALUES
(6, 'superadmin', '$2y$12$IwV0VQchAFdWDbP.qAw0K.VvJCHCQCffO0RoA/aFVBN.9qIbyKz16', 'superadmin', NULL, '2026-03-08 10:14:58', '2026-03-08 10:14:58'),
(10, 'admin1', '$2y$12$LMf8IRNYnymd4NkKHERpQemiePr8XaDxHjJiryvIFQA3HL/Bj5N7q', 'admin', NULL, '2026-03-09 09:48:18', '2026-03-09 09:48:18'),
(14, 'mariadi', '$2y$12$H/x3BMxcmPBXm8ZaeHsFR.PSDnU9B22gqiwKKPYnFVOgcQ9uH4u4C', 'petani', NULL, '2026-03-10 05:15:53', '2026-03-10 05:15:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bibits`
--
ALTER TABLE `bibits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bibit_petani`
--
ALTER TABLE `bibit_petani`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bibit_petani_bibit_id_foreign` (`bibit_id`),
  ADD KEY `bibit_petani_petani_id_foreign` (`petani_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lahans`
--
ALTER TABLE `lahans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lahans_petani_id_foreign` (`petani_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `periodes`
--
ALTER TABLE `periodes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `petanis`
--
ALTER TABLE `petanis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `petanis_user_id_foreign` (`user_id`);

--
-- Indexes for table `pindah_jatahs`
--
ALTER TABLE `pindah_jatahs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pindah_jatahs_pengirim_id_foreign` (`pengirim_id`),
  ADD KEY `pindah_jatahs_penerima_id_foreign` (`penerima_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `transaksis`
--
ALTER TABLE `transaksis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaksis_petani_id_foreign` (`petani_id`),
  ADD KEY `transaksis_lahan_id_foreign` (`lahan_id`),
  ADD KEY `transaksis_bibit_id_foreign` (`bibit_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_username_unique` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bibits`
--
ALTER TABLE `bibits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bibit_petani`
--
ALTER TABLE `bibit_petani`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lahans`
--
ALTER TABLE `lahans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `periodes`
--
ALTER TABLE `periodes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `petanis`
--
ALTER TABLE `petanis`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `pindah_jatahs`
--
ALTER TABLE `pindah_jatahs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaksis`
--
ALTER TABLE `transaksis`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bibit_petani`
--
ALTER TABLE `bibit_petani`
  ADD CONSTRAINT `bibit_petani_bibit_id_foreign` FOREIGN KEY (`bibit_id`) REFERENCES `bibits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bibit_petani_petani_id_foreign` FOREIGN KEY (`petani_id`) REFERENCES `petanis` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lahans`
--
ALTER TABLE `lahans`
  ADD CONSTRAINT `lahans_petani_id_foreign` FOREIGN KEY (`petani_id`) REFERENCES `petanis` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `petanis`
--
ALTER TABLE `petanis`
  ADD CONSTRAINT `petanis_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pindah_jatahs`
--
ALTER TABLE `pindah_jatahs`
  ADD CONSTRAINT `pindah_jatahs_penerima_id_foreign` FOREIGN KEY (`penerima_id`) REFERENCES `petanis` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pindah_jatahs_pengirim_id_foreign` FOREIGN KEY (`pengirim_id`) REFERENCES `petanis` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transaksis`
--
ALTER TABLE `transaksis`
  ADD CONSTRAINT `transaksis_bibit_id_foreign` FOREIGN KEY (`bibit_id`) REFERENCES `bibits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaksis_lahan_id_foreign` FOREIGN KEY (`lahan_id`) REFERENCES `lahans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaksis_petani_id_foreign` FOREIGN KEY (`petani_id`) REFERENCES `petanis` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
