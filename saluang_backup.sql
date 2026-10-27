-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: saluang_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('laravel-cache-5c785c036466adea360111aa28563bfd556b5fba','i:1;',1793086676),('laravel-cache-5c785c036466adea360111aa28563bfd556b5fba:timer','i:1793086676;',1793086676);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chapters`
--

DROP TABLE IF EXISTS `chapters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chapters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `nomor_bab` int(10) unsigned NOT NULL,
  `judul` varchar(255) NOT NULL,
  `deskripsi` longtext DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `pengantar` longtext DEFAULT NULL,
  `tujuan_pembelajaran` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tujuan_pembelajaran`)),
  `pertanyaan_pemantik` longtext DEFAULT NULL,
  `gambar_pengenalan_path` varchar(255) DEFAULT NULL,
  `gambar_pengenalan_judul` varchar(255) DEFAULT NULL,
  `gambar_pengenalan_keterangan` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chapters_user_id_nomor_bab_unique` (`user_id`,`nomor_bab`),
  CONSTRAINT `chapters_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chapters`
--

LOCK TABLES `chapters` WRITE;
/*!40000 ALTER TABLE `chapters` DISABLE KEYS */;
INSERT INTO `chapters` VALUES (3,3,6,'Ekologi dan Pelestarian Lingkungan','Lingkungan merupakan tempat berlangsungnya kehidupan berbagai makhluk hidup. Setiap organisme memiliki hubungan dengan lingkungan sekitarnya, baik dengan sesama makhluk hidup maupun dengan komponen tak hidup seperti air, tanah, udara, cahaya matahari, dan suhu.\r\n\r\nMelalui Bab VI, siswa akan mempelajari bagaimana lingkungan memengaruhi kehidupan organisme, hubungan antarkomponen dalam ekosistem, tingkatan organisasi kehidupan, serta perpindahan energi melalui rantai makanan.\r\n\r\nPembelajaran dikaitkan dengan lingkungan lahan basah Kalimantan Selatan, seperti sungai dan rawa, yang menjadi habitat berbagai organisme. Dengan memahami hubungan tersebut, siswa diharapkan semakin peduli terhadap keseimbangan dan kelestarian lingkungan.\r\n\r\nIlustrasi menunjukkan ekosistem lahan basah yang tersusun atas komponen biotik dan abiotik. Komponen biotik meliputi ikan haruan, burung air, dan berbagai tumbuhan air. Sementara itu, air, tanah, udara, dan cahaya matahari merupakan komponen abiotik yang mendukung kehidupan organisme.',1,'2026-10-23 15:38:38','2026-10-24 01:52:42',NULL,'[\"Mengidentifikasi komponen biotik dan abiotik dalam suatu lingkungan.\",\"Menjelaskan pengaruh faktor lingkungan terhadap pertumbuhan dan kehidupan organisme.\",\"Mengidentifikasi bentuk hubungan antarorganisme dan lingkungannya.\",\"Menjelaskan pengertian ekosistem dan ekologi.\",\"Membedakan individu, populasi, komunitas, ekosistem, bioma, dan biosfer.\",\"Menjelaskan konsep habitat dan mikrohabitat.\",\"Menjelaskan perpindahan energi melalui rantai makanan.\",\"Menunjukkan kepedulian terhadap kelestarian ekosistem.\"]','Mengapa ikan, tumbuhan air, dan organisme lainnya dapat hidup bersama di lingkungan rawa, serta apa yang terjadi jika salah satu komponen lingkungan tersebut mengalami perubahan?','chapters/introduction/0a3IpCyptuodGcGMxigPLUDGBQeESu9eooSibyif.jpg','Ekosistem Lahan Basah Kalimantan Selatan',NULL);
/*!40000 ALTER TABLE `chapters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discussion_replies`
--

DROP TABLE IF EXISTS `discussion_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `discussion_replies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `discussion_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `discussion_replies_discussion_id_foreign` (`discussion_id`),
  KEY `discussion_replies_user_id_foreign` (`user_id`),
  KEY `discussion_replies_created_at_index` (`created_at`),
  CONSTRAINT `discussion_replies_discussion_id_foreign` FOREIGN KEY (`discussion_id`) REFERENCES `discussions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `discussion_replies_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discussion_replies`
--

LOCK TABLES `discussion_replies` WRITE;
/*!40000 ALTER TABLE `discussion_replies` DISABLE KEYS */;
/*!40000 ALTER TABLE `discussion_replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discussions`
--

DROP TABLE IF EXISTS `discussions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `discussions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `chapter` varchar(30) NOT NULL,
  `subchapter` varchar(30) NOT NULL,
  `title` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `discussions_user_id_foreign` (`user_id`),
  KEY `discussions_chapter_subchapter_index` (`chapter`,`subchapter`),
  KEY `discussions_created_at_index` (`created_at`),
  CONSTRAINT `discussions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discussions`
--

LOCK TABLES `discussions` WRITE;
/*!40000 ALTER TABLE `discussions` DISABLE KEYS */;
/*!40000 ALTER TABLE `discussions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluation_attempts`
--

DROP TABLE IF EXISTS `evaluation_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `question_snapshot` longtext NOT NULL,
  `answers` longtext NOT NULL,
  `ends_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `submitted_at` timestamp NULL DEFAULT NULL,
  `score` tinyint(3) unsigned DEFAULT NULL,
  `correct_count` tinyint(3) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `evaluation_attempts_user_id_id_index` (`user_id`,`id`),
  CONSTRAINT `evaluation_attempts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluation_attempts`
--

LOCK TABLES `evaluation_attempts` WRITE;
/*!40000 ALTER TABLE `evaluation_attempts` DISABLE KEYS */;
INSERT INTO `evaluation_attempts` VALUES (7,4,'[{\"id\":1,\"user_id\":3,\"nomor_soal\":1,\"tipe_soal\":\"pilihan_ganda\",\"pertanyaan\":\"Di ekosistem rawa, ikan haruan hidup di air bersama tumbuhan air, lumpur, dan berbagai organisme lainnya. Manakah yang termasuk komponen abiotik?\",\"konten_soal\":{\"pilihan_a\":\"Ikan haruan\",\"pilihan_b\":\"Tumbuhan air\",\"pilihan_c\":\"Air dan lumpur\",\"pilihan_d\":\"Bakteri pengurai\"},\"kunci_jawaban\":{\"jawaban\":\"C\"},\"pembahasan\":\"Air dan lumpur merupakan komponen abiotik karena termasuk benda tak hidup yang memengaruhi kehidupan organisme. Ikan, tumbuhan, dan bakteri termasuk komponen biotik.\",\"is_published\":1,\"created_at\":\"2026-10-27 01:30:15\",\"updated_at\":\"2026-10-27 01:30:15\"},{\"id\":2,\"user_id\":3,\"nomor_soal\":2,\"tipe_soal\":\"pilihan_ganda\",\"pertanyaan\":\"Tanaman air yang kekurangan cahaya matahari dapat mengalami gangguan pertumbuhan. Hal ini menunjukkan bahwa ...\",\"konten_soal\":{\"pilihan_a\":\"Cahaya matahari tidak berpengaruh terhadap tumbuhan\",\"pilihan_b\":\"Komponen abiotik memengaruhi kehidupan organisme\",\"pilihan_c\":\"Tumbuhan hanya membutuhkan komponen biotik\",\"pilihan_d\":\"Semua organisme dapat hidup tanpa cahaya\"},\"kunci_jawaban\":{\"jawaban\":\"B\"},\"pembahasan\":\"Cahaya matahari merupakan komponen abiotik yang dibutuhkan tumbuhan untuk melakukan fotosintesis. Kekurangan cahaya dapat mengganggu pertumbuhannya.\",\"is_published\":1,\"created_at\":\"2026-10-27 01:30:55\",\"updated_at\":\"2026-10-27 01:30:55\"},{\"id\":3,\"user_id\":3,\"nomor_soal\":3,\"tipe_soal\":\"pilihan_ganda\",\"pertanyaan\":\"Sekumpulan ikan haruan yang hidup di kawasan rawa yang sama pada waktu tertentu disebut ...\",\"konten_soal\":{\"pilihan_a\":\"Individu\",\"pilihan_b\":\"Populasi\",\"pilihan_c\":\"Komunitas\",\"pilihan_d\":\"Biosfer\"},\"kunci_jawaban\":{\"jawaban\":\"B\"},\"pembahasan\":\"Populasi merupakan kumpulan individu dari spesies yang sama yang hidup pada tempat dan waktu tertentu.\",\"is_published\":1,\"created_at\":\"2026-10-27 01:31:34\",\"updated_at\":\"2026-10-27 01:31:34\"},{\"id\":4,\"user_id\":3,\"nomor_soal\":4,\"tipe_soal\":\"pilihan_ganda\",\"pertanyaan\":\"Warga membuang limbah rumah tangga secara langsung ke sungai. Jika kegiatan ini berlangsung terus-menerus, dampak yang paling mungkin terjadi adalah ...\",\"konten_soal\":{\"pilihan_a\":\"Kualitas air meningkat\",\"pilihan_b\":\"Jumlah oksigen terlarut selalu bertambah\",\"pilihan_c\":\"Keseimbangan ekosistem sungai terganggu\",\"pilihan_d\":\"Semua organisme air berkembang lebih cepat\"},\"kunci_jawaban\":{\"jawaban\":\"C\"},\"pembahasan\":\"Pembuangan limbah langsung ke sungai dapat menyebabkan pencemaran air, mengganggu kehidupan organisme, dan merusak keseimbangan ekosistem.\",\"is_published\":1,\"created_at\":\"2026-10-27 01:32:16\",\"updated_at\":\"2026-10-27 01:32:16\"},{\"id\":5,\"user_id\":3,\"nomor_soal\":5,\"tipe_soal\":\"pilihan_ganda\",\"pertanyaan\":\"Pelestarian bekantan di habitat hutan rawa tempat bekantan hidup secara alami merupakan contoh konservasi ...\",\"konten_soal\":{\"pilihan_a\":\"Eks-situ\",\"pilihan_b\":\"Buatan\",\"pilihan_c\":\"In-situ\",\"pilihan_d\":\"Domestikasi\"},\"kunci_jawaban\":{\"jawaban\":\"C\"},\"pembahasan\":\"Konservasi in-situ dilakukan di habitat asli organisme, sedangkan konservasi eks-situ dilakukan di luar habitat aslinya.\",\"is_published\":1,\"created_at\":\"2026-10-27 01:32:53\",\"updated_at\":\"2026-10-27 01:32:53\"},{\"id\":6,\"user_id\":3,\"nomor_soal\":6,\"tipe_soal\":\"drag_drop\",\"pertanyaan\":\"Kelompokkan komponen ekosistem rawa berikut ke dalam kategori biotik dan abiotik!\",\"konten_soal\":{\"items\":[{\"id\":\"gambar_3\",\"label\":\"Ikan Haruan\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/uCrIbhHPKOWWnaHID1INbhVzebE6NKzZS4tSYlAW.jpg\"},{\"id\":\"gambar_4\",\"label\":\"Tumbuhan Air\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/HXvHkZHOveVBtfY8Y2Q857hsOYqDcgyhEycVpb5d.jpg\"},{\"id\":\"gambar_5\",\"label\":\"Air Rawa\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/Ljfr8QdPJEnqQpTzD9Tu7HKdll4gkPK7rkNCrTGh.jpg\"}],\"targets\":[{\"id\":\"target_1\",\"label\":\"Biotik\"},{\"id\":\"target_2\",\"label\":\"Abiotik\"}]},\"kunci_jawaban\":{\"pairs\":{\"gambar_3\":\"target_1\",\"gambar_4\":\"target_1\",\"gambar_5\":\"target_2\"}},\"pembahasan\":\"Komponen biotik adalah makhluk hidup, sedangkan komponen abiotik adalah unsur tak hidup dalam ekosistem.\",\"is_published\":1,\"created_at\":\"2026-10-27 01:34:40\",\"updated_at\":\"2026-10-27 01:34:40\"},{\"id\":7,\"user_id\":3,\"nomor_soal\":7,\"tipe_soal\":\"drag_drop\",\"pertanyaan\":\"Kelompokkan organisme berikut berdasarkan perannya sebagai produsen atau konsumen dalam ekosistem!\",\"konten_soal\":{\"items\":[{\"id\":\"gambar_8\",\"label\":\"Alga Hijau\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/CTw76lWO1oAwXF5Xz2qcve2ZumNjacemBpbVYGBa.jpg\"},{\"id\":\"gambar_9\",\"label\":\"Kangkung Air\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/nzYnNSIoaH55Xh4CJNuU973PPuNywcfvg6qMi0bM.jpg\"},{\"id\":\"gambar_10\",\"label\":\"Ikan Nila\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/myk4OMhvUgp9LHIum3aJsd9DCkvnnOE2e7nyVPYy.jpg\"}],\"targets\":[{\"id\":\"target_6\",\"label\":\"Produsen\"},{\"id\":\"target_7\",\"label\":\"Konsumen\"}]},\"kunci_jawaban\":{\"pairs\":{\"gambar_8\":\"target_6\",\"gambar_9\":\"target_6\",\"gambar_10\":\"target_7\"}},\"pembahasan\":\"Produsen dapat membuat makanan sendiri melalui fotosintesis. Konsumen memperoleh energi dengan memakan organisme lain.\",\"is_published\":1,\"created_at\":\"2026-10-27 01:36:23\",\"updated_at\":\"2026-10-27 01:36:23\"},{\"id\":8,\"user_id\":3,\"nomor_soal\":8,\"tipe_soal\":\"drag_drop\",\"pertanyaan\":\"Kelompokkan contoh interaksi makhluk hidup berikut berdasarkan jenis simbiosisnya!\",\"konten_soal\":{\"items\":[{\"id\":\"gambar_13\",\"label\":\"Kupu-kupu dan Bunga\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/hifCi0GrOug0lPCsCGS6RKzti2H0efvNf77hfjS3.jpg\"},{\"id\":\"gambar_14\",\"label\":\"Anggrek dan Pohon\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/EMuLrAiCJP94f5ygWk6UNidhmM20DuFjY7acFGuw.jpg\"},{\"id\":\"gambar_16\",\"label\":\"Benalu dan Pohon\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/hmRZyoeUrP803JnFITbPSXNu9GQ8s1lZTbxePXdz.jpg\"}],\"targets\":[{\"id\":\"target_11\",\"label\":\"Mutualisme\"},{\"id\":\"target_12\",\"label\":\"Paratisme\"},{\"id\":\"target_15\",\"label\":\"Komensalisme\"}]},\"kunci_jawaban\":{\"pairs\":{\"gambar_13\":\"target_11\",\"gambar_14\":\"target_15\",\"gambar_16\":\"target_12\"}},\"pembahasan\":\"Mutualisme menguntungkan kedua organisme, komensalisme menguntungkan satu organisme tanpa merugikan organisme lain, sedangkan parasitisme menguntungkan satu organisme dan merugikan organisme lain.\",\"is_published\":1,\"created_at\":\"2026-10-27 01:38:21\",\"updated_at\":\"2026-10-27 01:38:21\"},{\"id\":9,\"user_id\":3,\"nomor_soal\":9,\"tipe_soal\":\"drag_drop\",\"pertanyaan\":\"Kelompokkan kegiatan manusia berikut berdasarkan dampaknya terhadap kelestarian ekosistem!\",\"konten_soal\":{\"items\":[{\"id\":\"gambar_19\",\"label\":\"Menanam pohon\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/vSZuWMXpNc6xwjSlBCY3nPdvRGRxePMoKPrAML7Z.jpg\"},{\"id\":\"gambar_20\",\"label\":\"Membuang sampah ke sungai\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/7dKqA60HzFRndqbAPbDpZ8MVTLGjOxJrpXp4tMoY.jpg\"},{\"id\":\"gambar_21\",\"label\":\"Membersihkan sungai\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/JeBpfBcf5V0fqD88fizsfiDtAI4wq9Ya9G44f4qw.jpg\"}],\"targets\":[{\"id\":\"target_17\",\"label\":\"Dampak Positif\"},{\"id\":\"target_18\",\"label\":\"Dampak Negatif\"}]},\"kunci_jawaban\":{\"pairs\":{\"gambar_19\":\"target_17\",\"gambar_20\":\"target_18\",\"gambar_21\":\"target_17\"}},\"pembahasan\":\"Menanam pohon dan membersihkan sungai membantu menjaga lingkungan. Sebaliknya, membuang sampah ke sungai dapat mencemari air dan mengganggu kehidupan organisme.\",\"is_published\":1,\"created_at\":\"2026-10-27 01:40:16\",\"updated_at\":\"2026-10-27 01:40:16\"},{\"id\":10,\"user_id\":3,\"nomor_soal\":10,\"tipe_soal\":\"drag_drop\",\"pertanyaan\":\"Kelompokkan contoh pelestarian keanekaragaman hayati berikut berdasarkan metode konservasinya!\",\"konten_soal\":{\"items\":[{\"id\":\"gambar_24\",\"label\":\"Taman nasional\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/hCkrhGlLlM7BiGnYT3z1L8KLy5c6KbygGvNa8wHk.jpg\"},{\"id\":\"gambar_25\",\"label\":\"Kebun botani\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/HDZepEiUlyR3EHNMv212d06h5QHbG4jLl7s46E2U.jpg\"},{\"id\":\"gambar_26\",\"label\":\"Pusat penangkaran satwa\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/MARuuxHYCN2EE0PMATsUq410GBPdddhBvIG6RIQa.jpg\"}],\"targets\":[{\"id\":\"target_22\",\"label\":\"In-Situ\"},{\"id\":\"target_23\",\"label\":\"Eks-Situ\"}]},\"kunci_jawaban\":{\"pairs\":{\"gambar_24\":\"target_22\",\"gambar_25\":\"target_23\",\"gambar_26\":\"target_23\"}},\"pembahasan\":\"Konservasi in-situ dilakukan di habitat asli spesies, sedangkan konservasi eks-situ dilakukan di luar habitat asli melalui fasilitas pelestarian. Pada soal ini, taman nasional dimaksudkan sebagai perlindungan spesies di habitat alaminya.\",\"is_published\":1,\"created_at\":\"2026-10-27 01:42:17\",\"updated_at\":\"2026-10-27 01:42:17\"},{\"id\":11,\"user_id\":3,\"nomor_soal\":11,\"tipe_soal\":\"menghubungkan\",\"pertanyaan\":\"Hubungkan gambar komponen abiotik berikut dengan pengaruhnya terhadap kehidupan organisme!\",\"konten_soal\":{\"items\":[{\"id\":\"gambar_29\",\"label\":\"Air\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/n2RxUJzg0Lj1YUxjQBUKFYqEgrdGwHGMbcAF1Ffw.jpg\"},{\"id\":\"gambar_30\",\"label\":\"Cahaya matahari\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/Eu6avY01enfxvWs9SP0TAOiWYY4ti8ewgeTbvYn1.jpg\"},{\"id\":\"gambar_32\",\"label\":\"Suhu\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/AC0xoKVeMSsPljQGQhoMG5z5Qo11OI8WZEz6a1Pq.jpg\"}],\"targets\":[{\"id\":\"target_27\",\"label\":\"Sumber energi untuk fotosintesis\"},{\"id\":\"target_28\",\"label\":\"Memengaruhi aktivitas dan pertumbuhan organisme\"},{\"id\":\"target_31\",\"label\":\"Dibutuhkan organisme untuk proses kehidupan\"}]},\"kunci_jawaban\":{\"pairs\":{\"gambar_29\":\"target_31\",\"gambar_30\":\"target_27\",\"gambar_32\":\"target_28\"}},\"pembahasan\":\"Cahaya, air, dan suhu adalah komponen abiotik yang memiliki pengaruh berbeda terhadap kehidupan organisme.\",\"is_published\":1,\"created_at\":\"2026-10-27 01:44:44\",\"updated_at\":\"2026-10-27 01:44:44\"},{\"id\":12,\"user_id\":3,\"nomor_soal\":12,\"tipe_soal\":\"menghubungkan\",\"pertanyaan\":\"Hubungkan gambar organisme berikut dengan perannya dalam rantai makanan!\",\"konten_soal\":{\"items\":[{\"id\":\"gambar_35\",\"label\":\"Rumput\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/zRT8dGYSuxEWZLygHEjKGDyRrpWHtUGnzOPAyf9M.jpg\"},{\"id\":\"gambar_36\",\"label\":\"Katak\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/T8wm6EwLpKr5VAbqZIb6Ae0cqZOrjxofVp7V2xOb.jpg\"},{\"id\":\"gambar_38\",\"label\":\"Belalang\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/khwvlyn6ssCtN5c6slr8hMAYBoemu9lMmja8eKHZ.jpg\"}],\"targets\":[{\"id\":\"target_33\",\"label\":\"Konsumen tingkat II\"},{\"id\":\"target_34\",\"label\":\"Produsen\"},{\"id\":\"target_37\",\"label\":\"Konsumen tingkat I\"}]},\"kunci_jawaban\":{\"pairs\":{\"gambar_35\":\"target_34\",\"gambar_36\":\"target_33\",\"gambar_38\":\"target_37\"}},\"pembahasan\":\"Pada rantai makanan rumput \\u2192 belalang \\u2192 katak, rumput merupakan produsen, belalang memakan produsen, dan katak memakan konsumen tingkat I.\",\"is_published\":1,\"created_at\":\"2026-10-27 01:46:32\",\"updated_at\":\"2026-10-27 01:46:32\"},{\"id\":13,\"user_id\":3,\"nomor_soal\":13,\"tipe_soal\":\"menghubungkan\",\"pertanyaan\":\"Hubungkan gambar aktivitas manusia berikut dengan dampak lingkungan yang dapat ditimbulkannya!\",\"konten_soal\":{\"items\":[{\"id\":\"gambar_41\",\"label\":\"Penebangan hutan\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/xOeYBzGzabUIbXvEHvVMCok7OrLtpkS11PZYbalK.jpg\"},{\"id\":\"gambar_42\",\"label\":\"Pembuangan limbah cair\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/HgzLCGQ3lVZgSttdSKDX5JSRaYKUbPTOYUoRjqfb.jpg\"},{\"id\":\"gambar_44\",\"label\":\"Pembakaran bahan bakar fosil\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/8p2P9DMSvV9qBieX3Ajv3x8AJoRUToAXBeIOdzUO.jpg\"}],\"targets\":[{\"id\":\"target_39\",\"label\":\"Peningkatan emisi gas rumah kaca\"},{\"id\":\"target_40\",\"label\":\"Pencemaran air\"},{\"id\":\"target_43\",\"label\":\"Hilangnya habitat organisme\"}]},\"kunci_jawaban\":{\"pairs\":{\"gambar_41\":\"target_43\",\"gambar_42\":\"target_40\",\"gambar_44\":\"target_39\"}},\"pembahasan\":\"Aktivitas manusia dapat menyebabkan kerusakan habitat, pencemaran air, dan peningkatan gas rumah kaca yang mendorong perubahan iklim.\",\"is_published\":1,\"created_at\":\"2026-10-27 01:48:43\",\"updated_at\":\"2026-10-27 01:48:43\"},{\"id\":14,\"user_id\":3,\"nomor_soal\":14,\"tipe_soal\":\"menghubungkan\",\"pertanyaan\":\"Hubungkan gambar kegiatan konservasi berikut dengan manfaatnya bagi kelestarian lingkungan!\",\"konten_soal\":{\"items\":[{\"id\":\"gambar_47\",\"label\":\"Melindungi bekantan\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/VPnHJq5WNpCjKHYPvAOuA8UiXWFId73RKMGY0z87.jpg\"},{\"id\":\"gambar_48\",\"label\":\"Mendaur ulang sampah\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/jwOP9prLS3bqyjCyKEaaVBWIah1sSRD1ZXEcBor1.jpg\"},{\"id\":\"gambar_50\",\"label\":\"Menanam mangrove\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/Hslo7P3rnEkhUFaqaSXysMzoivbiGuZiVu3R2sIK.jpg\"}],\"targets\":[{\"id\":\"target_45\",\"label\":\"Mengurangi jumlah sampah yang dibuang\"},{\"id\":\"target_46\",\"label\":\"Membantu memulihkan ekosistem pesisir\"},{\"id\":\"target_49\",\"label\":\"Membantu mencegah kepunahan satwa\"}]},\"kunci_jawaban\":{\"pairs\":{\"gambar_47\":\"target_49\",\"gambar_48\":\"target_45\",\"gambar_50\":\"target_46\"}},\"pembahasan\":\"Konservasi dapat dilakukan melalui pemulihan habitat, perlindungan satwa, serta pengelolaan sampah untuk menjaga keseimbangan ekosistem dan kelestarian keanekaragaman hayati.\",\"is_published\":1,\"created_at\":\"2026-10-27 01:50:54\",\"updated_at\":\"2026-10-27 01:50:54\"},{\"id\":15,\"user_id\":3,\"nomor_soal\":15,\"tipe_soal\":\"menghubungkan\",\"pertanyaan\":\"Hubungkan gambar proses dalam siklus air berikut dengan istilah yang tepat!\",\"konten_soal\":{\"items\":[{\"id\":\"gambar_53\",\"label\":\"Uap membentuk awan\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/P8nxzbt5qIRMaigGfcbF5RlGGWvWpChk602uTDEI.png\"},{\"id\":\"gambar_54\",\"label\":\"Air menguap\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/x1T4hkXWeu4ZqYFYo8FCoyQQ0W263KeEo0LeeNfP.png\"},{\"id\":\"gambar_56\",\"label\":\"Hujan turun\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/TJ0rpkXoFIAN9AMHQ2b0UXHgqFYaLXBO0jGwozZl.png\"}],\"targets\":[{\"id\":\"target_51\",\"label\":\"Evaporasi\"},{\"id\":\"target_52\",\"label\":\"Presipitasi\"},{\"id\":\"target_55\",\"label\":\"Kondensasi\"}]},\"kunci_jawaban\":{\"pairs\":{\"gambar_53\":\"target_55\",\"gambar_54\":\"target_51\",\"gambar_56\":\"target_52\"}},\"pembahasan\":\"Siklus air merupakan proses perputaran air yang berlangsung secara terus-menerus di alam. Evaporasi adalah proses penguapan air akibat panas matahari. Kondensasi adalah perubahan uap air menjadi titik-titik air yang membentuk awan. Presipitasi adalah proses jatuhnya air dari atmosfer ke permukaan bumi, salah satunya dalam bentuk hujan.\",\"is_published\":1,\"created_at\":\"2026-10-27 02:02:47\",\"updated_at\":\"2026-10-27 02:02:47\"}]','{\"1\":\"C\",\"2\":\"B\",\"3\":\"B\",\"4\":\"C\",\"5\":\"C\",\"6\":{\"gambar_3\":\"target_1\",\"gambar_4\":\"target_1\",\"gambar_5\":\"target_2\"},\"7\":{\"gambar_8\":\"target_6\",\"gambar_9\":\"target_6\",\"gambar_10\":\"target_7\"},\"8\":{\"gambar_13\":\"target_11\",\"gambar_14\":\"target_15\",\"gambar_16\":\"target_12\"},\"9\":{\"gambar_19\":\"target_17\",\"gambar_20\":\"target_18\",\"gambar_21\":\"target_17\"},\"10\":{\"gambar_24\":\"target_22\",\"gambar_25\":\"target_23\",\"gambar_26\":\"target_23\"},\"11\":{\"gambar_30\":\"target_27\",\"gambar_29\":\"target_31\",\"gambar_32\":\"target_28\"},\"12\":{\"gambar_35\":\"target_34\",\"gambar_36\":\"target_33\",\"gambar_38\":\"target_37\"},\"13\":{\"gambar_41\":\"target_43\",\"gambar_42\":\"target_40\",\"gambar_44\":\"target_39\"},\"14\":{\"gambar_47\":\"target_49\",\"gambar_48\":\"target_45\",\"gambar_50\":\"target_46\"},\"15\":{\"gambar_54\":\"target_51\",\"gambar_53\":\"target_55\",\"gambar_56\":\"target_52\"}}','2026-10-27 06:10:59','2026-10-26 22:10:59',100,15,'2026-10-26 22:08:56','2026-10-26 22:10:59');
/*!40000 ALTER TABLE `evaluation_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluation_questions`
--

DROP TABLE IF EXISTS `evaluation_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `nomor_soal` tinyint(3) unsigned NOT NULL,
  `tipe_soal` enum('pilihan_ganda','drag_drop','menghubungkan','pilih_gambar') NOT NULL,
  `pertanyaan` text NOT NULL,
  `konten_soal` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`konten_soal`)),
  `kunci_jawaban` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`kunci_jawaban`)),
  `pembahasan` text DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `evaluation_questions_guru_number_unique` (`user_id`,`nomor_soal`),
  CONSTRAINT `evaluation_questions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluation_questions`
--

LOCK TABLES `evaluation_questions` WRITE;
/*!40000 ALTER TABLE `evaluation_questions` DISABLE KEYS */;
INSERT INTO `evaluation_questions` VALUES (1,3,1,'pilihan_ganda','Di ekosistem rawa, ikan haruan hidup di air bersama tumbuhan air, lumpur, dan berbagai organisme lainnya. Manakah yang termasuk komponen abiotik?','{\"pilihan_a\":\"Ikan haruan\",\"pilihan_b\":\"Tumbuhan air\",\"pilihan_c\":\"Air dan lumpur\",\"pilihan_d\":\"Bakteri pengurai\"}','{\"jawaban\":\"C\"}','Air dan lumpur merupakan komponen abiotik karena termasuk benda tak hidup yang memengaruhi kehidupan organisme. Ikan, tumbuhan, dan bakteri termasuk komponen biotik.',1,'2026-10-26 17:30:15','2026-10-26 17:30:15'),(2,3,2,'pilihan_ganda','Tanaman air yang kekurangan cahaya matahari dapat mengalami gangguan pertumbuhan. Hal ini menunjukkan bahwa ...','{\"pilihan_a\":\"Cahaya matahari tidak berpengaruh terhadap tumbuhan\",\"pilihan_b\":\"Komponen abiotik memengaruhi kehidupan organisme\",\"pilihan_c\":\"Tumbuhan hanya membutuhkan komponen biotik\",\"pilihan_d\":\"Semua organisme dapat hidup tanpa cahaya\"}','{\"jawaban\":\"B\"}','Cahaya matahari merupakan komponen abiotik yang dibutuhkan tumbuhan untuk melakukan fotosintesis. Kekurangan cahaya dapat mengganggu pertumbuhannya.',1,'2026-10-26 17:30:55','2026-10-26 17:30:55'),(3,3,3,'pilihan_ganda','Sekumpulan ikan haruan yang hidup di kawasan rawa yang sama pada waktu tertentu disebut ...','{\"pilihan_a\":\"Individu\",\"pilihan_b\":\"Populasi\",\"pilihan_c\":\"Komunitas\",\"pilihan_d\":\"Biosfer\"}','{\"jawaban\":\"B\"}','Populasi merupakan kumpulan individu dari spesies yang sama yang hidup pada tempat dan waktu tertentu.',1,'2026-10-26 17:31:34','2026-10-26 17:31:34'),(4,3,4,'pilihan_ganda','Warga membuang limbah rumah tangga secara langsung ke sungai. Jika kegiatan ini berlangsung terus-menerus, dampak yang paling mungkin terjadi adalah ...','{\"pilihan_a\":\"Kualitas air meningkat\",\"pilihan_b\":\"Jumlah oksigen terlarut selalu bertambah\",\"pilihan_c\":\"Keseimbangan ekosistem sungai terganggu\",\"pilihan_d\":\"Semua organisme air berkembang lebih cepat\"}','{\"jawaban\":\"C\"}','Pembuangan limbah langsung ke sungai dapat menyebabkan pencemaran air, mengganggu kehidupan organisme, dan merusak keseimbangan ekosistem.',1,'2026-10-26 17:32:16','2026-10-26 17:32:16'),(5,3,5,'pilihan_ganda','Pelestarian bekantan di habitat hutan rawa tempat bekantan hidup secara alami merupakan contoh konservasi ...','{\"pilihan_a\":\"Eks-situ\",\"pilihan_b\":\"Buatan\",\"pilihan_c\":\"In-situ\",\"pilihan_d\":\"Domestikasi\"}','{\"jawaban\":\"C\"}','Konservasi in-situ dilakukan di habitat asli organisme, sedangkan konservasi eks-situ dilakukan di luar habitat aslinya.',1,'2026-10-26 17:32:53','2026-10-26 17:32:53'),(6,3,6,'drag_drop','Kelompokkan komponen ekosistem rawa berikut ke dalam kategori biotik dan abiotik!','{\"items\":[{\"id\":\"gambar_3\",\"label\":\"Ikan Haruan\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/uCrIbhHPKOWWnaHID1INbhVzebE6NKzZS4tSYlAW.jpg\"},{\"id\":\"gambar_4\",\"label\":\"Tumbuhan Air\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/HXvHkZHOveVBtfY8Y2Q857hsOYqDcgyhEycVpb5d.jpg\"},{\"id\":\"gambar_5\",\"label\":\"Air Rawa\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/Ljfr8QdPJEnqQpTzD9Tu7HKdll4gkPK7rkNCrTGh.jpg\"}],\"targets\":[{\"id\":\"target_1\",\"label\":\"Biotik\"},{\"id\":\"target_2\",\"label\":\"Abiotik\"}]}','{\"pairs\":{\"gambar_3\":\"target_1\",\"gambar_4\":\"target_1\",\"gambar_5\":\"target_2\"}}','Komponen biotik adalah makhluk hidup, sedangkan komponen abiotik adalah unsur tak hidup dalam ekosistem.',1,'2026-10-26 17:34:40','2026-10-26 17:34:40'),(7,3,7,'drag_drop','Kelompokkan organisme berikut berdasarkan perannya sebagai produsen atau konsumen dalam ekosistem!','{\"items\":[{\"id\":\"gambar_8\",\"label\":\"Alga Hijau\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/CTw76lWO1oAwXF5Xz2qcve2ZumNjacemBpbVYGBa.jpg\"},{\"id\":\"gambar_9\",\"label\":\"Kangkung Air\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/nzYnNSIoaH55Xh4CJNuU973PPuNywcfvg6qMi0bM.jpg\"},{\"id\":\"gambar_10\",\"label\":\"Ikan Nila\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/myk4OMhvUgp9LHIum3aJsd9DCkvnnOE2e7nyVPYy.jpg\"}],\"targets\":[{\"id\":\"target_6\",\"label\":\"Produsen\"},{\"id\":\"target_7\",\"label\":\"Konsumen\"}]}','{\"pairs\":{\"gambar_8\":\"target_6\",\"gambar_9\":\"target_6\",\"gambar_10\":\"target_7\"}}','Produsen dapat membuat makanan sendiri melalui fotosintesis. Konsumen memperoleh energi dengan memakan organisme lain.',1,'2026-10-26 17:36:23','2026-10-26 17:36:23'),(8,3,8,'drag_drop','Kelompokkan contoh interaksi makhluk hidup berikut berdasarkan jenis simbiosisnya!','{\"items\":[{\"id\":\"gambar_13\",\"label\":\"Kupu-kupu dan Bunga\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/hifCi0GrOug0lPCsCGS6RKzti2H0efvNf77hfjS3.jpg\"},{\"id\":\"gambar_14\",\"label\":\"Anggrek dan Pohon\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/EMuLrAiCJP94f5ygWk6UNidhmM20DuFjY7acFGuw.jpg\"},{\"id\":\"gambar_16\",\"label\":\"Benalu dan Pohon\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/hmRZyoeUrP803JnFITbPSXNu9GQ8s1lZTbxePXdz.jpg\"}],\"targets\":[{\"id\":\"target_11\",\"label\":\"Mutualisme\"},{\"id\":\"target_12\",\"label\":\"Paratisme\"},{\"id\":\"target_15\",\"label\":\"Komensalisme\"}]}','{\"pairs\":{\"gambar_13\":\"target_11\",\"gambar_14\":\"target_15\",\"gambar_16\":\"target_12\"}}','Mutualisme menguntungkan kedua organisme, komensalisme menguntungkan satu organisme tanpa merugikan organisme lain, sedangkan parasitisme menguntungkan satu organisme dan merugikan organisme lain.',1,'2026-10-26 17:38:21','2026-10-26 17:38:21'),(9,3,9,'drag_drop','Kelompokkan kegiatan manusia berikut berdasarkan dampaknya terhadap kelestarian ekosistem!','{\"items\":[{\"id\":\"gambar_19\",\"label\":\"Menanam pohon\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/vSZuWMXpNc6xwjSlBCY3nPdvRGRxePMoKPrAML7Z.jpg\"},{\"id\":\"gambar_20\",\"label\":\"Membuang sampah ke sungai\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/7dKqA60HzFRndqbAPbDpZ8MVTLGjOxJrpXp4tMoY.jpg\"},{\"id\":\"gambar_21\",\"label\":\"Membersihkan sungai\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/JeBpfBcf5V0fqD88fizsfiDtAI4wq9Ya9G44f4qw.jpg\"}],\"targets\":[{\"id\":\"target_17\",\"label\":\"Dampak Positif\"},{\"id\":\"target_18\",\"label\":\"Dampak Negatif\"}]}','{\"pairs\":{\"gambar_19\":\"target_17\",\"gambar_20\":\"target_18\",\"gambar_21\":\"target_17\"}}','Menanam pohon dan membersihkan sungai membantu menjaga lingkungan. Sebaliknya, membuang sampah ke sungai dapat mencemari air dan mengganggu kehidupan organisme.',1,'2026-10-26 17:40:16','2026-10-26 17:40:16'),(10,3,10,'drag_drop','Kelompokkan contoh pelestarian keanekaragaman hayati berikut berdasarkan metode konservasinya!','{\"items\":[{\"id\":\"gambar_24\",\"label\":\"Taman nasional\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/hCkrhGlLlM7BiGnYT3z1L8KLy5c6KbygGvNa8wHk.jpg\"},{\"id\":\"gambar_25\",\"label\":\"Kebun botani\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/HDZepEiUlyR3EHNMv212d06h5QHbG4jLl7s46E2U.jpg\"},{\"id\":\"gambar_26\",\"label\":\"Pusat penangkaran satwa\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/MARuuxHYCN2EE0PMATsUq410GBPdddhBvIG6RIQa.jpg\"}],\"targets\":[{\"id\":\"target_22\",\"label\":\"In-Situ\"},{\"id\":\"target_23\",\"label\":\"Eks-Situ\"}]}','{\"pairs\":{\"gambar_24\":\"target_22\",\"gambar_25\":\"target_23\",\"gambar_26\":\"target_23\"}}','Konservasi in-situ dilakukan di habitat asli spesies, sedangkan konservasi eks-situ dilakukan di luar habitat asli melalui fasilitas pelestarian. Pada soal ini, taman nasional dimaksudkan sebagai perlindungan spesies di habitat alaminya.',1,'2026-10-26 17:42:17','2026-10-26 17:42:17'),(11,3,11,'menghubungkan','Hubungkan gambar komponen abiotik berikut dengan pengaruhnya terhadap kehidupan organisme!','{\"items\":[{\"id\":\"gambar_29\",\"label\":\"Air\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/n2RxUJzg0Lj1YUxjQBUKFYqEgrdGwHGMbcAF1Ffw.jpg\"},{\"id\":\"gambar_30\",\"label\":\"Cahaya matahari\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/Eu6avY01enfxvWs9SP0TAOiWYY4ti8ewgeTbvYn1.jpg\"},{\"id\":\"gambar_32\",\"label\":\"Suhu\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/AC0xoKVeMSsPljQGQhoMG5z5Qo11OI8WZEz6a1Pq.jpg\"}],\"targets\":[{\"id\":\"target_27\",\"label\":\"Sumber energi untuk fotosintesis\"},{\"id\":\"target_28\",\"label\":\"Memengaruhi aktivitas dan pertumbuhan organisme\"},{\"id\":\"target_31\",\"label\":\"Dibutuhkan organisme untuk proses kehidupan\"}]}','{\"pairs\":{\"gambar_29\":\"target_31\",\"gambar_30\":\"target_27\",\"gambar_32\":\"target_28\"}}','Cahaya, air, dan suhu adalah komponen abiotik yang memiliki pengaruh berbeda terhadap kehidupan organisme.',1,'2026-10-26 17:44:44','2026-10-26 17:44:44'),(12,3,12,'menghubungkan','Hubungkan gambar organisme berikut dengan perannya dalam rantai makanan!','{\"items\":[{\"id\":\"gambar_35\",\"label\":\"Rumput\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/zRT8dGYSuxEWZLygHEjKGDyRrpWHtUGnzOPAyf9M.jpg\"},{\"id\":\"gambar_36\",\"label\":\"Katak\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/T8wm6EwLpKr5VAbqZIb6Ae0cqZOrjxofVp7V2xOb.jpg\"},{\"id\":\"gambar_38\",\"label\":\"Belalang\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/khwvlyn6ssCtN5c6slr8hMAYBoemu9lMmja8eKHZ.jpg\"}],\"targets\":[{\"id\":\"target_33\",\"label\":\"Konsumen tingkat II\"},{\"id\":\"target_34\",\"label\":\"Produsen\"},{\"id\":\"target_37\",\"label\":\"Konsumen tingkat I\"}]}','{\"pairs\":{\"gambar_35\":\"target_34\",\"gambar_36\":\"target_33\",\"gambar_38\":\"target_37\"}}','Pada rantai makanan rumput → belalang → katak, rumput merupakan produsen, belalang memakan produsen, dan katak memakan konsumen tingkat I.',1,'2026-10-26 17:46:32','2026-10-26 17:46:32'),(13,3,13,'menghubungkan','Hubungkan gambar aktivitas manusia berikut dengan dampak lingkungan yang dapat ditimbulkannya!','{\"items\":[{\"id\":\"gambar_41\",\"label\":\"Penebangan hutan\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/xOeYBzGzabUIbXvEHvVMCok7OrLtpkS11PZYbalK.jpg\"},{\"id\":\"gambar_42\",\"label\":\"Pembuangan limbah cair\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/HgzLCGQ3lVZgSttdSKDX5JSRaYKUbPTOYUoRjqfb.jpg\"},{\"id\":\"gambar_44\",\"label\":\"Pembakaran bahan bakar fosil\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/8p2P9DMSvV9qBieX3Ajv3x8AJoRUToAXBeIOdzUO.jpg\"}],\"targets\":[{\"id\":\"target_39\",\"label\":\"Peningkatan emisi gas rumah kaca\"},{\"id\":\"target_40\",\"label\":\"Pencemaran air\"},{\"id\":\"target_43\",\"label\":\"Hilangnya habitat organisme\"}]}','{\"pairs\":{\"gambar_41\":\"target_43\",\"gambar_42\":\"target_40\",\"gambar_44\":\"target_39\"}}','Aktivitas manusia dapat menyebabkan kerusakan habitat, pencemaran air, dan peningkatan gas rumah kaca yang mendorong perubahan iklim.',1,'2026-10-26 17:48:43','2026-10-26 17:48:43'),(14,3,14,'menghubungkan','Hubungkan gambar kegiatan konservasi berikut dengan manfaatnya bagi kelestarian lingkungan!','{\"items\":[{\"id\":\"gambar_47\",\"label\":\"Melindungi bekantan\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/VPnHJq5WNpCjKHYPvAOuA8UiXWFId73RKMGY0z87.jpg\"},{\"id\":\"gambar_48\",\"label\":\"Mendaur ulang sampah\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/jwOP9prLS3bqyjCyKEaaVBWIah1sSRD1ZXEcBor1.jpg\"},{\"id\":\"gambar_50\",\"label\":\"Menanam mangrove\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/Hslo7P3rnEkhUFaqaSXysMzoivbiGuZiVu3R2sIK.jpg\"}],\"targets\":[{\"id\":\"target_45\",\"label\":\"Mengurangi jumlah sampah yang dibuang\"},{\"id\":\"target_46\",\"label\":\"Membantu memulihkan ekosistem pesisir\"},{\"id\":\"target_49\",\"label\":\"Membantu mencegah kepunahan satwa\"}]}','{\"pairs\":{\"gambar_47\":\"target_49\",\"gambar_48\":\"target_45\",\"gambar_50\":\"target_46\"}}','Konservasi dapat dilakukan melalui pemulihan habitat, perlindungan satwa, serta pengelolaan sampah untuk menjaga keseimbangan ekosistem dan kelestarian keanekaragaman hayati.',1,'2026-10-26 17:50:54','2026-10-26 17:50:54'),(15,3,15,'menghubungkan','Hubungkan gambar proses dalam siklus air berikut dengan istilah yang tepat!','{\"items\":[{\"id\":\"gambar_53\",\"label\":\"Uap membentuk awan\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/P8nxzbt5qIRMaigGfcbF5RlGGWvWpChk602uTDEI.png\"},{\"id\":\"gambar_54\",\"label\":\"Air menguap\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/x1T4hkXWeu4ZqYFYo8FCoyQQ0W263KeEo0LeeNfP.png\"},{\"id\":\"gambar_56\",\"label\":\"Hujan turun\",\"image\":\"http:\\/\\/127.0.0.1:8000\\/storage\\/evaluation-images\\/TJ0rpkXoFIAN9AMHQ2b0UXHgqFYaLXBO0jGwozZl.png\"}],\"targets\":[{\"id\":\"target_51\",\"label\":\"Evaporasi\"},{\"id\":\"target_52\",\"label\":\"Presipitasi\"},{\"id\":\"target_55\",\"label\":\"Kondensasi\"}]}','{\"pairs\":{\"gambar_53\":\"target_55\",\"gambar_54\":\"target_51\",\"gambar_56\":\"target_52\"}}','Siklus air merupakan proses perputaran air yang berlangsung secara terus-menerus di alam. Evaporasi adalah proses penguapan air akibat panas matahari. Kondensasi adalah perubahan uap air menjadi titik-titik air yang membentuk awan. Presipitasi adalah proses jatuhnya air dari atmosfer ke permukaan bumi, salah satunya dalam bentuk hujan.',1,'2026-10-26 18:02:47','2026-10-26 18:02:47');
/*!40000 ALTER TABLE `evaluation_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_10_16_073115_add_saluang_fields_to_users_table',1),(5,'2026_10_16_073148_create_student_guardians_table',1),(6,'2026_10_17_013922_create_password_recovery_requests_table',1),(7,'2026_10_17_033917_make_email_nullable_in_users_table',1),(8,'2026_10_18_112532_create_forum_tables',1),(9,'2026_10_19_114356_add_profile_photo_to_users_table',1),(10,'2026_10_20_015751_create_chapters_table',1),(11,'2026_10_20_015755_create_subchapters_table',1),(12,'2026_10_20_023052_create_quiz_questions_table',1),(13,'2026_10_20_031757_add_introduction_and_objectives_to_chapters_table',1),(14,'2026_10_20_034421_create_evaluation_questions_table',1),(15,'2026_10_20_065237_create_student_assessment_results_table',1),(16,'2026_10_22_000001_create_subchapter_media_table',2),(17,'2026_10_22_150800_create_subchapter_content_blocks_table',3),(18,'2026_10_24_000001_add_introduction_image_to_chapters_table',4),(19,'2026_10_24_000002_add_introduction_image_captions_to_chapters_table',5),(20,'2026_10_26_000001_create_student_learning_progress_tables',6),(21,'2026_10_26_000002_add_subchapter_to_student_assessment_results',6),(22,'2026_10_26_160000_create_student_teacher_notes_table',7),(23,'2026_10_27_000001_create_evaluation_attempts_table',8);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_recovery_requests`
--

DROP TABLE IF EXISTS `password_recovery_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_recovery_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `password_recovery_requests_user_id_status_index` (`user_id`,`status`),
  CONSTRAINT `password_recovery_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_recovery_requests`
--

LOCK TABLES `password_recovery_requests` WRITE;
/*!40000 ALTER TABLE `password_recovery_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_recovery_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_questions`
--

DROP TABLE IF EXISTS `quiz_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subchapter_id` bigint(20) unsigned NOT NULL,
  `nomor_soal` int(10) unsigned NOT NULL,
  `pertanyaan` longtext NOT NULL,
  `pilihan_a` text NOT NULL,
  `pilihan_b` text NOT NULL,
  `pilihan_c` text NOT NULL,
  `pilihan_d` text NOT NULL,
  `kunci_jawaban` enum('A','B','C','D') NOT NULL,
  `pembahasan` longtext DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quiz_questions_subchapter_id_nomor_soal_unique` (`subchapter_id`,`nomor_soal`),
  CONSTRAINT `quiz_questions_subchapter_id_foreign` FOREIGN KEY (`subchapter_id`) REFERENCES `subchapters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_questions`
--

LOCK TABLES `quiz_questions` WRITE;
/*!40000 ALTER TABLE `quiz_questions` DISABLE KEYS */;
INSERT INTO `quiz_questions` VALUES (2,5,1,'Tujuan utama dilakukannya konservasi keanekaragaman hayati adalah ....','Menjaga kelestarian organisme dan keseimbangan ekosistem','Mengubah seluruh habitat alami menjadi tempat wisata','Memindahkan semua satwa dari habitat aslinya','Memanfaatkan seluruh sumber daya alam tanpa batas','A','Konservasi bertujuan melindungi keanekaragaman hayati dan mengelola sumber daya alam secara bijaksana agar manfaatnya tetap berkelanjutan.',1,'2026-10-25 02:36:05','2026-10-25 02:50:48'),(3,5,2,'Pelestarian hewan dan tumbuhan yang dilakukan di habitat aslinya disebut ....','Konservasi eks-situ','Konservasi buatan','Penangkaran tertutup','Konservasi in-situ','D','Konservasi in-situ dilakukan di habitat alami organisme. Contohnya adalah pelestarian satwa dan tumbuhan melalui perlindungan taman nasional, cagar alam, atau suaka margasatwa.',1,'2026-10-25 02:37:09','2026-10-25 02:51:28'),(4,5,3,'Contoh pelaksanaan konservasi eks-situ adalah ....','Melindungi seluruh organisme dalam kawasan cagar alam','Mengembangbiakkan satwa dalam fasilitas penangkaran','Menjaga populasi ikan di habitat rawa alaminya','Melindungi tumbuhan di kawasan taman nasional','B','Konservasi eks-situ merupakan pelestarian organisme di luar habitat aslinya. Penangkaran satwa adalah salah satu contohnya.',1,'2026-10-25 02:38:26','2026-10-25 02:52:25'),(5,5,4,'Salah satu manfaat konservasi dari aspek ekonomi adalah ....','Menghilangkan seluruh kegiatan masyarakat di sekitar kawasan alam','Mempercepat pemanfaatan sumber daya hingga habis','Mendukung ketersediaan sumber daya alam secara berkelanjutan','Mengubah semua hutan menjadi kawasan permukiman','C','Konservasi dapat membantu mempertahankan ketersediaan sumber daya alam yang mendukung kebutuhan manusia. Pengelolaan yang baik juga dapat memberikan manfaat ekonomi, misalnya melalui ekowisata.',1,'2026-10-25 02:39:24','2026-10-25 02:53:05'),(6,5,5,'Suatu kawasan rawa masih memiliki habitat alami yang baik, tetapi terancam oleh pembukaan lahan. Upaya konservasi yang paling sesuai untuk mempertahankan organisme beserta habitatnya adalah ....','Melindungi kawasan rawa melalui konservasi in-situ','Memindahkan seluruh organisme ke akuarium','Mengubah kawasan rawa menjadi permukiman','Menebang seluruh vegetasi untuk memperluas lahan terbuka','A','Apabila habitat alami masih layak dan dapat dilindungi, konservasi in-situ merupakan pendekatan yang sesuai karena melindungi organisme beserta hubungan ekologis dalam habitat aslinya.',1,'2026-10-25 02:40:18','2026-10-25 02:53:43'),(7,2,1,'Komponen abiotik yang paling langsung dibedakan dalam percobaan menanam biji kacang hijau menggunakan kapas kering dan kapas basah adalah ....','Suhu','Air','Cahaya Matahari','Jenis Organisme','B','Kapas kering dan kapas basah memiliki perbedaan ketersediaan air. Air merupakan faktor abiotik yang diperlukan dalam proses perkecambahan.',1,'2026-10-25 02:41:17','2026-10-25 02:41:17'),(8,2,2,'Dua tanaman sejenis diletakkan pada tempat dengan intensitas cahaya berbeda. Faktor lingkungan yang sedang diteliti adalah ....','Faktor biotik berupa tanaman','Faktor biotik berupa mikroorganisme','Faktor abiotik berupa cahaya','Faktor abiotik berupa jenis tanah','C','Cahaya merupakan komponen abiotik yang berpengaruh terhadap pertumbuhan dan proses fotosintesis tumbuhan.',1,'2026-10-25 02:41:54','2026-10-25 02:41:54'),(9,2,3,'Dalam ekosistem rawa, ikan haruan, tumbuhan air, dan mikroorganisme termasuk komponen ....','Biotik','Abiotik','Iklim','Topografi','A','Komponen biotik merupakan seluruh makhluk hidup dalam suatu lingkungan, termasuk hewan, tumbuhan, dan mikroorganisme.',1,'2026-10-25 02:42:35','2026-10-25 02:42:35'),(10,2,4,'Jika air rawa mengalami perubahan tingkat keasaman (pH) yang besar, dampak yang mungkin terjadi adalah ....','Seluruh organisme dapat hidup lebih baik','Kondisi organisme tidak akan berubah','Semua ikan akan langsung berkembang biak','Organisme yang tidak toleran terhadap perubahan pH dapat terganggu','D','Setiap organisme mempunyai kisaran kondisi lingkungan yang sesuai. Perubahan pH yang besar dapat mengganggu organisme yang tidak mampu menoleransinya.',1,'2026-10-25 02:43:14','2026-10-25 02:43:14'),(11,2,5,'Manakah kegiatan manusia yang dapat memengaruhi komponen biotik dan abiotik secara bersamaan?','Mengamati bentuk daun menggunakan kaca pembesar','Menggambar ikan berdasarkan foto','Membuang limbah ke sungai','Menuliskan nama tumbuhan di buku','C','Limbah dapat mengubah kualitas air sebagai komponen abiotik sekaligus mengganggu organisme perairan sebagai komponen biotik.',1,'2026-10-25 02:43:53','2026-10-25 02:43:53'),(12,3,1,'Sekumpulan ikan haruan dari spesies yang sama yang hidup pada satu rawa dalam waktu tertentu disebut ....','Individu','Populasi','Komunitas','Biosfer','B','Populasi merupakan kumpulan individu sejenis yang hidup pada tempat dan waktu tertentu.',1,'2026-10-25 02:44:36','2026-10-25 02:44:36'),(13,3,2,'Di sebuah rawa terdapat ikan haruan, ikan kecil, tumbuhan air, serangga, dan burung kuntul yang saling berinteraksi. Kumpulan berbagai populasi tersebut disebut ....','Individu','Populasi','Komunitas','Bioma','C','Komunitas merupakan kumpulan populasi dari berbagai spesies yang hidup dan berinteraksi pada suatu wilayah.',1,'2026-10-25 02:45:18','2026-10-25 02:45:18'),(14,3,3,'Dalam rantai makanan fitoplankton → zooplankton → ikan kecil → ikan haruan, organisme yang berperan sebagai produsen adalah ....','Fitoplankton','Zooplankton','Ikan kecil','Ikan haruan','A','Fitoplankton yang berfotosintesis berperan sebagai produsen karena mampu menghasilkan bahan organik dengan memanfaatkan energi cahaya matahari.',1,'2026-10-25 02:45:50','2026-10-25 02:45:50'),(15,3,4,'Mengapa energi yang tersedia semakin berkurang pada tingkat trofik yang lebih tinggi?','Semua energi berubah menjadi unsur hara','Konsumen tidak membutuhkan energi','Produsen tidak menyimpan energi','Sebagian energi digunakan untuk aktivitas hidup dan dilepaskan sebagai panas','D','Organisme menggunakan energi untuk melakukan aktivitas kehidupan. Karena itu, hanya sebagian energi yang dapat diteruskan ke tingkat trofik berikutnya.',1,'2026-10-25 02:46:28','2026-10-25 02:46:28'),(16,3,5,'Hubungan antara beberapa rantai makanan yang saling terhubung dalam satu ekosistem disebut ....','Siklus air','Jaring-jaring makanan','Populasi','Habitat','B','Jaring-jaring makanan terbentuk ketika sejumlah rantai makanan saling berhubungan karena organisme dapat memiliki lebih dari satu sumber makanan atau pemangsa.',1,'2026-10-25 02:47:00','2026-10-25 02:47:00'),(17,4,1,'Penggunaan pupuk kimia secara berlebihan yang terbawa ke perairan dapat menyebabkan pertumbuhan alga yang tidak terkendali. Peristiwa tersebut disebut ....','Fotosintesis','Respirasi','Eutrofikasi','Transpirasi','C','Eutrofikasi merupakan pengayaan unsur hara pada perairan yang dapat menyebabkan pertumbuhan alga secara berlebihan. Kondisi tersebut berpotensi menurunkan oksigen terlarut dan mengganggu kehidupan organisme perairan.',1,'2026-10-25 02:47:41','2026-10-25 02:47:41'),(18,4,2,'Pembukaan lahan rawa secara berlebihan dapat menyebabkan berkurangnya populasi organisme karena ....','Organisme kehilangan habitat dan sumber makanan','Semua organisme berpindah ke habitat yang lebih baik','Suhu lingkungan selalu menjadi lebih rendah','Jumlah produsen dalam ekosistem selalu meningkat','A','Alih fungsi lahan dapat merusak atau mengurangi habitat alami, sehingga organisme kehilangan tempat berlindung, sumber makanan, dan ruang berkembang biak.',1,'2026-10-25 02:48:15','2026-10-25 02:48:15'),(19,4,3,'Salah satu kegiatan manusia yang dapat menyebabkan pencemaran air sungai adalah ....','Menanam pohon di tepian sungai','Melakukan pengelolaan sampah','Membersihkan lingkungan sekolah','Membuang limbah rumah tangga langsung ke sungai','D','Pembuangan limbah tanpa pengolahan dapat menurunkan kualitas air dan mengganggu organisme yang hidup di dalam sungai.',1,'2026-10-25 02:48:50','2026-10-25 02:48:50'),(20,4,4,'Aktivitas manusia yang berkontribusi terhadap peningkatan konsentrasi gas rumah kaca di atmosfer adalah ....','Menggunakan energi matahari untuk menghasilkan listrik','Membakar bahan bakar fosil secara terus-menerus','Menggunakan kembali barang yang masih layak','Melakukan penghijauan pada lahan yang sesuai','B','Pembakaran bahan bakar fosil melepaskan karbon dioksida ke atmosfer. Peningkatan konsentrasi gas rumah kaca memperkuat efek rumah kaca dan mendorong pemanasan global.',1,'2026-10-25 02:49:29','2026-10-25 02:49:29'),(21,4,5,'Siswa menemukan banyak sampah plastik di sekitar sungai. Tindakan yang paling tepat untuk membantu menjaga ekosistem adalah ....','Memindahkan sampah ke bagian sungai yang lain','Membakar seluruh sampah di tepian sungai','Mengumpulkan dan memilah sampah untuk dikelola dengan benar','Membiarkan sampah terbawa arus air','C','Pengumpulan dan pemilahan sampah membantu mengurangi pencemaran serta memungkinkan sampah ditangani atau didaur ulang melalui pengelolaan yang sesuai.',1,'2026-10-25 02:50:07','2026-10-25 02:50:07');
/*!40000 ALTER TABLE `quiz_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('wg2rppKAgrzMMazef76oP3xSMqLdAFC1z7mInwiC',5,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiSWtXOHZWbW1lSkwyeVBWYXBnU1hXdDNSclRmV3V3Mk14Y0hKQUlRTSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6OToiZGFzaGJvYXJkIjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NTtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2NDoiZDNlY2M5MzUzMmY2MzliNzMyOGQzMzA5ZmY5NWViNjEwZjVmMDY1YjU0YTM3NjY3ZDZlNDIyYzU3NWRkYjA5OSI7fQ==',1793086640);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_assessment_results`
--

DROP TABLE IF EXISTS `student_assessment_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_assessment_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `assessment_type` enum('quiz','evaluation') NOT NULL,
  `chapter_id` bigint(20) unsigned DEFAULT NULL,
  `subchapter_id` bigint(20) unsigned DEFAULT NULL,
  `total_questions` smallint(5) unsigned NOT NULL,
  `correct_answers` smallint(5) unsigned NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_assessment_results_chapter_id_foreign` (`chapter_id`),
  KEY `sar_user_type_index` (`user_id`,`assessment_type`),
  KEY `sar_completed_index` (`completed_at`),
  KEY `student_assessment_results_subchapter_id_foreign` (`subchapter_id`),
  CONSTRAINT `student_assessment_results_chapter_id_foreign` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_assessment_results_subchapter_id_foreign` FOREIGN KEY (`subchapter_id`) REFERENCES `subchapters` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_assessment_results_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_assessment_results`
--

LOCK TABLES `student_assessment_results` WRITE;
/*!40000 ALTER TABLE `student_assessment_results` DISABLE KEYS */;
INSERT INTO `student_assessment_results` VALUES (3,4,'quiz',3,2,5,5,100.00,'2026-10-26 18:19:47','2026-10-26 18:19:47','2026-10-26 18:19:47');
/*!40000 ALTER TABLE `student_assessment_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_guardians`
--

DROP TABLE IF EXISTS `student_guardians`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_guardians` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `guardian_id` bigint(20) unsigned NOT NULL,
  `relationship` varchar(50) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_guardians_student_id_guardian_id_unique` (`student_id`,`guardian_id`),
  KEY `student_guardians_guardian_id_foreign` (`guardian_id`),
  CONSTRAINT `student_guardians_guardian_id_foreign` FOREIGN KEY (`guardian_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_guardians_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_guardians`
--

LOCK TABLES `student_guardians` WRITE;
/*!40000 ALTER TABLE `student_guardians` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_guardians` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_quiz_answers`
--

DROP TABLE IF EXISTS `student_quiz_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_quiz_answers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `subchapter_id` bigint(20) unsigned NOT NULL,
  `quiz_question_id` bigint(20) unsigned NOT NULL,
  `answer` enum('A','B','C','D') NOT NULL,
  `is_correct` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_quiz_answers_user_id_quiz_question_id_unique` (`user_id`,`quiz_question_id`),
  KEY `student_quiz_answers_subchapter_id_foreign` (`subchapter_id`),
  KEY `student_quiz_answers_quiz_question_id_foreign` (`quiz_question_id`),
  KEY `student_quiz_answers_user_id_subchapter_id_index` (`user_id`,`subchapter_id`),
  CONSTRAINT `student_quiz_answers_quiz_question_id_foreign` FOREIGN KEY (`quiz_question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_quiz_answers_subchapter_id_foreign` FOREIGN KEY (`subchapter_id`) REFERENCES `subchapters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_quiz_answers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_quiz_answers`
--

LOCK TABLES `student_quiz_answers` WRITE;
/*!40000 ALTER TABLE `student_quiz_answers` DISABLE KEYS */;
INSERT INTO `student_quiz_answers` VALUES (11,4,2,7,'B',1,'2026-10-26 18:19:35','2026-10-26 18:19:35'),(12,4,2,8,'C',1,'2026-10-26 18:19:39','2026-10-26 18:19:39'),(13,4,2,9,'A',1,'2026-10-26 18:19:42','2026-10-26 18:19:42'),(14,4,2,10,'D',1,'2026-10-26 18:19:44','2026-10-26 18:19:44'),(15,4,2,11,'C',1,'2026-10-26 18:19:47','2026-10-26 18:19:47');
/*!40000 ALTER TABLE `student_quiz_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_subchapter_progress`
--

DROP TABLE IF EXISTS `student_subchapter_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_subchapter_progress` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `subchapter_id` bigint(20) unsigned NOT NULL,
  `status` varchar(24) NOT NULL DEFAULT 'in_progress',
  `opened_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_subchapter_progress_user_id_subchapter_id_unique` (`user_id`,`subchapter_id`),
  KEY `student_subchapter_progress_subchapter_id_foreign` (`subchapter_id`),
  KEY `student_subchapter_progress_user_id_status_index` (`user_id`,`status`),
  CONSTRAINT `student_subchapter_progress_subchapter_id_foreign` FOREIGN KEY (`subchapter_id`) REFERENCES `subchapters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_subchapter_progress_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_subchapter_progress`
--

LOCK TABLES `student_subchapter_progress` WRITE;
/*!40000 ALTER TABLE `student_subchapter_progress` DISABLE KEYS */;
INSERT INTO `student_subchapter_progress` VALUES (5,4,2,'completed','2026-10-26 16:49:09','2026-10-26 18:19:47','2026-10-26 16:49:09','2026-10-26 18:19:47');
/*!40000 ALTER TABLE `student_subchapter_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_teacher_notes`
--

DROP TABLE IF EXISTS `student_teacher_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_teacher_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_teacher_notes_student_id_teacher_id_unique` (`student_id`,`teacher_id`),
  KEY `student_teacher_notes_teacher_id_foreign` (`teacher_id`),
  CONSTRAINT `student_teacher_notes_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_teacher_notes_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_teacher_notes`
--

LOCK TABLES `student_teacher_notes` WRITE;
/*!40000 ALTER TABLE `student_teacher_notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_teacher_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subchapter_content_blocks`
--

DROP TABLE IF EXISTS `subchapter_content_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subchapter_content_blocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subchapter_id` bigint(20) unsigned NOT NULL,
  `type` varchar(32) NOT NULL,
  `position` int(10) unsigned NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `media_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subchapter_content_blocks_position_unique` (`subchapter_id`,`position`),
  KEY `subchapter_content_blocks_media_id_foreign` (`media_id`),
  KEY `subchapter_content_blocks_subchapter_id_type_index` (`subchapter_id`,`type`),
  CONSTRAINT `subchapter_content_blocks_media_id_foreign` FOREIGN KEY (`media_id`) REFERENCES `subchapter_media` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subchapter_content_blocks_subchapter_id_foreign` FOREIGN KEY (`subchapter_id`) REFERENCES `subchapters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=449 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subchapter_content_blocks`
--

LOCK TABLES `subchapter_content_blocks` WRITE;
/*!40000 ALTER TABLE `subchapter_content_blocks` DISABLE KEYS */;
INSERT INTO `subchapter_content_blocks` VALUES (281,2,'text',1,'{\"content\":\"<p><strong>Ayo Mengamati!<\\/strong><\\/p><p>Pernahkah kamu mengamati tumbuhan yang tumbuh di pinggir sungai atau rawa? Ada tumbuhan yang hidup di tanah basah, sedangkan tumbuhan lainnya dapat hidup mengapung di permukaan air.<br>Setiap organisme memerlukan kondisi lingkungan tertentu agar dapat tumbuh dan bertahan hidup. Coba perhatikan lingkungan lahan basah di sekitarmu, kemudian jawab pertanyaan berikut.<\\/p><ol><li>Apa saja makhluk hidup yang dapat ditemukan di lingkungan rawa?<\\/li><li>Apa saja benda tak hidup yang ada di lingkungan tersebut?<\\/li><li>Mengapa air dan cahaya matahari penting bagi kehidupan tumbuhan?<\\/li><li>Apa yang mungkin terjadi jika air rawa berkurang akibat musim kemarau?<\\/li><\\/ol><p>Melalui kegiatan ini, kita akan mempelajari bagaimana lingkungan memengaruhi kehidupan organisme.<\\/p>\"}',NULL,'2026-10-24 03:52:08','2026-10-24 03:52:08'),(282,2,'text',2,'{\"content\":\"<h3>Pengaruh Lingkungan terhadap Kehidupan Organisme<\\/h3><p>Lingkungan adalah segala sesuatu yang berada di sekitar makhluk hidup dan dapat memengaruhi kehidupannya. Setiap organisme membutuhkan kondisi lingkungan tertentu untuk tumbuh, berkembang, dan mempertahankan kehidupannya.<\\/p><p>Lingkungan terdiri atas dua komponen utama, yaitu <strong>komponen biotik<\\/strong> dan <strong>komponen abiotik<\\/strong>. Komponen biotik merupakan seluruh makhluk hidup, sedangkan komponen abiotik adalah unsur tak hidup yang terdapat di lingkungan. Kedua komponen tersebut saling berhubungan. Sebagai contoh, ikan haruan hidup di perairan rawa dan membutuhkan oksigen terlarut untuk bernapas. Tumbuhan air membutuhkan cahaya matahari, air, karbon dioksida, serta unsur hara untuk melakukan proses kehidupannya.<\\/p><p>Apabila kondisi lingkungan berubah, kehidupan organisme juga dapat terpengaruh. Berkurangnya air di rawa, meningkatnya suhu, atau masuknya zat pencemar dapat mengubah kondisi habitat dan memengaruhi organisme yang hidup di dalamnya. Oleh karena itu, lingkungan memiliki peran penting dalam menentukan pertumbuhan, persebaran, dan kelangsungan hidup makhluk hidup.<\\/p>\"}',NULL,'2026-10-24 03:52:08','2026-10-24 03:52:08'),(283,2,'text',3,'{\"content\":\"<h3>1. Komponen Lingkungan Abiotik<\\/h3><p>Komponen abiotik adalah seluruh unsur tak hidup dalam suatu lingkungan yang dapat memengaruhi kehidupan organisme. Walaupun bukan makhluk hidup, komponen ini memiliki peran penting karena menentukan kondisi tempat tinggal organisme. Beberapa komponen abiotik yang dapat ditemukan dalam lingkungan antara lain:<\\/p><ul><li><strong>Air:<\\/strong> Air diperlukan makhluk hidup untuk menjalankan berbagai proses kehidupan. Selain itu, air merupakan habitat bagi banyak organisme perairan, seperti ikan dan tumbuhan air.<\\/li><\\/ul><ul><li><strong>Cahaya Matahari:<\\/strong> Cahaya matahari merupakan sumber energi utama bagi tumbuhan untuk melakukan fotosintesis. Melalui proses tersebut, tumbuhan menghasilkan bahan organik yang dapat digunakan sebagai sumber energi.<\\/li><\\/ul><ul><li><strong>Suhu:<\\/strong> Suhu memengaruhi aktivitas, pertumbuhan, metabolisme, dan kelangsungan hidup organisme. Setiap organisme memiliki rentang suhu yang sesuai untuk kehidupannya.<\\/li><\\/ul><ul><li><strong>Udara:<\\/strong> Udara mengandung berbagai gas yang dibutuhkan makhluk hidup. Oksigen digunakan dalam respirasi aerobik, sedangkan karbon dioksida diperlukan tumbuhan untuk fotosintesis.<\\/li><\\/ul><ul><li><strong>Tanah:<\\/strong> Tanah menjadi tempat tumbuh berbagai jenis tumbuhan. Tanah juga menyediakan air, unsur hara, dan tempat hidup bagi organisme tertentu.<\\/li><\\/ul><ul><li><strong>Kelembapan:<\\/strong> Kelembapan menunjukkan banyaknya uap air di udara. Kondisi kelembapan dapat memengaruhi penguapan air dari tubuh organisme dan kesesuaian suatu habitat.<\\/li><\\/ul><ul><li><strong>pH:<\\/strong> pH menunjukkan tingkat keasaman atau kebasaan suatu lingkungan, seperti air dan tanah. Perubahan pH dapat memengaruhi pertumbuhan dan kelangsungan hidup organisme.<\\/li><\\/ul><ul><li><strong>Salinitas:<\\/strong> Salinitas menunjukkan kadar garam dalam air. Perbedaan salinitas memengaruhi jenis organisme yang dapat hidup di suatu perairan.<\\/li><\\/ul><h3>Contoh di Lahan Basah Kalimantan Selatan<\\/h3><p>Lingkungan rawa memiliki ketersediaan air dan karakteristik tanah yang khas. Ketika musim hujan, ketinggian air dapat meningkat. Sebaliknya, pada musim kemarau, sebagian perairan dapat mengalami penurunan tinggi muka air. Perubahan tersebut dapat memengaruhi kehidupan ikan haruan, tumbuhan air, dan organisme lainnya. Tumbuhan yang memerlukan kondisi basah dapat mengalami gangguan pertumbuhan ketika kekurangan air, sedangkan organisme perairan dapat mengalami penyusutan ruang hidup. Dengan demikian, komponen abiotik berpengaruh terhadap kondisi habitat dan kelangsungan hidup organisme.<\\/p>\"}',NULL,'2026-10-24 03:52:08','2026-10-24 03:52:08'),(284,2,'image',4,'{\"title\":\"Komponen Abiotik dalam Ekosistem Rawa\",\"caption\":\"Ekosistem rawa memiliki berbagai komponen abiotik yang saling memengaruhi kondisi lingkungan. Cahaya matahari menyediakan energi bagi tumbuhan untuk berfotosintesis. Air menjadi habitat organisme perairan dan mendukung proses kehidupan. Tanah berlumpur menyediakan tempat tumbuh serta unsur hara bagi tumbuhan. Suhu, udara, dan tingkat keasaman air juga memengaruhi kemampuan organisme bertahan hidup.\"}',11,'2026-10-24 03:52:08','2026-10-24 03:52:08'),(285,2,'text',5,'{\"content\":\"<p><strong>Ayo Mencoba!<\\/strong><\\/p><h3>Pengaruh Air dan Cahaya terhadap Pertumbuhan Tanaman<\\/h3><p>Air dan cahaya merupakan komponen abiotik yang memengaruhi kehidupan tumbuhan. Untuk memahami pengaruh kedua faktor tersebut, lakukan percobaan sederhana menggunakan biji kacang hijau.<\\/p><p><strong>Alat dan Bahan<\\/strong><\\/p><ol><li>Tiga wadah atau gelas plastik kecil.<\\/li><li>Kapas secukupnya.<\\/li><li>Sembilan biji kacang hijau.<\\/li><li>Air bersih.<\\/li><li>Penggaris.<\\/li><li>Buku catatan.<\\/li><\\/ol><p><strong>Langkah Kegiatan<\\/strong><\\/p><ol><li>Siapkan tiga wadah dan beri tanda A, B, serta C.<\\/li><li>Masukkan kapas dan tiga biji kacang hijau ke setiap wadah.<\\/li><li>Wadah A menggunakan kapas kering dan diletakkan di tempat terang.<\\/li><li>Wadah B menggunakan kapas basah dan diletakkan di tempat terang.<\\/li><li>Wadah C menggunakan kapas basah dan diletakkan di tempat gelap.<\\/li><li>Pertahankan kondisi setiap wadah sesuai perlakuannya selama pengamatan.<\\/li><li>Amati perubahan biji dan pertumbuhan kecambah selama 10 hari.<\\/li><li>Ukur tinggi kecambah, amati warna daun dan batangnya, lalu catat hasil pengamatan.<\\/li><\\/ol><p><strong>Pertanyaan Pengamatan<\\/strong><\\/p><ol><li>Bagaimana perbedaan pertumbuhan kecambah pada ketiga wadah?<\\/li><li>Mengapa biji pada kapas kering sulit berkecambah?<\\/li><li>Bagaimana perbedaan warna dan bentuk kecambah yang tumbuh di tempat terang dan gelap?<\\/li><li>Apa variabel bebas dan variabel terikat dalam percobaan tersebut?<\\/li><li>Apa kesimpulan yang dapat kamu buat mengenai pengaruh air dan cahaya terhadap pertumbuhan tanaman?<\\/li><\\/ol><p><strong>Catatan Penting<\\/strong><br>Biji kacang hijau dapat berkecambah di tempat gelap dengan memanfaatkan cadangan makanan di dalam biji, tetapi kondisi cahaya memengaruhi pertumbuhan dan pembentukan warna hijau pada tanaman.<\\/p><p>Melalui kegiatan ini, kita dapat memahami bahwa ketersediaan air dan cahaya memengaruhi proses pertumbuhan tumbuhan.<\\/p>\"}',NULL,'2026-10-24 03:52:08','2026-10-24 03:52:08'),(286,2,'text',6,'{\"content\":\"<h3>2. Komponen Lingkungan Biotik<\\/h3><p>Komponen biotik adalah seluruh makhluk hidup yang terdapat dalam suatu lingkungan. Komponen ini meliputi manusia, hewan, tumbuhan, jamur, bakteri, dan organisme lainnya. Makhluk hidup memerlukan organisme lain untuk memenuhi kebutuhan tertentu. Oleh karena itu, dalam suatu ekosistem dapat terjadi berbagai bentuk interaksi antarmakhluk hidup.<\\/p><p><strong>1. Kompetisi<\\/strong>: Kompetisi merupakan persaingan antarorganisme untuk memperoleh sumber daya yang terbatas, seperti makanan, air, ruang hidup, atau cahaya matahari. Sebagai contoh, tumbuhan yang hidup berdekatan dapat bersaing untuk memperoleh cahaya dan unsur hara.<\\/p><p><strong>2. Predasi<\\/strong>: Predasi merupakan hubungan ketika suatu organisme menangkap dan memakan organisme lain. Contohnya, ikan haruan memangsa ikan kecil atau hewan air lainnya untuk memperoleh makanan.<\\/p><p><strong>3. Mutualisme<\\/strong>: Mutualisme merupakan hubungan antara dua organisme berbeda jenis yang sama-sama memperoleh manfaat. Contohnya, lebah memperoleh nektar dari bunga, sedangkan tumbuhan berbunga dapat terbantu dalam proses penyerbukan.<\\/p><p><strong>4. Komensalisme<\\/strong>: Komensalisme merupakan hubungan ketika satu organisme memperoleh manfaat, sedangkan organisme lainnya tidak mengalami keuntungan maupun kerugian yang berarti.<\\/p><p><strong>5. Parasitisme<\\/strong>: Parasitisme merupakan hubungan ketika satu organisme memperoleh keuntungan, sedangkan organisme lain yang menjadi inangnya dirugikan.<\\/p><h3>Contoh di Lahan Basah Kalimantan Selatan<\\/h3><p>Di lingkungan rawa, ikan haruan dapat memangsa hewan air yang lebih kecil. Burung kuntul mencari makan di tepian perairan, sedangkan tumbuhan rawa menyediakan tempat berlindung bagi organisme tertentu. Selain hubungan antarmakhluk hidup, organisme juga berinteraksi dengan komponen abiotik. Misalnya, perubahan kualitas air dapat memengaruhi kondisi ikan dan tumbuhan air. Hal ini menunjukkan bahwa kehidupan organisme dipengaruhi oleh hubungan dengan makhluk hidup lain serta kondisi lingkungan tempat tinggalnya.<\\/p>\"}',NULL,'2026-10-24 03:52:08','2026-10-24 03:52:08'),(287,2,'image',7,'{\"title\":\"Interaksi Komponen Biotik dalam Ekosistem Rawa\",\"caption\":\"Ilustrasi memperlihatkan interaksi berbagai organisme di lingkungan rawa, seperti ikan haruan, ikan kecil, burung kuntul, serangga air, dan tumbuhan rawa. Hubungan tersebut menunjukkan bahwa makhluk hidup saling memengaruhi dalam memperoleh makanan, tempat hidup, dan kebutuhan lainnya.\"}',12,'2026-10-24 03:52:08','2026-10-24 03:52:08'),(288,2,'text',8,'{\"content\":\"<p><strong>Ayo Berpikir!<\\/strong><\\/p><p>Bayangkan sebuah rawa yang awalnya memiliki air bersih, tumbuhan air, ikan, burung, dan berbagai organisme lainnya. Suatu hari, limbah masuk ke perairan sehingga kualitas air menurun. Beberapa tumbuhan air mengalami gangguan pertumbuhan dan jumlah ikan mulai berkurang.<\\/p><p><strong>Berdasarkan keadaan tersebut, jawablah pertanyaan berikut.<\\/strong><\\/p><ol><li>Bagaimana pencemaran air dapat memengaruhi kehidupan ikan dan tumbuhan air?<\\/li><li>Apa yang mungkin terjadi pada burung pemakan ikan ketika jumlah ikan menurun?<\\/li><li>Mengapa perubahan komponen abiotik dapat memengaruhi komponen biotik?<\\/li><li>Bagaimana aktivitas manusia dapat menyebabkan perubahan kondisi lingkungan?<\\/li><li>Apa tindakan sederhana yang dapat dilakukan untuk menjaga ekosistem rawa?<\\/li><\\/ol><p>Diskusikan jawabanmu bersama teman, kemudian sampaikan kesimpulan mengenai hubungan antara kondisi lingkungan dan kehidupan organisme.<\\/p>\"}',NULL,'2026-10-24 03:52:08','2026-10-24 03:52:08'),(289,2,'video',9,'{\"title\":\"Mengenal Ekosistem, Komponen Biotik dan Abiotik, serta Rantai Makanan\",\"caption\":\"Video animasi 3D ini memperkenalkan ekosistem beserta komponen biotik dan abiotik yang menyusunnya. Pembelajaran juga mencakup hubungan antarkomponen dalam ekosistem dan pengenalan rantai makanan sebagai proses perpindahan energi antarmakhluk hidup.\"}',10,'2026-10-24 03:52:08','2026-10-24 03:52:08'),(290,2,'callout',10,'{\"title\":\"Kesimpulan\",\"content\":\"### Rangkuman Pengaruh Lingkungan terhadap Organisme\\nLingkungan merupakan segala sesuatu yang berada di sekitar makhluk hidup dan dapat memengaruhi kehidupannya. Lingkungan tersusun atas komponen biotik dan abiotik yang saling berhubungan.\\n\\nBerikut hal-hal penting yang telah dipelajari.\\n1. Komponen Abiotik: Komponen abiotik merupakan unsur tak hidup dalam lingkungan, seperti air, cahaya matahari, suhu, udara, tanah, kelembapan, pH, dan salinitas.\\n2. Komponen Biotik: Komponen biotik merupakan seluruh makhluk hidup yang terdapat dalam lingkungan, termasuk manusia, hewan, tumbuhan, jamur, dan mikroorganisme.\\n3. Pengaruh Lingkungan terhadap Organisme: Setiap organisme membutuhkan kondisi lingkungan yang sesuai untuk tumbuh, berkembang, dan bertahan hidup. Perubahan lingkungan dapat memengaruhi aktivitas serta kelangsungan hidup organisme.\\n4. Hubungan Antarkomponen: Komponen biotik dan abiotik saling berkaitan. Tumbuhan membutuhkan air dan cahaya matahari, sedangkan hewan membutuhkan lingkungan dan sumber makanan yang sesuai.\\n5. Interaksi Antarorganisme: Makhluk hidup dapat berinteraksi melalui kompetisi, predasi, mutualisme, komensalisme, dan parasitisme.\\n6. Perubahan Lingkungan: Kekeringan, pencemaran air, dan perubahan kondisi habitat dapat mengganggu kehidupan organisme serta keseimbangan ekosistem.\\n\\n### Kesimpulan\\nKehidupan organisme tidak dapat dipisahkan dari kondisi lingkungan. Komponen biotik dan abiotik saling memengaruhi dan memiliki peran penting dalam suatu ekosistem. Lingkungan lahan basah Kalimantan Selatan merupakan habitat berbagai organisme yang membutuhkan kondisi lingkungan sesuai untuk mempertahankan kehidupannya. Oleh karena itu, menjaga kualitas air, melindungi vegetasi, dan tidak merusak habitat merupakan langkah penting untuk mendukung kelestarian lingkungan.\"}',NULL,'2026-10-24 03:52:08','2026-10-24 03:52:08'),(348,3,'text',1,'{\"content\":\"<p><strong>Ayo Mengamati!<\\/strong><\\/p><h3>Menjelajahi Kehidupan di Ekosistem Rawa<\\/h3><p>Pernahkah kamu memperhatikan kehidupan di sekitar sungai atau rawa? Di lingkungan tersebut, berbagai makhluk hidup tinggal berdampingan. Ikan berenang di perairan, burung kuntul mencari makanan, dan tumbuhan air tumbuh di sekitar permukaan air.<\\/p><p>Meskipun terlihat hidup secara terpisah, organisme tersebut sebenarnya saling berhubungan. Ikan membutuhkan makanan dari organisme lain, sedangkan tumbuhan air memanfaatkan cahaya matahari, air, dan unsur hara untuk tumbuh.<\\/p><p>Coba bayangkan sebuah rawa yang memiliki ikan haruan, ikan kecil, serangga air, tumbuhan rawa, dan burung kuntul.<\\/p><p><strong>Berdasarkan pengamatan tersebut, jawablah pertanyaan berikut.<\\/strong><\\/p><ol><li>Apa hubungan antara ikan haruan dan ikan kecil di lingkungan rawa?<\\/li><li>Mengapa burung kuntul sering ditemukan di sekitar perairan?<\\/li><li>Apa manfaat tumbuhan air bagi organisme lain?<\\/li><li>Apa yang mungkin terjadi jika jumlah ikan kecil di rawa berkurang secara drastis?<\\/li><li>Mengapa keseimbangan kehidupan di rawa perlu dijaga?<\\/li><\\/ol><p>Melalui kegiatan ini, kita akan mempelajari bagaimana organisme saling berinteraksi dan membentuk hubungan dalam suatu ekosistem.<\\/p>\"}',NULL,'2026-10-24 23:26:12','2026-10-24 23:26:12'),(349,3,'text',2,'{\"content\":\"<h3>1. Mengenal Ekologi dan Ekosistem<\\/h3><p>Ekologi merupakan cabang ilmu biologi yang mempelajari hubungan antara makhluk hidup dengan sesama makhluk hidup serta lingkungan tempat tinggalnya.<\\/p><p>Dalam kehidupan sehari-hari, kita dapat menemukan berbagai bentuk hubungan tersebut. Misalnya, ikan memerlukan air sebagai habitat, tumbuhan membutuhkan cahaya matahari untuk fotosintesis, dan burung memperoleh makanan dari organisme lain.<\\/p><p>Hubungan antara makhluk hidup dan lingkungan tak hidup tersebut membentuk suatu <strong>ekosistem<\\/strong>. Ekosistem adalah kesatuan antara komunitas makhluk hidup dengan komponen abiotik di suatu tempat yang saling berinteraksi.<\\/p><h3>2. Tingkatan Organisasi Kehidupan dalam Ekologi<\\/h3><p>Untuk memahami kehidupan dalam ekosistem, kita perlu mengenal beberapa tingkatan organisasi kehidupan.<br><strong>1. Individu<\\/strong><br>Individu merupakan satu organisme atau satu makhluk hidup. Contohnya, seekor ikan haruan yang hidup di rawa.<\\/p><p><strong>2. Populasi<\\/strong><br>Populasi merupakan kumpulan individu dari spesies yang sama yang hidup di wilayah dan waktu tertentu. Contohnya, sekumpulan ikan haruan yang hidup di suatu rawa.<\\/p><p><strong>3. Komunitas<\\/strong><br>Komunitas merupakan kumpulan berbagai populasi organisme yang hidup dan berinteraksi di suatu wilayah. Contohnya, populasi ikan haruan, ikan kecil, tumbuhan air, burung kuntul, dan serangga air yang hidup bersama di rawa.<\\/p><p><strong>4. Ekosistem<\\/strong><br>Ekosistem terbentuk dari interaksi antara komunitas organisme dengan komponen abiotik lingkungannya. Contohnya, ekosistem rawa yang meliputi ikan, burung, tumbuhan air, mikroorganisme, air, tanah, suhu, dan cahaya matahari.<\\/p><p><strong>5. Bioma<\\/strong><br>Bioma merupakan wilayah ekologis yang luas dan memiliki karakteristik iklim serta vegetasi tertentu. Contohnya, bioma hutan hujan tropis dan bioma gurun.<\\/p><p><strong>6. Biosfer<\\/strong><br>Biosfer merupakan seluruh bagian Bumi yang mendukung kehidupan, mencakup berbagai ekosistem di daratan maupun perairan.<\\/p><h3>Contoh di Lahan Basah Kalimantan Selatan<\\/h3><p>Seekor ikan haruan merupakan individu. Sekumpulan ikan haruan pada suatu lokasi membentuk populasi. Berbagai populasi ikan, tumbuhan air, burung, dan mikroorganisme membentuk komunitas. Ketika komunitas tersebut berinteraksi dengan air rawa, tanah, cahaya matahari, dan suhu, terbentuklah ekosistem rawa.<br>Dengan memahami tingkatan organisasi ini, kita dapat mengetahui bahwa kehidupan tersusun dari hubungan yang saling berkaitan, mulai dari organisme tunggal hingga seluruh kehidupan di Bumi.<\\/p>\"}',NULL,'2026-10-24 23:26:12','2026-10-24 23:26:12'),(350,3,'image',3,'{\"title\":\"Tingkatan Organisasi Kehidupan dalam Ekosistem Rawa\",\"caption\":\"Ilustrasi menunjukkan tingkatan organisasi kehidupan, mulai dari individu, populasi, komunitas, ekosistem, bioma, hingga biosfer, menggunakan contoh organisme dan lingkungan rawa Kalimantan Selatan.\"}',13,'2026-10-24 23:26:12','2026-10-24 23:26:12'),(351,3,'text',4,'{\"content\":\"<h3>3. Habitat dan Mikrohabitat<\\/h3><p>Setiap organisme membutuhkan tempat hidup yang sesuai untuk memenuhi kebutuhan hidupnya. Tempat hidup alami suatu organisme disebut <strong>habitat<\\/strong>. Habitat menyediakan berbagai kebutuhan, seperti makanan, air, tempat berlindung, dan kondisi lingkungan yang mendukung kehidupan.<\\/p><p>Sebagai contoh, rawa air tawar merupakan salah satu habitat ikan haruan. Di lingkungan tersebut, ikan haruan memperoleh makanan, tempat berlindung, dan ruang untuk berkembang biak. Selain habitat, terdapat istilah <strong>mikrohabitat<\\/strong>, yaitu bagian lingkungan yang lebih kecil dan memiliki kondisi khusus sebagai tempat hidup organisme. Contohnya, bagian bawah daun, sela-sela akar tumbuhan air, dan permukaan tanah lembap dapat menjadi mikrohabitat organisme kecil.<\\/p><h3>4. Interaksi Antarkomponen Ekosistem<\\/h3><p>Dalam suatu ekosistem, organisme dapat berinteraksi dengan makhluk hidup lain maupun komponen abiotik.<br><strong>1. Interaksi Biotik dengan Abiotik<\\/strong><br>Interaksi ini terjadi ketika organisme memanfaatkan atau dipengaruhi oleh unsur tak hidup dalam lingkungannya. Contohnya, tumbuhan air membutuhkan cahaya matahari, air, dan unsur hara untuk fotosintesis. Ikan memerlukan air yang memiliki kondisi sesuai agar dapat bertahan hidup.<\\/p><p><strong>2. Interaksi Antarorganisme<\\/strong><br>Interaksi antarorganisme terjadi ketika makhluk hidup saling memengaruhi, baik dalam memperoleh makanan, tempat hidup, maupun sumber daya lainnya. Beberapa bentuk interaksi tersebut antara lain:<\\/p><ul><li><strong>Predasi:<\\/strong> hubungan antara pemangsa dan mangsa, seperti ikan haruan memangsa ikan kecil.<\\/li><li><strong>Kompetisi:<\\/strong> persaingan organisme untuk memperoleh sumber daya yang terbatas, seperti makanan atau ruang hidup.<\\/li><li><strong>Mutualisme:<\\/strong> hubungan antara dua organisme berbeda spesies yang saling menguntungkan, seperti serangga penyerbuk dan bunga.<\\/li><li><strong>Komensalisme:<\\/strong> hubungan yang menguntungkan salah satu organisme tanpa memberikan pengaruh berarti bagi organisme lainnya.<\\/li><li><strong>Parasitisme:<\\/strong> hubungan ketika satu organisme memperoleh keuntungan, sedangkan organisme inangnya dirugikan.<\\/li><\\/ul><h3>Mengapa Interaksi Penting?<\\/h3><p>Interaksi memungkinkan organisme memperoleh kebutuhan hidupnya dan memengaruhi keseimbangan ekosistem. Jika jumlah salah satu organisme berubah secara drastis, organisme lain yang berhubungan dengannya juga dapat terpengaruh.<\\/p><p>Contohnya, penurunan populasi ikan kecil dapat mengurangi ketersediaan makanan bagi ikan haruan dan burung pemakan ikan. Kondisi tersebut menunjukkan bahwa perubahan pada satu komponen dapat memengaruhi komponen lainnya.<\\/p>\"}',NULL,'2026-10-24 23:26:12','2026-10-24 23:26:12'),(352,3,'image',5,'{\"title\":\"Hubungan Antarkomponen dalam Ekosistem Rawa\",\"caption\":\"Ilustrasi memperlihatkan hubungan antara berbagai komponen ekosistem rawa. Tumbuhan air memanfaatkan cahaya matahari dan unsur hara, ikan memperoleh makanan dari organisme lain, dan tumbuhan rawa menyediakan tempat berlindung. Hubungan tersebut menunjukkan bahwa komponen biotik dan abiotik saling memengaruhi dalam ekosistem.\"}',14,'2026-10-24 23:26:12','2026-10-24 23:26:12'),(353,3,'text',6,'{\"content\":\"<h3>5. Hubungan Makan dan Dimakan dalam Ekosistem<\\/h3><p>Makhluk hidup membutuhkan energi untuk melakukan berbagai aktivitas, seperti bergerak, tumbuh, berkembang biak, dan mempertahankan kehidupannya. Dalam ekosistem, organisme memperoleh energi melalui hubungan makan dan dimakan. Hubungan tersebut dapat digambarkan melalui rantai makanan dan jaring-jaring makanan.<\\/p><h3>6. Rantai Makanan<\\/h3><p>Rantai makanan adalah urutan perpindahan energi melalui peristiwa makan dan dimakan antarorganisme. Dalam rantai makanan, organisme dapat memiliki beberapa peran.<br><strong>1. Produsen<\\/strong><br>Produsen merupakan organisme yang dapat menghasilkan bahan organik sendiri, umumnya melalui fotosintesis. Contohnya, tumbuhan hijau dan alga.<\\/p><p><strong>2. Konsumen Tingkat I<\\/strong><br>Konsumen tingkat I merupakan organisme yang memakan produsen. Contohnya, hewan pemakan tumbuhan dan organisme pemakan alga.<\\/p><p><strong>3. Konsumen Tingkat II<\\/strong><br>Konsumen tingkat II merupakan organisme yang memakan konsumen tingkat I.<\\/p><p><strong>4. Konsumen Tingkat III dan Seterusnya<\\/strong><br>Konsumen tingkat lebih tinggi merupakan organisme yang memakan konsumen pada tingkat sebelumnya.<\\/p><p><strong>5. Pengurai<\\/strong><br>Pengurai, seperti bakteri dan jamur tertentu, menguraikan bahan organik dari organisme mati dan sisa-sisa makhluk hidup. Melalui proses tersebut, unsur hara dapat kembali tersedia dalam lingkungan.<\\/p><h3>Contoh Rantai Makanan di Lingkungan Rawa<\\/h3><p>Salah satu contoh sederhana hubungan makan dan dimakan di perairan rawa adalah <strong>Fitoplankton \\u2192 Zooplankton \\u2192 Ikan kecil pemakan zooplankton \\u2192 Ikan haruan<\\/strong>. Fitoplankton berperan sebagai produsen karena mampu melakukan fotosintesis. Zooplankton tertentu memakan fitoplankton, kemudian dimakan oleh ikan kecil. Ikan kecil selanjutnya dapat dimangsa oleh ikan haruan.<br>Tanda panah menunjukkan arah perpindahan energi, yaitu dari organisme yang dimakan menuju organisme pemakan. Contoh tersebut merupakan penyederhanaan. Jenis organisme dan hubungan makanannya dapat berbeda sesuai kondisi ekosistem.<\\/p><h3>7. Jaring-Jaring Makanan<\\/h3><p>Dalam ekosistem, suatu organisme biasanya tidak hanya memakan satu jenis makanan atau dimakan oleh satu jenis pemangsa. Oleh karena itu, beberapa rantai makanan dapat saling terhubung dan membentuk <strong>jaring-jaring makanan<\\/strong>.<\\/p><p>Sebagai contoh, ikan kecil dapat memakan beberapa jenis organisme air dan juga menjadi makanan bagi berbagai predator. Hubungan yang saling terhubung tersebut menunjukkan bahwa perubahan populasi suatu organisme dapat memengaruhi organisme lain dalam ekosistem.<\\/p><h3>8. Aliran Energi<\\/h3><p>Sumber energi utama bagi sebagian besar ekosistem adalah cahaya matahari. Energi matahari ditangkap oleh produsen melalui fotosintesis, kemudian berpindah ke konsumen melalui hubungan makan dan dimakan.<\\/p><p>Pada setiap perpindahan tingkat trofik, sebagian energi digunakan organisme untuk aktivitas hidup dan dilepaskan ke lingkungan sebagai panas. Akibatnya, jumlah energi yang tersedia untuk tingkat trofik berikutnya semakin berkurang. Energi mengalir melalui ekosistem, sedangkan unsur hara dapat didaur ulang melalui berbagai proses alam.<\\/p><p>Dengan demikian, rantai makanan dan jaring-jaring makanan membantu kita memahami hubungan antarorganisme serta perpindahan energi dalam ekosistem.<\\/p>\"}',NULL,'2026-10-24 23:26:12','2026-10-24 23:26:12'),(354,3,'image',7,'{\"title\":\"Rantai Makanan dan Aliran Energi dalam Ekosistem Rawa\",\"caption\":\"Ilustrasi menunjukkan perpindahan energi melalui rantai makanan di ekosistem rawa, dimulai dari fitoplankton sebagai produsen, kemudian zooplankton, ikan kecil, hingga ikan haruan sebagai predator. Arah panah menunjukkan perpindahan energi dari organisme yang dimakan kepada organisme pemakan.\"}',15,'2026-10-24 23:26:12','2026-10-24 23:26:12'),(355,3,'text',8,'{\"content\":\"<p><strong>Ayo Berdiskusi!<\\/strong><\\/p><h3>Menyelidiki Keseimbangan Ekosistem Rawa<\\/h3><p>Di sebuah rawa, terdapat fitoplankton, zooplankton, ikan kecil, ikan haruan, burung air, serta berbagai tumbuhan dan organisme lainnya. Organisme tersebut saling berhubungan melalui interaksi dalam ekosistem.<\\/p><p>Bayangkan terjadi pencemaran air yang menyebabkan sebagian organisme perairan mengalami penurunan populasi.<br><strong>Diskusikan pertanyaan berikut bersama kelompokmu.<\\/strong><\\/p><ol><li>Apa perbedaan antara individu, populasi, komunitas, dan ekosistem?<\\/li><li>Bagaimana hubungan komponen biotik dan abiotik dalam suatu ekosistem?<\\/li><li>Mengapa keberadaan produsen penting dalam rantai makanan?<\\/li><li>Apa yang mungkin terjadi pada ikan haruan jika jumlah ikan kecil berkurang?<\\/li><li>Bagaimana pencemaran air dapat memengaruhi jaring-jaring makanan?<\\/li><li>Apa yang dapat dilakukan manusia untuk menjaga keseimbangan ekosistem rawa?<\\/li><\\/ol><p><strong>Tugas Kelompok<\\/strong><br>Buatlah satu diagram rantai makanan yang mungkin terjadi di lingkungan rawa. Gunakan tanda panah untuk menunjukkan arah perpindahan energi. Setelah itu, jelaskan hubungan antarorganisme yang terdapat pada diagram kelompokmu. Sampaikan hasil diskusi dengan bahasa yang jelas dan berikan alasan berdasarkan konsep yang telah dipelajari.<\\/p>\"}',NULL,'2026-10-24 23:26:12','2026-10-24 23:26:12'),(356,3,'video',9,'{\"title\":\"Mengenal Macam-Macam Ekosistem dan Komponen Penyusunnya\",\"caption\":\"Video pembelajaran ini memperkenalkan berbagai macam ekosistem dan komponen penyusunnya, termasuk komponen biotik dan abiotik. Melalui video ini, siswa diajak mengenali bahwa setiap ekosistem memiliki karakteristik dan organisme yang berbeda sesuai kondisi lingkungannya.\"}',16,'2026-10-24 23:26:12','2026-10-24 23:26:12'),(357,3,'text',10,'{\"content\":\"<p><strong>Ayo Berpikir!<\\/strong><\\/p><h3>Apa yang Terjadi Jika Salah Satu Organisme Menghilang?<\\/h3><p>Dalam suatu ekosistem, organisme mempunyai peran yang saling berkaitan. Perubahan jumlah organisme dapat memengaruhi hubungan makan dan dimakan.<\\/p><p>Bayangkan populasi ikan kecil di suatu rawa mengalami penurunan drastis akibat perubahan kualitas air.<br><strong>Coba pikirkan pertanyaan berikut.<\\/strong><\\/p><ol><li>Bagaimana kondisi tersebut dapat memengaruhi ikan haruan?<\\/li><li>Apakah perubahan jumlah ikan kecil dapat memengaruhi organisme yang menjadi makanannya?<\\/li><li>Mengapa dampak perubahan populasi tidak selalu terbatas pada satu jenis organisme?<\\/li><li>Bagaimana jaring-jaring makanan membantu menjelaskan hubungan tersebut?<\\/li><li>Mengapa kelestarian lingkungan penting bagi keseimbangan ekosistem?<\\/li><\\/ol><p>Tuliskan pendapatmu berdasarkan hubungan antarorganisme yang telah dipelajari.<\\/p>\"}',NULL,'2026-10-24 23:26:12','2026-10-24 23:26:12'),(358,3,'callout',11,'{\"title\":\"Kesimpulan\",\"content\":\"### Rangkuman Interaksi Komponen Penyusun Ekosistem\\nEkosistem terbentuk dari hubungan antara organisme dengan lingkungan tempat hidupnya. Setiap komponen memiliki peran dan saling berkaitan dalam menjaga keberlangsungan kehidupan.\\n\\nBerikut hal-hal penting yang telah dipelajari:\\n1. **Ekologi dan Ekosistem:** Ekologi mempelajari hubungan makhluk hidup dengan lingkungannya, sedangkan ekosistem merupakan kesatuan antara komunitas organisme dan komponen abiotik yang saling berinteraksi.\\n\\n2. **Tingkatan Organisasi Kehidupan:** Organisasi kehidupan dalam ekologi meliputi individu, populasi, komunitas, ekosistem, bioma, dan biosfer.\\n\\n3. **Habitat dan Mikrohabitat:** Habitat merupakan tempat hidup alami organisme, sedangkan mikrohabitat merupakan bagian lingkungan yang lebih kecil dengan kondisi khusus.\\n\\n4. **Interaksi dalam Ekosistem:** Organisme dapat berinteraksi dengan komponen abiotik maupun organisme lain melalui berbagai bentuk hubungan, seperti predasi, kompetisi, dan simbiosis.\\n\\n5. **Rantai Makanan:** Rantai makanan menunjukkan urutan perpindahan energi melalui hubungan makan dan dimakan dari produsen kepada konsumen.\\n\\n6. **Jaring-Jaring Makanan:** Jaring-jaring makanan merupakan hubungan beberapa rantai makanan yang saling terhubung dalam suatu ekosistem.\\n\\n7. **Aliran Energi:** Energi berpindah dari produsen ke konsumen melalui hubungan makan dan dimakan. Pada setiap perpindahan tingkat trofik, energi yang tersedia semakin berkurang.\\n\\n8. **Keseimbangan Ekosistem:** Perubahan jumlah organisme atau kondisi lingkungan dapat memengaruhi hubungan antarorganisme dan keseimbangan ekosistem.\\n\\n### Kesimpulan\\nSetiap organisme merupakan bagian dari sistem kehidupan yang saling berhubungan. Interaksi antara komponen biotik dan abiotik membentuk ekosistem yang mendukung kehidupan berbagai organisme. Melalui rantai makanan dan jaring-jaring makanan, kita dapat memahami hubungan makan dan dimakan serta perpindahan energi dalam ekosistem.\\n\\nLingkungan rawa Kalimantan Selatan merupakan salah satu contoh ekosistem yang memiliki hubungan kompleks antara ikan, burung air, tumbuhan, mikroorganisme, air, tanah, dan unsur lingkungan lainnya. Menjaga kelestarian lingkungan penting untuk mempertahankan kehidupan organisme serta keseimbangan ekosistem.\"}',NULL,'2026-10-24 23:26:12','2026-10-24 23:26:12'),(359,3,'text',12,'{\"content\":\"<p><strong>Ayo Refleksi!<\\/strong><\\/p><p>Apa perbedaan antara populasi, komunitas, dan ekosistem? Mengapa perubahan pada salah satu organisme dapat memengaruhi organisme lainnya? Apa yang dapat kamu lakukan untuk membantu menjaga keseimbangan ekosistem di lingkungan sekitarmu?<\\/p>\"}',NULL,'2026-10-24 23:26:12','2026-10-24 23:26:12'),(385,4,'text',1,'{\"content\":\"<p><strong>Ayo Mengamati!<\\/strong><\\/p><h3>Perubahan Lingkungan di Sekitar Kita<\\/h3><p>Pernahkah kamu melihat sungai yang dipenuhi sampah, lahan yang berubah menjadi permukiman, atau pepohonan yang ditebang untuk membuka lahan baru?<\\/p><p>Berbagai perubahan tersebut dapat terjadi akibat aktivitas manusia. Sebagian kegiatan manusia memberikan manfaat, seperti menanam pohon dan membersihkan sungai. Namun, kegiatan lainnya dapat menyebabkan pencemaran dan kerusakan habitat makhluk hidup.<\\/p><p>Di Kalimantan Selatan, sungai dan rawa memiliki peran penting sebagai habitat berbagai organisme sekaligus mendukung kehidupan masyarakat.<\\/p><p><strong>Ayo amati lingkungan di sekitarmu, kemudian jawab pertanyaan berikut.<\\/strong><\\/p><ol><li>Apa saja aktivitas manusia yang kamu temukan di sekitar sungai, rawa, atau lingkungan tempat tinggalmu?<\\/li><li>Aktivitas apa yang memberikan manfaat bagi lingkungan?<\\/li><li>Aktivitas apa yang berpotensi merusak ekosistem?<\\/li><li>Bagaimana kondisi sungai atau rawa apabila banyak sampah dibuang ke dalamnya?<\\/li><li>Apa yang dapat dilakukan manusia untuk menjaga kelestarian lingkungan?<\\/li><\\/ol><p>Melalui kegiatan ini, kita akan mempelajari bagaimana aktivitas manusia dapat memberikan pengaruh positif maupun negatif terhadap ekosistem.<\\/p>\"}',NULL,'2026-10-25 00:09:58','2026-10-25 00:09:58'),(386,4,'text',2,'{\"content\":\"<h3>1. Pengaruh Aktivitas Manusia terhadap Ekosistem<\\/h3><p>Manusia merupakan salah satu komponen biotik yang mempunyai peran besar dalam memengaruhi kondisi lingkungan. Berbagai aktivitas manusia, seperti pertanian, pembangunan permukiman, industri, dan pemanfaatan sumber daya alam, dapat menyebabkan perubahan pada ekosistem. Pengaruh manusia terhadap ekosistem dapat bersifat positif maupun negatif.<\\/p><ul><li><strong>Pengaruh Positif<\\/strong><\\/li><\\/ul><p>Aktivitas manusia dapat memberikan manfaat bagi lingkungan apabila dilakukan secara bijaksana. Contohnya adalah melakukan penghijauan, menanam kembali pohon yang ditebang, mengolah sampah, mengurangi penggunaan plastik, dan menjaga kebersihan sungai. Kegiatan tersebut dapat membantu mempertahankan kualitas lingkungan dan mendukung kelangsungan hidup organisme.<\\/p><ul><li><strong>Pengaruh Negatif<\\/strong><\\/li><\\/ul><p>Aktivitas manusia juga dapat mengganggu keseimbangan ekosistem apabila dilakukan tanpa memperhatikan kelestarian lingkungan. Contohnya adalah penebangan hutan secara berlebihan, pembuangan limbah ke sungai, penggunaan bahan kimia pertanian secara tidak bijaksana, dan pembangunan yang merusak habitat alami.<\\/p><p>Dampak yang dapat terjadi meliputi penurunan kualitas air, kerusakan habitat, berkurangnya keanekaragaman hayati, dan perubahan kondisi lingkungan.<\\/p><h3>Contoh di Lahan Basah Kalimantan Selatan<\\/h3><p>Ekosistem sungai dan rawa menjadi tempat hidup berbagai organisme, seperti ikan haruan, tumbuhan air, burung air, dan mikroorganisme. Apabila perairan tercemar, organisme yang hidup di dalamnya dapat mengalami gangguan. Sebaliknya, kegiatan membersihkan sungai dan menjaga vegetasi rawa dapat membantu mempertahankan kualitas habitat. Oleh karena itu, manusia perlu memperhatikan dampak setiap kegiatan terhadap keseimbangan ekosistem.<\\/p>\"}',NULL,'2026-10-25 00:09:58','2026-10-25 00:09:58'),(387,4,'image',3,'{\"title\":\"Dampak Positif dan Negatif Aktivitas Manusia terhadap Ekosistem\",\"caption\":\"Ilustrasi memperlihatkan perbandingan kegiatan manusia yang menjaga dan merusak ekosistem lahan basah. Kegiatan penghijauan serta pengelolaan sampah membantu mempertahankan kualitas lingkungan, sedangkan pembuangan limbah dan penebangan vegetasi dapat mengganggu kehidupan organisme.\"}',17,'2026-10-25 00:09:58','2026-10-25 00:09:58'),(388,4,'text',4,'{\"content\":\"<h3>2. Pertanian dan Produksi Pangan<\\/h3><p>Pertanian merupakan salah satu kegiatan manusia yang bertujuan menghasilkan bahan pangan untuk memenuhi kebutuhan hidup.<\\/p><p>Melalui kegiatan pertanian, manusia dapat menanam berbagai tanaman, seperti padi, jagung, sayuran, dan buah-buahan. Namun, kegiatan pertanian juga dapat memengaruhi kondisi ekosistem.<\\/p><ul><li><strong>Penggunaan Pupuk Kimia<\\/strong><\\/li><\\/ul><p>Pupuk digunakan untuk membantu pertumbuhan tanaman. Akan tetapi, penggunaan pupuk secara berlebihan dapat menyebabkan kelebihan unsur hara yang terbawa air ke sungai atau danau.<\\/p><p>Kondisi tersebut dapat memicu <strong>eutrofikasi<\\/strong>, yaitu pengayaan unsur hara yang menyebabkan pertumbuhan alga atau tumbuhan air secara berlebihan. Jika tidak terkendali, kondisi ini dapat menurunkan kadar oksigen terlarut dan mengganggu kehidupan organisme perairan.<\\/p><ul><li><strong>Penggunaan Pestisida<\\/strong><\\/li><\\/ul><p>Pestisida digunakan untuk mengendalikan organisme pengganggu tanaman. Namun, penggunaan yang tidak tepat dapat membahayakan organisme lain yang bukan menjadi sasaran, termasuk serangga yang bermanfaat bagi lingkungan.<\\/p><ul><li><strong>Pertanian Monokultur<\\/strong><\\/li><\\/ul><p>Pertanian monokultur merupakan kegiatan menanam satu jenis tanaman secara luas pada suatu lahan. Kegiatan ini dapat memudahkan pengelolaan pertanian, tetapi berpotensi mengurangi keanekaragaman tumbuhan dan habitat organisme apabila menggantikan ekosistem alami.<\\/p><h3>Contoh dalam Lingkungan Lahan Basah<\\/h3><p>Pada kawasan pertanian yang berdekatan dengan sungai atau rawa, penggunaan pupuk dan pestisida perlu dilakukan secara bijaksana. Jika bahan-bahan tersebut terbawa aliran air menuju perairan, kualitas air dapat berubah dan memengaruhi organisme yang hidup di dalamnya. Oleh karena itu, pertanian perlu dikelola dengan memperhatikan kesehatan tanah, kualitas air, serta keseimbangan ekosistem.<\\/p><h3>3. Kerusakan Habitat<\\/h3><p>Habitat merupakan tempat hidup alami organisme yang menyediakan kebutuhan penting, seperti makanan, air, tempat berlindung, dan ruang untuk berkembang biak. Kerusakan habitat terjadi ketika kondisi suatu tempat hidup mengalami perubahan sehingga tidak lagi mendukung kehidupan organisme dengan baik. Salah satu penyebab kerusakan habitat adalah aktivitas manusia yang mengubah fungsi lahan.<\\/p><ul><li><strong>Penebangan Hutan<\\/strong><\\/li><\\/ul><p>Penebangan pohon secara berlebihan dapat mengurangi tempat hidup berbagai hewan dan tumbuhan. Hewan yang kehilangan habitat dapat mengalami kesulitan memperoleh makanan, tempat berlindung, dan tempat berkembang biak.<\\/p><ul><li><strong>Alih Fungsi Lahan<\\/strong><\\/li><\\/ul><p>Alih fungsi lahan adalah perubahan penggunaan suatu wilayah untuk kegiatan lain, seperti pembangunan permukiman, jalan, pertambangan, atau perkebunan. Apabila perubahan tersebut dilakukan pada ekosistem alami tanpa mempertimbangkan dampaknya, habitat organisme dapat berkurang atau terpecah menjadi bagian-bagian yang lebih kecil.<\\/p><ul><li><strong>Kerusakan Ekosistem Rawa<\\/strong><\\/li><\\/ul><p>Pada lingkungan rawa, penimbunan lahan, pembuangan limbah, atau hilangnya vegetasi dapat menyebabkan perubahan kondisi habitat organisme. Ikan haruan, ikan kecil, serangga air, dan burung air membutuhkan kondisi lingkungan yang sesuai untuk bertahan hidup. Jika habitatnya mengalami kerusakan, ketersediaan makanan dan tempat berlindung juga dapat berkurang.<\\/p><h3>Dampak Kerusakan Habitat<\\/h3><p>Kerusakan habitat dapat menyebabkan penurunan populasi organisme, gangguan hubungan makan dan dimakan, serta berkurangnya keanekaragaman hayati. Oleh karena itu, menjaga habitat alami merupakan salah satu langkah penting untuk mempertahankan keseimbangan ekosistem.<\\/p>\"}',NULL,'2026-10-25 00:09:58','2026-10-25 00:09:58'),(389,4,'image',5,'{\"title\":\"Kerusakan Habitat Akibat Aktivitas Manusia\",\"caption\":\"Ilustrasi menunjukkan perubahan habitat rawa akibat pembukaan lahan dan hilangnya vegetasi alami. Perubahan tersebut dapat mengurangi tempat berlindung, sumber makanan, serta ruang hidup organisme yang bergantung pada ekosistem rawa.\"}',18,'2026-10-25 00:09:58','2026-10-25 00:09:58'),(390,4,'text',6,'{\"content\":\"<h3>4. Polusi dan Pencemaran Lingkungan<\\/h3><p>Polusi atau pencemaran lingkungan terjadi ketika zat, energi, atau komponen lain masuk ke lingkungan sehingga menyebabkan perubahan kualitas lingkungan yang merugikan kehidupan.<\\/p><p>Pencemaran dapat terjadi di udara, air, maupun tanah.<br><strong>1. Pencemaran Air<\\/strong><br>Pencemaran air dapat terjadi akibat pembuangan sampah, limbah rumah tangga, limbah industri, atau bahan kimia pertanian ke sungai dan perairan lainnya. Pencemaran dapat menyebabkan perubahan warna, bau, dan kualitas air serta mengganggu kehidupan organisme perairan. Sebagai contoh, pembuangan limbah ke sungai dapat menurunkan kualitas habitat ikan dan organisme lainnya.<\\/p><p><strong>2. Pencemaran Udara<\\/strong><br>Pencemaran udara dapat disebabkan oleh asap kendaraan bermotor, pembakaran sampah, kegiatan industri, serta kebakaran hutan dan lahan. Udara yang tercemar dapat mengganggu kesehatan manusia dan organisme lainnya. Beberapa polutan udara juga dapat berperan dalam terbentuknya hujan asam.<\\/p><p><strong>3. Pencemaran Tanah<\\/strong><br>Pencemaran tanah dapat terjadi akibat pembuangan sampah dan bahan kimia yang tidak dikelola dengan baik. Pencemaran dapat mengganggu organisme tanah, menurunkan kualitas lingkungan, dan memengaruhi pertumbuhan tumbuhan.<\\/p><h3>Dampak Pencemaran terhadap Ekosistem<\\/h3><p>Pencemaran dapat mengganggu keseimbangan ekosistem karena setiap organisme membutuhkan kondisi lingkungan yang sesuai. Misalnya, pencemaran air yang menyebabkan kekurangan oksigen terlarut dapat membahayakan ikan dan organisme perairan lainnya. Gangguan pada organisme tersebut juga dapat memengaruhi hubungan makan dan dimakan.<\\/p><h3>Cara Mengurangi Pencemaran<\\/h3><p>Beberapa upaya yang dapat dilakukan adalah mengurangi sampah plastik, memilah sampah, tidak membuang limbah ke sungai, mengolah limbah dengan benar, dan menjaga kebersihan lingkungan. Menjaga kualitas air sungai dan rawa sangat penting bagi keberlangsungan kehidupan organisme di lahan basah Kalimantan Selatan.<\\/p>\"}',NULL,'2026-10-25 00:09:58','2026-10-25 00:09:58'),(391,4,'image',7,'{\"title\":\"Dampak Pencemaran Air terhadap Ekosistem Sungai dan Rawa\",\"caption\":\"Ilustrasi memperlihatkan perbedaan kondisi perairan yang terjaga dan perairan yang tercemar. Pencemaran akibat sampah serta limbah dapat menurunkan kualitas air dan mengganggu kehidupan ikan, tumbuhan air, serta organisme lainnya.\"}',19,'2026-10-25 00:09:58','2026-10-25 00:09:58'),(392,4,'text',8,'{\"content\":\"<h3>5. Perubahan Iklim<\\/h3><p>Iklim merupakan pola kondisi cuaca rata-rata pada suatu wilayah dalam jangka waktu panjang.Perubahan iklim adalah perubahan jangka panjang pada suhu dan pola cuaca di Bumi.<\\/p><p>Perubahan iklim dapat terjadi karena faktor alami. Namun, aktivitas manusia, terutama pembakaran bahan bakar fosil dan perubahan penggunaan lahan, menjadi penyebab utama pemanasan global yang terjadi saat ini.<\\/p><p><strong>Penyebab Perubahan Iklim<\\/strong><br>Beberapa aktivitas manusia yang dapat meningkatkan emisi gas rumah kaca antara lain:<\\/p><ol><li>Pembakaran bahan bakar fosil pada kendaraan, industri, dan pembangkit energi.<\\/li><li>Penebangan serta pembakaran hutan.<\\/li><li>Kegiatan pertanian dan peternakan tertentu.<\\/li><li>Pengelolaan sampah dan limbah yang menghasilkan gas rumah kaca.<\\/li><\\/ol><p>Gas rumah kaca, seperti karbon dioksida dan metana, dapat menyerap serta memancarkan kembali radiasi inframerah sehingga memengaruhi keseimbangan energi Bumi.<\\/p><p>Peningkatan konsentrasi gas rumah kaca akibat aktivitas manusia memperkuat efek rumah kaca dan menyebabkan suhu rata-rata Bumi meningkat.<br><strong>Dampak Perubahan Iklim<\\/strong><br>Perubahan iklim dapat menyebabkan perubahan pola hujan, peningkatan suhu, kejadian cuaca ekstrem, serta kenaikan permukaan air laut. Dampak tersebut dapat memengaruhi ketersediaan air, kegiatan pertanian, habitat organisme, dan kehidupan manusia.<\\/p><h3>Perubahan Iklim dan Lahan Basah Kalimantan Selatan<\\/h3><p>Ekosistem rawa sangat dipengaruhi oleh kondisi air dan pola musim. Perubahan pola curah hujan dapat menyebabkan perubahan ketinggian air, sedangkan musim kemarau yang berkepanjangan dapat membuat sebagian lahan menjadi lebih kering. Pada kondisi tertentu, kekeringan juga dapat meningkatkan risiko kebakaran lahan, termasuk pada kawasan gambut yang mengering. Perubahan tersebut dapat memengaruhi habitat ikan, tumbuhan rawa, burung air, dan organisme lainnya.<\\/p><h3>Upaya Mengurangi Dampak Perubahan Iklim<\\/h3><p>Manusia dapat berkontribusi melalui penghematan energi, penggunaan transportasi yang lebih ramah lingkungan, pengurangan pemborosan, perlindungan hutan, dan pengelolaan sampah. Selain itu, menjaga ekosistem lahan basah merupakan salah satu upaya penting untuk mempertahankan fungsi lingkungan. Melalui tindakan sederhana dan berkelanjutan, kita dapat berperan dalam mengurangi emisi gas rumah kaca sekaligus menjaga lingkungan.<\\/p>\"}',NULL,'2026-10-25 00:09:58','2026-10-25 00:09:58'),(393,4,'video',9,'{\"title\":\"Dampak Aktivitas Manusia terhadap Kerusakan Ekosistem\",\"caption\":\"Video ini membahas berbagai aktivitas manusia yang dapat menyebabkan kerusakan lingkungan, seperti deforestasi, pencemaran udara, dan sampah plastik. Melalui video ini, siswa dapat memahami bagaimana aktivitas manusia mengganggu keseimbangan ekosistem serta pentingnya menjaga kelestarian lingkungan.\"}',20,'2026-10-25 00:09:58','2026-10-25 00:09:58'),(394,4,'text',10,'{\"content\":\"<p><strong>Ayo Berdiskusi!<\\/strong><\\/p><h3>Menyelidiki Dampak Aktivitas Manusia terhadap Lingkungan<\\/h3><p>Di lingkungan sekitar, kita dapat menemukan berbagai kegiatan manusia yang memengaruhi kondisi ekosistem. Sebagai contoh, pembuangan sampah ke sungai dapat mengganggu kualitas perairan, sedangkan penghijauan dan pengelolaan limbah dapat membantu menjaga lingkungan.<\\/p><p><strong>Diskusikan pertanyaan berikut bersama kelompokmu.<\\/strong><\\/p><ol><li>Apa saja kegiatan manusia yang dapat menyebabkan kerusakan ekosistem?<\\/li><li>Mengapa penggunaan pupuk dan pestisida secara berlebihan dapat merugikan lingkungan?<\\/li><li>Bagaimana penebangan vegetasi dapat memengaruhi habitat hewan?<\\/li><li>Apa hubungan pencemaran air dengan kehidupan organisme di sungai dan rawa?<\\/li><li>Bagaimana perubahan iklim dapat memengaruhi kondisi lahan basah?<\\/li><li>Apa tindakan yang dapat dilakukan siswa untuk mengurangi dampak negatif aktivitas manusia terhadap ekosistem?<\\/li><\\/ol><p><strong>Tugas Kelompok<\\/strong><br>Pilih satu permasalahan lingkungan yang ditemukan di sekitar tempat tinggal atau sekolah. Identifikasi penyebab, dampak terhadap organisme, dan tindakan yang dapat dilakukan untuk mengurangi perm asalahan tersebut. Sajikan hasil diskusi dalam bentuk poster sederhana, diagram sebab-akibat, atau presentasi kelompok.<\\/p>\"}',NULL,'2026-10-25 00:09:58','2026-10-25 00:09:58'),(395,4,'image',11,'{\"title\":\"Dampak Perubahan Iklim terhadap Ekosistem Lahan Basah\",\"caption\":\"Ilustrasi menunjukkan bagaimana perubahan kondisi iklim dapat memengaruhi ekosistem lahan basah. Perubahan pola hujan dan musim kemarau berkepanjangan dapat mengubah ketinggian air, kondisi vegetasi, serta habitat organisme yang bergantung pada lingkungan rawa.\"}',21,'2026-10-25 00:09:58','2026-10-25 00:09:58'),(396,4,'text',12,'{\"content\":\"<h3>6. Konservasi sebagai Upaya Menjaga Ekosistem<\\/h3><p>Aktivitas manusia tidak selalu menyebabkan kerusakan lingkungan. Manusia juga dapat melakukan berbagai tindakan untuk melindungi dan memperbaiki ekosistem. Salah satu upaya tersebut adalah <strong>konservasi<\\/strong>. Konservasi merupakan upaya pengelolaan dan pelestarian sumber daya alam secara bijaksana agar manfaat serta keberlangsungannya tetap terjaga.<\\/p><p>Beberapa tindakan konservasi yang dapat dilakukan antara lain:<br><strong>1. Penghijauan dan Penanaman Kembali<\\/strong><br>Menanam pohon dan memulihkan vegetasi yang rusak dapat membantu menyediakan habitat organisme serta menjaga kondisi lingkungan.<\\/p><p><strong>2. Pengelolaan Sampah dan Limbah<\\/strong><br>Mengurangi sampah, memilah bahan yang dapat didaur ulang, dan mengolah limbah dengan benar dapat membantu mengurangi pencemaran.<\\/p><p><strong>3. Penggunaan Energi Secara Bijaksana<\\/strong><br>Menghemat listrik dan menggunakan energi yang lebih ramah lingkungan dapat membantu mengurangi penggunaan bahan bakar fosil serta emisi gas rumah kaca.<\\/p><p><strong>4. Perlindungan Habitat Alami<\\/strong><br>Menjaga hutan, sungai, dan rawa dari kerusakan dapat membantu mempertahankan tempat hidup berbagai organisme.<\\/p><h3>Konservasi Lahan Basah Kalimantan Selatan<\\/h3><p>Lingkungan rawa dan sungai perlu dikelola dengan memperhatikan keberlangsungan kehidupan organisme di dalamnya. Kegiatan menjaga kebersihan sungai, melindungi tumbuhan rawa, menghindari pembakaran lahan, serta menggunakan sumber daya alam secara bijaksana merupakan contoh upaya yang dapat mendukung konservasi.<\\/p><p>Dengan melakukan konservasi, manusia dapat membantu mempertahankan keseimbangan ekosistem sekaligus menjaga manfaat lingkungan bagi generasi sekarang dan masa depan.<\\/p>\"}',NULL,'2026-10-25 00:09:58','2026-10-25 00:09:58'),(397,4,'text',13,'{\"content\":\"<p><strong>Ayo Mencoba!<\\/strong><\\/p><h3>Aksi Nyata Menjaga Lingkungan<\\/h3><p>Setelah mempelajari dampak aktivitas manusia terhadap ekosistem, sekarang saatnya merencanakan tindakan sederhana yang dapat membantu menjaga lingkungan.<\\/p><p><strong>Langkah Kegiatan<\\/strong><\\/p><ol><li>Bentuk kelompok yang terdiri atas beberapa siswa.<\\/li><li>Amati kondisi lingkungan sekolah atau tempat tinggal.<\\/li><li>Pilih satu permasalahan lingkungan yang dapat ditangani melalui kegiatan sederhana.<\\/li><li>Tentukan tindakan yang dapat dilakukan kelompok.<\\/li><li>Susun rencana kegiatan yang mencakup tujuan, alat dan bahan, pembagian tugas, serta waktu pelaksanaan.<\\/li><li>Laksanakan kegiatan dengan memperhatikan keselamatan dan arahan guru.<\\/li><li>Dokumentasikan kegiatan dan hasilnya.<\\/li><li>Presentasikan hasil kegiatan kepada teman-teman.<\\/li><\\/ol><p><strong>Contoh Aksi Nyata<\\/strong><\\/p><ul><li>Mengurangi penggunaan plastik sekali pakai.<\\/li><li>Memilah sampah di lingkungan sekolah.<\\/li><li>Membuat poster ajakan menjaga kebersihan sungai.<\\/li><li>Melakukan penghijauan di area yang sesuai.<\\/li><li>Mengampanyekan penghematan listrik dan air.<\\/li><\\/ul><p><strong>Pertanyaan Refleksi Kegiatan<\\/strong><\\/p><ol><li>Apa permasalahan lingkungan yang dipilih kelompokmu?<\\/li><li>Mengapa permasalahan tersebut perlu ditangani?<\\/li><li>Apa tindakan yang dilakukan?<\\/li><li>Apa hasil yang diperoleh?<\\/li><li>Bagaimana kegiatan tersebut dapat dilanjutkan secara berkelanjutan?<\\/li><\\/ol><p>Melalui kegiatan ini, siswa diharapkan memahami bahwa menjaga ekosistem dapat dimulai dari tindakan sederhana dalam kehidupan sehari-hari.<\\/p>\"}',NULL,'2026-10-25 00:09:58','2026-10-25 00:09:58'),(398,4,'callout',14,'{\"title\":\"Kesimpulan\",\"content\":\"### Rangkuman Pengaruh Manusia terhadap Ekosistem\\nAktivitas manusia memiliki pengaruh besar terhadap kondisi ekosistem. Pengaruh tersebut dapat bersifat positif maupun negatif, bergantung pada cara manusia memanfaatkan dan mengelola lingkungan.\\n\\nBerikut hal-hal penting yang telah dipelajari:\\n1. **Aktivitas Manusia:** Kegiatan manusia dapat memengaruhi komponen biotik, abiotik, dan keseimbangan ekosistem.\\n\\n2. **Pertanian dan Produksi Pangan:** Pertanian menyediakan kebutuhan pangan, tetapi penggunaan pupuk serta pestisida secara berlebihan dan perubahan habitat alami dapat menimbulkan masalah lingkungan.\\n\\n3. **Kerusakan Habitat:** Penebangan hutan dan alih fungsi lahan dapat mengurangi tempat hidup organisme serta mengganggu keanekaragaman hayati.\\n\\n4. **Pencemaran Lingkungan:** Pencemaran air, udara, dan tanah dapat menurunkan kualitas lingkungan serta mengganggu kehidupan organisme.\\n\\n5. **Perubahan Iklim:** Peningkatan gas rumah kaca akibat aktivitas manusia menyebabkan pemanasan global dan perubahan jangka panjang pada suhu serta pola cuaca.\\n\\n6. **Dampak pada Lahan Basah:** Perubahan kondisi air, kerusakan vegetasi, dan pencemaran dapat memengaruhi habitat organisme di sungai dan rawa.\\n\\n7. **Konservasi Lingkungan:** Konservasi dilakukan melalui pengelolaan sumber daya alam secara bijaksana untuk menjaga kelestarian lingkungan.\\n\\n8. **Peran Siswa:** Siswa dapat berkontribusi dalam menjaga lingkungan melalui pengurangan sampah, penghematan energi, penghijauan, dan kegiatan peduli lingkungan lainnya.\\n\\n### Kesimpulan\\nManusia mempunyai peran penting dalam memengaruhi keberlangsungan suatu ekosistem. Kegiatan pertanian, pembangunan, pengelolaan limbah, dan pemanfaatan sumber daya alam perlu dilakukan dengan mempertimbangkan dampaknya terhadap lingkungan.\\n\\nAktivitas yang tidak memperhatikan kelestarian alam dapat menyebabkan kerusakan habitat, pencemaran, berkurangnya keanekaragaman hayati, dan peningkatan emisi gas rumah kaca. Sebaliknya, tindakan konservasi, pengelolaan sumber daya alam yang bijaksana, dan kepedulian terhadap lingkungan dapat membantu menjaga keseimbangan ekosistem.\"}',NULL,'2026-10-25 00:09:58','2026-10-25 00:09:58'),(399,4,'text',15,'{\"content\":\"<p><strong>Ayo Refleksi!<\\/strong><\\/p><p>Apa saja aktivitas manusia yang dapat merusak ekosistem? Mengapa menjaga kualitas air sungai dan rawa penting bagi kehidupan organisme? Tindakan sederhana apa yang dapat kamu lakukan untuk membantu menjaga lingkungan?<\\/p>\"}',NULL,'2026-10-25 00:09:58','2026-10-25 00:09:58'),(437,5,'text',1,'{\"content\":\"<p><strong>Ayo Mengamati!<\\/strong><\\/p><h3>Mengapa Makhluk Hidup Perlu Dilindungi?<\\/h3><p>Pernahkah kamu membayangkan bagaimana kondisi lingkungan jika suatu jenis hewan atau tumbuhan tidak dapat ditemukan lagi?<\\/p><p>Indonesia memiliki keanekaragaman hayati yang tinggi. Berbagai jenis organisme hidup di hutan, sungai, rawa, dan lingkungan lainnya. Setiap organisme mempunyai peran dalam ekosistem. Namun, beberapa jenis organisme menghadapi ancaman akibat perburuan, kerusakan habitat, pencemaran, dan aktivitas manusia lainnya.<\\/p><p>Sebagai contoh, ikan haruan, burung air, serta tumbuhan rawa membutuhkan habitat yang sesuai untuk bertahan hidup. Apabila habitat mereka rusak, kehidupan organisme tersebut dapat terganggu.<\\/p><p><strong>Ayo pikirkan pertanyaan berikut!<\\/strong><\\/p><ol><li>Mengapa hewan dan tumbuhan perlu dilindungi?<\\/li><li>Apa yang mungkin terjadi jika salah satu jenis organisme menghilang dari ekosistem?<\\/li><li>Bagaimana kerusakan habitat dapat memengaruhi keanekaragaman hayati?<\\/li><li>Apa yang dapat dilakukan manusia untuk mencegah kepunahan organisme?<\\/li><li>Mengapa menjaga sungai dan rawa penting bagi kehidupan di Kalimantan Selatan?<\\/li><\\/ol><p>Melalui kegiatan ini, kita akan mempelajari pentingnya konservasi keanekaragaman hayati serta berbagai cara yang dapat dilakukan untuk melindungi organisme dan habitatnya.<\\/p>\"}',NULL,'2026-10-25 02:29:52','2026-10-25 02:29:52'),(438,5,'text',2,'{\"content\":\"<h3>1. Keanekaragaman Hayati dan Ancaman Kepunahan<\\/h3><p>Keanekaragaman hayati merupakan keragaman kehidupan yang terdapat di Bumi, meliputi variasi genetik, berbagai jenis makhluk hidup, serta ekosistem tempat organisme hidup. Indonesia memiliki keanekaragaman hayati yang tinggi karena mempunyai beragam kondisi alam, mulai dari hutan hujan tropis, pegunungan, sungai, hingga lingkungan lahan basah. Keanekaragaman hayati memiliki peran penting dalam menjaga keseimbangan ekosistem. Setiap organisme dapat berperan sebagai produsen, konsumen, pengurai, atau menjalankan fungsi lainnya dalam lingkungan.<\\/p><h3>Ancaman terhadap Keanekaragaman Hayati<\\/h3><p>Keanekaragaman hayati dapat mengalami penurunan akibat berbagai aktivitas manusia.<\\/p><p><strong>1. Kerusakan Habitat<\\/strong><br>Penebangan hutan, pembangunan, serta alih fungsi lahan dapat mengurangi tempat hidup organisme. Akibatnya, organisme dapat kehilangan sumber makanan, tempat berlindung, dan tempat berkembang biak.<\\/p><p><strong>2. Perburuan Berlebihan<\\/strong><br>Perburuan tanpa memperhatikan kelestarian populasi dapat menyebabkan penurunan jumlah organisme dan meningkatkan risiko kepunahan spesies tertentu.<\\/p><p><strong>3. Pencemaran Lingkungan<\\/strong><br>Pencemaran air, tanah, dan udara dapat mengubah kondisi habitat sehingga mengganggu kehidupan organisme.<\\/p><p><strong>4. Perubahan Iklim<\\/strong><br>Perubahan suhu dan pola curah hujan dapat menyebabkan perubahan kondisi habitat dan memengaruhi kemampuan organisme untuk bertahan hidup.<\\/p><h3>Contoh Kepunahan di Indonesia<\\/h3><p>Salah satu contoh yang dibahas dalam buku IPA kelas VII adalah harimau Jawa. Hewan ini telah punah akibat berbagai tekanan, termasuk perburuan dan kerusakan habitat. Kepunahan bersifat permanen. Apabila suatu spesies telah punah, spesies tersebut tidak dapat ditemukan kembali sebagai populasi hidup di alam. Oleh karena itu, perlindungan organisme perlu dilakukan sebelum populasinya mengalami penurunan yang sulit dipulihkan.<\\/p><h3>Hubungan dengan Lahan Basah Kalimantan Selatan<\\/h3><p>Ekosistem rawa dan sungai menyediakan habitat bagi berbagai jenis ikan, tumbuhan air, burung air, dan mikroorganisme. Kerusakan habitat, pencemaran perairan, dan pemanfaatan sumber daya secara berlebihan dapat mengancam keanekaragaman organisme di lingkungan tersebut. Menjaga habitat alami merupakan langkah penting untuk mempertahankan keanekaragaman hayati dan keseimbangan ekosistem.<\\/p>\"}',NULL,'2026-10-25 02:29:52','2026-10-25 02:29:52'),(439,5,'image',3,'{\"title\":\"Pentingnya Konservasi Ekosistem Lahan Basah\",\"caption\":\"Ilustrasi memperlihatkan ekosistem lahan basah yang terjaga melalui perlindungan habitat dan vegetasi alami. Kawasan rawa yang sehat mendukung kehidupan ikan, burung air, tumbuhan, dan berbagai organisme lainnya serta memberikan manfaat bagi lingkungan dan masyarakat.\"}',22,'2026-10-25 02:29:52','2026-10-25 02:29:52'),(440,5,'text',4,'{\"content\":\"<h3>4. Metode Konservasi In-Situ<\\/h3><p>Konservasi dapat dilakukan melalui dua metode utama, yaitu konservasi in-situ dan konservasi eks-situ. <strong>Konservasi in-situ adalah upaya pelestarian makhluk hidup yang dilakukan di habitat aslinya.<\\/strong> Metode ini bertujuan melindungi organisme beserta lingkungan alami tempat mereka hidup. Melalui konservasi in-situ, organisme dapat mempertahankan berbagai hubungan alami dalam ekosistem, seperti mencari makanan, berkembang biak, dan berinteraksi dengan organisme lainnya.<\\/p><h3>Contoh Konservasi In-Situ<\\/h3><p><strong>1. Taman Nasional<\\/strong><br>Taman nasional merupakan kawasan pelestarian alam yang mempunyai ekosistem asli dan dikelola melalui sistem zonasi. Kawasan ini memiliki fungsi perlindungan, penelitian, pendidikan, serta kegiatan lain yang sesuai dengan tujuan pelestarian.<\\/p><p><strong>2. Cagar Alam<\\/strong><br>Cagar alam merupakan kawasan suaka alam yang memiliki kekhasan tumbuhan, hewan, atau ekosistem tertentu sehingga perlu dilindungi.<\\/p><p><strong>3. Suaka Margasatwa<\\/strong><br>Suaka margasatwa merupakan kawasan perlindungan yang terutama ditujukan untuk menjaga kelangsungan hidup satwa tertentu beserta habitatnya.<\\/p><h3>Kelebihan Konservasi In-Situ<\\/h3><p>Konservasi in-situ memungkinkan organisme hidup di lingkungan alaminya dan mempertahankan interaksi ekologis yang telah terbentuk. Metode ini juga membantu melindungi berbagai organisme lain yang berada dalam kawasan yang sama.<\\/p><h3>Contoh dalam Lingkungan Lahan Basah<\\/h3><p>Perlindungan kawasan rawa dan sungai yang menjadi habitat alami ikan, burung air, serta tumbuhan rawa merupakan contoh penerapan prinsip konservasi in-situ. Melalui perlindungan habitat, organisme dapat tetap hidup dan berkembang biak dalam lingkungan alaminya. Dengan demikian, konservasi in-situ tidak hanya melindungi satu jenis organisme, tetapi juga mendukung kelestarian ekosistem tempat organisme tersebut hidup.<\\/p>\"}',NULL,'2026-10-25 02:29:52','2026-10-25 02:29:52'),(441,5,'image',5,'{\"title\":\"Konservasi In-Situ pada Habitat Alami\",\"caption\":\"Ilustrasi menunjukkan pelestarian organisme di habitat aslinya melalui perlindungan ekosistem lahan basah. Konservasi in-situ bertujuan mempertahankan kondisi habitat dan hubungan alami antarorganisme agar kehidupan dalam ekosistem tetap terjaga.\"}',23,'2026-10-25 02:29:52','2026-10-25 02:29:52'),(442,5,'text',6,'{\"content\":\"<h3>5. Metode Konservasi Eks-Situ<\\/h3><p>Selain melindungi organisme di habitat aslinya, konservasi juga dapat dilakukan di luar habitat alami. <strong>Konservasi eks-situ adalah upaya pelestarian makhluk hidup yang dilakukan di luar habitat aslinya melalui lingkungan yang dikelola manusia.<\\/strong><\\/p><p>Metode ini dapat menjadi alternatif ketika habitat alami mengalami kerusakan, populasi organisme sangat terancam, atau organisme memerlukan perlindungan dan pengelolaan khusus. Dalam konservasi eks-situ, kebutuhan hidup organisme harus diperhatikan agar kondisi tempat pemeliharaan sesuai dengan karakteristik spesies.<\\/p><h3>Contoh Konservasi Eks-Situ<\\/h3><p><strong>1. Penangkaran Satwa<\\/strong><br>Penangkaran merupakan kegiatan pemeliharaan dan perkembangbiakan satwa dalam lingkungan yang dikelola manusia. Tujuannya dapat mencakup perlindungan serta peningkatan populasi spesies tertentu.<\\/p><p><strong>2. Kebun Binatang<\\/strong><br>Kebun binatang dapat berperan dalam konservasi eks-situ melalui pemeliharaan satwa, pendidikan, penelitian, dan program pelestarian yang sesuai.<\\/p><p><strong>3. Kebun Raya<\\/strong><br>Kebun raya merupakan tempat koleksi tumbuhan hidup yang dapat mendukung penelitian, pendidikan, dan pelestarian tumbuhan.<\\/p><p><strong>4. Bank Benih<\\/strong><br>Bank benih merupakan fasilitas penyimpanan benih tumbuhan dalam kondisi terkendali untuk membantu mempertahankan sumber daya genetik tumbuhan.<\\/p><h3>Kelebihan Konservasi Eks-Situ<\\/h3><p>Konservasi eks-situ memungkinkan organisme mendapatkan pengawasan, perawatan, dan perlindungan khusus. Metode ini juga dapat mendukung penelitian dan program perkembangbiakan spesies yang terancam punah. Namun, konservasi eks-situ tidak sepenuhnya menggantikan perlindungan habitat alami karena organisme tetap memerlukan ekosistem yang sesuai untuk bertahan hidup di alam.<\\/p><h3>Perbedaan Konservasi In-Situ dan Eks-Situ<\\/h3><p>Konservasi in-situ dilakukan di habitat asli organisme, sedangkan konservasi eks-situ dilakukan di luar habitat aslinya. Kedua metode tersebut memiliki tujuan yang sama, yaitu melindungi keanekaragaman hayati dari ancaman kepunahan. Metode yang dipilih perlu disesuaikan dengan kondisi organisme, tingkat ancaman, dan tujuan konservasi.<\\/p>\"}',NULL,'2026-10-25 02:29:52','2026-10-25 02:29:52'),(443,5,'image',7,'{\"title\":\"Perbedaan Konservasi In-Situ dan Eks-Situ\",\"caption\":\"Ilustrasi membandingkan dua metode konservasi keanekaragaman hayati. Konservasi in-situ dilakukan dengan melindungi organisme di habitat aslinya, sedangkan konservasi eks-situ dilakukan melalui pemeliharaan dan perlindungan organisme di luar habitat alami dalam lingkungan yang dikelola manusia.\"}',24,'2026-10-25 02:29:52','2026-10-25 02:29:52'),(444,5,'text',8,'{\"content\":\"<p><strong>Ayo Berdiskusi!<\\/strong><\\/p><h3>Menentukan Cara Terbaik Melindungi Makhluk Hidup<\\/h3><p>Setiap organisme memiliki kebutuhan hidup yang berbeda. Oleh karena itu, upaya pelestarian perlu disesuaikan dengan kondisi habitat dan ancaman yang dihadapi.<\\/p><p>Bayangkan terdapat dua kondisi berikut.<br><strong>Kondisi A<\\/strong><br>Sebuah kawasan rawa masih memiliki habitat yang baik, tetapi mengalami ancaman akibat pembukaan lahan. Di dalamnya hidup berbagai jenis ikan, burung air, dan tumbuhan rawa.<\\/p><p><strong>Kondisi B<\\/strong><br>Suatu jenis satwa mengalami penurunan populasi yang sangat besar dan membutuhkan program perkembangbiakan serta perlindungan khusus di fasilitas konservasi.<\\/p><p><strong>Diskusikan pertanyaan berikut bersama kelompokmu.<\\/strong><\\/p><ol><li>Metode konservasi apa yang lebih sesuai untuk kondisi A?<\\/li><li>Metode konservasi apa yang dapat dipertimbangkan untuk kondisi B?<\\/li><li>Apa perbedaan utama konservasi in-situ dan eks-situ?<\\/li><li>Mengapa perlindungan habitat alami tetap penting meskipun terdapat fasilitas penangkaran?<\\/li><li>Apa manfaat konservasi bagi kehidupan manusia?<\\/li><li>Bagaimana masyarakat dapat berperan dalam melindungi keanekaragaman hayati?<\\/li><\\/ol><p><strong>Tugas Kelompok<\\/strong><br>Buat tabel perbandingan konservasi in-situ dan eks-situ yang memuat pengertian, tujuan, contoh penerapan, serta kelebihan masing-masing metode. Presentasikan hasil diskusi di depan kelas dengan bahasa yang mudah dipahami.<\\/p>\"}',NULL,'2026-10-25 02:29:52','2026-10-25 02:29:52'),(445,5,'video',9,'{\"title\":\"Pelestarian Hewan melalui Konservasi In-Situ dan Eks-Situ\",\"caption\":\"Video pembelajaran ini memperkenalkan upaya pelestarian hewan melalui dua metode konservasi, yaitu in-situ dan eks-situ. Melalui contoh perlindungan hewan di habitat aslinya maupun di lingkungan konservasi yang dikelola manusia, siswa dapat memahami perbedaan kedua metode serta pentingnya menjaga kelangsungan hidup satwa.\"}',25,'2026-10-25 02:29:52','2026-10-25 02:29:52'),(446,5,'text',10,'{\"content\":\"<p><strong>Ayo Mencoba!<\\/strong><\\/p><h3>Proyek Konservasi: Aksi Nyata Menjaga Lingkungan<\\/h3><p>Setelah mempelajari Bab VI, sekarang saatnya menerapkan pengetahuan tentang ekologi dan pelestarian lingkungan melalui kegiatan sederhana.<\\/p><p><strong>Tujuan Kegiatan<\\/strong><br>Mengidentifikasi permasalahan lingkungan di sekitar sekolah atau tempat tinggal serta merancang tindakan yang dapat membantu menjaga kelestarian ekosistem.<\\/p><h3>Langkah Kegiatan<\\/h3><ol><li><strong>Bentuk Kelompok<\\/strong><\\/li><\\/ol><p>Bentuk kelompok sesuai arahan guru dan tentukan pembagian tugas setiap anggota.<\\/p><ol><li><strong>Identifikasi Permasalahan<\\/strong><\\/li><\\/ol><p>Amati kondisi lingkungan sekolah, sungai, taman, atau area sekitar tempat tinggal. Temukan permasalahan yang berkaitan dengan sampah, kerusakan vegetasi, pencemaran, atau gangguan lingkungan lainnya.<\\/p><ol><li><strong>Tentukan Masalah Utama<\\/strong><\\/li><\\/ol><p>Pilih satu permasalahan yang paling memungkinkan untuk ditangani melalui kegiatan sederhana.<\\/p><ol><li><strong>Analisis Penyebab dan Dampak<\\/strong><\\/li><\\/ol><p>Diskusikan penyebab permasalahan serta dampaknya terhadap makhluk hidup dan kondisi lingkungan.<\\/p><ol><li><strong>Rancang Solusi<\\/strong><\\/li><\\/ol><p>Susun beberapa alternatif tindakan yang dapat dilakukan. Pilih solusi yang sesuai dengan kemampuan kelompok, waktu, dan kondisi lingkungan.<\\/p><ol><li><strong>Laksanakan Aksi Konservasi<\\/strong><\\/li><\\/ol><p>Lakukan kegiatan dengan memperhatikan keselamatan, aturan sekolah, dan arahan guru.<\\/p><ol><li><strong>Dokumentasikan Kegiatan<\\/strong><\\/li><\\/ol><p>Ambil foto sebelum dan sesudah kegiatan serta catat hasil yang diperoleh.<\\/p><ol><li><strong>Buat Poster Hasil Kegiatan<\\/strong><\\/li><\\/ol><p>Susun poster yang berisi judul kegiatan, permasalahan lingkungan, tujuan, langkah kegiatan, dokumentasi, dan hasil.<\\/p><ol><li><strong>Presentasikan Hasil Proyek<\\/strong><\\/li><\\/ol><p>Sampaikan hasil kegiatan kepada teman-teman dan jelaskan manfaatnya bagi lingkungan.<\\/p><h3>Contoh Proyek<\\/h3><ul><li>Kampanye mengurangi penggunaan plastik sekali pakai.<\\/li><li>Pemilahan sampah di lingkungan sekolah.<\\/li><li>Penghijauan menggunakan tanaman yang sesuai dengan kondisi lokasi.<\\/li><li>Pembuatan poster pelestarian sungai dan rawa.<\\/li><li>Kegiatan menjaga kebersihan lingkungan sekolah.<\\/li><\\/ul><h3>Pertanyaan Refleksi<\\/h3><ol><li>Apa permasalahan lingkungan yang dipilih kelompokmu?<\\/li><li>Mengapa permasalahan tersebut perlu ditangani?<\\/li><li>Apa tindakan konservasi yang telah dilakukan?<\\/li><li>Apa keberhasilan yang diperoleh?<\\/li><li>Apa kesulitan yang dihadapi selama kegiatan?<\\/li><li>Bagaimana kegiatan tersebut dapat terus dilakukan untuk menjaga lingkungan?<\\/li><\\/ol><p>Melalui proyek ini, siswa diharapkan memahami bahwa konservasi bukan hanya konsep dalam pelajaran, tetapi juga tindakan nyata yang dapat dilakukan untuk menjaga kelestarian lingkungan.<\\/p>\"}',NULL,'2026-10-25 02:29:52','2026-10-25 02:29:52'),(447,5,'callout',11,'{\"title\":\"Kesimpulan\",\"content\":\"### Rangkuman Konservasi Keanekaragaman Hayati\\nKeanekaragaman hayati mempunyai peran penting dalam menjaga keseimbangan ekosistem serta mendukung kehidupan manusia dan organisme lainnya. Berikut hal-hal penting yang telah dipelajari:\\n1. **Keanekaragaman Hayati:** Keanekaragaman hayati mencakup variasi genetik, jenis organisme, dan ekosistem yang terdapat di Bumi.\\n\\n2. **Ancaman Kepunahan:** Kerusakan habitat, perburuan berlebihan, pencemaran, dan perubahan lingkungan dapat mengancam kelangsungan hidup organisme.\\n\\n3. **Pengertian Konservasi:** Konservasi merupakan upaya pengelolaan sumber daya alam secara bijaksana untuk menjaga kelestarian keanekaragaman hayati.\\n\\n4. **Manfaat Ekologi:** Konservasi membantu melindungi habitat, mempertahankan keanekaragaman hayati, dan mendukung keseimbangan ekosistem.\\n\\n5. **Manfaat Ekonomi:** Konservasi mendukung ketersediaan sumber daya alam secara berkelanjutan dan dapat memberikan manfaat ekonomi melalui kegiatan yang memperhatikan kelestarian lingkungan.\\n\\n6. **Konservasi In-Situ:** Konservasi in-situ dilakukan di habitat asli organisme, seperti melalui perlindungan kawasan taman nasional, cagar alam, dan suaka margasatwa.\\n\\n7. **Konservasi Eks-Situ:** Konservasi eks-situ dilakukan di luar habitat asli organisme melalui fasilitas yang dikelola manusia, seperti penangkaran, kebun binatang, kebun raya, dan bank benih.\\n\\n8. **Peran Manusia:** Manusia memiliki tanggung jawab untuk menjaga kelestarian lingkungan melalui perlindungan habitat, pengelolaan sumber daya secara bijaksana, dan kegiatan konservasi.\\n\\n### Kesimpulan\\nKeanekaragaman hayati merupakan bagian penting dari kehidupan di Bumi. Setiap organisme memiliki peran dalam ekosistem, sehingga penurunan keanekaragaman hayati dapat memengaruhi keseimbangan lingkungan. Konservasi dilakukan untuk melindungi organisme serta menjaga keberlanjutan manfaat sumber daya alam.\\n\\nMelalui metode in-situ, organisme dilindungi di habitat aslinya. Sementara itu, metode eks-situ memberikan perlindungan di luar habitat asli melalui lingkungan yang dikelola manusia. Kedua metode dapat saling melengkapi dalam upaya pelestarian keanekaragaman hayati. Sebagai bagian dari lingkungan, manusia perlu berperan aktif dalam menjaga kelestarian alam, termasuk ekosistem sungai dan rawa di Kalimantan Selatan.\"}',NULL,'2026-10-25 02:29:52','2026-10-25 02:29:52'),(448,5,'text',12,'{\"content\":\"<p><strong>Ayo Refleksi!<\\/strong><\\/p><p>Mengapa keanekaragaman hayati perlu dilestarikan? Apa perbedaan konservasi in-situ dan eks-situ? Mengapa menjaga habitat alami sangat penting? Tindakan konservasi apa yang dapat kamu lakukan di lingkungan sekolah atau tempat tinggalmu?<\\/p>\"}',NULL,'2026-10-25 02:29:52','2026-10-25 02:29:52');
/*!40000 ALTER TABLE `subchapter_content_blocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subchapter_media`
--

DROP TABLE IF EXISTS `subchapter_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subchapter_media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subchapter_id` bigint(20) unsigned NOT NULL,
  `media_type` enum('image','video') NOT NULL,
  `source` enum('upload','library','youtube') NOT NULL,
  `storage_path` varchar(255) DEFAULT NULL,
  `library_path` varchar(255) DEFAULT NULL,
  `youtube_video_id` varchar(11) DEFAULT NULL,
  `title` varchar(160) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subchapter_media_type_index` (`subchapter_id`,`media_type`),
  CONSTRAINT `subchapter_media_subchapter_id_foreign` FOREIGN KEY (`subchapter_id`) REFERENCES `subchapters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subchapter_media`
--

LOCK TABLES `subchapter_media` WRITE;
/*!40000 ALTER TABLE `subchapter_media` DISABLE KEYS */;
INSERT INTO `subchapter_media` VALUES (10,2,'video','youtube',NULL,NULL,'E0jbWRmGOvg','Mengenal Ekosistem, Komponen Biotik dan Abiotik, serta Rantai Makanan','Video animasi 3D ini memperkenalkan ekosistem beserta komponen biotik dan abiotik yang menyusunnya. Pembelajaran juga mencakup hubungan antarkomponen dalam ekosistem dan pengenalan rantai makanan sebagai proses perpindahan energi antarmakhluk hidup.','2026-10-24 02:44:22','2026-10-24 02:44:22'),(11,2,'image','upload','subchapter-media/pr5i7oqoyp7zWgRj9cXSXKzYopG1qttODgH9jyDf.png',NULL,NULL,'Komponen Abiotik dalam Ekosistem Rawa','Ekosistem rawa memiliki berbagai komponen abiotik yang saling memengaruhi kondisi lingkungan. Cahaya matahari menyediakan energi bagi tumbuhan untuk berfotosintesis. Air menjadi habitat organisme perairan dan mendukung proses kehidupan. Tanah berlumpur menyediakan tempat tumbuh serta unsur hara bagi tumbuhan. Suhu, udara, dan tingkat keasaman air juga memengaruhi kemampuan organisme bertahan hidup.','2026-10-24 03:25:14','2026-10-24 03:25:14'),(12,2,'image','upload','subchapter-media/BCEHmjKVWw9wqtiNtI4wK5jDPB7UKbZLkL0TOvAl.png',NULL,NULL,'Interaksi Komponen Biotik dalam Ekosistem Rawa','Ilustrasi memperlihatkan interaksi berbagai organisme di lingkungan rawa, seperti ikan haruan, ikan kecil, burung kuntul, serangga air, dan tumbuhan rawa. Hubungan tersebut menunjukkan bahwa makhluk hidup saling memengaruhi dalam memperoleh makanan, tempat hidup, dan kebutuhan lainnya.','2026-10-24 03:33:53','2026-10-24 03:33:53'),(13,3,'image','upload','subchapter-media/KP6hZDjswxcBOOCyEZV4XLKCzvszsWl14eQH6pY2.png',NULL,NULL,'Tingkatan Organisasi Kehidupan dalam Ekosistem Rawa','Ilustrasi menunjukkan tingkatan organisasi kehidupan, mulai dari individu, populasi, komunitas, ekosistem, bioma, hingga biosfer, menggunakan contoh organisme dan lingkungan rawa Kalimantan Selatan.','2026-10-24 23:07:45','2026-10-24 23:07:45'),(14,3,'image','upload','subchapter-media/my9tB4TubpYCJmCKeeaz00WVc17hJwDpIZA72LHk.png',NULL,NULL,'Hubungan Antarkomponen dalam Ekosistem Rawa','Ilustrasi memperlihatkan hubungan antara berbagai komponen ekosistem rawa. Tumbuhan air memanfaatkan cahaya matahari dan unsur hara, ikan memperoleh makanan dari organisme lain, dan tumbuhan rawa menyediakan tempat berlindung. Hubungan tersebut menunjukkan bahwa komponen biotik dan abiotik saling memengaruhi dalam ekosistem.','2026-10-24 23:13:50','2026-10-24 23:13:50'),(15,3,'image','upload','subchapter-media/IYlKOMWAWMZxERPkn6EoDVIJFIWDJnaJKHKN0eSx.png',NULL,NULL,'Rantai Makanan dan Aliran Energi dalam Ekosistem Rawa','Ilustrasi menunjukkan perpindahan energi melalui rantai makanan di ekosistem rawa, dimulai dari fitoplankton sebagai produsen, kemudian zooplankton, ikan kecil, hingga ikan haruan sebagai predator. Arah panah menunjukkan perpindahan energi dari organisme yang dimakan kepada organisme pemakan.','2026-10-24 23:17:02','2026-10-24 23:17:02'),(16,3,'video','youtube',NULL,NULL,'LR5LxjVP5i4','Mengenal Macam-Macam Ekosistem dan Komponen Penyusunnya','Video pembelajaran ini memperkenalkan berbagai macam ekosistem dan komponen penyusunnya, termasuk komponen biotik dan abiotik. Melalui video ini, siswa diajak mengenali bahwa setiap ekosistem memiliki karakteristik dan organisme yang berbeda sesuai kondisi lingkungannya.','2026-10-24 23:25:45','2026-10-24 23:25:45'),(17,4,'image','upload','subchapter-media/7ECvtDL1omtwrLn0FyQI3eXZsFBoaVr1XLjDt5WK.png',NULL,NULL,'Dampak Positif dan Negatif Aktivitas Manusia terhadap Ekosistem','Ilustrasi memperlihatkan perbandingan kegiatan manusia yang menjaga dan merusak ekosistem lahan basah. Kegiatan penghijauan serta pengelolaan sampah membantu mempertahankan kualitas lingkungan, sedangkan pembuangan limbah dan penebangan vegetasi dapat mengganggu kehidupan organisme.','2026-10-24 23:49:59','2026-10-24 23:49:59'),(18,4,'image','upload','subchapter-media/iaOQfthkDFUtZV6KkazjUQNMmv4G1ARSZJRgOj8c.png',NULL,NULL,'Kerusakan Habitat Akibat Aktivitas Manusia','Ilustrasi menunjukkan perubahan habitat rawa akibat pembukaan lahan dan hilangnya vegetasi alami. Perubahan tersebut dapat mengurangi tempat berlindung, sumber makanan, serta ruang hidup organisme yang bergantung pada ekosistem rawa.','2026-10-24 23:53:06','2026-10-24 23:53:06'),(19,4,'image','upload','subchapter-media/QlxaU7TyYN3o4EWUQKjHbvcd3wLm80WEWnQBTUwK.png',NULL,NULL,'Dampak Pencemaran Air terhadap Ekosistem Sungai dan Rawa','Ilustrasi memperlihatkan perbedaan kondisi perairan yang terjaga dan perairan yang tercemar. Pencemaran akibat sampah serta limbah dapat menurunkan kualitas air dan mengganggu kehidupan ikan, tumbuhan air, serta organisme lainnya.','2026-10-24 23:56:34','2026-10-24 23:56:34'),(20,4,'video','youtube',NULL,NULL,'3-5V5sq3RvE','Dampak Aktivitas Manusia terhadap Kerusakan Ekosistem','Video ini membahas berbagai aktivitas manusia yang dapat menyebabkan kerusakan lingkungan, seperti deforestasi, pencemaran udara, dan sampah plastik. Melalui video ini, siswa dapat memahami bagaimana aktivitas manusia mengganggu keseimbangan ekosistem serta pentingnya menjaga kelestarian lingkungan.','2026-10-25 00:09:58','2026-10-25 00:09:58'),(21,4,'image','upload','subchapter-media/lGOWs2KOnYAX0TjEZr7gKY2ZunEP9Ok06uQ7ZUqS.png',NULL,NULL,'Dampak Perubahan Iklim terhadap Ekosistem Lahan Basah','Ilustrasi menunjukkan bagaimana perubahan kondisi iklim dapat memengaruhi ekosistem lahan basah. Perubahan pola hujan dan musim kemarau berkepanjangan dapat mengubah ketinggian air, kondisi vegetasi, serta habitat organisme yang bergantung pada lingkungan rawa.','2026-10-25 00:09:58','2026-10-25 00:09:58'),(22,5,'image','upload','subchapter-media/GMq3OsR9ughYsiyXX0N53NvM04dlbDDDcUwa9V4B.png',NULL,NULL,'Pentingnya Konservasi Ekosistem Lahan Basah','Ilustrasi memperlihatkan ekosistem lahan basah yang terjaga melalui perlindungan habitat dan vegetasi alami. Kawasan rawa yang sehat mendukung kehidupan ikan, burung air, tumbuhan, dan berbagai organisme lainnya serta memberikan manfaat bagi lingkungan dan masyarakat.','2026-10-25 02:23:35','2026-10-25 02:23:35'),(23,5,'image','upload','subchapter-media/XMz7l07U7cuDUWFLtehPeJzUaFE2LX6P2zOIAdjI.png',NULL,NULL,'Konservasi In-Situ pada Habitat Alami','Ilustrasi menunjukkan pelestarian organisme di habitat aslinya melalui perlindungan ekosistem lahan basah. Konservasi in-situ bertujuan mempertahankan kondisi habitat dan hubungan alami antarorganisme agar kehidupan dalam ekosistem tetap terjaga.','2026-10-25 02:25:53','2026-10-25 02:25:53'),(24,5,'image','upload','subchapter-media/U8kpI40owr1WKUDCo8Pm5OAeYHbodGSpBetwljZm.png',NULL,NULL,'Perbedaan Konservasi In-Situ dan Eks-Situ','Ilustrasi membandingkan dua metode konservasi keanekaragaman hayati. Konservasi in-situ dilakukan dengan melindungi organisme di habitat aslinya, sedangkan konservasi eks-situ dilakukan melalui pemeliharaan dan perlindungan organisme di luar habitat alami dalam lingkungan yang dikelola manusia.','2026-10-25 02:25:53','2026-10-25 02:25:53'),(25,5,'video','youtube',NULL,NULL,'EJEOO0OMRzE','Pelestarian Hewan melalui Konservasi In-Situ dan Eks-Situ','Video pembelajaran ini memperkenalkan upaya pelestarian hewan melalui dua metode konservasi, yaitu in-situ dan eks-situ. Melalui contoh perlindungan hewan di habitat aslinya maupun di lingkungan konservasi yang dikelola manusia, siswa dapat memahami perbedaan kedua metode serta pentingnya menjaga kelangsungan hidup satwa.','2026-10-25 02:25:53','2026-10-25 02:25:53');
/*!40000 ALTER TABLE `subchapter_media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subchapters`
--

DROP TABLE IF EXISTS `subchapters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subchapters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `chapter_id` bigint(20) unsigned NOT NULL,
  `nomor_subbab` int(10) unsigned NOT NULL,
  `judul` varchar(255) NOT NULL,
  `konten` longtext DEFAULT NULL,
  `content_blocks_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subchapters_chapter_id_nomor_subbab_unique` (`chapter_id`,`nomor_subbab`),
  CONSTRAINT `subchapters_chapter_id_foreign` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subchapters`
--

LOCK TABLES `subchapters` WRITE;
/*!40000 ALTER TABLE `subchapters` DISABLE KEYS */;
INSERT INTO `subchapters` VALUES (2,3,1,'Bagaimanakah Pengaruh Lingkungan terhadap Suatu Organisme?',NULL,1,1,'2026-10-24 01:58:51','2026-10-24 01:58:51'),(3,3,2,'Bagaimanakah Interaksi antara Komponen Penyusun Suatu Ekosistem?',NULL,1,1,'2026-10-24 23:00:36','2026-10-24 23:07:45'),(4,3,3,'Bagaimanakah Pengaruh Manusia terhadap Ekosistem?',NULL,1,1,'2026-10-24 23:44:42','2026-10-24 23:44:42'),(5,3,4,'Mengapa Harus Dilakukan Konservasi Keanekaragaman Hayati?',NULL,1,1,'2026-10-25 02:22:17','2026-10-25 02:29:52');
/*!40000 ALTER TABLE `subchapters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `role` enum('siswa','guru','orang_tua') NOT NULL DEFAULT 'siswa',
  `nisn` varchar(20) DEFAULT NULL,
  `nip_nipk` varchar(30) DEFAULT NULL,
  `kelas` varchar(20) DEFAULT NULL,
  `sekolah` varchar(255) DEFAULT NULL,
  `mata_pelajaran` varchar(255) DEFAULT NULL,
  `nomor_telepon` varchar(20) DEFAULT NULL,
  `hubungan_anak` varchar(255) DEFAULT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `identity_verified_at` timestamp NULL DEFAULT NULL,
  `profile_photo_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_nisn_unique` (`nisn`),
  UNIQUE KEY `users_nip_nipk_unique` (`nip_nipk`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (3,'Putri Ayu S.B, S.Pd,. Gr.','llvrllucas@gmail.com',NULL,'$2y$12$1bkcw5gVgrYihqzr2beSYueXxKw0Rprx3bTgJhdXahOi0AZqhl1vK',NULL,'2026-10-21 17:04:25','2026-10-26 16:59:56','Ayu','guru',NULL,'2410131120009',NULL,'SMP Negeri 21 Banjarmasin','Ilmu Pengetahuan Alam','081239481029',NULL,NULL,0,NULL,NULL),(4,'Fatimah Az Zahra','az5707978@gmail.com',NULL,'$2y$12$FLBshyNTuj4f6P8u5G3yr.GbWb6PAij8X3fLHkcZWKESJx3YCXese',NULL,'2026-10-26 16:48:44','2026-10-26 16:48:44','ftazhra_','siswa','2410131103',NULL,'VII A','SMP Negeri 21 Banjarmasin',NULL,NULL,NULL,NULL,0,NULL,NULL),(5,'Putri Fatimatul Amalia',NULL,NULL,'$2y$12$.nwFBkooy/uGTS17OJlcBu0SeAhW.9jpSMlAYv0AusTQ.iSYhh.zG',NULL,'2026-10-26 23:36:57','2026-10-26 23:36:57','Iput','guru',NULL,'2410131120005',NULL,'SMP Negeri 21 Banjarmasin',NULL,NULL,NULL,NULL,0,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-27 16:40:10
