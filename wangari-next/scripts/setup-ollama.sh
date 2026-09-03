#!/bin/bash
# ============================================
# Wangari AI — Ollama Setup Script
# ============================================
# Run this on your VPS:
#   ssh lewis@20.164.18.34
#   bash setup-ollama.sh

set -e

echo "=== Step 1: Install Ollama ==="
curl -fsSL https://ollama.com/install.sh | sh

echo ""
echo "=== Step 2: Start Ollama service ==="
sudo systemctl enable ollama
sudo systemctl start ollama

echo ""
echo "=== Step 3: Wait for Ollama to start ==="
sleep 3
ollama --version

echo ""
echo "=== Step 4: Pull Qwen2.5 1.5B model (small, fast, supports tools) ==="
ollama pull qwen2.5:1.5b

echo ""
echo "=== Step 5: Configure Ollama to listen on all interfaces ==="
# Create or update the systemd override
sudo mkdir -p /etc/systemd/system/ollama.service.d
sudo tee /etc/systemd/system/ollama.service.d/override.conf > /dev/null << 'EOF'
[Service]
Environment="OLLAMA_HOST=0.0.0.0:11434"
EOF

sudo systemctl daemon-reload
sudo systemctl restart ollama

echo ""
echo "=== Step 6: Verify ==="
sleep 2
curl -s http://localhost:11434/api/tags | head -c 500
echo ""

echo ""
echo "=== DONE ==="
echo ""
echo "Ollama is running at http://20.164.18.34:11434"
echo "Model: qwen2.5:1.5b"
echo ""
echo "Update your Express server .env:"
echo "  OLLAMA_URL=http://127.0.0.1:11434"
echo "  OLLAMA_MODEL=qwen2.5:1.5b"
echo ""
echo "Test with:"
echo "  curl http://localhost:11434/api/chat -d '{\"model\":\"qwen2.5:1.5b\",\"messages\":[{\"role\":\"user\",\"content\":\"Hello\"}]}'"
