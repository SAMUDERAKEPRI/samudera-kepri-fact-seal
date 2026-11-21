// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

import "@openzeppelin/contracts/access/AccessControl.sol";

/// @title SamuderaKepri Fact Seal Registry
/// @notice Mencatat bukti keaslian kriptografi artikel investigasi dan dokumen publik SamuderaKepri
contract SamuderaKepriFactSeal is AccessControl {
    bytes32 public constant VERIFIER_ROLE = keccak256("VERIFIER_ROLE");

    struct FactRecord {
        bytes32 contentHash;      // Hash SHA-256 / keccak256 dari naskah atau berkas dokumen
        string referenceUri;      // Tautan artikel / ID arsip permanen (URL / IPFS CID)
        string category;          // Kategori laporan (misal: "Gelar Fakta", "Dokumen Publik")
        address verifier;         // Alamat verifikator redaksi yang mencatat
        uint64 timestamp;         // Stempel waktu blok saat dicatat
        bool revoked;             // Status pencabutan jika ditemukan kekeliruan faktual
        string revokeReason;      // Alasan koreksi/pencabutan transparan
    }

    mapping(bytes32 => FactRecord) private records;
    bytes32[] private allHashes;

    event FactSealed(
        bytes32 indexed contentHash,
        string referenceUri,
        string category,
        address indexed verifier,
        uint64 timestamp
    );

    event FactRevoked(
        bytes32 indexed contentHash,
        address indexed revoker,
        string reason,
        uint64 timestamp
    );

    error HashAlreadySealed(bytes32 contentHash);
    error HashNotFound(bytes32 contentHash);
    error HashAlreadyRevoked(bytes32 contentHash);
    error InvalidInput();

    constructor(address adminAddress) {
        if (adminAddress == address(0)) revert InvalidInput();
        _grantRole(DEFAULT_ADMIN_ROLE, adminAddress);
        _grantRole(VERIFIER_ROLE, adminAddress);
    }

    /// @notice Mencatat hash laporan/dokumen baru ke blockchain
    function sealFact(
        bytes32 contentHash,
        string calldata referenceUri,
        string calldata category
    ) external onlyRole(VERIFIER_ROLE) {
        if (contentHash == bytes32(0) || bytes(referenceUri).length == 0) {
            revert InvalidInput();
        }
        if (records[contentHash].timestamp != 0) {
            revert HashAlreadySealed(contentHash);
        }

        uint64 sealedAt = uint64(block.timestamp);
        records[contentHash] = FactRecord({
            contentHash: contentHash,
            referenceUri: referenceUri,
            category: category,
            verifier: msg.sender,
            timestamp: sealedAt,
            revoked: false,
            revokeReason: ""
        });

        allHashes.push(contentHash);
        emit FactSealed(contentHash, referenceUri, category, msg.sender, sealedAt);
    }

    /// @notice Menandai catatan jika ada koreksi/hak jawab tanpa menghapus riwayat historis
    function revokeFact(bytes32 contentHash, string calldata reason)
        external
        onlyRole(DEFAULT_ADMIN_ROLE)
    {
        FactRecord storage rec = records[contentHash];
        if (rec.timestamp == 0) revert HashNotFound(contentHash);
        if (rec.revoked) revert HashAlreadyRevoked(contentHash);
        if (bytes(reason).length == 0) revert InvalidInput();

        rec.revoked = true;
        rec.revokeReason = reason;
        emit FactRevoked(contentHash, msg.sender, reason, uint64(block.timestamp));
    }

    /// @notice Memeriksa keaslian dan rincian catatan fakta secara publik
    function verifyFact(bytes32 contentHash)
        external
        view
        returns (
            bool exists,
            bool isValid,
            string memory referenceUri,
            string memory category,
            address verifier,
            uint64 timestamp,
            string memory revokeReason
        )
    {
        FactRecord memory rec = records[contentHash];
        if (rec.timestamp == 0) {
            return (false, false, "", "", address(0), 0, "");
        }
        return (
            true,
            !rec.revoked,
            rec.referenceUri,
            rec.category,
            rec.verifier,
            rec.timestamp,
            rec.revokeReason
        );
    }

    function totalSealed() external view returns (uint256) {
        return allHashes.length;
    }
}