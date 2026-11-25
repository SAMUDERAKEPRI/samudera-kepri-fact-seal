# Samudera Kepri FactSeal Relayer (`api.samuderakepri.co.id`)

Layanan relayer Node.js + Express (ESM) untuk menerima webhook/permintaan dari plugin WordPress **Samudera Kepri FactSeal WP** dan mencatat `contentHash` (`bytes32`) artikel ke Smart Contract **`SamuderaKepriFactSeal`** pada jaringan **Ethereum Sepolia Testnet**.

## Spesifikasi Jaringan & Kontrak
- **Jaringan**: Ethereum Sepolia Testnet (`Chain ID: 11155111`)
- **Alamat Kontrak FactSeal**: `0xbAED3a7fb42736d6597145FAE88b3c9a00d5a033`
- **Akun Verifikator Utama (`VERIFIER_ROLE`)**: `0x7f8684CE32BC602D01B431c1f064f3838dF580eF`
- **Portal Media Resmi**: `https://www.samuderakepri.co.id/`

---

## Cara Deploy ke Server Node.js Hostinger (hPanel)

### 1. Persiapan Repositori GitHub
Pastikan folder `backend-relayer/` berada di repositori GitHub `SAMUDERAKEPRI/samudera-kepri-fact-seal`, dan file `.env` **tidak ikut ter-commit** (sudah diblokir oleh `.gitignore`).

### 2. Pengaturan Aplikasi Node.js di hPanel Hostinger
1. Login ke **hPanel Hostinger** -> buka menu **Websites** -> pilih domain/subdomain **`api.samuderakepri.co.id`**.
2. Masuk ke menu **Advanced -> Node.js** (atau **Websites -> Deploy Node.js App**).
3. Hubungkan repositori GitHub `SAMUDERAKEPRI/samudera-kepri-fact-seal` atau unggah isi folder `backend-relayer/` ke root aplikasi Node.js.
4. Atur konfigurasi berikut:
   - **Node.js Version**: Pilih `20.x`, `22.x`, atau `24.x` (disarankan `20.x` LTS atau `22.x` LTS).
   - **Application Root**: `backend-relayer` (atau direktori tempat `package.json` dan `server.js` berada).
   - **Application Startup File**: `server.js`
   - **Start Command**: `npm start` (`node server.js`)

### 3. Mengisi Environment Variables di hPanel Hostinger
Pada bagian **Environment Variables** di halaman konfigurasi Node.js Hostinger, tambahkan variabel berikut (mengacu pada `.env.example`):

| Nama Variabel | Nilai / Keterangan |
|---|---|
| `PORT` | `3000` (atau biarkan sesuai port yang ditetapkan Hostinger jika otomatis diisi platform) |
| `RELAYER_API_TOKEN` | Token acak panjang rahasia (minimal 32 karakter). Salin token yang sama persis ke pengaturan plugin WordPress (`API Bearer Token`). |
| `SEPOLIA_RPC_URL` | `https://rpc.sepolia.org` atau URL RPC Alchemy/Infura Sepolia agar lebih stabil. |
| `VERIFIER_PRIVATE_KEY` | Private key dari wallet verifikator `0x7f8684CE32BC602D01B431c1f064f3838dF580eF` (pastikan wallet memiliki saldo Sepolia ETH untuk biaya gas). |
| `FACTSEAL_CONTRACT_ADDRESS` | `0xbAED3a7fb42736d6597145FAE88b3c9a00d5a033` |
| `ALLOWED_ORIGIN` | `https://www.samuderakepri.co.id` |
| `EXPLORER_TX_BASE_URL` | `https://sepolia.etherscan.io/tx/` |

> **Catatan Keamanan Penting**: Jangan pernah menuliskan `VERIFIER_PRIVATE_KEY` atau `RELAYER_API_TOKEN` ke dalam kode sumber, commit GitHub, ataupun file `.env.example`.

### 4. Instalasi Dependensi & Pengujian
Setelah menyimpan Environment Variables dan menjalankan **Build / Restart** di hPanel Hostinger:
1. Buka `https://api.samuderakepri.co.id/health` di browser. Pastikan respons JSON menunjukkan `"status": "ok"`, `"signerConfigured": true`, dan `"apiTokenConfigured": true`.
2. Jika ingin menguji endpoint segel dari terminal (ganti `<TOKEN>` dengan `RELAYER_API_TOKEN`):
   ```bash
   curl -X POST https://api.samuderakepri.co.id/factseal/seal \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer <TOKEN>" \
     -d '{
       "postId": "101",
       "title": "Uji Verifikasi FactSeal Samudera Kepri",
       "url": "https://www.samuderakepri.co.id/uji-factseal/",
       "publishedAt": "2026-11-25T10:00:00Z"
     }'
   ```
