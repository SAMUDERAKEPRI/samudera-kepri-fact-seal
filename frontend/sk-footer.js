(function () {
  const mount = document.getElementById("sk-footer-mount");
  if (!mount) return;

  const isRoot = mount.getAttribute("data-root") === "true";
  const prefix = isRoot ? "./frontend/" : "./";
  const homeHref = isRoot ? "./index.html" : "../index.html";
  const iconPrimary = isRoot ? "./public/assets/aistudio/icon250_ef33c0f4.webp" : "../public/assets/aistudio/icon250_ef33c0f4.webp";
  const iconFallback = isRoot ? "./icon250.webp" : "../icon250.webp";

  const FOOTER_I18N = {
    id: {
      pf_kicker: "TRANSISI & VERIFIKASI EKOSISTEM",
      pf_title: "Siap Menelusuri Fakta & Arsip Berita Terverifikasi?",
      pf_desc: "Akses katalog artikel samuderakepri.co.id yang telah disegel on-chain, periksa contentHash via FactSeal Explorer, atau pelajari arsitektur teknis di dokumentasi resmi.",
      pf_btn_blog: "Buka Blog On-Chain \u2192",
      pf_btn_exp: "Buka FactSeal Explorer",
      ft_desc: "Ekosistem transparansi jurnalistik, penyegelan integritas berita berbasis blockchain (FactSeal), token MSK, dan sertifikat arsip NFT dari Indonesia untuk dunia.",
      ft_lead_role: "Founder & Pemimpin Redaksi:",
      ft_col_eco: "Ekosistem",
      ft_home: "BERANDA",
      ft_portal: "Portal Publik & FactSeal",
      ft_explorer: "Blockchain Explorer",
      ft_nft: "NFT Arsip & Bukti",
      ft_comm: "Komunitas",
      ft_col_learn: "LEARN",
      ft_learn_hub: "learn-hub",
      ft_blog: "blog (Berita On-Chain)",
      ft_col_net: "Jaringan & Portal",
      ft_net_pill: "Sepolia Testnet · Chain ID 11155111",
      ft_rights: "Hak cipta dilindungi.",
      ft_sig: "Coin MSK · FactSeal News · Archive NFT · Media",
      ft_eyebrow: "EKOSISTEM TRANSPARANSI JURNALISTIK WEB3"
    },
    en: {
      pf_kicker: "ECOSYSTEM VERIFICATION & TRANSITION",
      pf_title: "Ready to Inspect Verified News & On-Chain Archives?",
      pf_desc: "Browse samuderakepri.co.id publications sealed on-chain, verify any contentHash through FactSeal Explorer, or study the architecture in official developer docs.",
      pf_btn_blog: "Open On-Chain Blog \u2192",
      pf_btn_exp: "Open FactSeal Explorer",
      ft_desc: "A journalistic transparency ecosystem uniting blockchain news integrity sealing (FactSeal), MSK token utility, and archive proof NFTs from Indonesia to the world.",
      ft_lead_role: "Founder & Chief Editor:",
      ft_col_eco: "Ecosystem",
      ft_home: "HOME",
      ft_portal: "Public Portal & FactSeal",
      ft_explorer: "Blockchain Explorer",
      ft_nft: "Archive & Proof NFTs",
      ft_comm: "Community",
      ft_col_learn: "LEARN",
      ft_learn_hub: "learn-hub",
      ft_blog: "blog (On-Chain News)",
      ft_col_net: "Network & Portals",
      ft_net_pill: "Sepolia Testnet · Chain ID 11155111",
      ft_rights: "All rights reserved.",
      ft_sig: "MSK Coin · FactSeal News · Archive NFT · Media",
      ft_eyebrow: "WEB3 JOURNALISTIC TRANSPARENCY ECOSYSTEM"
    }
  };

  function getLang() {
    let lang = document.documentElement.lang || "id";
    try {
      lang = localStorage.getItem("sk_lang") || lang;
    } catch (e) {}
    return FOOTER_I18N[lang] ? lang : "id";
  }

  function renderFooter(lang) {
    const t = FOOTER_I18N[lang] || FOOTER_I18N.id;
    mount.innerHTML =
      '<section class="sk-prefooter" aria-label="Pre-Footer Ekosistem Samudera Kepri">' +
        '<div class="sk-prefooter-card">' +
          '<div class="sk-prefooter-copy">' +
            '<div class="sk-prefooter-kicker">' + t.pf_kicker + '</div>' +
            '<h2>' + t.pf_title + '</h2>' +
            '<p>' + t.pf_desc + '</p>' +
          '</div>' +
          '<div class="sk-prefooter-actions">' +
            '<a href="' + prefix + 'blog.html" class="sk-pf-btn sk-pf-btn-primary">' + t.pf_btn_blog + '</a>' +
            '<a href="' + prefix + 'explorer.html" class="sk-pf-btn sk-pf-btn-secondary">' + t.pf_btn_exp + '</a>' +
          '</div>' +
        '</div>' +
      '</section>' +
      '<footer class="sk-footer-shell">' +
        '<div class="sk-footer-inner">' +
          '<div class="sk-footer-grid">' +
            '<div>' +
              '<a href="' + homeHref + '" class="sk-footer-brand-row">' +
                '<img src="' + iconPrimary + '" onerror="this.onerror=null;this.src=\'' + iconFallback + '\';" alt="Logo Media Samudera Kepri" referrerpolicy="no-referrer" />' +
                '<div>' +
                  '<span class="sk-footer-brand-name">SAMUDERA KEPRI</span>' +
                  '<span class="sk-footer-brand-entity">PT Media Samudera Kepri</span>' +
                '</div>' +
              '</a>' +
              '<p class="sk-footer-desc">' + t.ft_desc + '</p>' +
              '<div class="sk-footer-lead">' +
                '<span>' + t.ft_lead_role + '</span>' +
                '<strong>Ronny Paslan</strong>' +
              '</div>' +
            '</div>' +
            '<div class="sk-footer-col">' +
              '<h4>' + t.ft_col_eco + '</h4>' +
              '<ul class="sk-footer-list">' +
                '<li><a href="' + homeHref + '">' + t.ft_home + '</a></li>' +
                '<li><a href="' + prefix + 'public-portal.html">' + t.ft_portal + '</a></li>' +
                '<li><a href="' + prefix + 'explorer.html">' + t.ft_explorer + '</a></li>' +
                '<li><a href="' + prefix + 'nft.html">' + t.ft_nft + '</a></li>' +
                '<li><a href="' + prefix + 'community.html">' + t.ft_comm + '</a></li>' +
              '</ul>' +
            '</div>' +
            '<div class="sk-footer-col">' +
              '<h4>' + t.ft_col_learn + '</h4>' +
              '<ul class="sk-footer-list">' +
                '<li><a href="' + prefix + 'learn-hub.html">' + t.ft_learn_hub + '</a></li>' +
                '<li><a href="' + prefix + 'blog.html">' + t.ft_blog + '</a></li>' +
              '</ul>' +
            '</div>' +
            '<div class="sk-footer-col">' +
              '<h4>DEVELOPERS</h4>' +
              '<ul class="sk-footer-list">' +
                '<li><a href="' + prefix + 'docs.html">docs</a></li>' +
                '<li><a href="' + prefix + 'Whitepaper.pdf" target="_blank" rel="noopener noreferrer">Whitepaper.pdf</a></li>' +
                '<li><a href="' + prefix + 'faucet.html">faucet</a></li>' +
                '<li><a href="' + prefix + 'report-abuse.html">report-abuse</a></li>' +
              '</ul>' +
            '</div>' +
            '<div class="sk-footer-col">' +
              '<h4>' + t.ft_col_net + '</h4>' +
              '<ul class="sk-footer-list">' +
                '<li><a href="https://www.samuderakepri.co.id/" target="_blank" rel="noopener noreferrer">samuderakepri.co.id &nearr;</a></li>' +
                '<li><a href="https://sepolia.etherscan.io/address/0xbAED3a7fb42736d6597145FAE88b3c9a00d5a033" target="_blank" rel="noopener noreferrer">FactSeal Etherscan &nearr;</a></li>' +
                '<li><a href="https://sepolia.etherscan.io/token/0x0DeF176EF6Bb5Ec6F7F398C367FdA58e9A522a1d" target="_blank" rel="noopener noreferrer">Token MSK Etherscan &nearr;</a></li>' +
                '<li><a href="https://github.com/SAMUDERAKEPRI/samudera-kepri-fact-seal" target="_blank" rel="noopener noreferrer">GitHub Repository &nearr;</a></li>' +
              '</ul>' +
              '<div class="sk-footer-net-pill">' +
                '<span class="sk-footer-net-dot" aria-hidden="true"></span>' +
                '<span>' + t.ft_net_pill + '</span>' +
              '</div>' +
            '</div>' +
          '</div>' +
          '<div class="sk-footer-divider"></div>' +
          '<div class="sk-footer-meta-bar">' +
            '<div>&copy; 2026 <a href="https://www.samuderakepri.co.id/" target="_blank" rel="noopener noreferrer">PT Media Samudera Kepri</a> &middot; <span>' + t.ft_rights + '</span></div>' +
            '<div><span>' + t.ft_sig + '</span></div>' +
          '</div>' +
          '<div class="sk-wordmark-stage" aria-label="Samudera Kepri">' +
            '<span class="sk-wordmark-eyebrow">' + t.ft_eyebrow + '</span>' +
            '<span class="sk-wordmark-text">SAMUDERA KEPRI</span>' +
            '<div class="sk-wordmark-underline"></div>' +
          '</div>' +
        '</div>' +
      '</footer>';
  }

  renderFooter(getLang());

  document.addEventListener("sk:langchange", (e) => {
    const nextLang = (e && e.detail && e.detail.lang) ? e.detail.lang : getLang();
    renderFooter(nextLang);
  });

  document.querySelectorAll(".sk-lang-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      renderFooter(btn.getAttribute("data-lang") || getLang());
    });
  });
})();
