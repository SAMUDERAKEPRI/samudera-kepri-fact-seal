import crypto from "node:crypto";
import dotenv from "dotenv";
import express from "express";
import cors from "cors";
import { ethers } from "ethers";

dotenv.config();

const rawPort = Number.parseInt(process.env.PORT || "3000", 10);
const PORT = Number.isFinite(rawPort) && rawPort > 0 ? rawPort : 3000;
const SEPOLIA_CHAIN_ID = 11155111n;
const DEFAULT_CONTRACT_ADDRESS = "0xbAED3a7fb42736d6597145FAE88b3c9a00d5a033";
const EXPECTED_PRIMARY_VERIFIER = "0x7f8684CE32BC602D01B431c1f064f3838dF580eF";
const DEFAULT_EXPLORER_TX_BASE = "https://sepolia.etherscan.io/tx/";
const DEFAULT_SITE_URL = "https://www.samuderakepri.co.id/";

const RELAYER_API_TOKEN = (process.env.RELAYER_API_TOKEN || "").trim();
const SEPOLIA_RPC_URL = (process.env.SEPOLIA_RPC_URL || "https://rpc.sepolia.org").trim();
const VERIFIER_PRIVATE_KEY = (process.env.VERIFIER_PRIVATE_KEY || "").trim();
const FACTSEAL_CONTRACT_ADDRESS = (
  process.env.FACTSEAL_CONTRACT_ADDRESS || DEFAULT_CONTRACT_ADDRESS
).trim();
const EXPLORER_TX_BASE_URL = ensureTrailingSlash(
  (process.env.EXPLORER_TX_BASE_URL || DEFAULT_EXPLORER_TX_BASE).trim()
);

/**
 * ABI Smart Contract SamuderaKepriFactSeal
 * Sumber kontrak: ../contracts/SamuderaKepriFactSeal.sol
 *
 * Jika Anda mengganti nama fungsi atau parameter pada file .sol di folder contracts/,
 * sesuaikan signature fungsi dan event di array FACTSEAL_ABI berikut ini serta
 * pemanggilan `contract.sealFact(...)` di dalam handler POST /factseal/seal.
 */
const FACTSEAL_ABI = [
  "function sealFact(bytes32 contentHash, string referenceUri, string category) external",
  "function verifyFact(bytes32 contentHash) external view returns (bool exists, bool isValid, string referenceUri, string category, address verifier, uint64 timestamp, string revokeReason)",
  "function totalSealed() external view returns (uint256)",
  "function VERIFIER_ROLE() external view returns (bytes32)",
  "function hasRole(bytes32 role, address account) external view returns (bool)",
  "event FactSealed(bytes32 indexed contentHash, string referenceUri, string category, address indexed verifier, uint64 timestamp)",
  "error HashAlreadySealed(bytes32 contentHash)",
  "error HashNotFound(bytes32 contentHash)",
  "error InvalidInput()",
  "error AccessControlUnauthorizedAccount(address account, bytes32 neededRole)"
];

const app = express();
app.disable("x-powered-by");
app.set("trust proxy", 1);

const allowedOrigins = buildAllowedOrigins(process.env.ALLOWED_ORIGIN);
app.use(
  cors({
    origin(origin, callback) {
      // Request server-to-server (misalnya wp_remote_post dari WordPress) tidak mengirim header Origin
      if (!origin) {
        return callback(null, true);
      }
      if (allowedOrigins.includes(origin)) {
        return callback(null, true);
      }
      return callback(new Error("Origin tidak diizinkan oleh kebijakan CORS."));
    },
    methods: ["GET", "POST", "OPTIONS"],
    allowedHeaders: ["Content-Type", "Authorization"]
  })
);

app.use(express.json({ limit: "256kb" }));

function ensureTrailingSlash(value) {
  if (!value) return DEFAULT_EXPLORER_TX_BASE;
  return value.endsWith("/") ? value : `${value}/`;
}

function buildAllowedOrigins(envOrigins) {
  const defaults = [
    "https://www.samuderakepri.co.id",
    "https://samuderakepri.co.id"
  ];
  if (!envOrigins) return defaults;
  const parsed = envOrigins
    .split(",")
    .map((item) => item.trim().replace(/\/+$/, ""))
    .filter(Boolean);
  return Array.from(new Set([...defaults, ...parsed]));
}

function isPlaceholderSecret(value) {
  if (!value) return true;
  const lower = value.toLowerCase();
  return (
    lower.includes("ganti_dengan_token") ||
    lower.includes("isi_private_key") ||
    lower.includes("jangan_di_github")
  );
}

function timingSafeTokenEqual(provided, expected) {
  const providedDigest = crypto.createHash("sha256").update(String(provided)).digest();
  const expectedDigest = crypto.createHash("sha256").update(String(expected)).digest();
  return crypto.timingSafeEqual(providedDigest, expectedDigest);
}

function isValidBytes32(value) {
  return (
    typeof value === "string" &&
    /^0x[0-9a-fA-F]{64}$/.test(value) &&
    value.toLowerCase() !== ethers.ZeroHash
  );
}

function normalizeHttpUrl(rawUrl) {
  if (typeof rawUrl !== "string") return null;
  const trimmed = rawUrl.trim();
  if (!trimmed || trimmed.length > 1024) return null;
  try {
    const parsed = new URL(trimmed);
    if (parsed.protocol !== "http:" && parsed.protocol !== "https:") {
      return null;
    }
    return parsed.toString();
  } catch {
    return null;
  }
}

function computeCanonicalContentHash({ postId, title, url, publishedAt }) {
  const canonicalPayload = JSON.stringify({
    publisher: "PT Media Samudera Kepri",
    site: DEFAULT_SITE_URL,
    postId: postId !== undefined && postId !== null ? String(postId) : "",
    title: typeof title === "string" ? title.trim() : "",
    url: typeof url === "string" ? url.trim() : "",
    publishedAt: typeof publishedAt === "string" ? publishedAt.trim() : ""
  });
  return ethers.keccak256(ethers.toUtf8Bytes(canonicalPayload));
}

function sanitizeCategory(rawCategory) {
  if (typeof rawCategory !== "string") return "Berita Media";
  const cleaned = rawCategory.trim().slice(0, 64);
  return cleaned || "Berita Media";
}

function extractSafeErrorMessage(error) {
  if (!error) return "Terjadi kesalahan saat memproses transaksi FactSeal.";

  const shortMsg = typeof error.shortMessage === "string" ? error.shortMessage : "";
  const rawMsg = typeof error.message === "string" ? error.message : "";
  const combined = `${shortMsg} ${rawMsg}`.toLowerCase();

  if (combined.includes("hashalreadysealed")) {
    return "Content hash artikel ini sudah tercatat di Smart Contract FactSeal.";
  }
  if (combined.includes("accesscontrolunauthorizedaccount")) {
    return "Akun signer belum memiliki VERIFIER_ROLE pada kontrak FactSeal.";
  }
  if (combined.includes("invalidinput")) {
    return "Input ke kontrak FactSeal ditolak (contentHash atau referenceUri tidak valid).";
  }
  if (combined.includes("insufficient funds") || error.code === "INSUFFICIENT_FUNDS") {
    return "Saldo Sepolia ETH pada akun verifikator tidak mencukupi untuk membayar gas.";
  }
  if (combined.includes("nonce") && combined.includes("expired")) {
    return "Konflik nonce transaksi sementara pada jaringan Sepolia. Silakan coba kirim ulang.";
  }
  if (error.code === "NETWORK_ERROR" || error.code === "TIMEOUT" || combined.includes("timeout")) {
    return "Koneksi ke RPC Ethereum Sepolia sedang bermasalah atau timeout.";
  }

  return "Gagal mengeksekusi transaksi ke jaringan Ethereum Sepolia.";
}

function getRelayerContext() {
  if (!ethers.isAddress(FACTSEAL_CONTRACT_ADDRESS)) {
    throw Object.assign(new Error("FACTSEAL_CONTRACT_ADDRESS tidak valid."), { statusCode: 500 });
  }
  if (isPlaceholderSecret(VERIFIER_PRIVATE_KEY)) {
    throw Object.assign(new Error("VERIFIER_PRIVATE_KEY belum dikonfigurasi pada environment server."), {
      statusCode: 500
    });
  }

  let normalizedPrivateKey = VERIFIER_PRIVATE_KEY;
  if (!normalizedPrivateKey.startsWith("0x")) {
    normalizedPrivateKey = `0x${normalizedPrivateKey}`;
  }
  if (!/^0x[0-9a-fA-F]{64}$/.test(normalizedPrivateKey)) {
    throw Object.assign(new Error("Format VERIFIER_PRIVATE_KEY pada server tidak valid."), {
      statusCode: 500
    });
  }

  const provider = new ethers.JsonRpcProvider(SEPOLIA_RPC_URL, Number(SEPOLIA_CHAIN_ID), {
    staticNetwork: true
  });
  const wallet = new ethers.Wallet(normalizedPrivateKey, provider);
  const contract = new ethers.Contract(FACTSEAL_CONTRACT_ADDRESS, FACTSEAL_ABI, wallet);

  return { provider, wallet, contract };
}

function requireBearerAuth(req, res, next) {
  if (isPlaceholderSecret(RELAYER_API_TOKEN)) {
    return res.status(503).json({
      success: false,
      error: "Server relayer belum dikonfigurasi dengan RELAYER_API_TOKEN yang valid."
    });
  }

  const authHeader = req.get("authorization") || "";
  if (!authHeader.startsWith("Bearer ")) {
    return res.status(401).json({
      success: false,
      error: "Header Authorization: Bearer <token> wajib disertakan."
    });
  }

  const incomingToken = authHeader.slice(7).trim();
  if (!incomingToken || !timingSafeTokenEqual(incomingToken, RELAYER_API_TOKEN)) {
    return res.status(403).json({
      success: false,
      error: "Token otorisasi relayer tidak valid."
    });
  }

  return next();
}

function buildHealthPayload() {
  let signerAddress = null;
  if (!isPlaceholderSecret(VERIFIER_PRIVATE_KEY)) {
    try {
      const normalizedKey = VERIFIER_PRIVATE_KEY.startsWith("0x")
        ? VERIFIER_PRIVATE_KEY
        : `0x${VERIFIER_PRIVATE_KEY}`;
      if (/^0x[0-9a-fA-F]{64}$/.test(normalizedKey)) {
        signerAddress = new ethers.Wallet(normalizedKey).address;
      }
    } catch {
      signerAddress = null;
    }
  }

  return {
    status: "ok",
    service: "Samudera Kepri FactSeal Relayer",
    publisher: "PT Media Samudera Kepri",
    mediaSite: DEFAULT_SITE_URL,
    network: "Ethereum Sepolia Testnet",
    chainId: Number(SEPOLIA_CHAIN_ID),
    contractAddress: FACTSEAL_CONTRACT_ADDRESS,
    primaryVerifier: EXPECTED_PRIMARY_VERIFIER,
    signerConfigured: Boolean(signerAddress),
    signerMatchesPrimaryVerifier: signerAddress
      ? signerAddress.toLowerCase() === EXPECTED_PRIMARY_VERIFIER.toLowerCase()
      : false,
    apiTokenConfigured: !isPlaceholderSecret(RELAYER_API_TOKEN),
    timestamp: new Date().toISOString()
  };
}

app.get("/", (_req, res) => {
  res.json(buildHealthPayload());
});

app.get("/health", (_req, res) => {
  res.json(buildHealthPayload());
});

app.post("/factseal/seal", requireBearerAuth, async (req, res) => {
  try {
    const { postId, title, url, publishedAt, contentHash: providedHash, category } = req.body || {};

    const cleanUrl = normalizeHttpUrl(url);
    if (!cleanUrl) {
      return res.status(400).json({
        success: false,
        error: "Field 'url' wajib berupa tautan HTTP/HTTPS artikel yang valid."
      });
    }

    const cleanTitle = typeof title === "string" ? title.trim() : "";
    if (!cleanTitle) {
      return res.status(400).json({
        success: false,
        error: "Field 'title' artikel tidak boleh kosong."
      });
    }

    const finalContentHash = isValidBytes32(providedHash)
      ? providedHash.toLowerCase()
      : computeCanonicalContentHash({
          postId,
          title: cleanTitle,
          url: cleanUrl,
          publishedAt
        });

    const finalCategory = sanitizeCategory(category);
    const { provider, wallet, contract } = getRelayerContext();

    // Pastikan provider terhubung ke jaringan Ethereum Sepolia (Chain ID 11155111)
    const network = await provider.getNetwork();
    if (network.chainId !== SEPOLIA_CHAIN_ID) {
      return res.status(502).json({
        success: false,
        error: `RPC tidak berada pada Ethereum Sepolia (diharapkan ${SEPOLIA_CHAIN_ID}, terdeteksi ${network.chainId}).`
      });
    }

    // Cek apakah contentHash sudah pernah disegel di kontrak untuk mencegah revert & pemborosan gas
    const existing = await contract.verifyFact(finalContentHash);
    if (existing && existing.exists) {
      let existingTxHash = null;
      try {
        const logs = await contract.queryFilter(
          contract.filters.FactSealed(finalContentHash),
          -50000
        );
        if (logs.length > 0) {
          existingTxHash = logs[logs.length - 1].transactionHash;
        }
      } catch {
        existingTxHash = null;
      }

      return res.status(200).json({
        success: true,
        alreadySealed: true,
        txHash: existingTxHash,
        contentHash: finalContentHash,
        verifier: existing.verifier,
        explorerUrl: existingTxHash ? `${EXPLORER_TX_BASE_URL}${existingTxHash}` : null
      });
    }

    // Pemanggilan fungsi Smart Contract SamuderaKepriFactSeal:
    // function sealFact(bytes32 contentHash, string calldata referenceUri, string calldata category)
    const tx = await contract.sealFact(finalContentHash, cleanUrl, finalCategory);
    const receipt = await tx.wait(1);

    const txHash = (receipt && receipt.hash) || tx.hash;
    return res.status(200).json({
      success: true,
      alreadySealed: false,
      txHash,
      contentHash: finalContentHash,
      verifier: wallet.address,
      blockNumber: receipt ? receipt.blockNumber : null,
      explorerUrl: `${EXPLORER_TX_BASE_URL}${txHash}`
    });
  } catch (error) {
    const statusCode = Number.isInteger(error?.statusCode) ? error.statusCode : 500;
    const safeMessage = error?.statusCode === 500 && error?.message
      ? error.message
      : extractSafeErrorMessage(error);

    // Log hanya ringkasan aman (tanpa payload environment / private key)
    console.error("[FactSeal Relayer Error]", {
      code: error?.code || "UNKNOWN",
      message: safeMessage
    });

    return res.status(statusCode).json({
      success: false,
      error: safeMessage
    });
  }
});

app.use((err, _req, res, next) => {
  if (err && err.message && err.message.includes("CORS")) {
    return res.status(403).json({ success: false, error: err.message });
  }
  if (err && err.type === "entity.parse.failed") {
    return res.status(400).json({ success: false, error: "Payload JSON tidak valid." });
  }
  return next(err);
});

app.use((_req, res) => {
  res.status(404).json({ success: false, error: "Endpoint tidak ditemukan." });
});

app.listen(PORT, () => {
  console.log(
    `Samudera Kepri FactSeal Relayer aktif pada port ${PORT} (Contract: ${FACTSEAL_CONTRACT_ADDRESS})`
  );
});
