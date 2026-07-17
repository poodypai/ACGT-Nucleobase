-- =====================================================================
-- Functional Nucleotide Database — Schema
-- =====================================================================
-- Import with:  mysql -u root -p < schema.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS nucleotide_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE nucleotide_db;

-- ---------------------------------------------------------------------
-- users : accounts that can log in to upload / edit / delete records
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username        VARCHAR(50)  NOT NULL UNIQUE,
  email           VARCHAR(150) NOT NULL UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  full_name       VARCHAR(150) NOT NULL,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- nucleotide_records : one row per FASTA sequence
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS nucleotide_records (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  accession_number   VARCHAR(64)   NOT NULL UNIQUE,
  organism           VARCHAR(150)  NOT NULL DEFAULT '',
  gene_name          VARCHAR(150)  NOT NULL DEFAULT '',
  sequence_type      ENUM('DNA','RNA') NOT NULL DEFAULT 'DNA',
  description        TEXT          NULL,
  sequence           LONGTEXT      NOT NULL,
  sequence_length     INT UNSIGNED NOT NULL DEFAULT 0,
  gc_content         DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  original_filename  VARCHAR(255)  NULL,
  uploaded_by        INT UNSIGNED  NOT NULL,
  created_at         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_records_user
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
    ON DELETE RESTRICT,
  FULLTEXT KEY ft_organism_gene_desc (organism, gene_name, description)
) ENGINE=InnoDB;

CREATE INDEX idx_organism   ON nucleotide_records (organism);
CREATE INDEX idx_gene_name  ON nucleotide_records (gene_name);
CREATE INDEX idx_seq_type   ON nucleotide_records (sequence_type);

-- ---------------------------------------------------------------------
-- activity_log : audit trail of who changed what and when
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_log (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NULL,
  record_id    INT UNSIGNED NULL,
  action       ENUM('CREATE','UPDATE','DELETE','DOWNLOAD') NOT NULL,
  details      VARCHAR(255) NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_log_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_log_record ON activity_log (record_id);
CREATE INDEX idx_log_user   ON activity_log (user_id);

-- ---------------------------------------------------------------------
-- Seed data (optional) — sample user: username "demo", password "demo1234"
-- Hash below is bcrypt for "demo1234"
-- ---------------------------------------------------------------------
INSERT INTO users (username, email, password_hash, full_name)
VALUES (
  'demo',
  'demo@example.com',
  '$2y$10$p3kmmY57kUIcE1HrDbGn.etAOlMRpAvnqc6Vgr2m/LClS5SFp/g7G',
  'Demo Researcher'
) ON DUPLICATE KEY UPDATE username = username;

INSERT INTO nucleotide_records
  (accession_number, organism, gene_name, sequence_type, description, sequence, sequence_length, gc_content, uploaded_by)
VALUES
  ('NM_000546.6', 'Homo sapiens', 'TP53', 'DNA',
   'Tumor protein p53, partial cds excerpt used as sample seed data.',
   'ATGGAGGAGCCGCAGTCAGATCCTAGCGTCGAGCCCCCTCTGAGTCAGGAAACATTTTCAGACCTATGGAAACTACTTCCTGAAAACAACGTTCTGTCCCCCTTGCCGTCCCAAGCAATGGATGATTTGATGCTGTCCCCGGACGATATTGAACAATGGTTCACTGAAGACCCAGGTCCAGATGAAGCTCCCAGAATGCCAGAGGCTGCTCCCCCCGTGGCCCCTGCACCAGCAGCTCCTACACCGGCGGCCCCTGCACCAGCCCCCTCCTGGCCCCTGTCATCTTCTGTCCCTTCCCAGAAAACCTACCAGGGCAGCTACGGTTTCCGTCTGGGCTTCTTGCATTCTGGGACAGCCAAGTCTGTGACTTGCACGTACTCCCCTGCCCTCAACAAGATGTTTTGCCAACTGGCCAAGACCTGCCCTGTGCAGCTGTGGGTTGATTCCACACCCCCGCCCGGCACCCGCGTCCGCGCCATGGCCATCTACAAGCAGTCACAGCACATGACGGAGGTTGTGAGGCGCTGCCCCCACCATGAGCGCTGCTCAGATAGCGATGGTCTGGCCCCTCCTCAGCATCTTATCCGAGTGGAAGGAAATTTGCGTGTGGAGTATTTGGATGACAGAAACACTTTTCGACATAGTGTGGTGGTGCCCTATGAGCCGCCTGAGGTTGGCTCTGACTGTACCACCATCCACTACAACTACATGTGTAACAGTTCCTGCATGGGCGGCATGAACCGGAGGCCCATCCTCACCATCATCACACTGGAAGACTCCAGTGGTAATCTACTGGGACGGAACAGCTTTGAGGTGCGTGTTTGTGCCTGTCCTGGGAGAGACCGGCGCACAGAGGAAGAGAATCTCCGCAAGAAAGGGGAGCCTCACCACGAGCTGCCCCCAGGGAGCACTAAGCGAGCACTGCCCAACAACACCAGCTCCTCTCCCCAGCCAAAGAAGAAACCACTGGATGGAGAATATTTCACCCTTCAGATCCGTGGGCGTGAGCGCTTCGAGATGTTCCGAGAGCTGAATGAGGCCTTGGAACTCAAGGATGCCCAGGCTGGGAAGGAGCCAGGGGGGAGCAGGGCTCACTCCAGCCACCTGAAGTCCAAAAAGGGTCAGTCTACCTCCCGCCATAAAAAACTCATGTTCAAGACAGAAGGGCCTGACTCAGACTGA',
   1182, 61.5, 1)
ON DUPLICATE KEY UPDATE accession_number = accession_number;

INSERT INTO nucleotide_records
  (accession_number, organism, gene_name, sequence_type, description, sequence, sequence_length, gc_content, uploaded_by)
VALUES
  ('NR_003286.4', 'Homo sapiens', '5S rRNA', 'RNA',
   'Human 5S ribosomal RNA, sample seed record for demo purposes.',
   'GUCUACGGCCAUACCACCCUGAACGCGCCCGAUCUCGUCUGAUCUCGGAAGCUAAGCAGGGUCGGGCCUGGUUAGUACUUGGAUGGGAGACCGCCUGGGAAUACCGGGUGCUGUAGGCUU',
   119, 66.4, 1)
ON DUPLICATE KEY UPDATE accession_number = accession_number;
