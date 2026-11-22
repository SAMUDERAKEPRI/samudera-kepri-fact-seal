# Samudera Kepri FactSeal & Coin (MSK)

[![Network](https://img.shields.io/badge/Network-Ethereum%20Sepolia-3C3C3D?style=flat-square&logo=ethereum)](https://sepolia.etherscan.io/)
[![Chain ID](https://img.shields.io/badge/Chain%20ID-11155111-0f4c81?style=flat-square)](https://sepolia.etherscan.io/)
[![Portal](https://img.shields.io/badge/Portal-samuderakepri.co.id-581c87?style=flat-square)](https://www.samuderakepri.co.id/)

Sistem dokumentasi, pencatatan integritas arsip berita, gelar fakta, dan investigasi **Media Samudera Kepri** (`samuderakepri.co.id`) berbasis teknologi *blockchain* pada jaringan **Ethereum Sepolia**. Proyek ini memadukan penyegelan bukti kriptografis (`contentHash` `bytes32`) melalui kontrak pintar **FactSeal** dengan identitas token ekosistem **SamuderaKepriToken (`MSK`)** serta antarmuka transparansi publik dan *blockchain explorer*.

---

## Ringkasan Proyek

Dalam praktik jurnalisme investigasi, gelar fakta, dan pengawasan publik, keaslian catatan waktu serta keutuhan sidik jari digital sebuah naskah atau bukti rujukan sangat penting untuk menjaga akuntabilitas. **Samudera Kepri FactSeal & Coin (`MSK`)** dirancang untuk:

- Menyediakan rujukan verifikasi *on-chain* atas `contentHash` (`bytes32`) berita dan laporan investigasi **Media Samudera Kepri**.
- Menampilkan identitas resmi **Coin Samudera Kepri (`MSK`)** beserta alamat kontrak dan informasi jaringan secara terbuka.
- Mempermudah pembaca, peneliti, dan tim redaksi menelusuri status segel fakta, riwayat transaksi, maupun saldo token `MSK` melalui antarmuka web dan **Sepolia Etherscan**.

---

## Informasi Jaringan & Smart Contract

| Komponen | Keterangan / Alamat |
| :--- | :--- |
| **Jaringan Blockchain** | Ethereum Sepolia Testnet |
| **Chain ID** | `11155111` |
| **Kontrak FactSeal** | `0xbAED3a7fb42736d6597145FAE88b3c9a00d5a033` |
| **Kontrak Token (`MSK`)** | `0x0DeF176EF6Bb5Ec6F7F398C367FdA58e9A522a1d` |
| **Akun Verifikator Utama** | `0x7f8684CE32BC602D01B431c1f064f3838dF580eF` |
| **Situs Utama Media** | [https://www.samuderakepri.co.id/](https://www.samuderakepri.co.id/) |

---

## Struktur Repositori

```text
samudera-kepri-fact-seal/
├── .deploys/
│   └── pinned-contracts/
│       └── 11155111/          # Catatan kontrak yang di-pin pada jaringan Sepolia (Chain ID: 11155111)
├── .deps/                     # Dependensi proyek dari Remix
├── artifacts/                 # Hasil kompilasi / artefak kontrak
├── contracts/                 # Kode sumber kontrak pintar Solidity (.sol)
├── frontend/
│   ├── public-portal.html     # Portal transparansi publik, pencarian judul, & pratinjau hash/bukti
│   └── explorer.html          # Halaman explorer untuk verifikasi contentHash, saldo MSK, & Etherscan
├── scripts/                   # Skrip deployment / interaksi kontrak
├── tests/                     # Berkas pengujian kontrak
├── remix.config.json          # Konfigurasi workspace Remix
├── README.txt                 # Catatan bawaan template workspace Remix
└── README.md                  # Dokumentasi utama proyek Samudera Kepri FactSeal & Coin (MSK)
