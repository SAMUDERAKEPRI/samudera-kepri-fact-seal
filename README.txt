# Samudera Kepri FactSeal & Coin (MSK)

Sistem dokumentasi dan verifikasi integritas naskah berita, gelar fakta, dan investigasi **Media Samudera Kepri** (`samuderakepri.co.id`) berbasis *blockchain* pada jaringan **Ethereum Sepolia**.

## Informasi Jaringan & Kontrak
- **Jaringan:** Ethereum Sepolia (`Chain ID: 11155111`)
- **Kontrak FactSeal:** `0xbAED3a7fb42736d6597145FAE88b3c9a00d5a033`
- **Kontrak Token (`MSK`):** `0x0DeF176EF6Bb5Ec6F7F398C367FdA58e9A522a1d`
- **Akun Verifikator:** `0x7f8684CE32BC602D01B431c1f064f3838dF580eF`

## Struktur Proyek
- `contracts/`: Kode sumber kontrak pintar Solidity (`.sol`) yang dikembangkan dan dikompilasi di Remix.
- `.deploys/pinned-contracts/11155111/`: Catatan kontrak yang di-pin pada jaringan Ethereum Sepolia.
- `frontend/public-portal.html`: Halaman portal transparansi publik untuk menampilkan identitas **Coin Samudera Kepri (`MSK`)**, mencari berita berdasarkan judul, mengalihkan tautan uji coba ke halaman kategori aktif, serta menampilkan pratinjau `contentHash` dan tanda tangan/bukti kontrak.
