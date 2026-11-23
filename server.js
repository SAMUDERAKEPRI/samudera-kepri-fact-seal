const express = require('express');
const path = require('path');

const app = express();
const PORT = Number(process.env.PORT) || 3000;
const HOST = '0.0.0.0';

// Handle case-insensitive whitepaper.pdf links on Linux
app.get('/frontend/whitepaper.pdf', (req, res) => {
  res.sendFile(path.join(__dirname, 'frontend', 'Whitepaper.pdf'));
});

// Serve static assets from repository root (index.html and /frontend/*)
app.use(express.static(__dirname, { dotfiles: 'ignore' }));

app.listen(PORT, HOST, () => {
  console.log(`Samudera Kepri FactSeal server listening at http://${HOST}:${PORT}`);
});
